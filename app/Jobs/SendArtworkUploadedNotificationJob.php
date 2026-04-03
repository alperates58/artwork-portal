<?php

namespace App\Jobs;

use App\Mail\ArtworkUploadedNotificationMail;
use App\Models\ArtworkRevision;
use App\Services\AuditLogService;
use App\Services\MailNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendArtworkUploadedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly int $revisionId,
        public readonly string $source = 'artwork_upload'
    ) {}

    public function handle(MailNotificationService $notifications, AuditLogService $audit): void
    {
        $revision = ArtworkRevision::query()
            ->with([
                'uploadedBy:id,name',
                'artwork.orderLine.purchaseOrder.supplier:id,name',
            ])
            ->find($this->revisionId);

        if (! $revision) {
            Log::warning('Artwork upload notification skipped because revision no longer exists', [
                'revision_id' => $this->revisionId,
                'source' => $this->source,
            ]);

            return;
        }

        $recipients = $notifications->artworkUploadedRecipients();
        $subject = $notifications->artworkUploadedSubject($revision);

        try {
            Mail::to($recipients['to'])
                ->cc($recipients['cc'])
                ->bcc($recipients['bcc'])
                ->send(new ArtworkUploadedNotificationMail($revision, $subject));

            $audit->log('mail.notification.sent', $revision, [
                'type' => 'artwork_uploaded',
                'source' => $this->source,
                'to_count' => count($recipients['to']),
                'cc_count' => count($recipients['cc']),
                'bcc_count' => count($recipients['bcc']),
            ]);
        } catch (\Throwable $exception) {
            $audit->log('mail.notification.failed', $revision, [
                'type' => 'artwork_uploaded',
                'source' => $this->source,
                'message' => $exception->getMessage(),
            ]);

            Log::error('Artwork upload notification send failed', [
                'revision_id' => $revision->id,
                'source' => $this->source,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function tags(): array
    {
        return ['mail-notification', 'artwork-uploaded', 'source:' . $this->source, 'revision:' . $this->revisionId];
    }
}
