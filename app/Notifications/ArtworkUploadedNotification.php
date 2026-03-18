<?php
namespace App\Notifications;
use App\Models\ArtworkRevision;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArtworkUploadedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(public ArtworkRevision $revision) {}
    public function via($notifiable): array { return ['mail']; }
    public function toMail($notifiable): MailMessage
    {
        $order = $this->revision->artwork->orderLine->purchaseOrder;
        return (new MailMessage)
            ->subject("Artwork Güncellendi — {$order->order_no}")
            ->greeting("Merhaba {$notifiable->name},")
            ->line("{$order->order_no} numaralı siparişinizde artwork güncellendi.")
            ->line("Revizyon: Rev.{$this->revision->revision_no} | Dosya: {$this->revision->original_filename}")
            ->action("Artwork'ü Görüntüle", url("/portal/siparisler/{$order->id}"))
            ->salutation('Artwork Portal');
    }
}
