<?php

namespace App\Services;

use App\Jobs\SendSupplierRegistrationMailJob;
use App\Models\SupplierRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SupplierRegistrationMailDispatcher
{
    public function __construct(
        private PortalSettings $settings,
        private SupplierRegistrationMailService $templates,
        private AuditLogService $audit,
    ) {}

    public function queueSubmittedMail(SupplierRegistration $registration): bool
    {
        return $this->queue(
            event: 'submitted',
            registration: $registration,
            user: null,
            source: 'supplier_registration_form',
            manual: false,
        );
    }

    public function queueApprovedMail(SupplierRegistration $registration, User $user): bool
    {
        return $this->queue(
            event: 'approved',
            registration: $registration,
            user: $user,
            source: 'supplier_registration_approval',
            manual: false,
        );
    }

    public function queueApprovedMailManually(SupplierRegistration $registration, User $user): bool
    {
        return $this->queue(
            event: 'approved',
            registration: $registration,
            user: $user,
            source: 'supplier_registration_manual',
            manual: true,
        );
    }

    private function queue(
        string $event,
        SupplierRegistration $registration,
        ?User $user,
        string $source,
        bool $manual,
    ): bool {
        if (! $this->settings->hasUsableMailConfiguration()) {
            $this->audit->log('mail.notification.skipped', $registration, [
                'type' => 'supplier_registration_' . $event,
                'reason' => 'mail_unconfigured',
                'source' => $source,
                'manual' => $manual,
            ]);

            return false;
        }

        if (! $manual && ! $this->templates->eventIsEnabled($event)) {
            $this->audit->log('mail.notification.skipped', $registration, [
                'type' => 'supplier_registration_' . $event,
                'reason' => 'event_disabled',
                'source' => $source,
                'manual' => false,
            ]);

            return false;
        }

        $recipient = $this->templates->recipientFor($registration, $user);

        if (! $recipient) {
            $this->audit->log('mail.notification.skipped', $registration, [
                'type' => 'supplier_registration_' . $event,
                'reason' => 'missing_recipient',
                'source' => $source,
                'manual' => $manual,
            ]);

            return false;
        }

        try {
            SendSupplierRegistrationMailJob::dispatch(
                registrationId: $registration->id,
                event: $event,
                userId: $user?->id,
                source: $source,
                manual: $manual,
            );

            $this->audit->log('mail.notification.queued', $registration, [
                'type' => 'supplier_registration_' . $event,
                'source' => $source,
                'manual' => $manual,
                'recipient' => $recipient,
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('Failed to queue supplier registration mail', [
                'registration_id' => $registration->id,
                'event' => $event,
                'source' => $source,
                'manual' => $manual,
                'error' => $exception->getMessage(),
            ]);

            $this->audit->log('mail.notification.queue_failed', $registration, [
                'type' => 'supplier_registration_' . $event,
                'source' => $source,
                'manual' => $manual,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
