<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\LoginTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private AuditLogService $audit,
        private LoginTwoFactorService $twoFactor
    ) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Auth::validate($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'E-posta veya şifre hatalı.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Hesabınız pasif durumda. Yönetici ile iletişime geçin.',
            ]);
        }

        if ($this->twoFactor->requiresChallenge($user)) {
            $this->twoFactor->clearChallenge($request);
            $request->session()->regenerate();

            try {
                $this->twoFactor->issueChallenge($request, $user, $request->boolean('remember'));
            } catch (\RuntimeException $exception) {
                throw ValidationException::withMessages([
                    'email' => $exception->getMessage(),
                ]);
            }

            return redirect()->route('login.two-factor.show')->with(
                'status',
                'Doğrulama kodu e-posta adresinize gönderildi.'
            );
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);
        $this->audit->logLogin($user->id);

        return redirect()->intended(
            $user->isSupplier() ? route('portal.orders.index') : route('dashboard')
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->audit->logLogout($userId);

        return redirect()->route('login');
    }
}
