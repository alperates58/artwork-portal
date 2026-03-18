<?php

namespace App\Http\Controllers\Artwork;

use App\Http\Controllers\Controller;
use App\Models\ArtworkRevision;
use App\Services\AuditLogService;
use App\Services\SpacesStorageService;
use Illuminate\Http\RedirectResponse;

class DownloadController extends Controller
{
    public function __construct(
        private SpacesStorageService $spaces,
        private AuditLogService $audit
    ) {}

    /**
     * Güvenli artwork indirme
     * - Policy kontrolü
     * - Tedarikçi sadece aktif revizyonu indirebilir
     * - Presigned URL ile yönlendirme (dosya sunucudan geçmez)
     */
    public function download(ArtworkRevision $revision): RedirectResponse
    {
        // 1. Genel görüntüleme yetkisi
        $this->authorize('view', $revision->artwork);

        // 2. Tedarikçi sadece aktif revizyonu indirebilir
        if (auth()->user()->isSupplier() && ! $revision->is_active) {
            abort(403, 'Bu dosyaya erişim yetkiniz bulunmamaktadır.');
        }

        // 3. Spaces'te dosya var mı kontrol
        if (! $this->spaces->exists($revision->spaces_path)) {
            abort(404, 'Dosya bulunamadı. Lütfen yönetici ile iletişime geçin.');
        }

        // 4. İndirme logu
        $this->audit->log('artwork.download', $revision, [
            'revision_no'       => $revision->revision_no,
            'original_filename' => $revision->original_filename,
            'file_size'         => $revision->file_size,
        ]);

        // 5. Presigned URL üret ve yönlendir
        // Dosya sunucudan geçmez — direkt Spaces'ten akar
        $url = $this->spaces->presignedUrl($revision->spaces_path);

        return redirect($url);
    }
}
