<?php

namespace Tests\Feature;

use App\Jobs\SendMailNotificationTestJob;
use App\Jobs\SendNewOrderNotificationJob;
use App\Models\Department;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierMikroAccount;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Erp\MikroOrderService;
use App\Services\MailNotificationService;
use App\Services\MailServerConnectionTester;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MailNotificationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_admin_can_persist_mail_notification_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'spaces' => [
                    'disk' => 'local',
                ],
                'mikro' => [
                    'enabled' => '0',
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
                'mail_notifications' => [
                    'enabled' => '1',
                    'graphics_to' => 'graphics@example.com,graphics2@example.com',
                    'graphics_cc' => 'purchasing@example.com',
                    'graphics_bcc' => 'audit@example.com',
                    'new_order_subject' => 'Yeni siparis geldi: {order_no}',
                    'override_from_name' => 'Lider Portal Bildirim',
                    'override_from_address' => 'portal@example.com',
                    'test_recipient' => 'test@example.com',
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('system_settings', [
            'key' => 'mail_notifications.graphics_to',
            'value' => 'graphics@example.com,graphics2@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertSee('graphics@example.com,graphics2@example.com', false);
        $response->assertSee('Yeni siparis geldi: {order_no}', false);
        $response->assertSee('test@example.com', false);
    }

    public function test_admin_can_persist_event_based_mail_notification_settings_with_departments(): void
    {
        $admin = User::factory()->admin()->create();
        $graphicsDepartment = Department::query()->updateOrCreate(['name' => 'Grafik Test'], ['permissions' => []]);
        $purchasingDepartment = Department::query()->updateOrCreate(['name' => 'Satın Alma Test'], ['permissions' => []]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update', ['tab' => 'mail']), [
                'tab' => 'mail',
                'settings_section' => 'mail',
                'mail_server' => [
                    'host' => 'smtp.example.com',
                    'port' => 587,
                    'username' => 'portal-user',
                    'password' => 'portal-password',
                    'encryption' => 'tls',
                    'from_address' => 'portal@example.com',
                    'from_name' => 'Lider Portal',
                ],
                'mail_notifications' => [
                    'enabled' => '1',
                    'events' => [
                        'new_order' => [
                            'enabled' => '1',
                            'department_ids' => [$graphicsDepartment->id, $purchasingDepartment->id],
                            'to' => 'graphics@example.com',
                            'cc' => 'planner@example.com',
                            'bcc' => 'audit@example.com',
                            'subject' => 'Yeni sipariş geldi: {order_no}',
                        ],
                        'artwork_uploaded' => [
                            'enabled' => '1',
                            'department_ids' => [$graphicsDepartment->id],
                            'to' => 'artwork@example.com',
                            'cc' => '',
                            'bcc' => 'archive@example.com',
                            'subject' => 'Yeni artwork yüklendi: {order_no} / {product_code}',
                        ],
                    ],
                    'override_from_name' => 'Lider Portal Bildirim',
                    'override_from_address' => 'portal@example.com',
                    'test_recipient' => 'test@example.com',
                ],
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'mail']));

        $this->assertDatabaseHas('system_settings', [
            'key' => 'mail_notifications.new_order_department_ids',
            'value' => json_encode([$graphicsDepartment->id, $purchasingDepartment->id]),
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'mail_notifications.artwork_uploaded_department_ids',
            'value' => json_encode([$graphicsDepartment->id]),
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'mail_notifications.artwork_uploaded_to',
            'value' => 'artwork@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.settings.edit', ['tab' => 'mail']));

        $response->assertOk();
        $response->assertSee('Artwork yüklendiğinde', false);
        $response->assertSee('Departman Alıcıları', false);
        $response->assertSee('Yeni artwork yüklendi: {order_no} / {product_code}', false);
    }

    public function test_mail_notification_service_includes_department_recipients_for_new_order_event(): void
    {
        $department = Department::query()->updateOrCreate(['name' => 'Grafik Mail Test'], ['permissions' => []]);
        User::factory()->graphic()->create([
            'department_id' => $department->id,
            'email' => 'graphic-user@example.com',
            'is_active' => true,
        ]);
        User::factory()->purchasing()->create([
            'department_id' => $department->id,
            'email' => 'planner@example.com',
            'is_active' => true,
        ]);

        SystemSetting::query()->updateOrCreate(
            ['key' => 'mail_notifications.enabled'],
            ['group' => 'mail_notifications', 'value' => '1', 'is_encrypted' => false]
        );
        SystemSetting::query()->updateOrCreate(
            ['key' => 'mail_notifications.new_order_department_ids'],
            ['group' => 'mail_notifications', 'value' => json_encode([$department->id]), 'is_encrypted' => false]
        );
        SystemSetting::query()->updateOrCreate(
            ['key' => 'mail_notifications.new_order_to'],
            ['group' => 'mail_notifications', 'value' => 'graphics@example.com', 'is_encrypted' => false]
        );

        $recipients = app(MailNotificationService::class)->newOrderRecipients();

        $this->assertEqualsCanonicalizing(
            ['graphic-user@example.com', 'planner@example.com', 'graphics@example.com'],
            $recipients['to']
        );
    }

    public function test_admin_can_queue_test_mail(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $this->seedMailSettings(testRecipient: 'stored-test@example.com');

        $this->actingAs($admin)
            ->post(route('admin.settings.mail-test'))
            ->assertRedirect();

        Queue::assertPushed(SendMailNotificationTestJob::class, fn (SendMailNotificationTestJob $job) => $job->recipient === 'stored-test@example.com');
    }

    public function test_admin_can_persist_mail_server_settings_without_rendering_secrets(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update', ['tab' => 'mail']), [
                'tab' => 'mail',
                'settings_section' => 'mail',
                'mail_server' => [
                    'host' => 'smtp.example.com',
                    'port' => 587,
                    'username' => 'portal-user',
                    'password' => 'super-secret-password',
                    'encryption' => 'tls',
                    'from_address' => 'portal@example.com',
                    'from_name' => 'Lider Portal',
                ],
                'mail_notifications' => [
                    'enabled' => '1',
                    'graphics_to' => 'graphics@example.com',
                    'graphics_cc' => '',
                    'graphics_bcc' => '',
                    'new_order_subject' => 'Yeni siparis geldi: {order_no}',
                    'override_from_name' => 'Lider Portal',
                    'override_from_address' => 'portal@example.com',
                    'test_recipient' => 'test@example.com',
                ],
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'mail']));

        $username = SystemSetting::query()->where('key', 'mail.username')->firstOrFail();
        $password = SystemSetting::query()->where('key', 'mail.password')->firstOrFail();

        $this->assertTrue($username->is_encrypted);
        $this->assertTrue($password->is_encrypted);
        $this->assertSame('portal-user', Crypt::decryptString($username->value));
        $this->assertSame('super-secret-password', Crypt::decryptString($password->value));

        $response = $this->actingAs($admin)->get(route('admin.settings.edit', ['tab' => 'mail']));

        $response->assertOk();
        $response->assertDontSee('portal-user');
        $response->assertDontSee('super-secret-password');
        $response->assertSee('Kayıtlı kullanıcı var', false);
        $response->assertSee('Kayıtlı şifre var', false);
        $response->assertSee('Mail Sunucusu');
    }

    public function test_admin_can_persist_office365_oauth_mail_settings_without_rendering_secret(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update', ['tab' => 'mail']), [
                'tab' => 'mail',
                'settings_section' => 'mail',
                'mail_server' => [
                    'provider' => 'office365_oauth',
                    'host' => 'smtp.office365.com',
                    'port' => 587,
                    'username' => '',
                    'password' => '',
                    'encryption' => 'tls',
                    'from_address' => 'portal@contoso.com',
                    'from_name' => 'Lider Portal',
                    'oauth_tenant_id' => 'tenant-id',
                    'oauth_client_id' => 'client-id',
                    'oauth_client_secret' => 'client-secret',
                    'oauth_sender' => 'portal@contoso.com',
                ],
                'mail_notifications' => [
                    'enabled' => '1',
                    'graphics_to' => 'graphics@example.com',
                    'graphics_cc' => '',
                    'graphics_bcc' => '',
                    'new_order_subject' => 'Yeni siparis geldi: {order_no}',
                    'override_from_name' => 'Lider Portal',
                    'override_from_address' => 'portal@contoso.com',
                    'test_recipient' => 'test@example.com',
                ],
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'mail']));

        $secret = SystemSetting::query()->where('key', 'mail.oauth_client_secret')->firstOrFail();

        $this->assertTrue($secret->is_encrypted);
        $this->assertSame('client-secret', Crypt::decryptString($secret->value));
        $this->assertDatabaseHas('system_settings', [
            'key' => 'mail.provider',
            'value' => 'office365_oauth',
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'mail.oauth_sender',
            'value' => 'portal@contoso.com',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.settings.edit', ['tab' => 'mail']));

        $response->assertOk();
        $response->assertDontSee('client-secret');
        $response->assertSee('Office 365 Modern Auth', false);
        $response->assertSee('Kayıtlı secret var', false);
    }

    public function test_blank_mail_password_preserves_existing_secret(): void
    {
        $admin = User::factory()->admin()->create();

        SystemSetting::query()->create([
            'group' => 'mail',
            'key' => 'mail.password',
            'value' => encrypt('persist-mail-password'),
            'is_encrypted' => true,
        ]);

        SystemSetting::query()->create([
            'group' => 'mail',
            'key' => 'mail.username',
            'value' => encrypt('persist-user'),
            'is_encrypted' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update', ['tab' => 'mail']), [
                'tab' => 'mail',
                'settings_section' => 'mail',
                'mail_server' => [
                    'host' => 'smtp.example.com',
                    'port' => 587,
                    'username' => '',
                    'password' => '',
                    'encryption' => 'tls',
                    'from_address' => 'portal@example.com',
                    'from_name' => 'Lider Portal',
                ],
                'mail_notifications' => [
                    'enabled' => '1',
                    'graphics_to' => 'graphics@example.com',
                    'graphics_cc' => '',
                    'graphics_bcc' => '',
                    'new_order_subject' => 'Yeni siparis geldi: {order_no}',
                    'override_from_name' => 'Lider Portal',
                    'override_from_address' => 'portal@example.com',
                    'test_recipient' => 'test@example.com',
                ],
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'mail']));

        $this->assertSame(
            'persist-mail-password',
            decrypt(SystemSetting::query()->where('key', 'mail.password')->value('value'))
        );
        $this->assertSame(
            'persist-user',
            decrypt(SystemSetting::query()->where('key', 'mail.username')->value('value'))
        );
    }

    public function test_mail_validation_redirects_back_to_same_tab(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.settings.edit', ['tab' => 'mail']))
            ->put(route('admin.settings.update', ['tab' => 'mail']), [
                'tab' => 'mail',
                'settings_section' => 'mail',
                'mail_server' => [
                    'host' => '',
                    'port' => 0,
                    'username' => '',
                    'password' => '',
                    'encryption' => 'tls',
                    'from_address' => 'not-an-email',
                    'from_name' => '',
                ],
                'mail_notifications' => [
                    'enabled' => '1',
                    'graphics_to' => 'bad-email',
                ],
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'mail']));
    }

    public function test_admin_can_run_mail_connection_test_and_stay_on_same_tab(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seedMailServerSettings();

        $tester = $this->mock(MailServerConnectionTester::class);
        $tester->shouldReceive('test')->once();

        $this->actingAs($admin)
            ->post(route('admin.settings.mail-connection-test', ['tab' => 'mail']), [
                'tab' => 'mail',
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'mail']));
    }

    public function test_non_admin_cannot_queue_test_mail(): void
    {
        Queue::fake();

        $user = User::factory()->purchasing()->create();

        $this->actingAs($user)
            ->post(route('admin.settings.mail-test'), [
                'test_mail_recipient' => 'blocked@example.com',
            ])
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_non_admin_cannot_run_mail_connection_test(): void
    {
        $user = User::factory()->purchasing()->create();

        $this->actingAs($user)
            ->post(route('admin.settings.mail-connection-test', ['tab' => 'mail']), [
                'tab' => 'mail',
            ])
            ->assertForbidden();
    }

    public function test_supplier_sync_queues_new_order_notification_only_once_for_new_mikro_order(): void
    {
        Queue::fake();

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

        $this->seedMailSettings(enabled: true, to: 'graphics@example.com');

        Http::fake([
            'https://mikro.example.test/api/purchase-orders*' => Http::sequence()
                ->push([
                    'data' => [[
                        'order_no' => 'PO-MAIL-001',
                        'supplier_code' => '120.01.001',
                        'supplier_name' => 'Supplier A',
                        'status' => 'active',
                        'order_date' => '2026-03-25',
                        'lines' => [[
                            'line_no' => '10',
                            'stock_code' => 'PRD-01',
                            'stock_name' => 'Ilk aciklama',
                            'order_qty' => 25,
                            'unit' => 'adet',
                        ]],
                    ]],
                ], 200)
                ->push([
                    'data' => [[
                        'order_no' => 'PO-MAIL-001',
                        'supplier_code' => '120.01.001',
                        'supplier_name' => 'Supplier A',
                        'status' => 'active',
                        'order_date' => '2026-03-25',
                        'lines' => [[
                            'line_no' => '10',
                            'stock_code' => 'PRD-01',
                            'stock_name' => 'Guncel aciklama',
                            'order_qty' => 25,
                            'unit' => 'adet',
                        ]],
                    ]],
                ], 200),
        ]);

        $service = app(MikroOrderService::class);
        $service->syncSupplier($supplier->id);
        $service->syncSupplier($supplier->id);

        Queue::assertPushed(SendNewOrderNotificationJob::class, 1);
    }

    public function test_supplier_sync_skips_notification_when_mail_notifications_disabled(): void
    {
        Queue::fake();

        config()->set('mikro.enabled', true);
        config()->set('mikro.base_url', 'https://mikro.example.test');

        User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();
        SupplierMikroAccount::query()->create([
            'supplier_id' => $supplier->id,
            'mikro_cari_kod' => '120.01.009',
            'is_active' => true,
        ]);

        $this->seedMailSettings(enabled: false, to: 'graphics@example.com');

        Http::fake([
            'https://mikro.example.test/api/purchase-orders*' => Http::response([
                'data' => [[
                    'order_no' => 'PO-MAIL-DISABLED',
                    'supplier_code' => '120.01.009',
                    'order_date' => '2026-03-25',
                    'lines' => [[
                        'line_no' => '10',
                        'stock_code' => 'PRD-09',
                        'stock_name' => 'No mail',
                        'order_qty' => 10,
                    ]],
                ]],
            ], 200),
        ]);

        app(MikroOrderService::class)->syncSupplier($supplier->id);

        Queue::assertNothingPushed();
        $this->assertDatabaseHas('purchase_orders', [
            'order_no' => 'PO-MAIL-DISABLED',
            'supplier_id' => $supplier->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'mail.notification.skipped',
            'model_type' => PurchaseOrder::class,
        ]);
    }

    public function test_new_order_notification_job_logs_failure_without_exposing_secrets(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Supplier A']);
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'order_no' => 'PO-MAIL-FAIL',
        ]);
        $order->lines()->create([
            'line_no' => '10',
            'product_code' => 'PRD-01',
            'description' => 'Line',
            'quantity' => 10,
            'unit' => 'adet',
            'artwork_status' => 'pending',
        ]);

        $this->seedMailSettings(enabled: true, to: 'graphics@example.com');

        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('cc')->once()->andReturnSelf();
        Mail::shouldReceive('bcc')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP unreachable'));

        try {
            (new SendNewOrderNotificationJob($order->id, 'mikro'))
                ->handle(app(MailNotificationService::class), app(AuditLogService::class));

            $this->fail('Job should throw when mail transport fails.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('SMTP unreachable', $exception->getMessage());
        }

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'mail.notification.failed',
            'model_type' => PurchaseOrder::class,
            'model_id' => $order->id,
        ]);

        $payload = \App\Models\AuditLog::query()
            ->where('action', 'mail.notification.failed')
            ->latest('created_at')
            ->value('payload');

        $this->assertStringNotContainsString('MAIL_PASSWORD', json_encode($payload));
    }

    private function seedMailSettings(
        bool $enabled = true,
        string $to = 'graphics@example.com',
        ?string $testRecipient = 'test@example.com'
    ): void {
        foreach ([
            'mail_notifications.enabled' => $enabled ? '1' : '0',
            'mail_notifications.graphics_to' => $to,
            'mail_notifications.graphics_cc' => '',
            'mail_notifications.graphics_bcc' => '',
            'mail_notifications.new_order_subject' => 'Yeni siparis geldi: {order_no}',
            'mail_notifications.override_from_name' => 'Lider Portal',
            'mail_notifications.override_from_address' => 'portal@example.com',
            'mail_notifications.test_recipient' => $testRecipient,
        ] as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => 'mail_notifications',
                    'value' => $value,
                    'is_encrypted' => false,
                ]
            );
        }
    }

    private function seedMailServerSettings(): void
    {
        foreach ([
            'mail.provider' => ['value' => 'smtp', 'encrypted' => false],
            'mail.host' => ['value' => 'smtp.example.com', 'encrypted' => false],
            'mail.port' => ['value' => '587', 'encrypted' => false],
            'mail.username' => ['value' => encrypt('portal-user'), 'encrypted' => true],
            'mail.password' => ['value' => encrypt('portal-secret'), 'encrypted' => true],
            'mail.encryption' => ['value' => 'tls', 'encrypted' => false],
            'mail.from_address' => ['value' => 'portal@example.com', 'encrypted' => false],
            'mail.from_name' => ['value' => 'Lider Portal', 'encrypted' => false],
        ] as $key => $setting) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => 'mail',
                    'value' => $setting['value'],
                    'is_encrypted' => $setting['encrypted'],
                ]
            );
        }
    }
}
