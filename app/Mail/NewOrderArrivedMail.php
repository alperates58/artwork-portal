<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use App\Services\MailNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderArrivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly PurchaseOrder $order,
        public readonly string $subjectLine
    ) {}

    public function envelope(): Envelope
    {
        $from = app(MailNotificationService::class)->fromOverride();

        return new Envelope(
            from: $from ? new Address($from['address'], $from['name']) : null,
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.new-order-arrived',
            with: [
                'order' => $this->order,
                'supplier' => $this->order->supplier,
                'lineCount' => $this->order->lines_count ?? $this->order->lines()->count(),
                'orderUrl' => route('orders.show', $this->order),
            ]
        );
    }
}
