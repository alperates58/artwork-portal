<?php

namespace App\Services;

use App\Jobs\SendLoginTwoFactorCodeJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class LoginTwoFactorService
{
    private const SESSION_KEY = 'auth.two_factor.challenge_id';
    private const TTL_MINUTES = 10;
    private const RESEND_COOLDOWN_SECONDS = 45;
    private const MAX_ATTEMPTS = 5;
    private const MAX_RESENDS = 5;

    public function __construct(
        private PortalSettings $settings,
        private AuditLogService $audit
    ) {}

    public function requiresChallenge(User $user): bool
    {
        return (bool) ($this->settings->portalConfig()['require_2fa_for_admin'] ?? false)
            && $user->isAdmin();
    }

    public function issueChallenge(Request $request, User $user, bool $remember): void
    {
        if (! $this->settings->hasUsableMailConfiguration()) {
            throw new RuntimeException('İki adımlı doğrulama için mail ayarları henüz hazır değil.');
        }

        $challengeId = (string) Str::uuid();
        $code = $this->generateCode();
        $expiresAt = now()->addMinutes(self::TTL_MINUTES);

        $challenge = [
            'id' => $challengeId,
            'user_id' => $user->id,
            'email' => $user->email,
            'remember' => $remember,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'resend_count' => 0,
            'expires_at' => $expiresAt->toIso8601String(),
            'resend_available_at' => now()->addSeconds(self::RESEND_COOLDOWN_SECONDS)->toIso8601String(),
        ];

        Cache::put($this->cacheKey($challengeId), $challenge, $expiresAt->copy()->addMinute());
        $request->session()->put(self::SESSION_KEY, $challengeId);

        try {
            SendLoginTwoFactorCodeJob::dispatch(
                $user->id,
                $user->email,
                Crypt::encryptString($code),
                $expiresAt
            );

            $this->audit->logForUser($user->id, 'user.2fa.challenge.queued', null, [
                'recipient' => $user->email,
                'code_expires_at' => $expiresAt->timezone(config('app.timezone', 'Europe/Istanbul'))->format('d.m.Y H:i'),
            ]);
        } catch (\Throwable $exception) {
            $this->clearChallenge($request);
            throw new RuntimeException('Doğrulama kodu kuyruğa alınamadı.', previous: $exception);
        }
    }

    public function currentChallenge(Request $request): ?array
    {
        $challengeId = (string) $request->session()->get(self::SESSION_KEY, '');

        if ($challengeId === '') {
            return null;
        }

        $challenge = Cache::get($this->cacheKey($challengeId));

        if (! is_array($challenge)) {
            $this->clearChallenge($request);

            return null;
        }

        if (Carbon::parse((string) $challenge['expires_at'])->isPast()) {
            $this->clearChallenge($request);

            return null;
        }

        return $challenge;
    }

    public function challengeViewData(Request $request): ?array
    {
        $challenge = $this->currentChallenge($request);

        if (! $challenge) {
            return null;
        }

        return [
            'email_masked' => $this->maskEmail((string) $challenge['email']),
            'expires_at' => Carbon::parse((string) $challenge['expires_at'])->timezone(config('app.timezone', 'Europe/Istanbul')),
            'resend_available_at' => Carbon::parse((string) $challenge['resend_available_at'])->timezone(config('app.timezone', 'Europe/Istanbul')),
        ];
    }

    public function verify(Request $request, string $code): array
    {
        $challenge = $this->currentChallenge($request);

        if (! $challenge) {
            throw new RuntimeException('Doğrulama oturumu bulunamadı. Lütfen tekrar giriş yapın.');
        }

        $attempts = (int) ($challenge['attempts'] ?? 0);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->audit->logForUser((int) $challenge['user_id'], 'user.2fa.failed', null, [
                'reason' => 'max_attempts',
            ]);

            $this->clearChallenge($request);

            throw new RuntimeException('Doğrulama kodu için deneme hakkınız doldu. Lütfen tekrar giriş yapın.');
        }

        if (! Hash::check($code, (string) $challenge['code_hash'])) {
            $challenge['attempts'] = $attempts + 1;
            Cache::put($this->cacheKey((string) $challenge['id']), $challenge, Carbon::parse((string) $challenge['expires_at'])->addMinute());

            $this->audit->logForUser((int) $challenge['user_id'], 'user.2fa.failed', null, [
                'reason' => 'invalid_code',
                'attempt' => $challenge['attempts'],
            ]);

            throw new RuntimeException('Doğrulama kodu hatalı.');
        }

        $user = User::query()->find((int) $challenge['user_id']);

        if (! $user || ! $user->is_active) {
            $this->clearChallenge($request);

            throw new RuntimeException('Kullanıcı hesabı doğrulanamadı.');
        }

        $remember = (bool) ($challenge['remember'] ?? false);

        $this->clearChallenge($request);
        $this->audit->logForUser($user->id, 'user.2fa.verified');

        return [
            'user' => $user,
            'remember' => $remember,
        ];
    }

    public function resend(Request $request): int
    {
        $challenge = $this->currentChallenge($request);

        if (! $challenge) {
            throw new RuntimeException('Doğrulama oturumu bulunamadı. Lütfen tekrar giriş yapın.');
        }

        $availableAt = Carbon::parse((string) $challenge['resend_available_at']);

        if ($availableAt->isFuture()) {
            throw new RuntimeException('Yeni kod göndermek için biraz bekleyin.');
        }

        $resendCount = (int) ($challenge['resend_count'] ?? 0);

        if ($resendCount >= self::MAX_RESENDS) {
            $this->clearChallenge($request);

            throw new RuntimeException('Çok fazla yeniden gönderim denemesi yapıldı. Lütfen tekrar giriş yapın.');
        }

        $code = $this->generateCode();
        $expiresAt = now()->addMinutes(self::TTL_MINUTES);

        $challenge['code_hash'] = Hash::make($code);
        $challenge['attempts'] = 0;
        $challenge['resend_count'] = $resendCount + 1;
        $challenge['expires_at'] = $expiresAt->toIso8601String();
        $challenge['resend_available_at'] = now()->addSeconds(self::RESEND_COOLDOWN_SECONDS)->toIso8601String();

        Cache::put($this->cacheKey((string) $challenge['id']), $challenge, $expiresAt->copy()->addMinute());

        try {
            SendLoginTwoFactorCodeJob::dispatch(
                (int) $challenge['user_id'],
                (string) $challenge['email'],
                Crypt::encryptString($code),
                $expiresAt
            );

            $this->audit->logForUser((int) $challenge['user_id'], 'user.2fa.challenge.queued', null, [
                'recipient' => (string) $challenge['email'],
                'code_expires_at' => $expiresAt->timezone(config('app.timezone', 'Europe/Istanbul'))->format('d.m.Y H:i'),
            ]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Doğrulama kodu yeniden gönderilemedi.', previous: $exception);
        }

        return self::RESEND_COOLDOWN_SECONDS;
    }

    public function clearChallenge(Request $request): void
    {
        $challengeId = (string) $request->session()->pull(self::SESSION_KEY, '');

        if ($challengeId !== '') {
            Cache::forget($this->cacheKey($challengeId));
        }
    }

    private function cacheKey(string $challengeId): string
    {
        return 'login-two-factor:' . $challengeId;
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($domain === '') {
            return '***';
        }

        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible . str_repeat('*', max(3, mb_strlen($local) - mb_strlen($visible))) . '@' . $domain;
    }
}
