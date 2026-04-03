<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_returns_generic_success_for_unknown_email(): void
    {
        $this->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => 'olmayan-kullanici@example.com',
            ])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'Eğer bu e-posta adresi sistemde kayıtlıysa, şifre sıfırlama bağlantısı gönderildi.')
            ->assertSessionMissing('errors');
    }

    public function test_password_reset_revokes_existing_api_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-user@example.com',
        ]);

        $user->createToken('mobile-client');
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'yeni-guclu-sifre',
            'password_confirmation' => 'yeni-guclu-sifre',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
