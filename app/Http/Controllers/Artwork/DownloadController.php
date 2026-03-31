<?php

namespace App\Http\Controllers\Artwork;

use App\Http\Controllers\Controller;
use App\Models\ArtworkRevision;
use App\Services\ArtworkUploadService;
use App\Services\AuditLogService;
use App\Services\PortalSettings;
use App\Services\SpacesStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __construct(
        private SpacesStorageService $spaces,
        private ArtworkUploadService $uploadService,
        private AuditLogService $audit,
        private PortalSettings $settings,
    ) {}

    public function download(ArtworkRevision $revision): RedirectResponse|BinaryFileResponse|StreamedResponse
    {
        $this->authorize('view', $revision->artwork);

        $user = auth()->user();
        $supplierId = $revision->artwork->orderLine->purchaseOrder->supplier_id;
        $storageDisk = $revision->galleryItem?->file_disk ?: $this->settings->filesystemDisk();

        if ($user->isSupplier() && ! $revision->is_active) {
            abort(403, 'Bu dosyaya erisim yetkiniz bulunmamaktadir.');
        }

        if (! $user->canDownloadForSupplier($supplierId)) {
            abort(403, 'Bu dosyayi indirme yetkiniz bulunmamaktadir.');
        }

        if (! $this->spaces->exists($revision->spaces_path, $storageDisk)) {
            abort(404, 'Dosya bulunamadi. Lutfen yonetici ile iletisime gecin.');
        }

        $this->uploadService->logDownload($revision, $user, null, $supplierId);
        $this->audit->log('artwork.download', $revision, [
            'revision_no' => $revision->revision_no,
            'original_filename' => $revision->original_filename,
            'file_size' => $revision->file_size,
        ]);

        if ($storageDisk === 'spaces') {
            return redirect($this->spaces->presignedUrl(
                $revision->spaces_path,
                0,
                $storageDisk,
                $revision->original_filename
            ));
        }

        return Storage::disk($storageDisk)
            ->download($revision->spaces_path, $revision->original_filename);
    }
}
