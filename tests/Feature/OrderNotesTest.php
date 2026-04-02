<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\OrderNote;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_user_can_add_line_note_and_reply(): void
    {
        $user = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
        ]);
        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
        ]);

        $this->actingAs($user)
            ->post(route('orders.notes.store', $order), [
                'purchase_order_line_id' => $line->id,
                'body' => 'Baskı öncesi renk referansı teyit edilecek.',
            ])
            ->assertRedirect();

        $note = OrderNote::query()
            ->where('purchase_order_id', $order->id)
            ->where('purchase_order_line_id', $line->id)
            ->whereNull('parent_id')
            ->first();

        $this->assertNotNull($note);
        $this->assertSame('Baskı öncesi renk referansı teyit edilecek.', $note->body);

        $this->actingAs($user)
            ->post(route('orders.notes.store', $order), [
                'purchase_order_line_id' => $line->id,
                'parent_id' => $note->id,
                'body' => 'Tamam, referans paylaşılınca revizyona geçelim.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('order_notes', [
            'purchase_order_id' => $order->id,
            'purchase_order_line_id' => $line->id,
            'parent_id' => $note->id,
            'body' => 'Tamam, referans paylaşılınca revizyona geçelim.',
        ]);

        $this->assertSame(2, AuditLog::query()->where('action', 'order.note.create')->count());
    }

    public function test_internal_users_endpoint_excludes_suppliers_and_inactive_users(): void
    {
        $author = User::factory()->admin()->create(['name' => 'Admin Kullanıcı']);
        $graphic = User::factory()->graphic()->create(['name' => 'Grafik Ekip']);
        $purchasing = User::factory()->purchasing()->create(['name' => 'Satın Alma']);
        $inactive = User::factory()->graphic()->inactive()->create(['name' => 'Pasif Grafik']);
        $supplier = Supplier::factory()->create();
        $supplierUser = User::factory()->supplier($supplier->id)->create(['name' => 'Tedarikçi Kullanıcı']);

        $response = $this->actingAs($author)->get(route('api.internal-users'));

        $response->assertOk()
            ->assertJsonFragment(['id' => $author->id, 'name' => 'Admin Kullanıcı'])
            ->assertJsonFragment(['id' => $graphic->id, 'name' => 'Grafik Ekip'])
            ->assertJsonFragment(['id' => $purchasing->id, 'name' => 'Satın Alma'])
            ->assertJsonMissing(['id' => $inactive->id, 'name' => 'Pasif Grafik'])
            ->assertJsonMissing(['id' => $supplierUser->id, 'name' => 'Tedarikçi Kullanıcı']);
    }

    public function test_note_create_sends_mention_notification_for_full_name_mentions(): void
    {
        $author = User::factory()->admin()->create(['name' => 'Not Sahibi']);
        $mentionedUser = User::factory()->graphic()->create(['name' => 'Alper Ateş']);
        $supplier = Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by' => $author->id,
        ]);

        $this->actingAs($author)
            ->post(route('orders.notes.store', $order), [
                'body' => '@Alper Ateş lütfen bu revizyonu kontrol eder misin?',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('portal_notifications', [
            'user_id' => $mentionedUser->id,
            'type' => 'mention',
            'url' => route('orders.show', $order),
        ]);
    }

    public function test_line_note_reply_accepts_browser_string_ids(): void
    {
        $user = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
        ]);
        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
        ]);
        $note = OrderNote::create([
            'purchase_order_id' => $order->id,
            'purchase_order_line_id' => $line->id,
            'user_id' => $user->id,
            'body' => 'İlk açıklama',
        ]);

        $this->actingAs($user)
            ->post(route('orders.notes.store', $order), [
                'purchase_order_line_id' => (string) $line->id,
                'parent_id' => (string) $note->id,
                'body' => 'Tarayıcıdan gelen yanıt',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('order_notes', [
            'purchase_order_id' => $order->id,
            'purchase_order_line_id' => $line->id,
            'parent_id' => $note->id,
            'body' => 'Tarayıcıdan gelen yanıt',
        ]);
    }

    public function test_replying_to_a_reply_continues_same_thread(): void
    {
        $user = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
        ]);
        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
        ]);
        $root = OrderNote::create([
            'purchase_order_id' => $order->id,
            'purchase_order_line_id' => $line->id,
            'user_id' => $user->id,
            'body' => 'Kök açıklama',
        ]);
        $reply = OrderNote::create([
            'purchase_order_id' => $order->id,
            'purchase_order_line_id' => $line->id,
            'parent_id' => $root->id,
            'user_id' => $user->id,
            'body' => 'İlk yanıt',
        ]);

        $this->actingAs($user)
            ->post(route('orders.notes.store', $order), [
                'purchase_order_line_id' => (string) $line->id,
                'parent_id' => (string) $reply->id,
                'body' => 'Yanıta verilen yeni mesaj',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('order_notes', [
            'purchase_order_id' => $order->id,
            'purchase_order_line_id' => $line->id,
            'parent_id' => $root->id,
            'body' => 'Yanıta verilen yeni mesaj',
        ]);
    }

    public function test_internal_user_can_update_note_and_reply(): void
    {
        $user = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
        ]);
        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
        ]);
        $note = OrderNote::create([
            'purchase_order_id' => $order->id,
            'purchase_order_line_id' => $line->id,
            'user_id' => $user->id,
            'body' => 'Eski açıklama',
        ]);
        $reply = OrderNote::create([
            'purchase_order_id' => $order->id,
            'purchase_order_line_id' => $line->id,
            'parent_id' => $note->id,
            'user_id' => $user->id,
            'body' => 'Eski yanıt',
        ]);

        $this->actingAs($user)
            ->patch(route('orders.notes.update', [$order, $note]), [
                'body' => 'Yeni açıklama',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->patch(route('orders.notes.update', [$order, $reply]), [
                'body' => 'Yeni yanıt',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('order_notes', [
            'id' => $note->id,
            'body' => 'Yeni açıklama',
        ]);
        $this->assertDatabaseHas('order_notes', [
            'id' => $reply->id,
            'body' => 'Yeni yanıt',
        ]);
        $this->assertSame(2, AuditLog::query()->where('action', 'order.note.update')->count());
    }

    public function test_note_cannot_be_added_to_a_line_from_another_order(): void
    {
        $user = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
        ]);
        $otherOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
        ]);
        $otherLine = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $otherOrder->id,
        ]);

        $this->actingAs($user)
            ->post(route('orders.notes.store', $order), [
                'purchase_order_line_id' => $otherLine->id,
                'body' => 'Bu kayıt eklenmemeli.',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('order_notes', [
            'purchase_order_id' => $order->id,
            'purchase_order_line_id' => $otherLine->id,
            'body' => 'Bu kayıt eklenmemeli.',
        ]);
        $this->assertSame(0, AuditLog::query()->where('action', 'order.note.create')->count());
    }
}
