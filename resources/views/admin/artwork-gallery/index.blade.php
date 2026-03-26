@extends('layouts.app')
@section('title', 'Artwork Galerisi')
@section('page-title', 'Artwork Galerisi')
@section('page-subtitle', 'Tekrar kullanılabilir artwork kayıtlarını arayın, filtreleyin ve yönetin.')

@section('content')
<div class="space-y-6">
    <section class="grid gap-5 xl:grid-cols-2">
        <div class="card p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Kategori ekle</h2>
                    <p class="mt-1 text-xs text-slate-500">Yeni artwork yüklemelerinde ve galeri filtrelerinde kullanılacak kategori oluşturun.</p>
                </div>
                <span class="badge badge-gray">Yönetim</span>
            </div>
            <form method="POST" action="{{ route('admin.artwork-gallery.categories.store') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="label" for="category_name">Kategori adı</label>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input id="category_name" name="name" class="input flex-1" placeholder="Örn. Kutu, Etiket, Ambalaj" value="{{ old('name') }}">
                        <button type="submit" class="btn-primary justify-center sm:px-5">Kategori ekle</button>
                    </div>
                    @error('name', 'storeCategory')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </form>
        </div>

        <div class="card p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Etiket ekle</h2>
                    <p class="mt-1 text-xs text-slate-500">Galeride hızlı arama ve tekrar kullanım için kısa etiketler oluşturun.</p>
                </div>
                <span class="badge badge-gray">Yönetim</span>
            </div>
            <form method="POST" action="{{ route('admin.artwork-gallery.tags.store') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="label" for="tag_name">Etiket adı</label>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input id="tag_name" name="name" class="input flex-1" placeholder="Örn. Onaylı, Sezonluk, Acil" value="{{ old('name') }}">
                        <button type="submit" class="btn-primary justify-center sm:px-5">Etiket ekle</button>
                    </div>
                    @error('name', 'storeTag')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </form>
        </div>
    </section>

    <section class="card p-5">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Filtreler</h2>
                <p class="mt-1 text-xs text-slate-500">Arama, kategori ve etiket filtresi birlikte çalışır.</p>
            </div>
            <a href="{{ route('admin.artwork-gallery.index') }}" class="btn-secondary">Filtreleri temizle</a>
        </div>

        <form method="GET" action="{{ route('admin.artwork-gallery.index') }}" class="mt-4 grid gap-4 md:grid-cols-4">
            <div>
                <label class="label" for="search">Arama</label>
                <input id="search" name="search" value="{{ request('search') }}" class="input" placeholder="Artwork adı veya dosya adı">
            </div>
            <div>
                <label class="label" for="category_id">Kategori</label>
                <select id="category_id" name="category_id" class="input">
                    <option value="">Tüm kategoriler</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="tag_id">Etiket</label>
                <select id="tag_id" name="tag_id" class="input">
                    <option value="">Tüm etiketler</option>
                    @foreach($tags as $tag)
                        <option value="{{ $tag->id }}" @selected((string) request('tag_id') === (string) $tag->id)>{{ $tag->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-primary w-full justify-center">Filtrele</button>
            </div>
        </form>
    </section>

    <section class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Galeri kayıtları</h2>
                <p class="mt-1 text-xs text-slate-500">{{ $galleryItems->total() }} kayıt bulundu</p>
            </div>
        </div>

        @if($galleryItems->isEmpty())
            <div class="card px-5 py-10 text-sm text-slate-500">Filtrelere uygun galeri kaydı bulunamadı.</div>
        @else
            <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                @foreach($galleryItems as $item)
                    <article class="card p-5">
                        <div class="flex items-start gap-4">
                            @include('artwork-gallery.partials.file-visual', [
                                'artworkGallery' => $item,
                                'sizeClass' => 'h-20 w-20',
                            ])

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate text-sm font-semibold text-slate-900">{{ $item->display_name }}</h3>
                                    <span class="badge badge-gray">{{ $item->file_type_display }}</span>
                                    <span class="badge badge-gray">{{ $item->category?->display_name ?? 'Kategorisiz' }}</span>
                                </div>

                                <dl class="mt-3 grid grid-cols-2 gap-3 text-xs text-slate-500">
                                    <div>
                                        <dt class="font-medium text-slate-400">Boyut</dt>
                                        <dd class="mt-1 text-slate-700">{{ $item->file_size_formatted }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-slate-400">Kullanım</dt>
                                        <dd class="mt-1 text-slate-700">{{ $item->usage_count }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-slate-400">Yükleyen</dt>
                                        <dd class="mt-1 truncate text-slate-700">{{ $item->uploadedBy->name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-slate-400">Son kullanım</dt>
                                        <dd class="mt-1 text-slate-700">{{ $item->last_used_at ? \Illuminate\Support\Carbon::parse($item->last_used_at)->format('d.m.Y') : 'Henüz yok' }}</dd>
                                    </div>
                                    <div class="col-span-2">
                                        <dt class="font-medium text-slate-400">Oluşturulma</dt>
                                        <dd class="mt-1 text-slate-700">{{ $item->created_at->format('d.m.Y H:i') }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        @if($item->tags->isNotEmpty())
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach($item->tags as $tag)
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">{{ $tag->display_name }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if($item->display_revision_note)
                            <p class="mt-4 text-sm text-slate-500">{{ $item->display_revision_note }}</p>
                        @endif

                        <div class="mt-5 flex flex-wrap gap-2">
                            <button type="button" class="btn-secondary" data-dialog-open="gallery-preview-{{ $item->id }}">Görüntüle</button>
                            <a href="{{ route('admin.artwork-gallery.edit', $item) }}" class="btn-primary">Düzenle</a>
                        </div>
                    </article>

                    @include('artwork-gallery.partials.preview-dialog', [
                        'artworkGallery' => $item,
                        'dialogId' => 'gallery-preview-' . $item->id,
                    ])
                @endforeach
            </div>
        @endif

        <div class="px-1 pt-2">
            {{ $galleryItems->links() }}
        </div>
    </section>
</div>
@endsection
