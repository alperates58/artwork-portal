<?php

namespace App\Http\Controllers\Artwork;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArtworkUploadRequest;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrderLine;
use App\Services\ArtworkUploadService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArtworkController extends Controller
{
    public function __construct(
        private ArtworkUploadService $uploadService,
        private AuditLogService $audit
    ) {}

    /**
     * Sipariş satırına artwork yükleme formu
     */
    public function create(PurchaseOrderLine $line): View
    {
        $this->authorize('uploadArtwork', $line);

        $line->load(['purchaseOrder.supplier', 'artwork.revisions.uploadedBy']);

        return view('artworks.create', compact('line'));
    }

    /**
     * Artwork yükle (yeni veya revizyon)
     */
    public function store(ArtworkUploadRequest $request, PurchaseOrderLine $line): RedirectResponse
    {
        $this->authorize('uploadArtwork', $line);

        $revision = $this->uploadService->store(
            line:     $line,
            file:     $request->file('artwork_file'),
            meta:     $request->only('title', 'notes'),
            uploader: auth()->user()
        );

        return redirect()
            ->route('order-lines.show', $line)
            ->with('success', "Artwork başarıyla yüklendi. Revizyon: Rev.{$revision->revision_no}");
    }

    /**
     * Revizyon detay ekranı (iç kullanıcılar)
     */
    public function show(ArtworkRevision $revision): View
    {
        $this->authorize('view', $revision->artwork);

        $revision->load([
            'artwork.orderLine.purchaseOrder.supplier',
            'uploadedBy',
        ]);

        $this->uploadService->logView($revision, auth()->user());
        $this->audit->log('artwork.view', $revision);

        return view('artworks.show', compact('revision'));
    }

    /**
     * Revizyonu aktif yap
     */
    public function activate(ArtworkRevision $revision): RedirectResponse
    {
        $this->authorize('manageRevisions', $revision->artwork);

        $this->uploadService->activate($revision, auth()->user());

        return back()->with('success', "Rev.{$revision->revision_no} aktif revizyon olarak işaretlendi.");
    }

    /**
     * Revizyon listesi (iç kullanıcılar — sipariş satırı bazında)
     */
    public function revisions(PurchaseOrderLine $line): View
    {
        $this->authorize('view', $line);

        $line->load([
            'purchaseOrder.supplier',
            'artwork.revisions' => fn ($q) => $q->with('uploadedBy')->orderByDesc('revision_no'),
        ]);

        return view('artworks.revisions', compact('line'));
    }
}
