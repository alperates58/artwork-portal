<?php

namespace App\Services;

use App\Jobs\SendMailNotificationTestJob;
use App\Jobs\SendNewOrderNotificationJob;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Log;

class MailNotificationDispatcher
{
    public function __construct(
        private MailNotificationService $notifications,
        private AuditLogService $audit
    ) {}

    public function queueNewOrderNotification(PurchaseOrder $order, string $source = 'mikro'): void
    {
        if (! $this->notifications->isEnabled()) {
            $this->audit->log('mail.notification.skipped', $order, [
                'reason' => 'disabled',
                'type' => 'new_order',
                'source' => $source,
            ]);

            return;
        }

        if (! $this->notifications->hasNewOrderRecipients()) {
            $this->audit->log('mail.notification.skipped', $order, [
                'reason' => 'missing_recipient',
                'type' => 'new_order',
                'source' => $source,
            ]);

            return;
        }

        try {
            SendNewOrderNotificationJob::dispatch($order->id, $source);

            $this->audit->log('mail.notification.queued', $order, [
                'type' => 'new_order',
                'source' => $source,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Failed to queue new order notification', [
                'order_id' => $order->id,
                'source' => $source,
                'error' => $exception->getMessage(),
            ]);

            $this->audit->log('mail.notification.queue_failed', $order, [
                'type' => 'new_order',
                'source' => $source,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function queueTestNotification(?string $fallbackRecipient = null, ?int $actorId = null): bool
    {
        $recipient = $this->notifications->testRecipient($fallbackRecipient);

        if (! $recipient) {
            return false;
        }

        try {
            SendMailNotificationTestJob::dispatch($recipient, $actorId);
            $this->audit->log('mail.notification.test.queued', null, [
                'recipient' => $recipient,
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('Failed to queue test mail notification', [
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);

            $this->audit->log('mail.notification.queue_failed', null, [
                'type' => 'test',
                'recipient' => $recipient,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
