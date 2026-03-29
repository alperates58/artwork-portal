<?php

namespace Tests\Feature;

use App\Enums\ErpSyncStatus;
use App\Jobs\SyncAllActiveSuppliersJob;
use App\Jobs\SyncSupplierOrdersJob;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierMikroAccount;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Erp\MikroOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MikroIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_run_mikro_connectivity_test(): void
    {
        config()->set('mikro.enabled', true);
        config()->set('mikro.base_url', 'https://mikro.example.test');

        Http::fake([
            'https://mikro.example.test/' => Http::response(['ok' => true], 200),
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson(route('admin.integrations.mikro.test'))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'ok',
            ]);
    }

    public function test_non_admin_cannot_run_mikro_connectivity_test(): void
    {
        $user = User::factory()->purchasing()->create();

        $this->actingAs($user)
            ->get(route('admin.integrations.mikro.test'))
            ->assertForbidden();
    }

    public function test_settings_page_does_not_render_mikro_secrets_or_direct_db_toggle(): void
    {
        $admin = User::factory()->admin()->create();

        SystemSetting::query()->create([
            'group' => 'mikro',
            'key' => 'mikro.api_key',
            'value' => encrypt('top-secret-key'),
            'is_encrypted' => true,
        ]);

        SystemSetting::query()->create([
            'group' => 'mikro',
            'key' => 'mikro.username',
            'value' => encrypt('mikro-user'),
            'is_encrypted' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertDontSee('top-secret-key');
        $response->assertDontSee('mikro-user');
        $response->assertDontSee('Dogrudan DB baglantisi kullanilacak');
        $response->assertSee('Kayıtlı anahtar var', false);
        $response->assertSee('Kayıtlı kullanıcı var', false);
    }

    public function test_updating_non_secret_mikro_settings_keeps_existing_secret_values(): void
    {
        $admin = User::factory()->admin()->create();

        SystemSetting::query()->create([
            'group' => 'mikro',
            'key' => 'mikro.api_key',
            'value' => encrypt('persist-me'),
            'is_encrypted' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'spaces' => [
                    'disk' => 'local',
                ],
                'mikro' => [
                    'enabled' => '1',
                    'base_url' => 'https://mikro.example.test',
                    'api_key' => '',
                    'username' => '',
                    'password' => '',
                    'company_code' => 'LDR',
                    'work_year' => '2026',
                    'timeout' => 20,
                    'verify_ssl' => '1',
                    'shipment_endpoint' => '/api/dispatch-status',
                    'sync_interval_minutes' => 60,
                ],
            ])
            ->assertRedirect();

        $setting = SystemSetting::query()->where('key', 'mikro.api_key')->firstOrFail();

        $this->assertTrue($setting->is_encrypted);
        $this->assertSame('persist-me', decrypt($setting->value));
        $this->assertDatabaseHas('system_settings', [
            'key' => 'mikro.company_code',
            'value' => 'LDR',
        ]);
        $this->assertDatabaseMissing('system_settings', [
            'key' => 'mikro.use_direct_db',
        ]);
    }

    public function test_supplier_can_have_multiple_mikro_accounts(): void
    {
        $supplier = Supplier::factory()->create();

        SupplierMikroAccount::query()->create([
            'supplier_id' => $supplier->id,
            'mikro_cari_kod' => '120.01.001',
            'mikro_company_code' => 'LDR',
            'mikro_work_year' => '2026',
            'is_active' => true,
        ]);

        SupplierMikroAccount::query()->create([
            'supplier_id' => $supplier->id,
            'mikro_cari_kod' => '120.01.002',
            'mikro_company_code' => 'LDR',
            'mikro_work_year' => '2026',
            'is_active' => true,
        ]);

        $supplier->refresh();

        $this->assertCount(2, $supplier->mikroAccounts);
    }

    public function test_admin_can_queue_supplier_specific_sync(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.suppliers.sync', $supplier))
            ->assertRedirect();

        Queue::assertPushed(SyncSupplierOrdersJob::class, fn (SyncSupplierOrdersJob $job) => $job->supplierId === $supplier->id);
    }

    public function test_non_admin_cannot_queue_supplier_specific_sync(): void
    {
        Queue::fake();

        $user = User::factory()->purchasing()->create();
        $supplier = Supplier::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.suppliers.sync', $supplier))
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_admin_can_queue_sync_for_all_active_suppliers(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.erp.sync'))
            ->assertRedirect();

        Queue::assertPushed(SyncAllActiveSuppliersJob::class);
    }

    public function test_supplier_sync_is_idempotent_and_updates_existing_lines(): void
    {
        config()->set('mikro.enabled', true);
        config()->set('mikro.base_url', 'https://mikro.example.test');

        User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();
        SupplierMikroAccount::query()->create([
            'supplier_id' => $supplier->id,
            'mikro_cari_kod' => '120.01.001',
            'mikro_company_code' => 'LDR',
            'mikro_work_year' => '2026',
            'is_active' => true,
        ]);

        Http::fake([
            'https://mikro.example.test/api/purchase-orders*' => Http::sequence()
                ->push([
                    'data' => [[
                        'order_no' => 'PO-MIKRO-001',
                        'supplier_code' => '120.01.001',
                        'supplier_name' => 'Supplier A',
                        'status' => 'active',
                        'order_date' => '2026-03-25',
                        'due_date' => '2026-04-05',
                        'shipment_status' => 'dispatched',
                        'shipment_reference' => 'IRS-001',
                        'lines' => [[
                            'line_no' => '10',
                            'stock_code' => 'PRD-01',
                            'stock_name' => 'Ilk aciklama',
                            'order_qty' => 100,
                            'shipped_qty' => 40,
                            'unit' => 'adet',
                        ]],
                    ]],
                ], 200)
                ->push([
                    'data' => [[
                        'order_no' => 'PO-MIKRO-001',
                        'supplier_code' => '120.01.001',
                        'supplier_name' => 'Supplier A',
                        'status' => 'active',
                        'order_date' => '2026-03-25',
                        'due_date' => '2026-04-05',
                        'shipment_status' => 'delivered',
                        'shipment_reference' => 'IRS-001',
                        'lines' => [[
                            'line_no' => '10',
                            'stock_code' => 'PRD-01',
                            'stock_name' => 'Guncel aciklama',
                            'order_qty' => 100,
                            'shipped_quantity' => 100,
                            'unit' => 'adet',
                        ]],
                    ]],
                ], 200),
        ]);

        $service = app(MikroOrderService::class);

        $service->syncSupplier($supplier->id);
        $service->syncSupplier($supplier->id);

        $this->assertDatabaseCount('purchase_orders', 1);
        $this->assertDatabaseCount('purchase_order_lines', 1);
        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $supplier->id,
            'order_no' => 'PO-MIKRO-001',
            'shipment_status' => 'delivered',
            'erp_source' => 'mikro',
        ]);
        $this->assertDatabaseHas('purchase_order_lines', [
            'line_no' => '10',
            'product_code' => 'PRD-01',
            'description' => 'Guncel aciklama',
            'shipped_quantity' => 100,
        ]);
        $this->assertDatabaseHas('supplier_mikro_accounts', [
            'supplier_id' => $supplier->id,
            'mikro_cari_kod' => '120.01.001',
            'last_sync_status' => ErpSyncStatus::SUCCESS->value,
        ]);
    }

    public function test_supplier_sync_preserves_null_shipped_quantity_when_data_is_unknown(): void
    {
        config()->set('mikro.enabled', true);
        config()->set('mikro.base_url', 'https://mikro.example.test');

        User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();
        SupplierMikroAccount::query()->create([
            'supplier_id' => $supplier->id,
            'mikro_cari_kod' => '120.01.001',
            'is_active' => true,
        ]);

        Http::fake([
            'https://mikro.example.test/api/purchase-orders*' => Http::response([
                'data' => [[
                    'order_no' => 'PO-MIKRO-NULL',
                    'supplier_code' => '120.01.001',
                    'order_date' => '2026-03-25',
                    'lines' => [[
                        'line_no' => '10',
                        'stock_code' => 'PRD-01',
                        'stock_name' => 'No shipment yet',
                        'order_qty' => 50,
                        'shipped_quantity' => null,
                    ]],
                ]],
            ], 200),
        ]);

        app(MikroOrderService::class)->syncSupplier($supplier->id);

        $this->assertDatabaseHas('purchase_order_lines', [
            'line_no' => '10',
            'shipped_quantity' => null,
        ]);
    }

    public function test_supplier_sync_marks_error_summary_when_mikro_request_fails(): void
    {
        config()->set('mikro.enabled', true);
        config()->set('mikro.base_url', 'https://mikro.example.test');

        User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();
        $account = SupplierMikroAccount::query()->create([
            'supplier_id' => $supplier->id,
            'mikro_cari_kod' => '120.01.009',
            'is_active' => true,
        ]);

        Http::fake([
            'https://mikro.example.test/api/purchase-orders*' => Http::response(['message' => 'boom'], 500),
        ]);

        $service = app(MikroOrderService::class);
        $result = $service->syncSupplier($supplier->id);

        $this->assertSame(1, $result['failed']);
        $this->assertDatabaseHas('supplier_mikro_accounts', [
            'id' => $account->id,
            'last_sync_status' => ErpSyncStatus::FAILED->value,
        ]);

        $account->refresh();
        $this->assertNotNull($account->last_sync_error);
    }

    public function test_same_order_number_can_exist_for_different_suppliers(): void
    {
        config()->set('mikro.enabled', true);
        config()->set('mikro.base_url', 'https://mikro.example.test');

        User::factory()->admin()->create();
        $supplierA = Supplier::factory()->create();
        $supplierB = Supplier::factory()->create();

        PurchaseOrder::factory()->create([
            'supplier_id' => $supplierA->id,
            'order_no' => 'PO-SHARED-001',
        ]);

        SupplierMikroAccount::query()->create([
            'supplier_id' => $supplierB->id,
            'mikro_cari_kod' => '120.02.001',
            'is_active' => true,
        ]);

        Http::fake([
            'https://mikro.example.test/api/purchase-orders*' => Http::response([
                'data' => [[
                    'order_no' => 'PO-SHARED-001',
                    'supplier_code' => '120.02.001',
                    'supplier_name' => 'Supplier B',
                    'status' => 'active',
                    'order_date' => '2026-03-25',
                    'lines' => [[
                        'line_no' => '10',
                        'stock_code' => 'PRD-01',
                        'stock_name' => 'Shared order',
                        'order_qty' => 10,
                    ]],
                ]],
            ], 200),
        ]);

        $service = app(MikroOrderService::class);
        $result = $service->syncSupplier($supplierB->id);

        $this->assertSame(0, $result['conflicts']);
        $this->assertDatabaseCount('purchase_orders', 2);
        $this->assertDatabaseHas('purchase_orders', [
            'order_no' => 'PO-SHARED-001',
            'supplier_id' => $supplierA->id,
        ]);
        $this->assertDatabaseHas('purchase_orders', [
            'order_no' => 'PO-SHARED-001',
            'supplier_id' => $supplierB->id,
        ]);
    }

    public function test_supplier_sync_classifies_payload_mismatch_without_reassigning_orders(): void
    {
        config()->set('mikro.enabled', true);
        config()->set('mikro.base_url', 'https://mikro.example.test');

        User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();

        SupplierMikroAccount::query()->create([
            'supplier_id' => $supplier->id,
            'mikro_cari_kod' => '120.02.001',
            'is_active' => true,
        ]);

        Http::fake([
            'https://mikro.example.test/api/purchase-orders*' => Http::response([
                'data' => [[
                    'order_no' => 'PO-MISMATCH-001',
                    'supplier_code' => '999.99.999',
                    'order_date' => '2026-03-25',
                    'lines' => [[
                        'line_no' => '10',
                        'stock_code' => 'PRD-01',
                        'stock_name' => 'Mismatch',
                        'order_qty' => 10,
                    ]],
                ]],
            ], 200),
        ]);

        $result = app(MikroOrderService::class)->syncSupplier($supplier->id);

        $this->assertSame(1, $result['conflicts']);
        $this->assertContains('endpoint_payload_mismatch', $result['conflict_codes']);
        $this->assertDatabaseMissing('purchase_orders', [
            'order_no' => 'PO-MISMATCH-001',
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_supplier_sync_classifies_invalid_line_identity(): void
    {
        config()->set('mikro.enabled', true);
        config()->set('mikro.base_url', 'https://mikro.example.test');

        User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();

        SupplierMikroAccount::query()->create([
            'supplier_id' => $supplier->id,
            'mikro_cari_kod' => '120.03.001',
            'is_active' => true,
        ]);

        Http::fake([
            'https://mikro.example.test/api/purchase-orders*' => Http::response([
                'data' => [[
                    'order_no' => 'PO-BAD-LINE-001',
                    'supplier_code' => '120.03.001',
                    'order_date' => '2026-03-25',
                    'lines' => [[
                        'line_no' => '',
                        'stock_code' => 'PRD-01',
                        'stock_name' => 'Bad line',
                        'order_qty' => 10,
                    ]],
                ]],
            ], 200),
        ]);

        $result = app(MikroOrderService::class)->syncSupplier($supplier->id);

        $this->assertSame(1, $result['conflicts']);
        $this->assertContains('invalid_line_identity', $result['conflict_codes']);
        $this->assertDatabaseMissing('purchase_orders', [
            'order_no' => 'PO-BAD-LINE-001',
            'supplier_id' => $supplier->id,
        ]);
    }
}
