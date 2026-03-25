<?php

namespace App\Jobs;

use App\Mail\NewOrderArrivedMail;
use App\Models\PurchaseOrder;
use App\Services\AuditLogService;
use App\Services\MailNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewOrderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly int $orderId,
        public readonly string $source = 'mikro'
    ) {}

    public function handle(MailNotificationService $notifications, AuditLogService $audit): void
    {
        $order = PurchaseOrder::query()
            ->with('supplier:id,name')
            ->withCount('lines')
            ->find($this->orderId);

        if (! $order) {
            Log::warning('New order notification skipped because order no longer exists', [
                'order_id' => $this->orderId,
                'source' => $this->source,
            ]);

            return;
        }

        $recipients = $notifications->newOrderRecipients();
        $subject = $notifications->newOrderSubject($order);

        try {
            Mail::to($recipients['to'])
                ->cc($recipients['cc'])
                ->bcc($recipients['bcc'])
                ->send(new NewOrderArrivedMail($order, $subject));

            $audit->log('mail.notification.sent', $order, [
                'type' => 'new_order',
                'source' => $this->source,
                'to_count' => count($recipients['to']),
                'cc_count' => count($recipients['cc']),
                'bcc_count' => count($recipients['bcc']),
            ]);
        } catch (\Throwable $exception) {
            $audit->log('mail.notification.failed', $order, [
                'type' => 'new_order',
                'source' => $this->source,
                'message' => $exception->getMessage(),
            ]);

            Log::error('New order notification send failed', [
                'order_id' => $order->id,
                'source' => $this->source,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function tags(): array
    {
        return ['mail-notification', 'new-order', 'source:' . $this->source, 'order:' . $this->orderId];
    }
}
