<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Supplier;
use App\Models\SupplierMikroAccount;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_settings_page_does_not_render_mikro_secrets(): void
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
        $response->assertSee('Kayitli anahtar var');
        $response->assertSee('Kayitli kullanici var');
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
                    'use_direct_db' => '0',
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
}
