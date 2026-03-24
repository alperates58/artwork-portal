<?php

namespace App\Notifications;

use App\Models\ArtworkRevision;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArtworkRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ArtworkRevision $revision, public ?string $notes = null) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $order = $this->revision->artwork->orderLine->purchaseOrder;

        $mail = (new MailMessage)
            ->subject("Artwork reddedildi — {$order->order_no}")
            ->greeting("Merhaba {$notifiable->name},")
            ->line("{$order->supplier->name} artwork'ü reddetti. Revizyon gerekiyor.")
            ->line("Sipariş: {$order->order_no} | Rev.{$this->revision->revision_no}");

        if ($this->notes) {
            $mail->line("Tedarikçi notu: {$this->notes}");
        }

        return $mail
            ->action('Revizyon Yükle', url("/siparisler/{$order->id}"))
            ->salutation(config('portal.brand_name'));
    }
}
