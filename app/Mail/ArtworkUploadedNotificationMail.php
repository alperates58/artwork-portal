<?php

namespace App\Mail;

use App\Models\ArtworkRevision;
use App\Services\MailNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArtworkUploadedNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ArtworkRevision $revision,
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
        $line = $this->revision->artwork->orderLine;
        $order = $line->purchaseOrder;

        return new Content(
            view: 'emails.artworks.artwork-uploaded-notification',
            with: [
                'revision' => $this->revision,
                'line' => $line,
                'order' => $order,
                'supplier' => $order->supplier,
                'detailUrl' => route('order-lines.show', $line),
            ]
        );
    }
}
