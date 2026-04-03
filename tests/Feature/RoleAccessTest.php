<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SystemSetting;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
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

    public function test_supplier_cannot_access_internal_user_directory_endpoint(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
        ]);

        $this->actingAs($user)
            ->get(route('api.internal-users'))
            ->assertForbidden();
    }

    public function test_graphic_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['role' => UserRole::GRAPHIC]);

        $this->actingAs($user)
             ->get(route('admin.users.index'))
             ->assertForbidden();
    }

    public function test_purchasing_user_can_create_supplier(): void
    {
        $user = User::factory()->create(['role' => UserRole::PURCHASING]);

        $this->actingAs($user)
            ->post(route('admin.suppliers.store'), [
                'name' => 'Yeni Tedarikçi',
                'code' => 'TED-998',
                'email' => 'tedarikci@example.com',
                'phone' => '5551234567',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'code' => 'TED-998',
            'name' => 'Yeni Tedarikçi',
        ]);
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

    public function test_order_creation_setting_overrides_custom_order_create_permission(): void
    {
        SystemSetting::query()->create([
            'group' => 'portal',
            'key' => 'portal.order_creation_enabled',
            'value' => '0',
        ]);

        $supplier = Supplier::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::GRAPHIC,
            'permissions' => [
                'orders' => [
                    'view' => true,
                    'create' => true,
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('orders.create'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('orders.store'), [
                'supplier_id' => $supplier->id,
                'order_no' => 'PO-SETTING-001',
                'order_date' => now()->toDateString(),
                'due_date' => now()->addDay()->toDateString(),
                'notes' => 'Test siparişi',
                'lines' => [
                    [
                        'line_no' => '1',
                        'product_code' => 'STK-001',
                        'description' => 'Deneme ürün',
                        'quantity' => 10,
                        'unit' => 'Adet',
                    ],
                ],
            ])
            ->assertForbidden();
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

    public function test_internal_user_without_orders_view_permission_cannot_access_order_pages(): void
    {
        $supplier = Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);
        $line = PurchaseOrderLine::factory()->create(['purchase_order_id' => $order->id]);
        $user = User::factory()->create([
            'role' => UserRole::GRAPHIC,
            'permissions' => [
                'orders' => [
                    'view' => false,
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('order-lines.show', $line))
            ->assertForbidden();
    }
}
