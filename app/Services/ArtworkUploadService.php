<?php

namespace App\Services;

use App\Models\Artwork;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ArtworkUploadService
{
    public function __construct(
        private SpacesStorageService $spaces,
        private AuditLogService $audit
    ) {}

    /**
     * Yeni artwork veya yeni revizyon yükler
     * Transaction içinde — ya hepsi ya hiçbiri
     */
    public function store(
        PurchaseOrderLine $line,
        UploadedFile $file,
        array $meta,
        User $uploader
    ): ArtworkRevision {
        return DB::transaction(function () use ($line, $file, $meta, $uploader) {

            // 1. Artwork kaydı bul veya oluştur
            $artwork = $line->artwork ?? Artwork::create([
                'order_line_id' => $line->id,
                'title'         => $meta['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            ]);

            // 2. Sonraki revizyon numarasını belirle
            $nextRevNo = $artwork->next_revision_no;

            // 3. Önceki aktif revizyonları pasife al
            $artwork->revisions()->where('is_active', true)->update(['is_active' => false]);

            // 4. Spaces path üret
            $path = $this->spaces->buildPath(
                supplierId:  $line->purchaseOrder->supplier_id,
                orderNo:     $line->purchaseOrder->order_no,
                lineId:      $line->id,
                revisionNo:  $nextRevNo,
                extension:   $file->getClientOriginalExtension()
            );

            // 5. Dosyayı Spaces'e yükle
            $fileData = $this->spaces->upload($file, $path);

            // 6. Revizyon kaydını oluştur
            $revision = ArtworkRevision::create([
                ...$fileData,
                'artwork_id'  => $artwork->id,
                'revision_no' => $nextRevNo,
                'is_active'   => true,
                'uploaded_by' => $uploader->id,
                'notes'       => $meta['notes'] ?? null,
            ]);

            // 7. Artwork'ün aktif revizyon pointer'ını güncelle
            $artwork->update(['active_revision_id' => $revision->id]);

            // 8. Sipariş satırı artwork durumunu güncelle
            $line->update(['artwork_status' => 'uploaded']);

            // 9. Log
            $this->audit->log('artwork.upload', $revision, [
                'revision_no'       => $nextRevNo,
                'original_filename' => $revision->original_filename,
                'file_size'         => $revision->file_size,
            ]);

            return $revision;
        });
    }

    /**
     * Belirli bir revizyonu aktif yap
     */
    public function activate(ArtworkRevision $revision, User $actor): void
    {
        DB::transaction(function () use ($revision, $actor) {
            $revision->artwork->activateRevision($revision);

            $this->audit->log('artwork.revision.activate', $revision, [
                'revision_no' => $revision->revision_no,
            ]);
        });
    }
}
