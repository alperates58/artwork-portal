<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFactoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_preview_accepts_product_code_and_order_no_dimensions(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create([
            'name' => 'Akdeniz Tedarik',
            'code' => 'TED-700',
        ]);

        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'order_no' => 'PO-2026-7001',
            'status' => 'active',
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'line_no' => 1,
            'product_code' => 'STK-700',
            'artwork_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.reports.factory.preview'), [
            'dimensions' => ['product_code', 'order_no'],
            'metrics' => ['pending_artwork'],
            'filters' => [
                'supplier_id' => $supplier->id,
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'row_count' => 1,
                'columns' => ['Stok Kodu', 'Sipariş No', 'Bekleyen Artwork'],
            ]);

        $this->assertSame('STK-700 · PO-2026-7001', $response->json('labels.0'));
        $this->assertSame('STK-700 · PO-2026-7001', $response->json('table.0.label'));
        $this->assertSame('1', (string) $response->json('table.0.pending_artwork'));
    }

    public function test_preview_counts_manual_artwork_lines_with_dedicated_metric(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create([
            'name' => 'Manuel Tedarik',
            'code' => 'TED-701',
        ]);

        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'order_no' => 'PO-2026-7011',
            'status' => 'active',
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'line_no' => 1,
            'product_code' => 'STK-701',
            'artwork_status' => 'pending',
            'manual_artwork_completed_at' => now(),
            'manual_artwork_completed_by' => $admin->id,
            'manual_artwork_note' => 'Mail ile tamamlandı.',
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.reports.factory.preview'), [
            'dimensions' => ['supplier'],
            'metrics' => ['manual_artwork', 'pending_artwork'],
            'filters' => [
                'supplier_id' => $supplier->id,
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'row_count' => 1,
                'columns' => ['Tedarikçi', 'Manuel Tamamlanan Artwork', 'Bekleyen Artwork'],
            ]);

        $this->assertSame('1', (string) $response->json('table.0.manual_artwork'));
        $this->assertSame('0', (string) $response->json('table.0.pending_artwork'));
    }
}
