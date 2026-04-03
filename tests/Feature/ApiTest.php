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

    public function test_supplier_api_uses_all_accessible_supplier_mappings(): void
    {
        $primarySupplier = Supplier::factory()->create();
        $secondarySupplier = Supplier::factory()->create();
        $otherSupplier = Supplier::factory()->create();

        $user = User::factory()->create([
            'role' => UserRole::SUPPLIER,
            'supplier_id' => $primarySupplier->id,
        ]);

        SupplierUser::query()->updateOrCreate([
            'supplier_id' => $secondarySupplier->id,
            'user_id' => $user->id,
        ], [
            'is_primary' => false,
            'can_download' => true,
            'can_approve' => false,
        ]);

        PurchaseOrder::factory()->create(['supplier_id' => $primarySupplier->id]);
        PurchaseOrder::factory(2)->create(['supplier_id' => $secondarySupplier->id]);
        PurchaseOrder::factory(3)->create(['supplier_id' => $otherSupplier->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonCount(3, 'data');
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

    public function test_supplier_order_detail_hides_orders_from_other_suppliers(): void
    {
        $supplier = Supplier::factory()->create();
        $otherSupplier = Supplier::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
        ]);

        PurchaseOrder::factory()->create([
            'supplier_id' => $otherSupplier->id,
            'order_no' => 'PO-HIDDEN-001',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/orders/PO-HIDDEN-001')->assertNotFound();
    }

    public function test_supplier_without_download_permission_cannot_get_api_download_url(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
        ]);

        SupplierUser::query()->updateOrCreate([
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
        ], [
            'is_primary' => true,
            'can_download' => false,
            'can_approve' => false,
        ]);

        $order = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);
        $line = PurchaseOrderLine::factory()->create(['purchase_order_id' => $order->id]);
        $artwork = Artwork::factory()->create(['order_line_id' => $line->id]);
        $revision = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'is_active' => true,
            'original_filename' => 'etiket.pdf',
            'spaces_path' => 'artworks/test/etiket.pdf',
        ]);

        $artwork->update(['active_revision_id' => $revision->id]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/artworks/{$revision->id}/download-url")
            ->assertForbidden();
    }

    public function test_api_token_generation(): void
    {
        $user = User::factory()->create([
            'role'      => UserRole::ADMIN,
            'is_active' => true,
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.11'])
            ->postJson('/api/v1/auth/token', [
            'email'       => $user->email,
            'password'    => 'password',
            'device_name' => 'test-device',
        ])->assertOk()
          ->assertJsonStructure(['token', 'token_type', 'user']);
    }

    public function test_inactive_user_cannot_get_api_token(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.12'])
            ->postJson('/api/v1/auth/token', [
            'email'       => $user->email,
            'password'    => 'password',
            'device_name' => 'test-device',
        ])->assertForbidden();
    }

    public function test_api_token_generation_is_throttled(): void
    {
        User::factory()->create([
            'role' => UserRole::ADMIN,
            'is_active' => true,
            'email' => 'rate-limit@example.com',
        ]);

        $lastResponse = null;

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $lastResponse = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.13'])
                ->postJson('/api/v1/auth/token', [
                    'email' => 'rate-limit@example.com',
                    'password' => 'wrong-password',
                    'device_name' => 'test-device',
                ]);

            if ($lastResponse->status() === 429) {
                break;
            }
        }

        $this->assertNotNull($lastResponse);
        $lastResponse->assertTooManyRequests();
    }
}
