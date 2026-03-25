<?php

namespace App\Services\Erp;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Services\Mikro\MikroClient;
use App\Services\Mikro\MikroException;
use Illuminate\Support\Facades\Log;

class MikroErpService
{
    public function __construct(private MikroClient $mikro) {}

    public function syncOrders(): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

        try {
            $erpOrders = $this->fetchOrdersFromErp();

            foreach ($erpOrders as $erpOrder) {
                try {
                    $result = $this->upsertOrder($erpOrder);
                    $stats[$result]++;
                } catch (\Throwable $exception) {
                    Log::error('ERP order sync error', [
                        'order_no' => $erpOrder['order_no'] ?? null,
                        'error' => $exception->getMessage(),
                    ]);
                    $stats['errors']++;
                }
            }
        } catch (\Throwable $exception) {
            Log::error('ERP sync connection error', ['error' => $exception->getMessage()]);
            throw $exception;
        }

        Log::info('ERP sync tamamlandi', $stats);

        return $stats;
    }

    private function fetchOrdersFromErp(): array
    {
        if (! $this->mikro->isEnabled()) {
            return [];
        }

        try {
            $response = $this->mikro->get('/api/purchase-orders', [
                'status' => 'active',
                'updated_after' => now()->subHours(1)->toIso8601String(),
            ]);
        } catch (MikroException $exception) {
            throw new \RuntimeException($exception->getMessage(), previous: $exception);
        }

        return $response->json('data', []);
    }

    private function upsertOrder(array $erpOrder): string
    {
        $supplier = Supplier::firstOrCreate(
            ['code' => $erpOrder['supplier_code']],
            [
                'name' => $erpOrder['supplier_name'],
                'email' => $erpOrder['supplier_email'] ?? null,
                'is_active' => true,
            ]
        );

        $order = PurchaseOrder::where('order_no', $erpOrder['order_no'])->first();

        if ($order) {
            $order->update([
                'status' => $this->mapStatus($erpOrder['status']),
                'due_date' => $erpOrder['due_date'] ?? null,
            ]);
            $action = 'updated';
        } else {
            $order = PurchaseOrder::create([
                'supplier_id' => $supplier->id,
                'order_no' => $erpOrder['order_no'],
                'status' => $this->mapStatus($erpOrder['status']),
                'order_date' => $erpOrder['order_date'],
                'due_date' => $erpOrder['due_date'] ?? null,
                'created_by' => 1,
                'notes' => 'ERPden otomatik aktarildi',
            ]);
            $action = 'created';
        }

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

        return $action;
    }

    private function mapStatus(string $erpStatus): string
    {
        return match (strtolower($erpStatus)) {
            'open', 'aktif', 'active' => 'active',
            'closed', 'completed' => 'completed',
            'cancelled', 'iptal' => 'cancelled',
            default => 'active',
        };
    }
}
