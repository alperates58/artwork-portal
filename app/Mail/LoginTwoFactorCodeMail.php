<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginTwoFactorCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly Carbon $expiresAt
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Lider Portal giriş doğrulama kodu',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.two-factor-code',
            with: [
                'code' => $this->code,
                'expiresAt' => $this->expiresAt->timezone(config('app.timezone', 'Europe/Istanbul'))->format('d.m.Y H:i'),
            ],
        );
    }
}
