<?php

namespace Tests\Feature;

use App\Models\Artwork;
use App\Models\ArtworkApproval;
use App\Models\ArtworkGallery;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockCard;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraceabilityReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_traceability_report_can_search_by_stock_name_and_highlight_latest_printed_supplier(): void
    {
        $admin = User::factory()->admin()->create();
        $stockCard = StockCard::factory()->create([
            'stock_code' => 'STK-900',
            'stock_name' => 'Kraft Kahve Poşeti',
        ]);

        $firstSupplier = Supplier::factory()->create(['name' => 'Ege Ambalaj']);
        $firstOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $firstSupplier->id,
            'order_no' => 'PO-TRC-001',
            'order_date' => '2026-03-01',
            'shipment_status' => 'dispatched',
            'shipment_reference' => 'IRS-001',
            'shipment_synced_at' => '2026-03-05 00:00:00',
        ]);
        $firstLine = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $firstOrder->id,
            'line_no' => 1,
            'product_code' => 'STK-900',
            'description' => 'İlk kahve poşeti işi',
            'artwork_status' => 'approved',
            'shipped_quantity' => 100,
        ]);
        $firstArtwork = Artwork::factory()->create(['order_line_id' => $firstLine->id]);
        $firstGallery = ArtworkGallery::factory()->create([
            'stock_card_id' => $stockCard->id,
            'stock_code' => 'STK-900',
            'name' => 'Kraft Kahve Poşeti',
            'revision_no' => 1,
        ]);
        $firstRevision = ArtworkRevision::factory()->create([
            'artwork_id' => $firstArtwork->id,
            'artwork_gallery_id' => $firstGallery->id,
            'revision_no' => 1,
            'is_active' => true,
            'created_at' => '2026-03-02 00:00:00',
        ]);
        $firstArtwork->update(['active_revision_id' => $firstRevision->id]);

        $latestSupplier = Supplier::factory()->create(['name' => 'Marmara Ambalaj']);
        $latestOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $latestSupplier->id,
            'order_no' => 'PO-TRC-002',
            'order_date' => '2026-03-10',
            'shipment_status' => 'delivered',
            'shipment_reference' => 'IRS-002',
            'shipment_synced_at' => '2026-03-15 00:00:00',
        ]);
        $latestLine = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $latestOrder->id,
            'line_no' => 2,
            'product_code' => 'STK-900',
            'description' => 'Güncel kahve poşeti işi',
            'artwork_status' => 'approved',
            'shipped_quantity' => 250,
        ]);
        $latestArtwork = Artwork::factory()->create(['order_line_id' => $latestLine->id]);
        $latestGallery = ArtworkGallery::factory()->create([
            'stock_card_id' => $stockCard->id,
            'stock_code' => 'STK-900',
            'name' => 'Kraft Kahve Poşeti',
            'revision_no' => 3,
        ]);
        $latestRevision = ArtworkRevision::factory()->create([
            'artwork_id' => $latestArtwork->id,
            'artwork_gallery_id' => $latestGallery->id,
            'revision_no' => 3,
            'is_active' => true,
            'created_at' => '2026-03-11 00:00:00',
        ]);
        $latestArtwork->update(['active_revision_id' => $latestRevision->id]);

        $response = $this->actingAs($admin)->get(route('admin.reports.traceability', [
            'query' => 'Kraft Kahve',
        ]));

        $response->assertOk()
            ->assertSee('Kraft Kahve Poşeti')
            ->assertSee('PO-TRC-001')
            ->assertSee('PO-TRC-002')
            ->assertSeeInOrder([
                'Son Basım İzi',
                'Marmara Ambalaj',
                'PO-TRC-002',
                'Revizyon #3',
            ]);
    }

    public function test_traceability_report_shows_stage_durations_for_matching_item(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create(['name' => 'Trakya Baskı']);
        $supplierUser = User::factory()->supplier($supplier->id)->create([
            'name' => 'Tedarikçi Kullanıcısı',
        ]);
        $stockCard = StockCard::factory()->create([
            'stock_code' => 'STK-910',
            'stock_name' => 'Çay Kutusu',
        ]);

        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'order_no' => 'PO-TRC-010',
            'order_date' => '2026-03-01',
            'shipment_status' => 'delivered',
            'shipment_reference' => 'IRS-910',
            'shipment_synced_at' => '2026-03-08 00:00:00',
        ]);
        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'line_no' => 4,
            'product_code' => 'STK-910',
            'description' => 'Özel çay kutusu',
            'artwork_status' => 'approved',
            'shipped_quantity' => 500,
        ]);
        $artwork = Artwork::factory()->create(['order_line_id' => $line->id]);

        $firstGallery = ArtworkGallery::factory()->create([
            'stock_card_id' => $stockCard->id,
            'stock_code' => 'STK-910',
            'name' => 'Çay Kutusu',
            'revision_no' => 1,
        ]);
        ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'artwork_gallery_id' => $firstGallery->id,
            'revision_no' => 1,
            'is_active' => false,
            'created_at' => '2026-03-03 00:00:00',
        ]);

        $latestGallery = ArtworkGallery::factory()->create([
            'stock_card_id' => $stockCard->id,
            'stock_code' => 'STK-910',
            'name' => 'Çay Kutusu',
            'revision_no' => 2,
        ]);
        $latestRevision = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'artwork_gallery_id' => $latestGallery->id,
            'revision_no' => 2,
            'is_active' => true,
            'created_at' => '2026-03-05 00:00:00',
        ]);
        $artwork->update(['active_revision_id' => $latestRevision->id]);

        ArtworkApproval::create([
            'artwork_revision_id' => $latestRevision->id,
            'user_id' => $supplierUser->id,
            'supplier_id' => $supplier->id,
            'status' => 'approved',
            'actioned_at' => '2026-03-06 00:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.traceability', [
            'query' => 'Çay Kutusu',
        ]));

        $response->assertOk()
            ->assertSee('Sipariş → ilk revizyon: 2,0 gün')
            ->assertSee('İlk revizyon → ilk tedarikçi aksiyonu: 3,0 gün')
            ->assertSee('Son revizyon → onay: 1,0 gün')
            ->assertSee('Sipariş → basım / sevk: 7,0 gün')
            ->assertSee('Tedarikçi onayladı')
            ->assertSee('Basım / sevk sinyali alındı');
    }
}
