<?php

namespace App\Services\Erp;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mikro ERP entegrasyonu — Faz 2 (öne alındı)
 *
 * Mikro ERP'den sipariş verisi çekip portala aktarır.
 * Mikro'nun REST API'si veya veritabanı bağlantısı üzerinden çalışabilir.
 *
 * Config: config/erp.php
 * Çalıştırma: php artisan erp:sync (SyncErpOrdersJob aracılığıyla)
 */
class MikroErpService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('erp.mikro.base_url', '');
        $this->apiKey  = config('erp.mikro.api_key', '');
    }

    /**
     * Tüm aktif siparişleri Mikro'dan çek ve portala aktar
     */
    public function syncOrders(): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

        try {
            $erpOrders = $this->fetchOrdersFromErp();

            foreach ($erpOrders as $erpOrder) {
                try {
                    $result = $this->upsertOrder($erpOrder);
                    $stats[$result]++;
                } catch (\Throwable $e) {
                    Log::error("ERP order sync error: {$e->getMessage()}", ['order' => $erpOrder]);
                    $stats['errors']++;
                }
            }
        } catch (\Throwable $e) {
            Log::error("ERP sync connection error: {$e->getMessage()}");
            throw $e;
        }

        Log::info('ERP sync tamamlandı', $stats);

        return $stats;
    }

    /**
     * Mikro API'den sipariş listesi çek
     *
     * Gerçek Mikro entegrasyonu için bu metodu güncelle:
     * - Mikro REST API kullanıyorsan: Http::get(...)
     * - Mikro veritabanına bağlıysan: DB::connection('mikro')->table(...)
     */
    private function fetchOrdersFromErp(): array
    {
        if (empty($this->baseUrl)) {
            // Geliştirme ortamı için örnek veri
            return $this->getMockOrders();
        }

        $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
            ->timeout(30)
            ->get("{$this->baseUrl}/api/purchase-orders", [
                'status'     => 'active',
                'updated_after' => now()->subHours(1)->toIso8601String(),
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("ERP API hatası: HTTP {$response->status()}");
        }

        return $response->json('data', []);
    }

    /**
     * Siparişi portal'a yaz veya güncelle
     */
    private function upsertOrder(array $erpOrder): string
    {
        // Tedarikçiyi koda göre bul veya oluştur
        $supplier = Supplier::firstOrCreate(
            ['code' => $erpOrder['supplier_code']],
            [
                'name'      => $erpOrder['supplier_name'],
                'email'     => $erpOrder['supplier_email'] ?? null,
                'is_active' => true,
            ]
        );

        // Siparişi bul veya oluştur
        $order = PurchaseOrder::where('order_no', $erpOrder['order_no'])->first();

        if ($order) {
            $order->update([
                'status'   => $this->mapStatus($erpOrder['status']),
                'due_date' => $erpOrder['due_date'] ?? null,
            ]);
            $action = 'updated';
        } else {
            $order = PurchaseOrder::create([
                'supplier_id' => $supplier->id,
                'order_no'    => $erpOrder['order_no'],
                'status'      => $this->mapStatus($erpOrder['status']),
                'order_date'  => $erpOrder['order_date'],
                'due_date'    => $erpOrder['due_date'] ?? null,
                'created_by'  => 1, // System user
                'notes'       => 'ERP\'den otomatik aktarıldı',
            ]);
            $action = 'created';
        }

        // Sipariş satırlarını sync et
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

        return $action;
    }

    private function mapStatus(string $erpStatus): string
    {
        return match(strtolower($erpStatus)) {
            'open', 'aktif', 'active' => 'active',
            'closed', 'completed'     => 'completed',
            'cancelled', 'iptal'      => 'cancelled',
            default                   => 'active',
        };
    }

    /**
     * Geliştirme ortamı için sahte veri
     */
    private function getMockOrders(): array
    {
        return [
            [
                'order_no'      => 'PO-2024-MOCK-001',
                'supplier_code' => 'TED-001',
                'supplier_name' => 'Mock Tedarikçi A.Ş.',
                'supplier_email'=> 'info@mock.com',
                'status'        => 'active',
                'order_date'    => now()->subDays(5)->format('Y-m-d'),
                'due_date'      => now()->addDays(30)->format('Y-m-d'),
                'lines'         => [
                    ['line_no'=>'001','product_code'=>'AMB-001','description'=>'Koli 30x20x15','quantity'=>1000,'unit'=>'adet'],
                    ['line_no'=>'002','product_code'=>'ETK-015','description'=>'Etiket 10x5cm','quantity'=>5000,'unit'=>'adet'],
                ],
            ],
        ];
    }
}
