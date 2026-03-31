<?php

namespace App\Http\Controllers\Artwork;

use App\Http\Controllers\Controller;
use App\Models\ArtworkGallery;
use App\Models\ArtworkRevision;
use App\Services\PortalSettings;
use App\Services\SpacesStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArtworkGalleryPreviewController extends Controller
{
    public function __construct(
        private SpacesStorageService $spaces,
        private PortalSettings $settings,
    ) {}

    public function __invoke(ArtworkGallery $artworkGallery): RedirectResponse|BinaryFileResponse
    {
        abort_unless(auth()->user()?->isInternal(), 403, 'Bu önizlemeyi görüntüleme yetkiniz bulunmamaktadır.');
        abort_unless($artworkGallery->has_preview, 404, 'Bu artwork için kullanılabilir önizleme bulunmuyor.');

        $preview = $this->resolvePreview($artworkGallery);

        if ($preview === null) {
            abort(404, 'Dosya bulunamadı. Lütfen yönetici ile iletişime geçin.');
        }

        if ($preview['disk'] === 'spaces') {
            return redirect($this->spaces->presignedInlineUrl($preview['path'], 5, $preview['disk']));
        }

        return response()->file(
            Storage::disk($preview['disk'])->path($preview['path']),
            [
                'Content-Type' => $preview['mime_type'],
                'Content-Disposition' => 'inline; filename="' . $preview['filename'] . '"',
                'Cache-Control' => 'private, max-age=300',
            ]
        );
    }

    private function resolvePreview(ArtworkGallery $artworkGallery): ?array
    {
        $preview = $this->galleryPreviewPayload($artworkGallery);

        if ($preview !== null) {
            return $preview;
        }

        $fallbackRevision = $artworkGallery->relationLoaded('latestPreviewRevision')
            ? $artworkGallery->latestPreviewRevision
            : $artworkGallery->latestPreviewRevision()->first();

        if (! $fallbackRevision instanceof ArtworkRevision) {
            return null;
        }

        $preview = $this->revisionPreviewPayload($artworkGallery, $fallbackRevision);

        if ($preview === null) {
            return null;
        }

        $artworkGallery->forceFill([
            'preview_file_name' => $preview['filename'],
            'preview_file_path' => $preview['path'],
            'preview_file_disk' => $preview['disk'],
            'preview_file_size' => $fallbackRevision->preview_file_size,
            'preview_file_type' => $preview['mime_type'],
        ])->save();

        return $preview;
    }

    private function galleryPreviewPayload(ArtworkGallery $artworkGallery): ?array
    {
        $path = $artworkGallery->preview_path;
        $disk = $artworkGallery->preview_disk ?: $this->settings->filesystemDisk();

        if (! $path || ! $this->spaces->exists($path, $disk)) {
            return null;
        }

        return [
            'path' => $path,
            'disk' => $disk,
            'mime_type' => $artworkGallery->preview_mime_type ?: 'image/png',
            'filename' => $artworkGallery->preview_filename ?: basename((string) $path),
        ];
    }

    private function revisionPreviewPayload(ArtworkGallery $artworkGallery, ArtworkRevision $revision): ?array
    {
        $path = $revision->preview_spaces_path;
        $disk = $artworkGallery->file_disk
            ?: $artworkGallery->getAttributeFromArray('preview_file_disk')
            ?: $this->settings->filesystemDisk();

        if (! $path || ! $this->spaces->exists($path, $disk)) {
            return null;
        }

        return [
            'path' => $path,
            'disk' => $disk,
            'mime_type' => $revision->preview_mime_type ?: 'image/png',
            'filename' => $revision->preview_original_filename ?: basename((string) $path),
        ];
    }
}
