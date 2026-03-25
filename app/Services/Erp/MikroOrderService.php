<?php

namespace App\Services\Erp;

use App\Enums\ErpSyncStatus;
use App\Enums\MikroSyncConflictCode;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\SupplierMikroAccount;
use App\Models\User;
use App\Services\MailNotificationDispatcher;
use App\Services\Mikro\MikroClient;
use App\Services\Mikro\MikroException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MikroOrderService
{
    public function __construct(
        private MikroClient $mikro,
        private MikroOrderPayloadNormalizer $normalizer
    ) {}

    public function syncSupplier(Supplier|int $supplier): array
    {
        $supplierId = $supplier instanceof Supplier ? $supplier->getKey() : $supplier;

        $supplier = Supplier::query()
            ->with(['mikroAccounts' => fn ($query) => $query->active()->orderBy('id')])
            ->findOrFail($supplierId);

        $stats = [
            'supplier_id' => $supplier->id,
            'accounts' => 0,
            'orders_created' => 0,
            'orders_updated' => 0,
            'lines_created' => 0,
            'lines_updated' => 0,
            'conflicts' => 0,
            'failed' => 0,
            'errors' => [],
            'conflict_codes' => [],
        ];

        /** @var Collection<int, SupplierMikroAccount> $accounts */
        $accounts = $supplier->mikroAccounts;
        $stats['accounts'] = $accounts->count();
        $seenOrders = [];

        foreach ($accounts as $account) {
            try {
                $orders = $this->fetchOrdersForAccount($account);
                $accountStats = $this->syncAccountOrders($supplier, $account, $orders, $seenOrders);
            } catch (\Throwable $exception) {
                $accountStats = [
                    'orders_created' => 0,
                    'orders_updated' => 0,
                    'lines_created' => 0,
                    'lines_updated' => 0,
                    'conflicts' => 0,
                    'failed' => 1,
                    'errors' => ['Cari ' . $account->mikro_cari_kod . ': ' . $exception->getMessage()],
                    'conflict_codes' => [],
                    'status' => ErpSyncStatus::FAILED->value,
                ];
            }

            foreach (['orders_created', 'orders_updated', 'lines_created', 'lines_updated', 'conflicts', 'failed'] as $key) {
                $stats[$key] += $accountStats[$key];
            }

            $stats['errors'] = [...$stats['errors'], ...$accountStats['errors']];
            $stats['conflict_codes'] = [...$stats['conflict_codes'], ...$accountStats['conflict_codes']];

            $this->markAccountSyncResult(
                $account,
                $accountStats['status'],
                $accountStats['errors'] ? implode("\n", array_slice($accountStats['errors'], 0, 3)) : null
            );
        }

        $this->writeSyncLog($supplier, $stats);

        return $stats;
    }

    public function fetchOrdersForAccount(SupplierMikroAccount $account): array
    {
        if (! $this->mikro->isEnabled()) {
            return [];
        }

        try {
            $response = $this->mikro->get('/api/purchase-orders', array_filter([
                'supplier_code' => $account->mikro_cari_kod,
                'company_code' => $account->mikro_company_code ?: null,
                'work_year' => $account->mikro_work_year ?: null,
                'status' => 'active',
            ], fn ($value) => filled($value)));
        } catch (MikroException $exception) {
            throw new \RuntimeException($exception->getMessage(), previous: $exception);
        }

        return Arr::wrap($response->json('data', []));
    }

    private function syncAccountOrders(
        Supplier $supplier,
        SupplierMikroAccount $account,
        array $orders,
        array &$seenOrders
    ): array {
        $stats = [
            'orders_created' => 0,
            'orders_updated' => 0,
            'lines_created' => 0,
            'lines_updated' => 0,
            'conflicts' => 0,
            'failed' => 0,
            'errors' => [],
            'conflict_codes' => [],
            'status' => ErpSyncStatus::SUCCESS->value,
        ];

        foreach ($orders as $orderPayload) {
            try {
                $normalized = $this->normalizer->normalizeOrder(is_array($orderPayload) ? $orderPayload : [], $account);
                $validationIssues = $this->normalizer->validateOrder($normalized, $account);

                if ($validationIssues !== []) {
                    $this->recordIssues($stats, $supplier, $account, $normalized, $validationIssues);

                    continue;
                }

                $supplierOrderKey = $supplier->id . '|' . $normalized['order_no'];

                if (isset($seenOrders[$supplierOrderKey])) {
                    $this->recordIssues($stats, $supplier, $account, $normalized, [[
                        'code' => MikroSyncConflictCode::DUPLICATE_ORDER_CONFLICT->value,
                        'message' => sprintf(
                            'Ayni supplier sync icinde tekrar eden order_no tespit edildi: %s',
                            $normalized['order_no']
                        ),
                    ]]);

                    continue;
                }

                $seenOrders[$supplierOrderKey] = true;

                $result = DB::transaction(fn () => $this->upsertOrder($supplier, $account, $normalized));

                foreach (['orders_created', 'orders_updated', 'lines_created', 'lines_updated', 'conflicts'] as $key) {
                    $stats[$key] += $result[$key];
                }

                if ($result['conflict_message']) {
                    $stats['errors'][] = $result['conflict_message'];
                }

                if ($result['conflict_code']) {
                    $stats['conflict_codes'][] = $result['conflict_code'];
                }
            } catch (\Throwable $exception) {
                $stats['failed']++;
                $stats['errors'][] = 'Siparis ' . ($orderPayload['order_no'] ?? '-') . ': ' . $exception->getMessage();
            }
        }

        $hasProblems = $stats['failed'] > 0 || $stats['conflicts'] > 0;
        $hasSuccessfulWrites = ($stats['orders_created'] + $stats['orders_updated']) > 0;

        $stats['status'] = $hasProblems
            ? ($hasSuccessfulWrites ? ErpSyncStatus::PARTIAL->value : ErpSyncStatus::FAILED->value)
            : ErpSyncStatus::SUCCESS->value;

        return $stats;
    }

    private function upsertOrder(Supplier $supplier, SupplierMikroAccount $account, array $payload): array
    {
        $orderNo = (string) ($payload['order_no'] ?? '');

        if ($orderNo === '') {
            throw new \RuntimeException('Siparis numarasi eksik.');
        }

        $existing = PurchaseOrder::query()
            ->where('supplier_id', $supplier->id)
            ->where('order_no', $orderNo)
            ->first();

        $wasExisting = $existing !== null;

        $order = PurchaseOrder::query()->updateOrCreate(
            [
                'supplier_id' => $supplier->id,
                'order_no' => $orderNo,
            ],
            [
                'status' => $this->mapOrderStatus((string) ($payload['status'] ?? 'active')),
                'shipment_status' => $this->mapShipmentStatus($payload),
                'shipment_reference' => $this->extractShipmentReference($payload),
                'shipment_synced_at' => now(),
                'shipment_payload' => $this->extractShipmentPayload($payload),
                'erp_source' => 'mikro',
                'source_metadata' => $payload['source_metadata'] ?? null,
                'order_date' => $this->resolveDate($payload['order_date'] ?? null) ?? now()->toDateString(),
                'due_date' => $this->resolveDate($payload['due_date'] ?? null),
                'notes' => $payload['notes'] ?? ($existing?->notes ?? 'Mikro supplier sync ile guncellendi'),
                'created_by' => $existing?->created_by ?? User::query()->orderBy('id')->value('id')
                    ?? throw new \RuntimeException('Sync icin en az bir kullanici kaydi gereklidir.'),
            ]
        );

        $lineStats = ['created' => 0, 'updated' => 0];

        foreach (Arr::wrap($payload['line_items'] ?? []) as $linePayload) {
            $wasCreated = $this->upsertLine($order, $linePayload);
            $wasCreated ? $lineStats['created']++ : $lineStats['updated']++;
        }

        if (! $wasExisting) {
            DB::afterCommit(function () use ($order): void {
                app(MailNotificationDispatcher::class)->queueNewOrderNotification($order, 'mikro');
            });
        }

        return [
            'orders_created' => $wasExisting ? 0 : 1,
            'orders_updated' => $wasExisting ? 1 : 0,
            'lines_created' => $lineStats['created'],
            'lines_updated' => $lineStats['updated'],
            'conflicts' => 0,
            'conflict_message' => null,
            'conflict_code' => null,
        ];
    }

    private function upsertLine(PurchaseOrder $order, array $payload): bool
    {
        $lineNo = trim((string) ($payload['line_no'] ?? ''));

        if ($lineNo === '') {
            throw new \RuntimeException('Siparis satir numarasi eksik.');
        }

        $line = PurchaseOrderLine::query()
            ->firstOrNew([
                'purchase_order_id' => $order->id,
                'line_no' => $lineNo,
            ]);

        $isNew = ! $line->exists;

        $line->fill([
            'product_code' => (string) ($payload['stock_code'] ?? '-'),
            'description' => (string) ($payload['stock_name'] ?? '-'),
            'quantity' => (int) ($payload['order_qty'] ?? 0),
            'shipped_quantity' => $this->extractShippedQuantity($payload),
            'unit' => $payload['unit'] ?? null,
            'notes' => $payload['notes'] ?? $line->notes,
        ]);

        $line->save();

        return $isNew;
    }

    private function mapOrderStatus(string $status): string
    {
        return match (strtolower($status)) {
            'open', 'aktif', 'active' => 'active',
            'closed', 'completed', 'kapali' => 'completed',
            'cancelled', 'iptal' => 'cancelled',
            'draft', 'taslak' => 'draft',
            default => 'active',
        };
    }

    private function mapShipmentStatus(array $payload): ?string
    {
        $status = strtolower((string) Arr::get($payload, 'shipment.status', Arr::get($payload, 'shipment_status', '')));

        return match ($status) {
            'pending', 'bekliyor' => 'pending',
            'dispatched', 'shipped', 'sevk' => 'dispatched',
            'delivered', 'delivered_to_stock', 'teslim' => 'delivered',
            'not_found' => 'not_found',
            default => null,
        };
    }

    private function extractShipmentReference(array $payload): ?string
    {
        $reference = Arr::get($payload, 'shipment.reference', Arr::get($payload, 'shipment_reference'));

        return filled($reference) ? (string) $reference : null;
    }

    private function extractShipmentPayload(array $payload): ?array
    {
        $shipment = Arr::get($payload, 'shipment_payload', Arr::get($payload, 'shipment'));

        return is_array($shipment) && $shipment !== [] ? $shipment : null;
    }

    private function extractShippedQuantity(array $payload): ?int
    {
        $value = Arr::get($payload, 'shipped_qty', Arr::get($payload, 'shipped_quantity'));

        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (int) $value);
    }

    private function resolveDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    private function markAccountSyncResult(SupplierMikroAccount $account, string $status, ?string $error = null): void
    {
        $account->forceFill([
            'last_sync_at' => now(),
            'last_sync_status' => $status,
            'last_sync_error' => filled($error) ? mb_substr($error, 0, 1000) : null,
        ])->save();
    }

    private function writeSyncLog(Supplier $supplier, array $stats): void
    {
        DB::table('erp_sync_logs')->insert([
            'source' => 'mikro',
            'type' => 'orders',
            'status' => ($stats['failed'] > 0 || $stats['conflicts'] > 0)
                ? ($stats['orders_created'] + $stats['orders_updated'] > 0 ? ErpSyncStatus::PARTIAL->value : ErpSyncStatus::FAILED->value)
                : ErpSyncStatus::SUCCESS->value,
            'records_synced' => $stats['orders_created'] + $stats['orders_updated'],
            'records_failed' => $stats['failed'] + $stats['conflicts'],
            'error_message' => $stats['errors'] ? implode("\n", array_slice($stats['errors'], 0, 5)) : null,
            'payload_summary' => json_encode([
                'supplier_id' => $supplier->id,
                'accounts' => $stats['accounts'],
                'orders_created' => $stats['orders_created'],
                'orders_updated' => $stats['orders_updated'],
                'lines_created' => $stats['lines_created'],
                'lines_updated' => $stats['lines_updated'],
                'conflicts' => $stats['conflicts'],
                'conflict_codes' => array_count_values($stats['conflict_codes']),
            ], JSON_UNESCAPED_UNICODE),
            'started_at' => now(),
            'finished_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function recordIssues(
        array &$stats,
        Supplier $supplier,
        SupplierMikroAccount $account,
        array $payload,
        array $issues
    ): void {
        foreach ($issues as $issue) {
            $stats['conflicts']++;
            $stats['conflict_codes'][] = $issue['code'];

            $message = sprintf(
                '[%s] supplier=%d cari=%s order=%s %s',
                $issue['code'],
                $supplier->id,
                $account->mikro_cari_kod,
                $payload['order_no'] ?? '-',
                $issue['message']
            );

            $stats['errors'][] = $message;

            Log::warning('Mikro sync issue detected', [
                'code' => $issue['code'],
                'supplier_id' => $supplier->id,
                'supplier_mikro_account_id' => $account->id,
                'order_no' => $payload['order_no'] ?? null,
                'message' => $issue['message'],
            ]);
        }
    }
}
