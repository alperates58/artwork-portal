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
        abort_unless($artworkGallery->has_preview, 404, 'Bu artwork için kullanılabilir önizleme bulunmuyor.');

        $path = $artworkGallery->preview_path;
        $disk = $artworkGallery->preview_disk ?: $this->settings->filesystemDisk();
        $mimeType = $artworkGallery->preview_mime_type ?: 'image/png';
        $filename = $artworkGallery->preview_filename ?: basename((string) $path);

        if (! $path || ! $this->spaces->exists($path, $disk)) {
            abort(404, 'Dosya bulunamadı. Lütfen yönetici ile iletişime geçin.');
        }

        if ($disk === 'spaces') {
            return redirect($this->spaces->presignedInlineUrl($path, 5, $disk));
        }

        return Storage::disk($disk)->response(
            $path,
            $filename,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]
        );
    }
}
