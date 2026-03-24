<?php

namespace App\Notifications;

use App\Models\ArtworkRevision;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArtworkApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ArtworkRevision $revision) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $order = $this->revision->artwork->orderLine->purchaseOrder;

        return (new MailMessage)
            ->subject("Artwork onaylandı — {$order->order_no}")
            ->greeting("Merhaba {$notifiable->name},")
            ->line("{$order->supplier->name} firması artwork'ü onayladı.")
            ->line("Sipariş: {$order->order_no} | Rev.{$this->revision->revision_no}")
            ->action('Sipariş Detayı', url("/siparisler/{$order->id}"))
            ->salutation(config('portal.brand_name'));
    }
}
