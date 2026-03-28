@extends('layouts.app')
@section('title', 'Artwork Galerisi')
@section('page-title', 'Artwork Galerisi')
@section('page-subtitle', 'Tekrar kullanılabilir artwork dosyalarını arayın, filtreleyin ve yönetin.')

@php
$typeFilter = request('type', '');
// Dinamik gruplar: ayarlardan gelir, 'Tümü' her zaman başta
$typeTabs = ['' => ['label' => 'Tümü', 'icon' => null]];
foreach ($fileGroups as $grp) {
    $typeTabs[$grp['key']] = ['label' => $grp['label'], 'icon' => $grp['key']];
}
@endphp

@php
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
                <div class="w-full sm:min-w-[180px] flex-1">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input name="search" value="{{ request('search') }}"
                               placeholder="Dosya adı ile ara…"
                               class="input pl-9"
                               autocomplete="off"
                               id="gallery-search-input">
                    </div>
                </div>

                {{-- Stok kodu --}}
                <div class="w-full sm:w-44">
                    <div class="relative">
                        <input name="stock_code" value="{{ request('stock_code') }}"
                               placeholder="Stok kodu ara…"
                               class="input"
                               autocomplete="off"
                               id="gallery-stock-input">
                    </div>
                </div>

                {{-- Kategori --}}
                <div class="w-full sm:w-44">
                    <select name="category_id" class="input">
                        <option value="">Tüm kategoriler</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Etiket --}}
                <div class="w-full sm:w-44">
                    <select name="tag_id" class="input" id="gallery-tag-select">
                        <option value="">Tüm etiketler</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" @selected(request('tag_id') == $tag->id)>{{ $tag->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Filtrele</button>

                @if(request()->hasAny(['search','stock_code','category_id','tag_id','type']))
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

    {{-- ── Galeri tablo ── --}}
    @if($galleryItems->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white/60 py-20">
            <svg class="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-10h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="mt-4 text-sm font-medium text-slate-500">Filtrelere uygun dosya bulunamadı</p>
            <a href="{{ route('admin.artwork-gallery.index') }}" class="mt-3 text-xs text-brand-600 hover:underline">Filtreleri temizle</a>
        </div>
    @else
        <div class="card overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left">
                        <th class="px-4 py-3 font-medium text-slate-600 w-16">Format</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Dosya Adı</th>
                        <th class="px-4 py-3 font-medium text-slate-600 w-28">Stok Kodu</th>
                        <th class="px-4 py-3 font-medium text-slate-600 w-32">Kategori</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Etiketler</th>
                        <th class="px-4 py-3 font-medium text-slate-600 w-24 text-right">Boyut</th>
                        <th class="px-4 py-3 font-medium text-slate-600 w-32">Yükleyen</th>
                        <th class="px-4 py-3 font-medium text-slate-600 w-24">Tarih</th>
                        <th class="px-4 py-3 font-medium text-slate-600 w-16 text-center">Kullanım</th>
                        <th class="px-4 py-3 w-20"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($galleryItems as $item)
                        @php
                            $ext = strtoupper(pathinfo($item->file_path ?: $item->name, PATHINFO_EXTENSION));
                            $badgeClass = $fileTypeColors[$ext] ?? 'bg-slate-100 text-slate-600 border border-slate-200';
                            $formatLabels = [
                                'PDF'  => 'Adobe PDF',
                                'AI'   => 'Illustrator',
                                'EPS'  => 'EPS',
                                'PSD'  => 'Photoshop',
                                'INDD' => 'InDesign',
                                'PNG'  => 'PNG Görsel',
                                'JPG'  => 'JPEG Görsel',
                                'JPEG' => 'JPEG Görsel',
                                'SVG'  => 'SVG Vektör',
                                'WEBP' => 'WebP Görsel',
                                'ZIP'  => 'ZIP Arşiv',
                            ];
                            $formatLabel = $formatLabels[$ext] ?? ($ext ?: '—');
                        @endphp
                        <tr class="hover:bg-slate-50/60 group">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold tracking-wide {{ $badgeClass }}">
                                    {{ $formatLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 max-w-xs">
                                <p class="truncate font-medium text-slate-900" title="{{ $item->display_name }}">{{ $item->display_name }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ $item->name }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($item->stock_code)
                                    <span class="font-mono font-semibold text-slate-800">{{ $item->stock_code }}</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                {{ $item->category?->display_name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($item->tags->take(4) as $tag)
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600">{{ $tag->display_name }}</span>
                                    @endforeach
                                    @if($item->tags->count() > 4)
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-400">+{{ $item->tags->count() - 4 }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500 text-right whitespace-nowrap">{{ $item->file_size_formatted }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600 truncate max-w-[128px]">{{ $item->uploadedBy?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ $item->created_at->format('d.m.Y') }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($item->usage_count > 0)
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700">
                                        <svg class="h-3 w-3 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        {{ $item->usage_count }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-300">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button type="button"
                                            data-dialog-open="gallery-preview-{{ $item->id }}"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition"
                                            title="Önizle">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    <a href="{{ route('admin.artwork-gallery.edit', $item) }}"
                                       class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-brand-700 transition"
                                       title="Düzenle">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        @include('artwork-gallery.partials.preview-dialog', [
                            'artworkGallery' => $item,
                            'dialogId' => 'gallery-preview-' . $item->id,
                        ])
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pt-2">
            {{ $galleryItems->links() }}
        </div>
    @endif

@push('scripts')
<script>
(function () {
    const form   = document.getElementById('gallery-filter-form');
    const search = document.getElementById('gallery-search-input');
    const stockInput = document.getElementById('gallery-stock-input');
    const tagSel = document.getElementById('gallery-tag-select');
    let   timer  = null;

    function autoSubmit() {
        clearTimeout(timer);
        timer = setTimeout(() => form.submit(), 400);
    }

    if (search) search.addEventListener('input', autoSubmit);
    if (stockInput) stockInput.addEventListener('input', autoSubmit);
    if (tagSel) tagSel.addEventListener('change', () => form.submit());

    // Also auto-submit category change
    const catSel = form ? form.querySelector('select[name="category_id"]') : null;
    if (catSel) catSel.addEventListener('change', () => form.submit());
})();
</script>
@endpush
</div>
@endsection
