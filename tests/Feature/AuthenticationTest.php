<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\SendLoginTwoFactorCodeJob;
use App\Models\Supplier;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private static int $requestCounter = 0;

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_users_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);

        $this->postLogin([
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_login_requires_two_factor_when_enabled(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create([
            'is_active' => true,
            'email' => 'admin@example.com',
        ]);

        $this->enableAdminTwoFactor();
        $this->seedMailServerSettings();

        $this->postLogin([
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('login.two-factor.show'));

        $this->assertGuest();
        Queue::assertPushed(SendLoginTwoFactorCodeJob::class, 1);
    }

    public function test_admin_can_complete_two_factor_login_with_valid_code(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create([
            'is_active' => true,
            'email' => 'admin@example.com',
        ]);

        $this->enableAdminTwoFactor();
        $this->seedMailServerSettings();

        $this->postLogin([
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('login.two-factor.show'));

        $job = null;

        Queue::assertPushed(SendLoginTwoFactorCodeJob::class, function (SendLoginTwoFactorCodeJob $queuedJob) use (&$job): bool {
            $job = $queuedJob;

            return true;
        });

        $this->assertNotNull($job);

        $this->postTwoFactor(route('login.two-factor.verify'), [
            'code' => Crypt::decryptString($job->encryptedCode),
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'user.2fa.verified',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'user.login',
        ]);
    }

    public function test_admin_cannot_complete_two_factor_login_with_wrong_code(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create([
            'is_active' => true,
            'email' => 'admin@example.com',
        ]);

        $this->enableAdminTwoFactor();
        $this->seedMailServerSettings();

        $this->postLogin([
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('login.two-factor.show'));

        $this->postTwoFactor(route('login.two-factor.verify'), [
            'code' => '111111',
        ])->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'user.2fa.failed',
        ]);
    }

    public function test_non_admin_users_skip_admin_two_factor_requirement(): void
    {
        Queue::fake();

        $user = User::factory()->graphic()->create([
            'is_active' => true,
        ]);

        $this->enableAdminTwoFactor();
        $this->seedMailServerSettings();

        $this->postLogin([
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        Queue::assertNothingPushed();
    }

    public function test_inactive_users_cannot_login(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->postLogin([
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_users_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postLogin([
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_supplier_redirected_to_portal_after_login(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
            'is_active' => true,
        ]);

        $this->postLogin([
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('portal.orders.index'));
    }

    public function test_internal_user_redirected_to_dashboard_after_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::GRAPHIC,
            'is_active' => true,
        ]);

        $this->postLogin([
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    private function enableAdminTwoFactor(): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => 'portal.require_2fa_for_admin'],
            [
                'group' => 'portal',
                'value' => '1',
                'is_encrypted' => false,
            ]
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

    private function postLogin(array $payload)
    {
        return $this->withUniqueIp()->post('/login', $payload);
    }

    private function postTwoFactor(string $uri, array $payload)
    {
        return $this->withUniqueIp()->post($uri, $payload);
    }

    private function withUniqueIp(): self
    {
        self::$requestCounter++;

        return $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.' . ((self::$requestCounter % 200) + 1),
        ]);
    }
}
