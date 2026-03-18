<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\SupplierUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_with_pivot_mapping_can_list_only_authorized_orders(): void
    {
        $supplierA = Supplier::factory()->create();
        $supplierB = Supplier::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::SUPPLIER,
            'supplier_id' => null,
        ]);

        SupplierUser::create([
            'supplier_id' => $supplierA->id,
            'user_id' => $user->id,
            'is_primary' => true,
            'can_download' => true,
            'can_approve' => false,
        ]);

        $allowedOrder = PurchaseOrder::factory()->create(['supplier_id' => $supplierA->id]);
        $blockedOrder = PurchaseOrder::factory()->create(['supplier_id' => $supplierB->id]);

        $this->actingAs($user)
            ->get(route('portal.orders.index'))
            ->assertOk()
            ->assertSee($allowedOrder->order_no)
            ->assertDontSee($blockedOrder->order_no);
    }

    public function test_supplier_order_detail_shows_only_active_revision_and_logs_view(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
        ]);

        $order = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);
        $line = PurchaseOrderLine::factory()->create(['purchase_order_id' => $order->id]);
        $artwork = Artwork::factory()->create(['order_line_id' => $line->id]);

        $activeRevision = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'revision_no' => 2,
            'is_active' => true,
            'original_filename' => 'aktif-revizyon.pdf',
        ]);

        $archivedRevision = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'revision_no' => 1,
            'is_active' => false,
            'original_filename' => 'eski-revizyon.pdf',
        ]);

        $artwork->update(['active_revision_id' => $activeRevision->id]);

        $this->actingAs($user)
            ->get(route('portal.orders.show', $order))
            ->assertOk()
            ->assertSee('aktif-revizyon.pdf')
            ->assertDontSee('eski-revizyon.pdf');

        $this->assertDatabaseHas('artwork_view_logs', [
            'artwork_revision_id' => $activeRevision->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseMissing('artwork_view_logs', [
            'artwork_revision_id' => $archivedRevision->id,
            'user_id' => $user->id,
        ]);
    }
}
