<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SpacesStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(SpacesStorageService::class, function ($mock) {
            $mock->shouldReceive('presignedUrl')->andReturn('https://spaces.example.com/presigned');
            $mock->shouldReceive('exists')->andReturn(true);
        });
    }

    public function test_unauthenticated_api_request_returns_401(): void
    {
        $this->getJson('/api/v1/orders')->assertUnauthorized();
    }

    public function test_supplier_can_list_their_orders_via_api(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create([
            'role'        => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
        ]);

        PurchaseOrder::factory(3)->create(['supplier_id' => $supplier->id]);
        PurchaseOrder::factory(2)->create(); // Başka tedarikçi

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/orders')->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_internal_user_can_list_all_orders_via_api(): void
    {
        $user = User::factory()->create(['role' => UserRole::PURCHASING]);

        PurchaseOrder::factory(5)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/orders')->assertOk();
        $response->assertJsonCount(5, 'data');
    }

    public function test_admin_can_push_order_via_api(): void
    {
        $admin    = User::factory()->create(['role' => UserRole::ADMIN]);
        $supplier = Supplier::factory()->create(['code' => 'TED-API-001']);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/orders', [
            'supplier_code' => 'TED-API-001',
            'order_no'      => 'PO-API-001',
            'order_date'    => now()->format('Y-m-d'),
            'lines'         => [
                [
                    'line_no'      => '001',
                    'product_code' => 'PRD-001',
                    'description'  => 'Test ürün',
                    'quantity'     => 100,
                ],
            ],
        ])->assertCreated()
          ->assertJsonPath('data.order_no', 'PO-API-001');

        $this->assertDatabaseHas('purchase_orders', ['order_no' => 'PO-API-001']);
        $this->assertDatabaseHas('purchase_order_lines', ['product_code' => 'PRD-001']);
    }

    public function test_supplier_cannot_push_orders_via_api(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'TED-001']);
        $user = User::factory()->create([
            'role'        => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/orders', [
            'supplier_code' => 'TED-001',
            'order_no'      => 'PO-HACK-001',
            'order_date'    => now()->format('Y-m-d'),
            'lines'         => [['line_no'=>'001','product_code'=>'X','description'=>'Y','quantity'=>1]],
        ])->assertForbidden();
    }

    public function test_api_token_generation(): void
    {
        $user = User::factory()->create([
            'role'      => UserRole::ADMIN,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/auth/token', [
            'email'       => $user->email,
            'password'    => 'password',
            'device_name' => 'test-device',
        ])->assertOk()
          ->assertJsonStructure(['token', 'token_type', 'user']);
    }

    public function test_inactive_user_cannot_get_api_token(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->postJson('/api/v1/auth/token', [
            'email'       => $user->email,
            'password'    => 'password',
            'device_name' => 'test-device',
        ])->assertForbidden();
    }
}
