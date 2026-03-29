<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PurchaseOrder;
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
}
