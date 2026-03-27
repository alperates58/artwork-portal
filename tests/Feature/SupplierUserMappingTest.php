<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierUserMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_supplier_user_also_creates_supplier_mapping(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $supplier = Supplier::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Tedarikçi Kullanıcısı',
                'email' => 'supplier@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => UserRole::SUPPLIER->value,
                'supplier_id' => $supplier->id,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'supplier@example.com')->firstOrFail();

        $this->assertDatabaseHas('supplier_users', [
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'is_primary' => true,
            'can_download' => true,
        ]);
    }

    public function test_creating_supplier_user_directly_also_creates_primary_mapping(): void
    {
        $supplier = Supplier::factory()->create();

        $user = User::factory()->create([
            'role' => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
        ]);

        $this->assertDatabaseHas('supplier_users', [
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'is_primary' => true,
            'can_download' => true,
        ]);
    }

    public function test_switching_user_away_from_supplier_role_removes_mapping(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
        ]);

        $this->assertDatabaseHas('supplier_users', [
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
        ]);

        $user->update([
            'role' => UserRole::PURCHASING,
            'supplier_id' => null,
        ]);

        $this->assertDatabaseMissing('supplier_users', [
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
        ]);
    }
}
