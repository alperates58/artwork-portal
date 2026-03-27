@extends('layouts.app')
@section('title', 'Artwork Galerisi')
@section('page-title', 'Artwork Galerisi')
@section('page-subtitle', 'Tekrar kullanılabilir artwork dosyalarını arayın, filtreleyin ve yönetin.')

@php
$typeFilter = request('type', '');
$typeTabs = [
    ''       => ['label' => 'Tümü',    'icon' => null],
    'image'  => ['label' => 'Görseller', 'icon' => 'image'],
    'pdf'    => ['label' => 'PDF',      'icon' => 'pdf'],
    'design' => ['label' => 'Tasarım',  'icon' => 'design'],
];
$fileTypeColors = [
    'PDF'  => 'bg-red-50 text-red-700 border border-red-200',
    'AI'   => 'bg-orange-50 text-orange-700 border border-orange-200',
    'EPS'  => 'bg-orange-50 text-orange-700 border border-orange-200',
    'PSD'  => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
    'INDD' => 'bg-purple-50 text-purple-700 border border-purple-200',
    'PNG'  => 'bg-sky-50 text-sky-700 border border-sky-200',
    'JPG'  => 'bg-amber-50 text-amber-700 border border-amber-200',
    'JPEG' => 'bg-amber-50 text-amber-700 border border-amber-200',
    'SVG'  => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
    'WEBP' => 'bg-teal-50 text-teal-700 border border-teal-200',
    'ZIP'  => 'bg-slate-100 text-slate-600 border border-slate-200',
];
$fileTypeBg = [
    'PDF'  => 'bg-red-50',
    'AI'   => 'bg-orange-50',
    'EPS'  => 'bg-orange-50',
    'PSD'  => 'bg-indigo-50',
    'INDD' => 'bg-purple-50',
    'PNG'  => 'bg-sky-50',
    'JPG'  => 'bg-amber-50',
    'JPEG' => 'bg-amber-50',
    'SVG'  => 'bg-emerald-50',
    'ZIP'  => 'bg-slate-100',
];
$fileTypeText = [
    'PDF'  => 'text-red-400',
    'AI'   => 'text-orange-400',
    'EPS'  => 'text-orange-400',
    'PSD'  => 'text-indigo-400',
    'INDD' => 'text-purple-400',
    'PNG'  => 'text-sky-400',
    'JPG'  => 'text-amber-400',
    'JPEG' => 'text-amber-400',
    'SVG'  => 'text-emerald-400',
    'ZIP'  => 'text-slate-400',
];
@endphp

