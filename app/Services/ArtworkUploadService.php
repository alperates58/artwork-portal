<?php

namespace App\Services;

use App\Models\Artwork;
use App\Models\ArtworkDownloadLog;
use App\Models\ArtworkGallery;
use App\Models\ArtworkGalleryUsage;
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
        private SpacesStorageService $spaces,
        private MultipartUploadService $multipart,
        private AuditLogService $audit,
        private PortalSettings $settings,
        private DashboardCacheService $dashboardCache,
    ) {}

    public function storeUploadedFile(PurchaseOrderLine $line, UploadedFile $file, array $meta, User $uploader): ArtworkRevision
    {
        return DB::transaction(function () use ($line, $file, $meta, $uploader) {
            $artwork = $this->resolveArtwork($line, $meta['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $nextRevNo = $artwork->next_revision_no;

            $path = $this->spaces->buildPath(
                $line->purchaseOrder->supplier_id,
                $line->purchaseOrder->order_no,
                $line->id,
                $nextRevNo,
                $file->getClientOriginalExtension()
            );

            $fileData = $this->multipart->upload($file, $path);

            $galleryItem = ArtworkGallery::create([
                'name' => $meta['gallery_name'] ?? $fileData['original_filename'],
                'stock_code' => $meta['stock_code'] ?? null,
                'category_id' => $meta['category_id'] ?? null,
                'file_path' => $fileData['spaces_path'],
                'file_disk' => $this->settings->filesystemDisk(),
                'file_size' => $fileData['file_size'],
                'file_type' => $fileData['mime_type'],
                'uploaded_by' => $uploader->id,
                'revision_note' => $meta['notes'] ?? null,
            ]);

            $this->syncGalleryTags($galleryItem, $meta['tag_ids'] ?? []);

            $revision = $this->createRevision($artwork, $line, $uploader, $nextRevNo, [
                ...$fileData,
                'artwork_gallery_id' => $galleryItem->id,
                'notes' => $meta['notes'] ?? null,
            ]);

            $this->recordUsage($galleryItem, $line, 'upload');

            $this->audit->log('artwork.upload', $revision, [
                'revision_no' => $nextRevNo,
                'original_filename' => $revision->original_filename,
                'file_size' => $revision->file_size,
                'strategy' => $file->getSize() >= 100 * 1024 * 1024 ? 'multipart' : 'single',
            ]);
            $this->audit->log('artwork.gallery.create', $galleryItem, [
                'purchase_order_id' => $line->purchase_order_id,
                'purchase_order_line_id' => $line->id,
                'usage_type' => 'upload',
            ]);

            \App\Jobs\Faz2\SendArtworkNotificationJob::dispatch($revision);

            return $revision;
        });
    }

    public function storeFromGallery(PurchaseOrderLine $line, ArtworkGallery $galleryItem, array $meta, User $uploader): ArtworkRevision
    {
        return DB::transaction(function () use ($line, $galleryItem, $meta, $uploader) {
            $artwork = $this->resolveArtwork($line, $meta['title'] ?? pathinfo($galleryItem->name, PATHINFO_FILENAME));
            $nextRevNo = $artwork->next_revision_no;

            $revision = $this->createRevision($artwork, $line, $uploader, $nextRevNo, [
                'artwork_gallery_id' => $galleryItem->id,
                'spaces_path' => $galleryItem->file_path,
                'original_filename' => $galleryItem->name,
                'stored_filename' => basename($galleryItem->file_path),
                'mime_type' => $galleryItem->file_type,
                'file_size' => $galleryItem->file_size,
                'notes' => $meta['notes'] ?? $galleryItem->revision_note,
            ]);

            $this->recordUsage($galleryItem, $line, 'reuse');

            $this->audit->log('artwork.upload', $revision, [
                'revision_no' => $nextRevNo,
                'original_filename' => $revision->original_filename,
                'file_size' => $revision->file_size,
                'strategy' => 'gallery-reuse',
            ]);
            $this->audit->log('artwork.gallery.reuse', $galleryItem, [
                'purchase_order_id' => $line->purchase_order_id,
                'purchase_order_line_id' => $line->id,
                'revision_id' => $revision->id,
            ]);

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

    public function logView(ArtworkRevision $revision, User $user, ?int $supplierId = null): void
    {
        $supplierId ??= $user->supplier_id
            ?? $revision->artwork->orderLine->purchaseOrder->supplier_id;

        ArtworkViewLog::create([
            'artwork_revision_id' => $revision->id,
            'user_id' => $user->id,
            'supplier_id' => $supplierId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'viewed_at' => now(),
        ]);
    }

    public function logViews(iterable $revisions, User $user, ?int $supplierId = null): void
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
            $revisionCollection->map(function (ArtworkRevision $revision) use ($user, $supplierId, $now, $ipAddress, $userAgent) {
                $resolvedSupplierId = $supplierId
                    ?? $user->supplier_id
                    ?? $revision->artwork->orderLine->purchaseOrder->supplier_id;

                return [
                    'artwork_revision_id' => $revision->id,
                    'user_id' => $user->id,
                    'supplier_id' => $resolvedSupplierId,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'viewed_at' => $now,
                ];
            })->all()
        );
    }

    public function logDownload(ArtworkRevision $revision, User $user, ?string $token = null, ?int $supplierId = null): void
    {
        $supplierId ??= $user->supplier_id
            ?? $revision->artwork->orderLine->purchaseOrder->supplier_id;

        ArtworkDownloadLog::create([
            'artwork_revision_id' => $revision->id,
            'user_id' => $user->id,
            'supplier_id' => $supplierId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'download_token' => $token,
            'downloaded_at' => now(),
        ]);
    }

    private function resolveArtwork(PurchaseOrderLine $line, string $defaultTitle): Artwork
    {
        return $line->artwork ?? Artwork::create([
            'order_line_id' => $line->id,
            'title' => $defaultTitle,
        ]);
    }

    private function createRevision(Artwork $artwork, PurchaseOrderLine $line, User $uploader, int $nextRevNo, array $attributes): ArtworkRevision
    {
        $artwork->revisions()->where('is_active', true)->update(['is_active' => false]);

        $revision = ArtworkRevision::create([
            ...$attributes,
            'artwork_id' => $artwork->id,
            'revision_no' => $nextRevNo,
            'is_active' => true,
            'uploaded_by' => $uploader->id,
        ]);

        $artwork->update(['active_revision_id' => $revision->id]);
        $line->update(['artwork_status' => 'uploaded']);
        $this->dashboardCache->forgetAllAfterCommit();

        return $revision;
    }

    private function recordUsage(ArtworkGallery $galleryItem, PurchaseOrderLine $line, string $usageType): void
    {
        ArtworkGalleryUsage::create([
            'artwork_gallery_id' => $galleryItem->id,
            'purchase_order_id' => $line->purchase_order_id,
            'purchase_order_line_id' => $line->id,
            'supplier_id' => $line->purchaseOrder->supplier_id,
            'used_at' => now(),
            'usage_type' => $usageType,
        ]);
    }

    private function syncGalleryTags(ArtworkGallery $galleryItem, array $tagIds): void
    {
        $galleryItem->tags()->sync(
            collect($tagIds)
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all()
        );
    }
}
