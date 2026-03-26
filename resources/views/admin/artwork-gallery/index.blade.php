@extends('layouts.app')
@section('title', 'Artwork Galerisi')
@section('page-title', 'Artwork Galerisi')
@section('page-subtitle', 'Tekrar kullanilabilir artwork kayitlarini arayin, filtreleyin ve yonetin.')

@section('content')
<div class="space-y-6">
    <section class="grid gap-5 xl:grid-cols-2">
        <div class="card p-5">
            <h2 class="text-sm font-semibold text-slate-900">Kategori Ekle</h2>
            <form method="POST" action="{{ route('admin.artwork-gallery.categories.store') }}" class="mt-4 flex gap-3">
                @csrf
                <input name="name" class="input flex-1" placeholder="Kategori adi">
                <button type="submit" class="btn-secondary">Ekle</button>
            </form>
        </div>
        <div class="card p-5">
            <h2 class="text-sm font-semibold text-slate-900">Etiket Ekle</h2>
            <form method="POST" action="{{ route('admin.artwork-gallery.tags.store') }}" class="mt-4 flex gap-3">
                @csrf
                <input name="name" class="input flex-1" placeholder="Etiket adi">
                <button type="submit" class="btn-secondary">Ekle</button>
            </form>
        </div>
    </section>

    <section class="card p-5">
        <form method="GET" action="{{ route('admin.artwork-gallery.index') }}" class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="label" for="search">Arama</label>
                <input id="search" name="search" value="{{ request('search') }}" class="input" placeholder="Artwork adi">
            </div>
            <div>
                <label class="label" for="category_id">Kategori</label>
                <select id="category_id" name="category_id" class="input">
                    <option value="">Tum kategoriler</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="tag_id">Etiket</label>
                <select id="tag_id" name="tag_id" class="input">
                    <option value="">Tum etiketler</option>
                    @foreach($tags as $tag)
                        <option value="{{ $tag->id }}" @selected((string) request('tag_id') === (string) $tag->id)>{{ $tag->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-primary w-full justify-center">Filtrele</button>
            </div>
        </form>
    </section>

    <section class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Galeri Kayitlari</h2>
                <p class="mt-1 text-xs text-slate-500">{{ $galleryItems->total() }} kayit bulundu</p>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($galleryItems as $item)
                <div class="px-5 py-4 flex flex-col gap-3 xl:flex-row xl:items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-sm font-mono text-slate-500">
                        {{ $item->extension }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $item->name }}</p>
                            <span class="badge badge-gray">{{ $item->category?->name ?? 'Kategorisiz' }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $item->file_size_formatted }} · {{ $item->uploadedBy->name }} · {{ $item->created_at->format('d.m.Y H:i') }} · Kullanim: {{ $item->usages_count }}
                        </p>
                        @if($item->tags->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach($item->tags as $tag)
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600">{{ $tag->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.artwork-gallery.edit', $item) }}" class="btn-secondary">Duzenle</a>
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-sm text-slate-500">Galeri kaydi bulunamadi.</div>
            @endforelse
        </div>

        <div class="px-5 py-4 border-t border-slate-100">
            {{ $galleryItems->links() }}
        </div>
    </section>
</div>
@endsection