@section('content')
<div class="space-y-5">

    {{-- ── Filtre çubuğu ── --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-4 shadow-[0_4px_16px_rgba(15,23,42,0.04)]">
        <form method="GET" action="{{ route('admin.artwork-gallery.index') }}" id="gallery-filter-form">
            <div class="flex flex-wrap items-end gap-3">
                {{-- Arama --}}
                <div class="min-w-[220px] flex-1">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input name="search" value="{{ request('search') }}"
                               placeholder="Dosya adı ile ara…"
                               class="input pl-9"
                               autocomplete="off">
                    </div>
                </div>

                {{-- Kategori --}}
                <div class="w-44">
                    <select name="category_id" class="input">
                        <option value="">Tüm kategoriler</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Etiket --}}
                <div class="w-44">
                    <select name="tag_id" class="input">
                        <option value="">Tüm etiketler</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" @selected(request('tag_id') == $tag->id)>{{ $tag->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Filtrele</button>

                @if(request()->hasAny(['search','category_id','tag_id','type']))
                    <a href="{{ route('admin.artwork-gallery.index') }}" class="btn btn-secondary">Temizle</a>
                @endif
            </div>

            {{-- Dosya tipi sekmeleri --}}
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($typeTabs as $tabKey => $tab)
                    <a href="{{ route('admin.artwork-gallery.index', array_merge(request()->except(['type','page']), $tabKey !== '' ? ['type' => $tabKey] : [])) }}"
                       class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-semibold transition
                              {{ $typeFilter === $tabKey
                                  ? 'bg-brand-500 text-white shadow-sm'
                                  : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        @if($tab['icon'] === 'pdf')
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        @elseif($tab['icon'] === 'image')
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-10h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        @elseif($tab['icon'] === 'design')
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/></svg>
                        @endif
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </div>
        </form>
    </div>

    {{-- ── Sonuç başlığı ── --}}
    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-slate-500">
            <span class="font-semibold text-slate-800">{{ $galleryItems->total() }}</span> dosya bulundu
            @if($galleryItems->total() !== $totalCount)
                <span class="text-slate-400"> / toplam {{ $totalCount }}</span>
            @endif
        </p>
        <a href="{{ route('admin.artwork-gallery.manage') }}" class="btn btn-secondary text-xs">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
            Kategori & Etiket Yönetimi
        </a>
    </div>

    {{-- ── Galeri grid ── --}}
    @if($galleryItems->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white/60 py-20">
            <svg class="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-10h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="mt-4 text-sm font-medium text-slate-500">Filtrelere uygun dosya bulunamadı</p>
            <a href="{{ route('admin.artwork-gallery.index') }}" class="mt-3 text-xs text-brand-600 hover:underline">Filtreleri temizle</a>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
            @foreach($galleryItems as $item)
                @php
                    $ext = strtoupper(pathinfo($item->name, PATHINFO_EXTENSION));
                    $badgeClass = $fileTypeColors[$ext] ?? 'bg-slate-100 text-slate-600 border border-slate-200';
                    $iconBg     = $fileTypeBg[$ext] ?? 'bg-slate-100';
                    $iconText   = $fileTypeText[$ext] ?? 'text-slate-400';
                @endphp
                <article class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_2px_8px_rgba(15,23,42,0.05)] transition hover:border-brand-200 hover:shadow-[0_8px_24px_rgba(244,154,11,0.10)]">

                    {{-- Thumbnail --}}
                    <div class="relative aspect-[4/3] w-full overflow-hidden {{ $item->is_image ? 'bg-slate-100' : $iconBg }}">
                        @if($item->is_image)
                            <img src="{{ route('artworks.gallery.preview', $item) }}"
                                 alt="{{ $item->display_name }}"
                                 class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                 loading="lazy">
                        @else
                            <div class="flex h-full w-full flex-col items-center justify-center gap-2">
                                @if($ext === 'PDF')
                                    <svg class="h-14 w-14 {{ $iconText }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
                                        <path d="M7 3.75h7l4 4V20.25A1.75 1.75 0 0 1 16.25 22h-8.5A1.75 1.75 0 0 1 6 20.25v-14.5A1.75 1.75 0 0 1 7.75 4Z"/>
                                        <path d="M14 3.75v4h4"/><path d="M8 15.25h8M8 18h5"/>
                                    </svg>
                                @elseif(in_array($ext, ['AI','EPS','PSD','INDD']))
                                    <svg class="h-14 w-14 {{ $iconText }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
                                        <path d="M4.75 6.75A2.75 2.75 0 0 1 7.5 4h9A2.75 2.75 0 0 1 19.25 6.75v10.5A2.75 2.75 0 0 1 16.5 20h-9a2.75 2.75 0 0 1-2.75-2.75Z"/>
                                        <path d="M8 16l2.5-3 2 2 3.5-5"/><path d="M8 9.5h.01"/>
                                    </svg>
                                @elseif($ext === 'ZIP')
                                    <svg class="h-14 w-14 {{ $iconText }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
                                        <path d="M5 8a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2H7a2 2 0 01-2-2V8z"/>
                                        <path d="M12 10v4m0 0v2m0-2h2m-2 0h-2M10 6V4m4 0v2"/>
                                    </svg>
                                @else
                                    <svg class="h-14 w-14 {{ $iconText }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
                                        <path d="M7 3.75h7l4 4V20.25A1.75 1.75 0 0 1 16.25 22h-8.5A1.75 1.75 0 0 1 6 20.25v-14.5A1.75 1.75 0 0 1 7.75 4Z"/>
                                        <path d="M14 3.75v4h4"/>
                                    </svg>
                                @endif
                                <span class="text-lg font-bold {{ $iconText }} opacity-60">{{ $ext }}</span>
                            </div>
                        @endif

                        {{-- Hover overlay --}}
                        <div class="absolute inset-0 flex items-center justify-center gap-2 bg-slate-900/50 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                            <button type="button"
                                    data-dialog-open="gallery-preview-{{ $item->id }}"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/90 text-slate-700 transition hover:bg-white">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                            <a href="{{ route('admin.artwork-gallery.edit', $item) }}"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/90 text-slate-700 transition hover:bg-white">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                        </div>

                        {{-- File type badge --}}
                        <span class="absolute left-2 top-2 inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold tracking-wide {{ $badgeClass }}">
                            {{ $ext ?: '—' }}
                        </span>

                        {{-- Usage count --}}
                        @if($item->usage_count > 0)
                            <span class="absolute right-2 top-2 inline-flex items-center gap-1 rounded-lg bg-white/90 px-2 py-0.5 text-[10px] font-semibold text-slate-700">
                                <svg class="h-3 w-3 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                {{ $item->usage_count }}
                            </span>
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="flex flex-1 flex-col p-3">
                        <p class="truncate text-sm font-semibold text-slate-900" title="{{ $item->display_name }}">
                            {{ $item->display_name }}
                        </p>

                        <div class="mt-1 flex items-center gap-2 text-xs text-slate-400">
                            <span>{{ $item->file_size_formatted }}</span>
                            @if($item->category)
                                <span class="text-slate-300">·</span>
                                <span class="truncate">{{ $item->category->display_name }}</span>
                            @endif
                        </div>

                        @if($item->tags->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach($item->tags->take(3) as $tag)
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600">
                                        {{ $tag->display_name }}
                                    </span>
                                @endforeach
                                @if($item->tags->count() > 3)
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-400">+{{ $item->tags->count() - 3 }}</span>
                                @endif
                            </div>
                        @endif

                        <div class="mt-auto pt-2 text-[10px] text-slate-400">
                            {{ $item->created_at->format('d.m.Y') }}
                            @if($item->uploadedBy)
                                · {{ $item->uploadedBy->name }}
                            @endif
                        </div>
                    </div>
                </article>

                @include('artwork-gallery.partials.preview-dialog', [
                    'artworkGallery' => $item,
                    'dialogId' => 'gallery-preview-' . $item->id,
                ])
            @endforeach
        </div>

        <div class="pt-2">
            {{ $galleryItems->links() }}
        </div>
    @endif
</div>
@endsection
