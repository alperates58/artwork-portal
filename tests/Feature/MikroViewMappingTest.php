<?php

namespace Tests\Feature;

use App\Models\SupplierMikroAccount;
use App\Models\User;
use App\Services\Erp\MikroViewMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MikroViewMappingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('mikro_view_mappings')) {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2026_03_29_170000_create_mikro_view_mappings_table.php',
                '--force' => true,
            ]);
        }
    }

    public function test_mikro_view_mapping_can_be_saved_and_used_for_flat_rows(): void
    {
        $admin = User::factory()->admin()->create();
        $service = app(MikroViewMappingService::class);

        $mapping = $service->save([
            'name' => 'Mikro View V1',
            'view_name' => 'vw_portal_purchase_orders',
            'endpoint_path' => '/api/portal-orders',
            'payload_mode' => 'flat_rows',
            'line_array_key' => 'lines',
            'notes' => 'BT test mapping',
            'mapping' => [
                'order' => [
                    'supplier_code' => 'CARI_KOD',
                    'supplier_name' => 'CARI_UNVAN',
                    'order_no' => 'EVRAK_NO',
                    'order_date' => 'TARIH',
                    'status' => 'DURUM',
                    'due_date' => 'TESLIM_TARIHI',
                    'notes' => 'ACIKLAMA',
                    'shipment_status' => '',
                    'shipment_reference' => '',
                ],
                'line' => [
                    'line_no' => 'SIP_SATIRNO',
                    'stock_code' => 'STOK_KODU',
                    'stock_name' => 'STOK_ADI',
                    'order_qty' => 'MIKTAR',
                    'shipped_quantity' => 'SEVK_MIKTAR',
                    'unit' => 'BIRIM',
                    'line_notes' => 'SATIR_NOTU',
                ],
            ],
        ], $admin);

        $this->assertDatabaseHas('mikro_view_mappings', [
            'id' => $mapping->id,
            'view_name' => 'vw_portal_purchase_orders',
            'endpoint_path' => '/api/portal-orders',
            'payload_mode' => 'flat_rows',
            'is_active' => true,
        ]);

        $grouped = $service->groupFlatRows([
            [
                'CARI_KOD' => '120.01.001',
                'CARI_UNVAN' => 'ABC Ambalaj',
                'EVRAK_NO' => 'SIP-2026-001',
                'TARIH' => '2026-03-29',
                'DURUM' => 'active',
                'TESLIM_TARIHI' => '2026-04-05',
                'ACIKLAMA' => 'Test siparişi',
                'SIP_SATIRNO' => '1',
                'STOK_KODU' => 'STK-001',
                'STOK_ADI' => 'Etiket A',
                'MIKTAR' => '1000',
                'SEVK_MIKTAR' => '0',
                'BIRIM' => 'Adet',
                'SATIR_NOTU' => 'Birinci satır',
            ],
            [
                'CARI_KOD' => '120.01.001',
                'CARI_UNVAN' => 'ABC Ambalaj',
                'EVRAK_NO' => 'SIP-2026-001',
                'TARIH' => '2026-03-29',
                'DURUM' => 'active',
                'TESLIM_TARIHI' => '2026-04-05',
                'ACIKLAMA' => 'Test siparişi',
                'SIP_SATIRNO' => '2',
                'STOK_KODU' => 'STK-002',
                'STOK_ADI' => 'Etiket B',
                'MIKTAR' => '2000',
                'SEVK_MIKTAR' => '250',
                'BIRIM' => 'Adet',
                'SATIR_NOTU' => 'İkinci satır',
            ],
        ], $mapping);

        $this->assertCount(1, $grouped);
        $this->assertCount(2, $grouped[0]['lines']);

        $normalized = $service->normalizePayload(
            $grouped[0],
            $mapping,
            new SupplierMikroAccount(['mikro_cari_kod' => '120.01.001'])
        );

        $this->assertSame('SIP-2026-001', $normalized['order_no']);
        $this->assertSame('120.01.001', $normalized['supplier_code']);
        $this->assertCount(2, $normalized['line_items']);
        $this->assertSame('1', $normalized['line_items'][0]['line_no']);
        $this->assertSame('STK-001', $normalized['line_items'][0]['stock_code']);
        $this->assertSame(2000, $normalized['line_items'][1]['order_qty']);
        $this->assertSame('vw_portal_purchase_orders', $normalized['source_metadata']['view_name']);
    }
}
