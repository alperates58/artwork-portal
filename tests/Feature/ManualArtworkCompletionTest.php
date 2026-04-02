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

class ManualArtworkCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchasing_user_can_mark_line_as_manual_artwork_with_note(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create(['role' => UserRole::PURCHASING]);
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
        ]);

        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'artwork_status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post(route('order-lines.manual-artwork.store', $line), [
            'manual_artwork_note' => 'Bu ürünün tasarımı daha önce mail ile gönderilmişti.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $line->refresh();

        $this->assertNotNull($line->manual_artwork_completed_at);
        $this->assertSame($user->id, $line->manual_artwork_completed_by);
        $this->assertSame('Bu ürünün tasarımı daha önce mail ile gönderilmişti.', $line->manual_artwork_note);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'order_line.manual_artwork.complete',
            'model_type' => PurchaseOrderLine::class,
            'model_id' => $line->id,
        ]);
    }

    public function test_graphic_user_can_mark_line_as_manual_artwork_with_note(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create(['role' => UserRole::GRAPHIC]);
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
        ]);

        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'artwork_status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('order-lines.manual-artwork.store', $line), [
                'manual_artwork_note' => 'Grafik ekibi aynı tasarımı dış kanaldan kullandı.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('purchase_order_lines', [
            'id' => $line->id,
            'manual_artwork_completed_by' => $user->id,
            'manual_artwork_note' => 'Grafik ekibi aynı tasarımı dış kanaldan kullandı.',
        ]);
    }

    public function test_manual_artwork_note_is_required_for_line(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create(['role' => UserRole::PURCHASING]);
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
        ]);

        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
        ]);

        $this->actingAs($user)
            ->from(route('orders.show', $order))
            ->post(route('order-lines.manual-artwork.store', $line), [
                'manual_artwork_note' => '',
            ])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasErrors('manual_artwork_note');
    }

    public function test_order_pending_count_ignores_manual_completed_lines(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create(['role' => UserRole::PURCHASING]);
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
        ]);

        $manualLine = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'artwork_status' => 'pending',
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'artwork_status' => 'pending',
        ]);

        $this->actingAs($user)->post(route('order-lines.manual-artwork.store', $manualLine), [
            'manual_artwork_note' => 'Bu satır dışarıdan tamamlandı.',
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('1 satır bekliyor')
            ->assertSee('1 satır manuel');
    }

    public function test_orders_index_and_show_pages_render_successfully(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create(['role' => UserRole::PURCHASING]);
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'artwork_status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($order->order_no);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_no);
    }

    public function test_graphic_user_can_mark_revision_request_as_completed(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create(['role' => UserRole::GRAPHIC]);
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
        ]);

        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'artwork_status' => 'revision',
        ]);

        $artwork = Artwork::factory()->create([
            'order_line_id' => $line->id,
        ]);

        $revision = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'revision_no' => 12,
            'is_active' => true,
        ]);

        $artwork->update(['active_revision_id' => $revision->id]);

        $this->actingAs($user)
            ->post(route('order-lines.revision-complete.store', $line))
            ->assertRedirect();

        $this->assertDatabaseHas('purchase_order_lines', [
            'id' => $line->id,
            'artwork_status' => 'uploaded',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'order_line.revision.complete',
            'model_type' => PurchaseOrderLine::class,
            'model_id' => $line->id,
        ]);
    }

    public function test_purchasing_user_cannot_mark_revision_request_as_completed(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create(['role' => UserRole::PURCHASING]);
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
        ]);

        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'artwork_status' => 'revision',
        ]);

        $artwork = Artwork::factory()->create([
            'order_line_id' => $line->id,
        ]);

        $revision = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'revision_no' => 3,
            'is_active' => true,
        ]);

        $artwork->update(['active_revision_id' => $revision->id]);

        $this->actingAs($user)
            ->post(route('order-lines.revision-complete.store', $line))
            ->assertForbidden();

        $this->assertDatabaseHas('purchase_order_lines', [
            'id' => $line->id,
            'artwork_status' => 'revision',
        ]);
    }
}
