<?php

namespace App\Mail\Faz2;

use App\Models\ArtworkRevision;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArtworkUploadedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ArtworkRevision $revision
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Artwork Portal] Yeni Artwork Yüklendi — ' .
                     $this->revision->artwork->orderLine->purchaseOrder->order_no,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.faz2.artwork-uploaded',
            with: [
                'revision'   => $this->revision,
                'order'      => $this->revision->artwork->orderLine->purchaseOrder,
                'line'       => $this->revision->artwork->orderLine,
                'downloadUrl'=> route('portal.download', $this->revision),
            ]
        );
    }
}
