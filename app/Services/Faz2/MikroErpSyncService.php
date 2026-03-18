<?php

namespace App\Services\Faz2;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\ErpSyncLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mikro ERP entegrasyonu — Faz 2
 *
 * Mikro, REST API veya doğrudan veritabanı bağlantısı ile entegre olabilir.
 * Bu servis HTTP API yaklaşımını uygular.
 * Mikro'nun API dökümantasyonuna göre endpoint ve payload formatı güncellenmeli.
 */
class MikroErpSyncService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('erp.mikro_url', '');
        $this->apiKey  = config('erp.mikro_api_key', '');
    }

    /**
     * Tüm siparişleri senkronize et
     * Cron ile günde birkaç kez çalışır
     */
    public function syncOrders(): array
    {
        $startedAt = now();
        $synced    = 0;
        $failed    = 0;
        $errors    = [];

        try {
            $orders = $this->fetchOrdersFromMikro();

            foreach ($orders as $erpOrder) {
                try {
                    $this->upsertOrder($erpOrder);
                    $synced++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Sipariş {$erpOrder['order_no']}: {$e->getMessage()}";
                    Log::error('ERP order sync error', ['order' => $erpOrder, 'error' => $e->getMessage()]);
                }
            }

            $status = $failed === 0 ? 'success' : ($synced > 0 ? 'partial' : 'failed');

        } catch (\Exception $e) {
            $status = 'failed';
            $errors[] = $e->getMessage();
            Log::error('ERP sync failed', ['error' => $e->getMessage()]);
        }

        // Sync logu kaydet
        $this->writeSyncLog('orders', $status, $synced, $failed, $errors, $startedAt);

        return compact('synced', 'failed', 'errors', 'status');
    }

    /**
     * Mikro'dan siparişleri çek
     * Gerçek implementasyonda Mikro API formatına göre düzenlenmeli
     */
    private function fetchOrdersFromMikro(): array
    {
        if (empty($this->baseUrl)) {
            // Mikro URL tanımlanmamışsa boş dön — geliştirme ortamı
            return [];
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Accept'        => 'application/json',
        ])
        ->timeout(30)
        ->get("{$this->baseUrl}/api/purchase-orders", [
            'updated_after' => now()->subHours(24)->toIso8601String(),
            'status'        => 'active',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Mikro API hatası: HTTP {$response->status()}");
        }

        return $response->json('data', []);
    }

    /**
     * Siparişi portal veritabanına ekle veya güncelle
     */
    private function upsertOrder(array $erpOrder): void
    {
        DB::transaction(function () use ($erpOrder) {
            // Tedarikçiyi bul veya oluştur
            $supplier = Supplier::firstOrCreate(
                ['code' => $erpOrder['supplier_code']],
                [
                    'name'      => $erpOrder['supplier_name'],
                    'email'     => $erpOrder['supplier_email'] ?? null,
                    'is_active' => true,
                ]
            );

            // Siparişi ekle/güncelle
            $order = PurchaseOrder::updateOrCreate(
                ['order_no' => $erpOrder['order_no']],
                [
                    'supplier_id' => $supplier->id,
                    'status'      => $this->mapErpStatus($erpOrder['status']),
                    'order_date'  => $erpOrder['order_date'],
                    'due_date'    => $erpOrder['due_date'] ?? null,
                    'notes'       => $erpOrder['notes'] ?? null,
                    'created_by'  => 1, // System user
                ]
            );

            // Sipariş satırlarını güncelle
            foreach ($erpOrder['lines'] ?? [] as $erpLine) {
                PurchaseOrderLine::updateOrCreate(
                    [
                        'purchase_order_id' => $order->id,
                        'line_no'           => $erpLine['line_no'],
                    ],
                    [
                        'product_code' => $erpLine['product_code'],
                        'description'  => $erpLine['description'],
                        'quantity'     => $erpLine['quantity'],
                        'unit'         => $erpLine['unit'] ?? null,
                    ]
                );
            }
        });
    }

    private function mapErpStatus(string $erpStatus): string
    {
        return match(strtolower($erpStatus)) {
            'açık', 'open', 'active'  => 'active',
            'kapalı', 'closed'        => 'completed',
            'iptal', 'cancelled'      => 'cancelled',
            default                   => 'active',
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
            'source'          => 'mikro',
            'type'            => $type,
            'status'          => $status,
            'records_synced'  => $synced,
            'records_failed'  => $failed,
            'error_message'   => $errors ? implode("\n", array_slice($errors, 0, 5)) : null,
            'payload_summary' => json_encode(['synced' => $synced, 'failed' => $failed]),
            'started_at'      => $startedAt,
            'finished_at'     => now(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }
}
