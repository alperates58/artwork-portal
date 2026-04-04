<?php

namespace App\Jobs;

use App\Mail\SupplierRegistrationTemplateMail;
use App\Models\SupplierRegistration;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SupplierRegistrationMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSupplierRegistrationMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly int $registrationId,
        public readonly string $event,
        public readonly ?int $userId = null,
        public readonly string $source = 'supplier_registration',
        public readonly bool $manual = false,
    ) {}

    public function handle(SupplierRegistrationMailService $templates, AuditLogService $audit): void
    {
        $registration = SupplierRegistration::query()
            ->with(['reviewedBy:id,name'])
            ->find($this->registrationId);

        if (! $registration) {
            Log::warning('Supplier registration mail skipped because registration no longer exists', [
                'registration_id' => $this->registrationId,
                'event' => $this->event,
                'source' => $this->source,
                'manual' => $this->manual,
            ]);

            return;
        }

        $user = $this->userId
            ? User::query()->find($this->userId)
            : null;

        $recipient = $templates->recipientFor($registration, $user);

        if (! $recipient) {
            $audit->log('mail.notification.skipped', $registration, [
                'type' => 'supplier_registration_' . $this->event,
                'reason' => 'missing_recipient',
                'source' => $this->source,
                'manual' => $this->manual,
            ]);

            return;
        }

        $subject = $templates->subjectFor($this->event, $registration, $user);
        $bodyHtml = $templates->bodyHtmlFor($this->event, $registration, $user);

        try {
            Mail::to($recipient)->send(
                new SupplierRegistrationTemplateMail(
                    subjectLine: $subject,
                    bodyHtml: $bodyHtml,
                )
            );

            $audit->log('mail.notification.sent', $registration, [
                'type' => 'supplier_registration_' . $this->event,
                'source' => $this->source,
                'manual' => $this->manual,
                'recipient' => $recipient,
            ]);
        } catch (\Throwable $exception) {
            $audit->log('mail.notification.failed', $registration, [
                'type' => 'supplier_registration_' . $this->event,
                'source' => $this->source,
                'manual' => $this->manual,
                'message' => $exception->getMessage(),
            ]);

            Log::error('Supplier registration mail send failed', [
                'registration_id' => $registration->id,
                'event' => $this->event,
                'source' => $this->source,
                'manual' => $this->manual,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function tags(): array
    {
        return [
            'mail-notification',
            'supplier-registration',
            'event:' . $this->event,
            'registration:' . $this->registrationId,
        ];
    }
}
