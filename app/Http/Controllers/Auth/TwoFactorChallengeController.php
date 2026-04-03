<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\LoginTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private LoginTwoFactorService $twoFactor,
        private AuditLogService $audit
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $challenge = $this->twoFactor->challengeViewData($request);

        if (! $challenge) {
            return redirect()->route('login')->withErrors([
                'email' => 'Doğrulama oturumu bulunamadı. Lütfen tekrar giriş yapın.',
            ]);
        }

        return view('auth.two-factor-challenge', $challenge);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        try {
            $result = $this->twoFactor->verify($request, $validated['code']);
            $user = $result['user'];

            Auth::login($user, (bool) $result['remember']);

            $request->session()->regenerate();
            $user->update(['last_login_at' => now()]);
            $this->audit->logLogin($user->id);

            return redirect()->intended(
                $user->isSupplier() ? route('portal.orders.index') : route('dashboard')
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }
    }

    public function resend(Request $request): RedirectResponse
    {
        try {
            $this->twoFactor->resend($request);

            return back()->with('status', 'Yeni doğrulama kodu gönderildi.');
        } catch (RuntimeException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }
    }
}
