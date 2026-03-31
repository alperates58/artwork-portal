<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_index_uses_turkish_pagination_labels(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $supplier = Supplier::factory()->create();

        PurchaseOrder::factory()->count(30)->create([
            'supplier_id' => $supplier->id,
            'created_by' => $admin->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Önceki')
            ->assertSee('Sonraki')
            ->assertDontSee('pagination.previous')
            ->assertDontSee('pagination.next');
    }

    public function test_orders_index_renders_lines_with_active_artwork_revision(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $graphic = User::factory()->graphic()->create();
        $supplier = Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by' => $admin->id,
            'status' => 'active',
        ]);
        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'product_code' => 'STK-500',
            'description' => 'Karton kutu',
        ]);
        $artwork = Artwork::factory()->create([
            'order_line_id' => $line->id,
        ]);
        $revision = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'uploaded_by' => $graphic->id,
            'is_active' => true,
        ]);

        $artwork->update(['active_revision_id' => $revision->id]);

        $this->actingAs($admin)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('STK-500');
    }
}
