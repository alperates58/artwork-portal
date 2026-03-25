<?php

namespace App\Services\Faz2;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Services\Mikro\MikroClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MikroErpSyncService
{
    public function __construct(private MikroClient $mikro) {}

    public function syncOrders(): array
    {
        $startedAt = now();
        $synced = 0;
        $failed = 0;
        $errors = [];

        try {
            $orders = $this->fetchOrdersFromMikro();

            foreach ($orders as $erpOrder) {
                try {
                    $this->upsertOrder($erpOrder);
                    $synced++;
                } catch (\Throwable $exception) {
                    $failed++;
                    $errors[] = 'Siparis ' . ($erpOrder['order_no'] ?? '-') . ': ' . $exception->getMessage();
                    Log::error('ERP order sync error', [
                        'order_no' => $erpOrder['order_no'] ?? null,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            $status = $failed === 0 ? 'success' : ($synced > 0 ? 'partial' : 'failed');
        } catch (\Throwable $exception) {
            $status = 'failed';
            $errors[] = $exception->getMessage();
            Log::error('ERP sync failed', ['error' => $exception->getMessage()]);
        }

        $this->writeSyncLog('orders', $status, $synced, $failed, $errors, $startedAt);

        return compact('synced', 'failed', 'errors', 'status');
    }

    private function fetchOrdersFromMikro(): array
    {
        if (! $this->mikro->isEnabled()) {
            return [];
        }

        $response = $this->mikro->get('/api/purchase-orders', [
            'updated_after' => now()->subHours(24)->toIso8601String(),
            'status' => 'active',
        ]);

        return $response->json('data', []);
    }

    private function upsertOrder(array $erpOrder): void
    {
        DB::transaction(function () use ($erpOrder) {
            $supplier = Supplier::firstOrCreate(
                ['code' => $erpOrder['supplier_code']],
                [
                    'name' => $erpOrder['supplier_name'],
                    'email' => $erpOrder['supplier_email'] ?? null,
                    'is_active' => true,
                ]
            );

            $order = PurchaseOrder::updateOrCreate(
                ['order_no' => $erpOrder['order_no']],
                [
                    'supplier_id' => $supplier->id,
                    'status' => $this->mapErpStatus($erpOrder['status']),
                    'order_date' => $erpOrder['order_date'],
                    'due_date' => $erpOrder['due_date'] ?? null,
                    'notes' => $erpOrder['notes'] ?? null,
                    'created_by' => 1,
                ]
            );

            foreach ($erpOrder['lines'] ?? [] as $erpLine) {
                PurchaseOrderLine::updateOrCreate(
                    [
                        'purchase_order_id' => $order->id,
                        'line_no' => $erpLine['line_no'],
                    ],
                    [
                        'product_code' => $erpLine['product_code'],
                        'description' => $erpLine['description'],
                        'quantity' => $erpLine['quantity'],
                        'unit' => $erpLine['unit'] ?? null,
                    ]
                );
            }
        });
    }

    private function mapErpStatus(string $erpStatus): string
    {
        return match (strtolower($erpStatus)) {
            'acik', 'open', 'active' => 'active',
            'kapali', 'closed' => 'completed',
            'iptal', 'cancelled' => 'cancelled',
            default => 'active',
        };
    }

    private function writeSyncLog(
        string $type,
        string $status,
        int $synced,
        int $failed,
        array $errors,
        \Carbon\Carbon $startedAt
    ): void {
        DB::table('erp_sync_logs')->insert([
            'source' => 'mikro',
            'type' => $type,
            'status' => $status,
            'records_synced' => $synced,
            'records_failed' => $failed,
            'error_message' => $errors ? implode("\n", array_slice($errors, 0, 5)) : null,
            'payload_summary' => json_encode(['synced' => $synced, 'failed' => $failed]),
            'started_at' => $startedAt,
            'finished_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
