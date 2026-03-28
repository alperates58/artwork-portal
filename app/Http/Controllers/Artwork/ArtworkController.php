<?php

namespace App\Http\Controllers\Artwork;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArtworkUploadRequest;
use App\Models\ArtworkCategory;
use App\Models\ArtworkGallery;
use App\Models\ArtworkRevision;
use App\Models\ArtworkTag;
use App\Models\PurchaseOrderLine;
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

        $line->load(['purchaseOrder.supplier', 'artwork.revisions.uploadedBy', 'artwork.revisions.galleryItem']);

        $galleryItems = ArtworkGallery::query()
            ->with(['category:id,name', 'tags:id,name', 'uploadedBy:id,name'])
            ->withCount('usages')
            ->withMax('usages', 'used_at')
            ->when(request('gallery_search'), fn ($query, $search) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('stock_code', 'like', '%' . $search . '%');
            }))
            ->when(request('gallery_category_id'), fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when(request('gallery_tag_id'), fn ($query, $tagId) => $query->whereHas('tags', fn ($tagQuery) => $tagQuery->where('artwork_tags.id', $tagId)))
            ->latest()
            ->limit(15)
            ->get();

        $categories = ArtworkCategory::query()->orderBy('name')->get(['id', 'name']);
        $tags = ArtworkTag::query()->orderBy('name')->get(['id', 'name']);

        return view('artworks.create', compact('line', 'galleryItems', 'categories', 'tags'));
    }

    public function store(ArtworkUploadRequest $request, PurchaseOrderLine $line): RedirectResponse
    {
        $this->authorize('uploadArtwork', $line);

        $meta = $request->only('title', 'notes', 'gallery_name', 'stock_code', 'description', 'category_id', 'tag_ids');

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

        // Notify all purchasing/admin users about the new artwork upload
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
            ->with('success', "Artwork basariyla islendi. Revizyon: Rev.{$revision->revision_no}");
    }

    public function show(ArtworkRevision $revision): View
    {
        $this->authorize('view', $revision->artwork);

        $revision->load([
            'artwork.orderLine.purchaseOrder.supplier',
            'uploadedBy',
            'galleryItem.category',
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

        return back()->with('success', "Rev.{$revision->revision_no} aktif revizyon olarak isaretlendi.");
    }

    public function revisions(PurchaseOrderLine $line): View
    {
        $this->authorize('view', $line);

        $line->load([
            'purchaseOrder.supplier',
            'artwork.revisions' => fn ($query) => $query->with(['uploadedBy', 'galleryItem'])->orderByDesc('revision_no'),
        ]);

        return view('artworks.revisions', compact('line'));
    }
}
