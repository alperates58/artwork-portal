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

        if ($user->isSupplier() && ! $revision->is_active) {
            abort(403, 'Bu dosyaya erişim yetkiniz bulunmamaktadır.');
        }

        if (! $user->canDownloadForSupplier($supplierId)) {
            abort(403, 'Bu dosyayı indirme yetkiniz bulunmamaktadır.');
        }

        if (! $this->spaces->exists($revision->spaces_path)) {
            abort(404, 'Dosya bulunamadı. Lütfen yönetici ile iletişime geçin.');
        }

        $this->uploadService->logDownload($revision, $user, null, $supplierId);
        $this->audit->log('artwork.download', $revision, [
            'revision_no' => $revision->revision_no,
            'original_filename' => $revision->original_filename,
            'file_size' => $revision->file_size,
        ]);

        if ($this->settings->filesystemDisk() === 'spaces') {
            return redirect($this->spaces->presignedUrl($revision->spaces_path));
        }

        return Storage::disk($this->settings->filesystemDisk())
            ->download($revision->spaces_path, $revision->original_filename);
    }
}
