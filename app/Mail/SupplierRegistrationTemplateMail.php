<?php

namespace App\Mail;

use App\Services\MailNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupplierRegistrationTemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $subjectLine,
        public readonly string $bodyHtml,
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
            view: 'emails.supplier-registration.template',
            with: [
                'bodyHtml' => $this->bodyHtml,
                'brandName' => config('portal.brand_name'),
            ]
        );
    }
}
