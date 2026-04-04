<?php

namespace App\Mail;

use App\Models\SupplierRegistration;
use App\Models\User;
use App\Services\MailNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupplierWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly SupplierRegistration $registration,
    ) {}

    public function envelope(): Envelope
    {
        $from = app(MailNotificationService::class)->fromOverride();

        return new Envelope(
            from: $from ? new Address($from['address'], $from['name']) : null,
            subject: config('portal.brand_name') . ' - Hoşgeldiniz!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.supplier-registration.welcome',
            with: [
                'user'         => $this->user,
                'registration' => $this->registration,
                'loginUrl'     => route('login'),
                'brandName'    => config('portal.brand_name'),
            ]
        );
    }
}
