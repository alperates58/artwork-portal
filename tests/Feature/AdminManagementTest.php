<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_archive_supplier_without_orders(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $supplier = Supplier::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.suppliers.destroy', $supplier), [
                'confirmation' => '1',
            ])
            ->assertRedirect(route('admin.suppliers.index'));

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_admin_can_delete_order_with_matching_confirmation_text(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $supplier = Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('orders.destroy', $order), [
                'confirmation_text' => $order->order_no,
            ])
            ->assertRedirect(route('orders.index'));

        $this->assertDatabaseMissing('purchase_orders', ['id' => $order->id]);
    }

    public function test_permissions_index_supports_department_names_when_filtering_cards(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $department = Department::query()->firstOrCreate(
            ['name' => 'Ar-Ge'],
            ['permissions' => []]
        );

        User::factory()->create([
            'role' => UserRole::GRAPHIC,
            'department_id' => $department->id,
            'permissions' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.permissions.index'))
            ->assertOk()
            ->assertSee('Ar-Ge');
    }
}
