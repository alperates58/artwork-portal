<?php

namespace App\Jobs\Faz2;

use App\Mail\Faz2\ArtworkUploadedMail;
use App\Models\ArtworkRevision;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendArtworkNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly ArtworkRevision $revision
    ) {}

    public function handle(): void
    {
        $supplier = $this->revision->artwork->orderLine->purchaseOrder->supplier;

        // Tedarikçinin tüm aktif kullanıcılarına bildirim gönder
        $supplier->users()->each(function ($user) {
            Mail::to($user->email)->send(new ArtworkUploadedMail($this->revision));
        });
    }
}
