<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_cannot_access_internal_orders(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create([
            'role'        => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
        ]);

        // Supplier /siparisler endpoint'ine giremez — iç kullanıcı rotası
        $this->actingAs($user)
             ->get('/siparisler')
             ->assertForbidden();
    }

    public function test_supplier_can_access_portal(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create([
            'role'        => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
        ]);

        $this->actingAs($user)
             ->get(route('portal.orders.index'))
             ->assertOk();
    }

    public function test_graphic_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['role' => UserRole::GRAPHIC]);

        $this->actingAs($user)
             ->get(route('admin.users.index'))
             ->assertForbidden();
    }

    public function test_purchasing_user_cannot_upload_artwork(): void
    {
        $supplier = Supplier::factory()->create();
        $order    = \App\Models\PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);
        $line     = \App\Models\PurchaseOrderLine::factory()->create(['purchase_order_id' => $order->id]);
        $user     = User::factory()->create(['role' => UserRole::PURCHASING]);

        $this->actingAs($user)
             ->get(route('artworks.create', $line))
             ->assertForbidden();
    }

    public function test_admin_can_access_all_areas(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('orders.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.suppliers.index'))->assertOk();
    }

    public function test_supplier_cannot_see_other_suppliers_orders(): void
    {
        $supplier1 = Supplier::factory()->create();
        $supplier2 = Supplier::factory()->create();

        $user = User::factory()->create([
            'role'        => UserRole::SUPPLIER,
            'supplier_id' => $supplier1->id,
        ]);

        $order = \App\Models\PurchaseOrder::factory()->create([
            'supplier_id' => $supplier2->id,
        ]);

        $this->actingAs($user)
             ->get(route('portal.orders.show', $order))
             ->assertForbidden();
    }
}
