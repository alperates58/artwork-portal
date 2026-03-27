<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArtworkGalleryUpdateRequest;
use App\Models\ArtworkCategory;
use App\Models\ArtworkGallery;
use App\Models\ArtworkTag;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ArtworkGalleryController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(): View
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('gallery', 'view'),
            403
        );

        $query = ArtworkGallery::query()
            ->with(['category:id,name', 'tags:id,name', 'uploadedBy:id,name'])
            ->withCount('usages')
            ->withMax('usages', 'used_at')
            ->when(request('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when(request('stock_code'), fn ($q, $s) => $q->where('stock_code', 'like', "%{$s}%"))
            ->when(request('category_id'), fn ($q, $v) => $q->where('category_id', $v))
            ->when(request('tag_id'), fn ($q, $v) => $q->whereHas('tags', fn ($t) => $t->where('artwork_tags.id', $v)))
            ->when(request('type'), function ($q, $type) {
                match ($type) {
                    'image'  => $q->whereIn('file_type', ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'])
                                  ->orWhereRaw("LOWER(name) REGEXP '\\\\.(jpg|jpeg|png|gif|webp|svg|bmp|tif|tiff)$'"),
                    'pdf'    => $q->where('file_type', 'application/pdf')->orWhereRaw("LOWER(name) LIKE '%.pdf'"),
                    'design' => $q->whereRaw("LOWER(name) REGEXP '\\\\.(ai|eps|psd|indd)$'"),
                    default  => null,
                };
            });

        $totalCount = (clone $query)->count();

        $galleryItems = $query->latest()->paginate(24)->withQueryString();

        $categories = ArtworkCategory::query()->orderBy('name')->get(['id', 'name']);
        $tags       = ArtworkTag::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.artwork-gallery.index', compact('galleryItems', 'categories', 'tags', 'totalCount'));
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

    public function storeCategory(): RedirectResponse
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('gallery', 'manage'),
            403
        );

        $validated = request()->validateWithBag('storeCategory', [
            'name' => ['required', 'string', 'max:120', 'unique:artwork_categories,name'],
        ]);

        ArtworkCategory::create($validated);

        if (request()->boolean('_redirect_back')) {
            return back()->with('success', 'Kategori eklendi.');
        }

        return redirect()
            ->route('admin.artwork-gallery.manage')
            ->with('success', 'Kategori eklendi.');
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

    public function storeTag(): RedirectResponse
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('gallery', 'manage'),
            403
        );

        $validated = request()->validateWithBag('storeTag', [
            'name' => ['required', 'string', 'max:120', 'unique:artwork_tags,name'],
        ]);

        ArtworkTag::create($validated);

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

    public function update(ArtworkGalleryUpdateRequest $request, ArtworkGallery $artworkGallery): RedirectResponse
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('gallery', 'manage'),
            403
        );

        $artworkGallery->update($request->safe()->only(['name', 'stock_code', 'category_id', 'revision_note']));
        $artworkGallery->tags()->sync($request->input('tag_ids', []));

        $this->audit->log('artwork.gallery.update', $artworkGallery, [
            'category_id' => $artworkGallery->category_id,
            'tag_ids' => $artworkGallery->tags()->pluck('artwork_tags.id')->all(),
        ]);

        return redirect()
            ->route('admin.artwork-gallery.edit', $artworkGallery)
            ->with('success', 'Artwork galerisi kaydı güncellendi.');
    }
}
