<?php

namespace App\Jobs;

use App\Mail\TestMailNotificationMail;
use App\Services\AuditLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendMailNotificationTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        public readonly string $recipient,
        public readonly ?int $actorId = null
    ) {}

    public function handle(AuditLogService $audit): void
    {
        try {
            Mail::to($this->recipient)->send(new TestMailNotificationMail());

            $audit->log('mail.notification.test.sent', null, [
                'recipient' => $this->recipient,
                'actor_id' => $this->actorId,
            ]);
        } catch (\Throwable $exception) {
            $audit->log('mail.notification.failed', null, [
                'type' => 'test',
                'recipient' => $this->recipient,
                'actor_id' => $this->actorId,
                'message' => $exception->getMessage(),
            ]);

            Log::error('Test mail notification send failed', [
                'recipient' => $this->recipient,
                'actor_id' => $this->actorId,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
