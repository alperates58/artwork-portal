<?php

namespace App\Notifications;

use App\Models\ArtworkRevision;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// ─── Artwork yüklenince tedarikçiye bildirim ──────────────────────

class ArtworkUploadedNotification extends Notification implements ShouldQueue
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
            ->subject("Artwork Güncellendi — {$order->order_no}")
            ->greeting("Merhaba {$notifiable->name},")
            ->line("**{$order->order_no}** numaralı siparişinizde artwork güncellendi.")
            ->line("**Revizyon:** Rev.{$this->revision->revision_no}")
            ->line("**Dosya:** {$this->revision->original_filename}")
            ->action('Artwork\'ü Görüntüle ve İndir', url("/portal/siparisler/{$order->id}"))
            ->line('Bu bağlantı yalnızca yetkili kullanıcılar tarafından erişilebilir.')
            ->salutation('Saygılarımızla, Artwork Portal');
    }
}

// ─── Tedarikçi onayladığında iç kullanıcıya bildirim ──────────────

class ArtworkApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ArtworkRevision $revision) {}

    public function via($notifiable): array { return ['mail']; }

    public function toMail($notifiable): MailMessage
    {
        $order    = $this->revision->artwork->orderLine->purchaseOrder;
        $supplier = $order->supplier;

        return (new MailMessage)
            ->subject("Artwork Onaylandı — {$order->order_no}")
            ->greeting("Merhaba {$notifiable->name},")
            ->line("**{$supplier->name}** firması artwork'ü onayladı.")
            ->line("**Sipariş:** {$order->order_no} | **Rev.{$this->revision->revision_no}**")
            ->action('Sipariş Detayına Git', url("/siparisler/{$order->id}"))
            ->salutation('Artwork Portal');
    }
}

// ─── Tedarikçi reddettiğinde iç kullanıcıya bildirim ──────────────

class ArtworkRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ArtworkRevision $revision,
        public ?string $notes = null
    ) {}

    public function via($notifiable): array { return ['mail']; }

    public function toMail($notifiable): MailMessage
    {
        $order    = $this->revision->artwork->orderLine->purchaseOrder;
        $supplier = $order->supplier;

        return (new MailMessage)
            ->subject("⚠ Artwork Reddedildi — {$order->order_no}")
            ->greeting("Merhaba {$notifiable->name},")
            ->line("**{$supplier->name}** firması artwork'ü reddetti. Revizyon gerekiyor.")
            ->line("**Sipariş:** {$order->order_no} | **Rev.{$this->revision->revision_no}**")
            ->when($this->notes, fn ($mail) => $mail->line("**Tedarikçi notu:** {$this->notes}"))
            ->action('Yeni Revizyon Yükle', url("/siparisler/{$order->id}"))
            ->salutation('Artwork Portal');
    }
}
