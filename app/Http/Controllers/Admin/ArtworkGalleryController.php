<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArtworkGalleryUpdateRequest;
use App\Models\ArtworkCategory;
use App\Models\ArtworkGallery;
use App\Models\ArtworkRevision;
use App\Models\ArtworkTag;
use App\Models\StockCard;
use App\Services\ArtworkCategoryService;
use App\Services\AuditLogService;
use App\Services\PortalSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ArtworkGalleryController extends Controller
{
    public function __construct(
        private AuditLogService $audit,
        private PortalSettings $settings,
        private ArtworkCategoryService $categories,
    ) {}

    public function index(): View
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('gallery', 'view'),
            403
        );

        $query = ArtworkGallery::query()
            ->with(['category:id,name', 'tags:id,name', 'uploadedBy:id,name', 'stockCard.category:id,name'])
            ->withCount('usages')
            ->withMax('usages', 'used_at')
            ->when(request('search'), fn ($q, $s) => $q->where('name', 'like', '%' . $s . '%'))
            ->when(request('stock_code'), fn ($q, $s) => $q->where('stock_code', 'like', '%' . mb_strtoupper(trim((string) $s)) . '%'))
            ->when(request('category_id'), fn ($q, $v) => $q->where('category_id', $v))
            ->when(request('tag_id'), fn ($q, $v) => $q->whereHas('tags', fn ($t) => $t->where('artwork_tags.id', $v)))
            ->when(request('type'), function ($q, $type) {
                match ($type) {
                    'image' => $q->whereIn('file_type', ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'])
                        ->orWhereRaw("LOWER(name) REGEXP '\\.(jpg|jpeg|png|gif|webp|svg|bmp|tif|tiff)$'"),
                    'pdf' => $q->where('file_type', 'application/pdf')->orWhereRaw("LOWER(name) LIKE '%.pdf'"),
                    'design' => $q->whereRaw("LOWER(name) REGEXP '\\.(ai|eps|psd|indd)$'"),
                    default => null,
                };
            });

        $totalCount = (clone $query)->count();
        $galleryItems = $query->latest()->paginate(24)->withQueryString();
        $categories = ArtworkCategory::query()->orderBy('name')->get(['id', 'name']);
        $tags = ArtworkTag::query()->orderBy('name')->get(['id', 'name']);
        $fileGroups = $this->settings->fileGroups();
        $stockCodeFilter = trim((string) request('stock_code', ''));
        $stockRevisionGroups = $this->buildStockRevisionGroups($stockCodeFilter);

        return view('admin.artwork-gallery.index', compact(
            'galleryItems',
            'categories',
            'tags',
            'totalCount',
            'fileGroups',
            'stockRevisionGroups',
            'stockCodeFilter',
        ));
    }

    public function manage(): View
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('gallery', 'manage'),
            403
        );

        $categories = ArtworkCategory::query()
            ->withCount('galleryItems')
            ->orderBy('name')
            ->get();

        $tags = ArtworkTag::query()
            ->withCount('galleryItems')
            ->orderBy('name')
            ->get();

        return view('admin.artwork-gallery.manage', compact('categories', 'tags'));
    }

    public function storeCategory(): JsonResponse|RedirectResponse
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('gallery', 'manage'),
            403
        );

        $validated = request()->validateWithBag('storeCategory', [
            'name' => ['required', 'string', 'max:120'],
        ]);

        $category = $this->categories->findOrCreate($validated['name']);

        if (request()->expectsJson()) {
            return response()->json(['id' => $category->id, 'name' => $category->name]);
        }

        if (request()->boolean('_redirect_back')) {
            return back()->with('success', 'Kategori kaydedildi.');
        }

        return redirect()
            ->route('admin.artwork-gallery.manage')
            ->with('success', 'Kategori kaydedildi.');
    }

    public function destroyCategory(ArtworkCategory $category): RedirectResponse
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('gallery', 'manage'),
            403
        );

        $category->delete();

        return redirect()
            ->route('admin.artwork-gallery.manage')
            ->with('success', '"' . $category->name . '" kategorisi silindi.');
    }

    public function storeTag(): JsonResponse|RedirectResponse
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('gallery', 'manage'),
            403
        );

        $validated = request()->validateWithBag('storeTag', [
            'name' => ['required', 'string', 'max:120', 'unique:artwork_tags,name'],
        ]);

        $tag = ArtworkTag::create($validated);

        if (request()->expectsJson()) {
            return response()->json(['id' => $tag->id, 'name' => $tag->name]);
        }

        if (request()->boolean('_redirect_back')) {
            return back()->with('success', 'Etiket eklendi.');
        }

        return redirect()
            ->route('admin.artwork-gallery.manage')
            ->with('success', 'Etiket eklendi.');
    }

    public function destroyTag(ArtworkTag $tag): RedirectResponse
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('gallery', 'manage'),
            403
        );

        $tag->delete();

        return redirect()
            ->route('admin.artwork-gallery.manage')
            ->with('success', '"' . $tag->name . '" etiketi silindi.');
    }

    public function edit(ArtworkGallery $artworkGallery): View
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('gallery', 'manage'),
            403
        );

        $artworkGallery->load([
            'category:id,name',
            'stockCard.category:id,name',
            'tags:id,name',
            'uploadedBy:id,name',
            'usages.supplier:id,name',
            'usages.order:id,order_no,order_date',
            'usages.line:id,product_code,line_no',
        ])->loadCount('usages')->loadMax('usages', 'used_at');

        $categories = ArtworkCategory::query()->orderBy('name')->get(['id', 'name']);
        $tags = ArtworkTag::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.artwork-gallery.edit', compact('artworkGallery', 'categories', 'tags'));
    }

    public function destroy(ArtworkGallery $artworkGallery): RedirectResponse
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('gallery', 'manage'),
            403
        );

        $name = $artworkGallery->name;

        if ($artworkGallery->file_path) {
            Storage::disk($artworkGallery->file_disk ?? 'public')->delete($artworkGallery->file_path);
        }

        $this->audit->log('artwork.gallery.delete', $artworkGallery, [
            'name' => $name,
            'stock_code' => $artworkGallery->stock_code,
        ]);

        $artworkGallery->delete();

        return redirect()
            ->route('admin.artwork-gallery.index')
            ->with('success', '"' . $name . '" galeriden silindi.');
    }

    public function update(ArtworkGalleryUpdateRequest $request, ArtworkGallery $artworkGallery): RedirectResponse
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('gallery', 'manage'),
            403
        );

        $stockCard = null;

        if (filled($request->input('stock_code'))) {
            $stockCard = StockCard::query()
                ->where('stock_code', mb_strtoupper(trim((string) $request->input('stock_code'))))
                ->first();
        }

        $artworkGallery->update([
            'name' => $request->input('name'),
            'stock_code' => $stockCard?->stock_code,
            'stock_card_id' => $stockCard?->id,
            'category_id' => $stockCard?->category_id ?? $artworkGallery->category_id,
            'revision_note' => $request->input('revision_note'),
        ]);
        $artworkGallery->tags()->sync($request->input('tag_ids', []));

        $this->audit->log('artwork.gallery.update', $artworkGallery->fresh('stockCard'), [
            'stock_code' => $artworkGallery->stock_code,
            'category_id' => $artworkGallery->category_id,
            'tag_ids' => $artworkGallery->tags()->pluck('artwork_tags.id')->all(),
        ]);

        return redirect()
            ->route('admin.artwork-gallery.edit', $artworkGallery)
            ->with('success', 'Artwork galerisi kaydı güncellendi.');
    }

    private function buildStockRevisionGroups(string $stockCodeFilter)
    {
        if ($stockCodeFilter === '') {
            return collect();
        }

        $normalized = mb_strtoupper($stockCodeFilter);

        return ArtworkRevision::query()
            ->with([
                'uploadedBy:id,name',
                'galleryItem:id,stock_code,stock_card_id,category_id,name',
                'galleryItem.stockCard:id,stock_code,stock_name,category_id',
                'galleryItem.stockCard.category:id,name',
                'artwork.orderLine.purchaseOrder:id,order_no',
                'artwork.orderLine:id,purchase_order_id,line_no,product_code',
            ])
            ->whereHas('galleryItem', fn ($query) => $query->where('stock_code', 'like', '%' . $normalized . '%'))
            ->orderByDesc('revision_no')
            ->get()
            ->groupBy(fn (ArtworkRevision $revision) => $revision->galleryItem?->stock_code ?: 'Belirsiz');
    }
}
