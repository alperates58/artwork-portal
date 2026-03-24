<?php

namespace App\Services;

use App\Models\Artwork;
use App\Models\ArtworkDownloadLog;
use App\Models\ArtworkRevision;
use App\Models\ArtworkViewLog;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ArtworkUploadService
{
    public function __construct(
        private SpacesStorageService   $spaces,
        private MultipartUploadService $multipart,
        private AuditLogService        $audit
    ) {}

    public function store(PurchaseOrderLine $line, UploadedFile $file, array $meta, User $uploader): ArtworkRevision
    {
        return DB::transaction(function () use ($line, $file, $meta, $uploader) {
            $artwork = $line->artwork ?? Artwork::create([
                'order_line_id' => $line->id,
                'title'         => $meta['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            ]);

            $nextRevNo = $artwork->next_revision_no;
            $artwork->revisions()->where('is_active', true)->update(['is_active' => false]);

            $path = $this->spaces->buildPath(
                $line->purchaseOrder->supplier_id,
                $line->purchaseOrder->order_no,
                $line->id,
                $nextRevNo,
                $file->getClientOriginalExtension()
            );

            // 100 MB altı tek request, üstü multipart
            $fileData = $this->multipart->upload($file, $path);

            $revision = ArtworkRevision::create([
                ...$fileData,
                'artwork_id'  => $artwork->id,
                'revision_no' => $nextRevNo,
                'is_active'   => true,
                'uploaded_by' => $uploader->id,
                'notes'       => $meta['notes'] ?? null,
            ]);

            $artwork->update(['active_revision_id' => $revision->id]);
            $line->update(['artwork_status' => 'uploaded']);

            $this->audit->log('artwork.upload', $revision, [
                'revision_no'       => $nextRevNo,
                'original_filename' => $revision->original_filename,
                'file_size'         => $revision->file_size,
                'strategy'          => $file->getSize() >= 100 * 1024 * 1024 ? 'multipart' : 'single',
            ]);

            // 10. Tedarikçiye e-posta bildirimi (Faz 2 — queue'da çalışır)
            \App\Jobs\Faz2\SendArtworkNotificationJob::dispatch($revision);

            return $revision;
        });
    }

    public function activate(ArtworkRevision $revision, User $actor): void
    {
        DB::transaction(function () use ($revision) {
            $revision->artwork->activateRevision($revision);
            $this->audit->log('artwork.revision.activate', $revision);
        });
    }

    public function logView(ArtworkRevision $revision, User $user): void
    {
        $supplierId = $user->supplier_id
            ?? $revision->artwork->orderLine->purchaseOrder->supplier_id;

        ArtworkViewLog::create([
            'artwork_revision_id' => $revision->id,
            'user_id'             => $user->id,
            'supplier_id'         => $supplierId,
            'ip_address'          => request()->ip(),
            'user_agent'          => request()->userAgent(),
            'viewed_at'           => now(),
        ]);
    }

    public function logViews(iterable $revisions, User $user): void
    {
        $revisionCollection = $revisions instanceof Collection
            ? $revisions->filter()
            : collect($revisions)->filter();

        if ($revisionCollection->isEmpty()) {
            return;
        }

        $now = now();
        $ipAddress = request()->ip();
        $userAgent = request()->userAgent();

        ArtworkViewLog::insert(
            $revisionCollection->map(function (ArtworkRevision $revision) use ($user, $now, $ipAddress, $userAgent) {
                $supplierId = $user->supplier_id
                    ?? $revision->artwork->orderLine->purchaseOrder->supplier_id;

                return [
                    'artwork_revision_id' => $revision->id,
                    'user_id' => $user->id,
                    'supplier_id' => $supplierId,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'viewed_at' => $now,
                ];
            })->all()
        );
    }

    public function logDownload(ArtworkRevision $revision, User $user, ?string $token = null): void
    {
        $supplierId = $user->supplier_id
            ?? $revision->artwork->orderLine->purchaseOrder->supplier_id;

        ArtworkDownloadLog::create([
            'artwork_revision_id' => $revision->id,
            'user_id'             => $user->id,
            'supplier_id'         => $supplierId,
            'ip_address'          => request()->ip(),
            'user_agent'          => request()->userAgent(),
            'download_token'      => $token,
            'downloaded_at'       => now(),
        ]);
    }
}
