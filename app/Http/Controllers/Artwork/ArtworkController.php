<?php

namespace App\Http\Controllers\Artwork;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArtworkUploadRequest;
use App\Models\ArtworkCategory;
use App\Models\ArtworkGallery;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrderLine;
use App\Models\StockCard;
use App\Services\ArtworkUploadService;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ArtworkController extends Controller
{
    public function __construct(
        private ArtworkUploadService $uploadService,
        private AuditLogService $audit,
        private NotificationService $notifications,
    ) {}

    public function create(PurchaseOrderLine $line): View
    {
        $this->authorize('uploadArtwork', $line);

        $line->load([
            'purchaseOrder.supplier',
            'artwork.revisions.uploadedBy',
            'artwork.revisions.galleryItem.stockCard.category',
        ]);

        $prefillStockCode = old('stock_code', $line->product_code);
        $resolvedStockCard = filled($prefillStockCode)
            ? StockCard::query()
                ->with('category:id,name')
                ->where('stock_code', mb_strtoupper(trim((string) $prefillStockCode)))
                ->first()
            : null;
        $nextRevisionNo = max(1, (int) (($line->artwork?->revisions()->max('revision_no') ?? 0) + 1));
        $galleryCandidates = ArtworkGallery::query()
            ->select([
                'id',
                'name',
                'stock_code',
                'revision_no',
                'stock_card_id',
                'category_id',
                'file_type',
                'preview_file_type',
                'file_size',
                'file_path',
                'file_disk',
                'preview_file_path',
                'preview_file_disk',
                'preview_file_name',
                'revision_note',
                'created_at',
            ])
            ->with([
                'stockCard:id,stock_code,stock_name,category_id',
                'stockCard.category:id,name',
                'latestPreviewRevision:id,artwork_gallery_id,preview_spaces_path,preview_original_filename,preview_mime_type,preview_file_size',
            ])
            ->withMax('revisions', 'revision_no')
            ->whereNotNull('stock_code')
            ->when(
                $resolvedStockCard?->stock_code,
                fn ($query, $stockCode) => $query->orderByRaw('CASE WHEN stock_code = ? THEN 0 ELSE 1 END', [$stockCode])
            )
            ->latest()
            ->limit(60)
            ->get()
            ->unique(fn (ArtworkGallery $item) => ($item->stock_code ?? '') . '|' . (string) ($item->revision_no ?? 0))
            ->values();
        $galleryCategories = ArtworkCategory::query()->orderBy('name')->get(['id', 'name']);

        return view('artworks.create', compact('line', 'resolvedStockCard', 'nextRevisionNo', 'galleryCandidates', 'galleryCategories'));
    }

    public function store(ArtworkUploadRequest $request, PurchaseOrderLine $line): RedirectResponse
    {
        $this->authorize('uploadArtwork', $line);

        $meta = $request->only('notes', 'stock_code', 'revision_no');

        $revision = $request->input('source_type') === 'gallery'
            ? $this->uploadService->storeFromGallery(
                line: $line,
                galleryItem: ArtworkGallery::query()->findOrFail($request->integer('gallery_item_id')),
                meta: $meta,
                uploader: auth()->user()
            )
            : $this->uploadService->storeUploadedFile(
                line: $line,
                file: $request->file('artwork_file'),
                meta: $meta,
                uploader: auth()->user()
            );

        $line->load('purchaseOrder:id,order_no,supplier_id');
        $orderNo = $line->purchaseOrder?->order_no ?? 'Sipariş';
        $this->notifications->notifyDepartment(
            null,
            'artwork_uploaded',
            "Yeni artwork yüklendi: {$orderNo}",
            auth()->user()->name . ' tarafından Rev.' . $revision->revision_no . ' yüklendi.',
            route('order-lines.show', $line),
        );

        return redirect()
            ->route('order-lines.show', $line)
            ->with('success', 'Artwork başarıyla işlendi. Revizyon: Rev.' . $revision->revision_no);
    }

    public function show(ArtworkRevision $revision): View
    {
        $this->authorize('view', $revision->artwork);

        $revision->load([
            'artwork.orderLine.purchaseOrder.supplier',
            'uploadedBy',
            'galleryItem.category',
            'galleryItem.stockCard.category',
            'galleryItem.tags',
        ]);

        $this->uploadService->logView($revision, auth()->user());
        $this->audit->log('artwork.view', $revision);

        return view('artworks.show', compact('revision'));
    }

    public function activate(ArtworkRevision $revision): RedirectResponse
    {
        $this->authorize('manageRevisions', $revision->artwork);

        $this->uploadService->activate($revision, auth()->user());

        return back()->with('success', 'Rev.' . $revision->revision_no . ' aktif revizyon olarak işaretlendi.');
    }

    public function destroy(ArtworkRevision $revision): RedirectResponse
    {
        $this->authorize('manageRevisions', $revision->artwork);

        $revision->load('artwork.orderLine');
        $line = $revision->artwork->orderLine;
        $revNo = $revision->revision_no;

        if ($revision->is_active && $revision->artwork->revisions()->count() === 1) {
            return back()->with('error', 'Tek aktif revizyonu silemezsiniz. Önce yeni revizyon yükleyin.');
        }

        if ($revision->is_active) {
            $prev = $revision->artwork->revisions()
                ->where('id', '!=', $revision->id)
                ->orderByDesc('revision_no')
                ->first();

            if ($prev) {
                $this->uploadService->activate($prev, auth()->user());
            }
        }

        $this->audit->log('artwork.delete', $revision, [
            'revision_no' => $revNo,
            'original_filename' => $revision->original_filename,
            'line_id' => $line?->id,
            'order_no' => $line?->purchaseOrder?->order_no,
        ]);

        $revision->delete();

        return redirect()
            ->route('order-lines.show', $line)
            ->with('success', 'Rev.' . $revNo . ' silindi.');
    }

    public function revisions(PurchaseOrderLine $line): View
    {
        $this->authorize('view', $line);

        $line->load([
            'purchaseOrder.supplier',
            'artwork.revisions' => fn ($query) => $query
                ->with(['uploadedBy', 'galleryItem.stockCard.category'])
                ->orderByDesc('revision_no'),
        ]);

        return view('artworks.revisions', compact('line'));
    }
}
