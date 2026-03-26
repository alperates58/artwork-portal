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
        $galleryItems = ArtworkGallery::query()
            ->with(['category:id,name', 'tags:id,name', 'uploadedBy:id,name'])
            ->withCount('usages')
            ->withMax('usages', 'used_at')
            ->when(request('search'), fn ($query, $search) => $query->where('name', 'like', '%' . $search . '%'))
            ->when(request('category_id'), fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when(request('tag_id'), fn ($query, $tagId) => $query->whereHas('tags', fn ($tagQuery) => $tagQuery->where('artwork_tags.id', $tagId)))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $categories = ArtworkCategory::query()->orderBy('name')->get(['id', 'name']);
        $tags = ArtworkTag::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.artwork-gallery.index', compact('galleryItems', 'categories', 'tags'));
    }

    public function storeCategory(): RedirectResponse
    {
        $validated = request()->validateWithBag('storeCategory', [
            'name' => ['required', 'string', 'max:120', 'unique:artwork_categories,name'],
        ]);

        ArtworkCategory::create($validated);

        return redirect()
            ->route('admin.artwork-gallery.index')
            ->with('success', 'Artwork kategorisi eklendi.');
    }

    public function storeTag(): RedirectResponse
    {
        $validated = request()->validateWithBag('storeTag', [
            'name' => ['required', 'string', 'max:120', 'unique:artwork_tags,name'],
        ]);

        ArtworkTag::create($validated);

        return redirect()
            ->route('admin.artwork-gallery.index')
            ->with('success', 'Artwork etiketi eklendi.');
    }

    public function edit(ArtworkGallery $artworkGallery): View
    {
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
        $artworkGallery->update($request->safe()->only(['name', 'category_id', 'revision_note']));
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
