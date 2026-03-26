<?php

namespace App\Http\Controllers\Artwork;

use App\Http\Controllers\Controller;
use App\Models\ArtworkGallery;
use App\Services\PortalSettings;
use App\Services\SpacesStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArtworkGalleryPreviewController extends Controller
{
    public function __construct(
        private SpacesStorageService $spaces,
        private PortalSettings $settings,
    ) {}

    public function __invoke(ArtworkGallery $artworkGallery): RedirectResponse|BinaryFileResponse|StreamedResponse
    {
        abort_unless(auth()->user()?->canUploadArtwork(), 403, 'Bu önizlemeyi görüntüleme yetkiniz bulunmamaktadır.');
        abort_unless($artworkGallery->is_image, 404, 'Önizleme yalnızca görsel dosyalar için kullanılabilir.');

        $disk = $artworkGallery->file_disk ?: $this->settings->filesystemDisk();

        if (! $this->spaces->exists($artworkGallery->file_path, $disk)) {
            abort(404, 'Dosya bulunamadı. Lütfen yönetici ile iletişime geçin.');
        }

        if ($disk === 'spaces') {
            return redirect($this->spaces->presignedInlineUrl($artworkGallery->file_path, 5, $disk));
        }

        return Storage::disk($disk)->response(
            $artworkGallery->file_path,
            basename($artworkGallery->file_path),
            [
                'Content-Type' => $artworkGallery->file_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . basename($artworkGallery->file_path) . '"',
            ]
        );
    }
}
