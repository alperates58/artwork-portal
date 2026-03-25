<?php

namespace App\Mail;

use App\Services\MailNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMailNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        $from = app(MailNotificationService::class)->fromOverride();

        return new Envelope(
            from: $from ? new Address($from['address'], $from['name']) : null,
            subject: 'Lider Portal mail testi',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.test-mail-notification',
            with: [
                'sentAt' => now()->timezone(config('app.timezone', 'Europe/Istanbul'))->format('d.m.Y H:i'),
                'mailer' => (string) config('mail.default'),
                'host' => (string) config('mail.mailers.smtp.host'),
                'port' => (string) config('mail.mailers.smtp.port'),
            ]
        );
    }
}
