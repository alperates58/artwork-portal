<?php

namespace App\Services;

use App\Models\Artwork;
use App\Models\ArtworkDownloadLog;
use App\Models\ArtworkGallery;
use App\Models\ArtworkGalleryUsage;
use App\Models\ArtworkRevision;
use App\Models\ArtworkViewLog;
use App\Models\PurchaseOrderLine;
use App\Models\StockCard;
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

    public function storeUploadedFile(PurchaseOrderLine $line, UploadedFile $file, ?UploadedFile $previewFile, array $meta, User $uploader): ArtworkRevision
    {
        return DB::transaction(function () use ($line, $file, $previewFile, $meta, $uploader) {
            $stockCard = $this->resolveStockCard($meta['stock_code'] ?? null);
            $artwork = $this->resolveArtwork($line, $stockCard?->stock_name ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $revisionNo = (int) ($meta['revision_no'] ?? $artwork->next_revision_no);

            $path = $this->spaces->buildPath(
                $line->purchaseOrder->supplier_id,
                $line->purchaseOrder->order_no,
                $line->id,
                $revisionNo,
                $file->getClientOriginalExtension()
            );

            $fileData = $this->multipart->upload($file, $path);
            $previewData = $previewFile ? $this->storePreviewFile($line, $revisionNo, $previewFile) : null;

            $galleryItem = ArtworkGallery::create([
                'name' => $fileData['original_filename'],
                'preview_file_name' => $previewData['original_filename'] ?? null,
                'stock_code' => $stockCard?->stock_code,
                'revision_no' => $revisionNo,
                'stock_card_id' => $stockCard?->id,
                'category_id' => $stockCard?->category_id,
                'file_path' => $fileData['spaces_path'],
                'preview_file_path' => $previewData['spaces_path'] ?? null,
                'file_disk' => $this->settings->filesystemDisk(),
                'preview_file_disk' => $previewData ? $this->settings->filesystemDisk() : null,
                'file_size' => $fileData['file_size'],
                'preview_file_size' => $previewData['file_size'] ?? null,
                'file_type' => $fileData['mime_type'],
                'preview_file_type' => $previewData['mime_type'] ?? null,
                'uploaded_by' => $uploader->id,
                'revision_note' => $meta['notes'] ?? null,
            ]);

            $revision = $this->createRevision($artwork, $line, $uploader, $revisionNo, [
                ...$fileData,
                ...$this->mapPreviewDataForRevision($previewData),
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
                'preview_available' => $previewData !== null,
            ]);
            $this->audit->log('artwork.gallery.create', $galleryItem, [
                'purchase_order_id' => $line->purchase_order_id,
                'purchase_order_line_id' => $line->id,
                'usage_type' => 'upload',
                'stock_code' => $stockCard?->stock_code,
            ]);

            \App\Jobs\Faz2\SendArtworkNotificationJob::dispatch($revision);

            return $revision;
        });
    }

    public function storeFromGallery(PurchaseOrderLine $line, ArtworkGallery $galleryItem, array $meta, User $uploader): ArtworkRevision
    {
        return DB::transaction(function () use ($line, $galleryItem, $meta, $uploader) {
            $stockCard = $this->resolveStockCard($meta['stock_code'] ?? $galleryItem->stock_code);
            $artwork = $this->resolveArtwork($line, $stockCard?->stock_name ?? pathinfo($galleryItem->name, PATHINFO_FILENAME));
            $revisionNo = (int) ($meta['revision_no'] ?? $artwork->next_revision_no);

            if ($stockCard) {
                $galleryItem->update([
                    'stock_code' => $stockCard->stock_code,
                    'revision_no' => $galleryItem->revision_no ?? $revisionNo,
                    'stock_card_id' => $stockCard->id,
                    'category_id' => $stockCard->category_id,
                ]);
            }

            $revision = $this->createRevision($artwork, $line, $uploader, $revisionNo, [
                'artwork_gallery_id' => $galleryItem->id,
                'spaces_path' => $galleryItem->file_path,
                'preview_spaces_path' => $galleryItem->preview_file_path,
                'original_filename' => $galleryItem->name,
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

    private function storePreviewFile(PurchaseOrderLine $line, int $revisionNo, UploadedFile $previewFile): array
    {
        $previewPath = $this->spaces->buildVariantPath(
            $line->purchaseOrder->supplier_id,
            $line->purchaseOrder->order_no,
            $line->id,
            $revisionNo,
            'preview',
            'png'
        );

        return $this->multipart->upload($previewFile, $previewPath);
    }

    private function mapPreviewDataForRevision(?array $previewData): array
    {
        if ($previewData === null) {
            return [
                'preview_original_filename' => null,
                'preview_stored_filename' => null,
                'preview_spaces_path' => null,
                'preview_mime_type' => null,
                'preview_file_size' => null,
            ];
        }

        return [
            'preview_original_filename' => $previewData['original_filename'],
            'preview_stored_filename' => $previewData['stored_filename'],
            'preview_spaces_path' => $previewData['spaces_path'],
            'preview_mime_type' => $previewData['mime_type'],
            'preview_file_size' => $previewData['file_size'],
        ];
    }
}
