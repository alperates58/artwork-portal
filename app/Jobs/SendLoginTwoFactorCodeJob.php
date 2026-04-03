<?php

namespace App\Jobs;

use App\Mail\LoginTwoFactorCodeMail;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLoginTwoFactorCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly int $userId,
        public readonly string $recipient,
        public readonly string $encryptedCode,
        public readonly Carbon $expiresAt
    ) {}

    public function handle(AuditLogService $audit): void
    {
        try {
            Mail::to($this->recipient)->send(
                new LoginTwoFactorCodeMail(
                    Crypt::decryptString($this->encryptedCode),
                    $this->expiresAt
                )
            );

            $audit->logForUser($this->userId, 'user.2fa.challenge.sent', null, [
                'recipient' => $this->recipient,
                'code_expires_at' => $this->expiresAt->timezone(config('app.timezone', 'Europe/Istanbul'))->format('d.m.Y H:i'),
            ]);
        } catch (\Throwable $exception) {
            $audit->logForUser($this->userId, 'user.2fa.challenge.failed', null, [
                'recipient' => $this->recipient,
                'message' => $exception->getMessage(),
            ]);

            Log::error('Login two-factor mail send failed', [
                'user_id' => $this->userId,
                'recipient' => $this->recipient,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
