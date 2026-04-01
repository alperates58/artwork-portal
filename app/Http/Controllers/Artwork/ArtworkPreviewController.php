<?php

namespace App\Http\Controllers\Artwork;

use App\Http\Controllers\Controller;
use App\Models\ArtworkRevision;
use App\Services\PortalSettings;
use App\Services\SpacesStorageService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArtworkPreviewController extends Controller
{
    public function __construct(
        private SpacesStorageService $spaces,
        private PortalSettings $settings,
    ) {}

    public function __invoke(ArtworkRevision $revision): RedirectResponse|BinaryFileResponse
    {
        $this->authorize('view', $revision->artwork);

        $user = auth()->user();
        $supplierId = $revision->artwork->orderLine->purchaseOrder->supplier_id;

        if ($user->isSupplier() && ! $revision->is_active) {
            abort(403, 'Bu önizlemeyi görüntüleme yetkiniz bulunmamaktadır.');
        }

        if (! $user->canDownloadForSupplier($supplierId)) {
            abort(403, 'Bu önizlemeyi görüntüleme yetkiniz bulunmamaktadır.');
        }

        abort_unless($revision->has_preview, 404, 'Bu artwork için kullanılabilir önizleme bulunmuyor.');

        $path = $revision->preview_path;
        $disk = $revision->preview_disk ?: $this->settings->filesystemDisk();
        $mimeType = $revision->preview_mime_type ?: 'image/png';
        $filename = $revision->preview_filename ?: basename((string) $path);

        if (! $path || ! $this->spaces->exists($path, $disk)) {
            abort(404, 'Dosya bulunamadı. Lütfen yönetici ile iletişime geçin.');
        }

        if ($disk === 'spaces') {
            return redirect($this->spaces->presignedInlineUrl($path, 5, $disk));
        }

        $absolutePath = $this->spaces->ensureReadable($path, $disk);

        abort_unless($absolutePath, 404, 'Dosya bulunamadı. Lütfen yönetici ile iletişime geçin.');

        return response()->file(
            $absolutePath,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Cache-Control' => 'private, max-age=300',
            ]
        );
    }
}
