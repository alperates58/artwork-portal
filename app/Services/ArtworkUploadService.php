<?php

namespace App\Services;

use App\Jobs\GenerateArtworkPreviewJob;
use App\Models\Artwork;
use App\Models\ArtworkDownloadLog;
use App\Models\ArtworkGallery;
use App\Models\ArtworkGalleryUsage;
use App\Models\ArtworkRevision;
use App\Models\ArtworkViewLog;
use App\Models\PurchaseOrderLine;
use App\Models\StockCard;
use App\Support\ArtworkFileName;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArtworkUploadService
{
    public function __construct(
        private SpacesStorageService $spaces,
        private MultipartUploadService $multipart,
        private AuditLogService $audit,
        private PortalSettings $settings,
        private DashboardCacheService $dashboardCache,
        private ArtworkPreviewGenerator $previewGenerator,
    ) {}

    public function storeUploadedFile(PurchaseOrderLine $line, UploadedFile $file, array $meta, User $uploader): ArtworkRevision
    {
        return DB::transaction(function () use ($line, $file, $meta, $uploader) {
            $stockCard = $this->resolveStockCard($meta['stock_code'] ?? null);
            $artwork = $this->resolveArtwork($line, $stockCard?->stock_name ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $revisionNo = (int) ($meta['revision_no'] ?? $artwork->next_revision_no);
            $standardizedFilename = ArtworkFileName::original(
                stockCode: $stockCard?->stock_code ?? $meta['stock_code'] ?? $line->product_code,
                revisionNo: $revisionNo,
                extension: $file->getClientOriginalExtension(),
                fallback: $line->product_code,
            );

            $path = $this->spaces->buildPath(
                $line->purchaseOrder->supplier_id,
                $line->purchaseOrder->order_no,
                $line->id,
                $revisionNo,
                $file->getClientOriginalExtension()
            );

            $fileData = $this->multipart->upload($file, $path);
            $fileData['original_filename'] = $standardizedFilename;
            $this->spaces->normalizeArtworkStoragePermissions($this->settings->filesystemDisk());

            $galleryItem = ArtworkGallery::create([
                'name' => $standardizedFilename,
                'stock_code' => $stockCard?->stock_code,
                'revision_no' => $revisionNo,
                'stock_card_id' => $stockCard?->id,
                'category_id' => $stockCard?->category_id,
                'file_path' => $fileData['spaces_path'],
                'file_disk' => $this->settings->filesystemDisk(),
                'file_size' => $fileData['file_size'],
                'file_type' => $fileData['mime_type'],
                'uploaded_by' => $uploader->id,
                'revision_note' => $meta['notes'] ?? null,
            ]);

            $revision = $this->createRevision($artwork, $line, $uploader, $revisionNo, [
                ...$fileData,
                'artwork_gallery_id' => $galleryItem->id,
                'notes' => $meta['notes'] ?? null,
            ]);

            $this->recordUsage($galleryItem, $line, 'upload');

            $this->audit->log('artwork.upload', $revision, [
                'revision_no' => $revisionNo,
                'original_filename' => $revision->original_filename,
                'file_size' => $revision->file_size,
                'strategy' => $file->getSize() >= 100 * 1024 * 1024 ? 'multipart' : 'single',
                'stock_code' => $stockCard?->stock_code,
                'stock_name' => $stockCard?->stock_name,
                'preview_available' => false,
            ]);
            $this->audit->log('artwork.gallery.create', $galleryItem, [
                'purchase_order_id' => $line->purchase_order_id,
                'purchase_order_line_id' => $line->id,
                'usage_type' => 'upload',
                'stock_code' => $stockCard?->stock_code,
            ]);

            \App\Jobs\Faz2\SendArtworkNotificationJob::dispatch($revision);
            $this->dispatchPreviewGeneration($revision);

            return $revision;
        });
    }

    public function storeFromGallery(PurchaseOrderLine $line, ArtworkGallery $galleryItem, array $meta, User $uploader): ArtworkRevision
    {
        return DB::transaction(function () use ($line, $galleryItem, $meta, $uploader) {
            $stockCard = $this->resolveStockCard($meta['stock_code'] ?? $galleryItem->stock_code);
            $artwork = $this->resolveArtwork($line, $stockCard?->stock_name ?? pathinfo($galleryItem->name, PATHINFO_FILENAME));
            $revisionNo = (int) ($meta['revision_no'] ?? $artwork->next_revision_no);
            $standardizedFilename = ArtworkFileName::original(
                stockCode: $stockCard?->stock_code ?? $galleryItem->stock_code ?? $line->product_code,
                revisionNo: $revisionNo,
                extension: pathinfo($galleryItem->name ?: $galleryItem->file_path, PATHINFO_EXTENSION) ?: 'pdf',
                fallback: $line->product_code,
            );

            if ($stockCard) {
                $galleryItem->update([
                    'stock_code' => $stockCard->stock_code,
                    'revision_no' => $galleryItem->revision_no ?? $revisionNo,
                    'stock_card_id' => $stockCard->id,
                    'category_id' => $stockCard->category_id,
                ]);
            }

            $this->spaces->normalizeArtworkStoragePermissions($this->settings->filesystemDisk());

            $revision = $this->createRevision($artwork, $line, $uploader, $revisionNo, [
                'artwork_gallery_id' => $galleryItem->id,
                'spaces_path' => $galleryItem->file_path,
                'preview_spaces_path' => $galleryItem->preview_file_path,
                'original_filename' => $standardizedFilename,
                'preview_original_filename' => $galleryItem->preview_file_name,
                'stored_filename' => basename($galleryItem->file_path),
                'preview_stored_filename' => $galleryItem->preview_file_path ? basename($galleryItem->preview_file_path) : null,
                'mime_type' => $galleryItem->file_type,
                'preview_mime_type' => $galleryItem->preview_file_type,
                'file_size' => $galleryItem->file_size,
                'preview_file_size' => $galleryItem->preview_file_size,
                'notes' => $meta['notes'] ?? $galleryItem->revision_note,
            ]);

            $this->recordUsage($galleryItem, $line, 'reuse');

            $this->audit->log('artwork.upload', $revision, [
                'revision_no' => $revisionNo,
                'original_filename' => $revision->original_filename,
                'file_size' => $revision->file_size,
                'strategy' => 'gallery-reuse',
                'stock_code' => $stockCard?->stock_code ?? $galleryItem->stock_code,
                'preview_available' => $galleryItem->has_preview,
            ]);
            $this->audit->log('artwork.gallery.reuse', $galleryItem, [
                'purchase_order_id' => $line->purchase_order_id,
                'purchase_order_line_id' => $line->id,
                'revision_id' => $revision->id,
                'stock_code' => $stockCard?->stock_code ?? $galleryItem->stock_code,
            ]);

            \App\Jobs\Faz2\SendArtworkNotificationJob::dispatch($revision);
            $this->dispatchPreviewGeneration($revision);

            return $revision;
        });
    }

    public function storeDirectToGallery(UploadedFile $file, array $meta, User $uploader): ArtworkGallery
    {
        return DB::transaction(function () use ($file, $meta, $uploader) {
            $stockCard = $this->resolveStockCard($meta['stock_code'] ?? null);
            $revisionNo = (int) ($meta['revision_no'] ?? 0);
            $ext = strtolower($file->getClientOriginalExtension());

            $standardizedFilename = ArtworkFileName::original(
                stockCode: $stockCard?->stock_code ?? ($meta['stock_code'] ? mb_strtoupper(trim((string) $meta['stock_code'])) : null),
                revisionNo: $revisionNo,
                extension: $ext,
                fallback: 'gallery',
            );

            $path = 'artworks/gallery/' . now()->format('Y/m') . '/' . Str::uuid() . '.' . $ext;

            $fileData = $this->multipart->upload($file, $path);
            $this->spaces->normalizeArtworkStoragePermissions($this->settings->filesystemDisk());

            $galleryItem = ArtworkGallery::create([
                'name' => $standardizedFilename,
                'stock_code' => $stockCard?->stock_code ?? ($meta['stock_code'] ? mb_strtoupper(trim((string) $meta['stock_code'])) : null),
                'revision_no' => $revisionNo,
                'stock_card_id' => $stockCard?->id,
                'category_id' => $stockCard?->category_id,
                'file_path' => $fileData['spaces_path'],
                'file_disk' => $this->settings->filesystemDisk(),
                'file_size' => $fileData['file_size'],
                'file_type' => $fileData['mime_type'],
                'uploaded_by' => $uploader->id,
                'revision_note' => $meta['notes'] ?? null,
            ]);

            $this->audit->log('artwork.gallery.create', $galleryItem, [
                'stock_code' => $galleryItem->stock_code,
                'category_id' => $galleryItem->category_id,
                'direct_upload' => true,
            ]);

            if ($this->previewGenerator->supportsGalleryItem($galleryItem)) {
                \App\Jobs\GenerateGalleryPreviewJob::dispatch($galleryItem->id)->afterCommit();
            }

            $this->dashboardCache->forgetAllAfterCommit();

            return $galleryItem;
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

    private function createRevision(Artwork $artwork, PurchaseOrderLine $line, User $uploader, int $revisionNo, array $attributes): ArtworkRevision
    {
        $artwork->revisions()->where('is_active', true)->update(['is_active' => false]);

        $revision = ArtworkRevision::create([
            ...$attributes,
            'artwork_id' => $artwork->id,
            'revision_no' => $revisionNo,
            'is_active' => true,
            'uploaded_by' => $uploader->id,
        ]);

        $artwork->update(['active_revision_id' => $revision->id]);
        $line->update([
            'artwork_status' => 'uploaded',
            'manual_artwork_completed_at' => null,
            'manual_artwork_completed_by' => null,
            'manual_artwork_note' => null,
        ]);
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

    private function resolveStockCard(?string $stockCode): ?StockCard
    {
        if (! $stockCode) {
            return null;
        }

        return StockCard::query()
            ->where('stock_code', mb_strtoupper(trim($stockCode)))
            ->first();
    }

    private function dispatchPreviewGeneration(ArtworkRevision $revision): void
    {
        if (! $this->previewGenerator->supports($revision) || $revision->has_preview) {
            return;
        }

        GenerateArtworkPreviewJob::dispatch($revision->id)->afterCommit();
    }
}
