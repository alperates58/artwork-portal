<?php

namespace App\Http\Controllers\Artwork;

use App\Http\Controllers\Controller;
use App\Models\ArtworkGallery;
use App\Services\AuditLogService;
use App\Services\PortalSettings;
use App\Services\SpacesStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArtworkGalleryDownloadController extends Controller
{
    public function __construct(
        private SpacesStorageService $spaces,
        private AuditLogService $audit,
        private PortalSettings $settings,
    ) {}

    public function __invoke(ArtworkGallery $artworkGallery): RedirectResponse|BinaryFileResponse|StreamedResponse
    {
        abort_unless(auth()->user()?->isInternal(), 403, 'Bu dosyayı indirme yetkiniz bulunmamaktadır.');

        $path = $artworkGallery->file_path;
        $disk = $artworkGallery->file_disk ?: $this->settings->filesystemDisk();
        $downloadName = $artworkGallery->download_filename;

        abort_if(! $path, 404, 'Bu galeri öğesine ait dosya yolu bulunamadı.');

        if (! $this->spaces->exists($path, $disk)) {
            abort(404, 'Dosya bulunamadı. Lütfen yönetici ile iletişime geçin.');
        }

        $this->audit->log('artwork.gallery.download', $artworkGallery, [
            'name' => $artworkGallery->name,
            'stock_code' => $artworkGallery->stock_code,
            'file_size' => $artworkGallery->file_size,
            'download_name' => $downloadName,
        ]);

        if ($disk === 'spaces') {
            return redirect($this->spaces->presignedUrl($path, 0, $disk, $downloadName));
        }

        return Storage::disk($disk)->download($path, $downloadName);
    }
}
