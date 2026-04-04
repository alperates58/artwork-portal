<?php

namespace Tests\Feature;

use App\Jobs\SendSupplierRegistrationMailJob;
use App\Models\SupplierRegistration;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SupplierRegistrationMailTemplateTest extends TestCase
{
    use DatabaseMigrations;

    public function test_admin_can_persist_supplier_registration_mail_templates(): void
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
                    'password' => 'portal-password',
                    'encryption' => 'tls',
                    'from_address' => 'portal@example.com',
                    'from_name' => 'Lider Portal',
                ],
                'mail_notifications' => [
                    'enabled' => '1',
                    'graphics_to' => 'graphics@example.com',
                    'graphics_cc' => '',
                    'graphics_bcc' => '',
                    'new_order_subject' => 'Yeni sipariş geldi: {order_no}',
                    'override_from_name' => 'Lider Portal',
                    'override_from_address' => 'portal@example.com',
                    'test_recipient' => 'test@example.com',
                ],
                'supplier_registration_mail' => [
                    'events' => [
                        'submitted' => [
                            'enabled' => '1',
                            'subject' => 'Kayit alindi - {{firma_adi}}',
                            'body' => "Merhaba {{kayit_user}}\nKaydiniz alindi.",
                        ],
                        'approved' => [
                            'enabled' => '1',
                            'subject' => 'Onaylandi - {{firma_adi}}',
                            'body' => "Merhaba {{kayit_user}}\nGiris: {{login_url}}",
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'mail']));

        $this->assertDatabaseHas('system_settings', [
            'key' => 'mail_notifications.supplier_registration_submitted_subject',
            'value' => 'Kayit alindi - {{firma_adi}}',
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'mail_notifications.supplier_registration_approved_body',
            'value' => "Merhaba {{kayit_user}}\nGiris: {{login_url}}",
        ]);

        $response = $this->actingAs($admin)->get(route('admin.settings.edit', ['tab' => 'mail']));

        $response->assertOk();
        $response->assertSee('Tedarikçi Kayıt Mailleri', false);
        $response->assertSee('Kayit alindi - {{firma_adi}}', false);
        $response->assertSee('Merhaba {{kayit_user}}', false);
    }

    public function test_supplier_registration_submission_queues_submitted_mail_when_enabled(): void
    {
        Queue::fake();

        $this->seedMailServerSettings();
        $this->seedSupplierRegistrationMailSettings('submitted', true);

        $this->postJson(route('supplier-registration.store'), [
            'company_name' => 'Alper Ticaret',
            'company_email' => 'kayit@example.com',
            'contact_name' => 'Alper ATEŞ',
            'phone' => '05076012199',
            'notes' => 'Test kayıt',
            'website' => '',
        ])->assertOk();

        Queue::assertPushed(
            SendSupplierRegistrationMailJob::class,
            fn (SendSupplierRegistrationMailJob $job) => $job->event === 'submitted'
                && $job->source === 'supplier_registration_form'
                && $job->manual === false
        );
    }

    public function test_supplier_registration_approval_queues_approved_mail_when_enabled(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $this->seedMailServerSettings();
        $this->seedSupplierRegistrationMailSettings('approved', true);

        $registration = SupplierRegistration::query()->create([
            'company_name' => 'Alper Ticaret',
            'company_email' => 'onay@example.com',
            'contact_name' => 'Alper ATEŞ',
            'phone' => '05076012199',
            'notes' => 'Test kayıt',
            'status' => 'pending',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.supplier-registrations.approve', $registration), [
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertRedirect(route('admin.supplier-registrations.index'));

        Queue::assertPushed(
            SendSupplierRegistrationMailJob::class,
            fn (SendSupplierRegistrationMailJob $job) => $job->event === 'approved'
                && $job->source === 'supplier_registration_approval'
                && $job->manual === false
        );
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

    private function seedSupplierRegistrationMailSettings(string $event, bool $enabled): void
    {
        foreach ([
            "mail_notifications.supplier_registration_{$event}_enabled" => $enabled ? '1' : '0',
            "mail_notifications.supplier_registration_{$event}_subject" => 'Konu {{firma_adi}}',
            "mail_notifications.supplier_registration_{$event}_body" => "Merhaba {{kayit_user}}\nPortal: {{portal_adi}}",
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
}
