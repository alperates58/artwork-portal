@extends('layouts.app')
@section('title', 'Artwork Galerisi')
@section('page-title', 'Artwork Galerisi')
@section('page-subtitle', 'Stok kodu, stok kartı ve revizyon geçmişi üzerinden tekrar kullanılabilir dosyaları yönetin.')

@php
    $typeFilter = request('type', '');
    $typeTabs = ['' => ['label' => 'Tümü', 'icon' => null]];
    foreach ($fileGroups as $group) {
        $typeTabs[$group['key']] = ['label' => $group['label'], 'icon' => $group['key']];
    }

    $fileTypeColors = [
        'PDF' => 'bg-red-50 text-red-700 border border-red-200',
        'AI' => 'bg-orange-50 text-orange-700 border border-orange-200',
        'EPS' => 'bg-orange-50 text-orange-700 border border-orange-200',
        'PSD' => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
        'INDD' => 'bg-purple-50 text-purple-700 border border-purple-200',
        'PNG' => 'bg-sky-50 text-sky-700 border border-sky-200',
        'JPG' => 'bg-amber-50 text-amber-700 border border-amber-200',
        'JPEG' => 'bg-amber-50 text-amber-700 border border-amber-200',
        'SVG' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        'WEBP' => 'bg-teal-50 text-teal-700 border border-teal-200',
        'ZIP' => 'bg-slate-100 text-slate-600 border border-slate-200',
    ];
@endphp

@section('content')
<div class="space-y-5">
    <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-4 shadow-[0_4px_16px_rgba(15,23,42,0.04)]">
        <form method="GET" action="{{ route('admin.artwork-gallery.index') }}" id="gallery-filter-form">
            <div class="flex flex-wrap items-end gap-3">
                <div class="w-full flex-1 sm:min-w-[180px]">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input name="search" value="{{ request('search') }}" placeholder="Dosya adı ile ara..." class="input pl-9" autocomplete="off" id="gallery-search-input">
                    </div>
                </div>

                <div class="w-full sm:w-48">
                    <input name="stock_code" value="{{ request('stock_code') }}" placeholder="Stok kodu ara..." class="input font-mono" autocomplete="off" id="gallery-stock-input">
                </div>

                <div class="w-full sm:w-48">
                    <select name="category_id" class="input">
                        <option value="">Tüm kategoriler</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full sm:w-48">
                    <select name="tag_id" class="input" id="gallery-tag-select">
                        <option value="">Tüm etiketler</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" @selected((string) request('tag_id') === (string) $tag->id)>{{ $tag->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Filtrele</button>
                @if(request()->hasAny(['search', 'stock_code', 'category_id', 'tag_id', 'type']))
                    <a href="{{ route('admin.artwork-gallery.index') }}" class="btn btn-secondary">Temizle</a>
                @endif
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($typeTabs as $tabKey => $tab)
                    <a href="{{ route('admin.artwork-gallery.index', array_merge(request()->except(['type', 'page']), $tabKey !== '' ? ['type' => $tabKey] : [])) }}"
                       class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-semibold transition {{ $typeFilter === $tabKey ? 'bg-brand-500 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </div>
        </form>
    </div>

    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-slate-500">
            <span class="font-semibold text-slate-800">{{ $galleryItems->total() }}</span> dosya bulundu
            @if($galleryItems->total() !== $totalCount)
                <span class="text-slate-400"> / toplam {{ $totalCount }}</span>
            @endif
        </p>
        <a href="{{ route('admin.artwork-gallery.manage') }}" class="btn btn-secondary text-xs">Kategori & Etiket Yönetimi</a>
    </div>

    @if($stockRevisionGroups->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Stok Kodu Revizyon Görünümü</h3>
                        <p class="mt-1 text-xs text-slate-500">“{{ $stockCodeFilter }}” aramasına uyan tüm revizyonlar stok koduna göre gruplanmıştır.</p>
                    </div>
                    <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">{{ $stockRevisionGroups->sum->count() }} revizyon</span>
                </div>
            </div>
            <div class="space-y-4 p-5">
                @foreach($stockRevisionGroups as $stockCode => $revisions)
                    @php
                        $first = $revisions->first();
                        $stockName = $first?->galleryItem?->stockCard?->stock_name ?? 'Stok adı bulunamadı';
                        $categoryName = $first?->galleryItem?->stockCard?->category?->display_name ?? ($first?->galleryItem?->category?->display_name ?? 'Kategorisiz');
                    @endphp
                    <div class="rounded-2xl border border-slate-200">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $stockName }}</p>
                                <p class="mt-0.5 text-xs text-slate-500"><span class="font-mono">{{ $stockCode }}</span> · {{ $categoryName }}</p>
                            </div>
                            <span class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white">{{ $revisions->count() }} revizyon</span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @foreach($revisions as $revision)
                                <div class="grid gap-3 px-4 py-3 md:grid-cols-[110px_minmax(0,1fr)_130px_130px] md:items-center">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-slate-100 px-2 py-1 text-xs font-mono text-slate-700">Rev.{{ $revision->revision_no }}</span>
                                        @if($revision->is_active)
                                            <span class="badge badge-success">Aktif</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-900">{{ $revision->original_filename }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            {{ $revision->artwork?->orderLine?->purchaseOrder?->order_no ?? 'Sipariş yok' }}
                                            · {{ $revision->artwork?->orderLine?->product_code ?? 'Ürün kodu yok' }}
                                            · Satır {{ $revision->artwork?->orderLine?->line_no ?? '—' }}
                                        </p>
                                    </div>
                                    <div class="text-xs text-slate-500">{{ $revision->uploadedBy?->name ?? 'Bilinmiyor' }}</div>
                                    <div class="text-xs text-slate-500">{{ $revision->created_at->format('d.m.Y H:i') }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

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
            <table class="w-full min-w-[1080px] text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left">
                        <th class="px-4 py-3 font-medium text-slate-600">Format</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Dosya / Stok</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Stok Adı</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Kategori</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Etiketler</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">Boyut</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Yükleyen</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Tarih</th>
                        <th class="px-4 py-3 text-center font-medium text-slate-600">Kullanım</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($galleryItems as $item)
                        @php
                            $ext = strtoupper(pathinfo($item->file_path ?: $item->name, PATHINFO_EXTENSION));
                            $badgeClass = $fileTypeColors[$ext] ?? 'bg-slate-100 text-slate-600 border border-slate-200';
                            $stockName = $item->stockCard?->stock_name ?? 'Stok kartı eşleşmedi';
                            $categoryName = $item->stockCard?->category?->display_name ?? ($item->category?->display_name ?? '—');
                        @endphp
                        <tr class="group hover:bg-slate-50/60">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold tracking-wide {{ $badgeClass }}">{{ $ext ?: 'DOSYA' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="truncate font-medium text-slate-900" title="{{ $item->display_name }}">{{ $item->display_name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    @if($item->stock_code)
                                        <span class="font-mono font-semibold text-brand-700">{{ $item->stock_code }}</span>
                                    @else
                                        <span class="text-slate-300">Stok kodu yok</span>
                                    @endif
                                </p>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $stockName }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $categoryName }}</td>
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
                            <td class="px-4 py-3 text-right text-xs text-slate-500">{{ $item->file_size_formatted }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $item->uploadedBy?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $item->created_at->format('d.m.Y') }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($item->usage_count > 0)
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700">{{ $item->usage_count }}</span>
                                @else
                                    <span class="text-xs text-slate-300">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                    <button type="button" data-dialog-open="gallery-preview-{{ $item->id }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" title="Önizle">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    <a href="{{ route('admin.artwork-gallery.edit', $item) }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-brand-700" title="Düzenle">
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

        <div class="pt-2">{{ $galleryItems->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('gallery-filter-form');
    const search = document.getElementById('gallery-search-input');
    const stockInput = document.getElementById('gallery-stock-input');
    const tagSelect = document.getElementById('gallery-tag-select');
    const categorySelect = form ? form.querySelector('select[name="category_id"]') : null;
    let timer = null;

    function autoSubmit() {
        clearTimeout(timer);
        timer = setTimeout(() => form.submit(), 350);
    }

    if (search) search.addEventListener('input', autoSubmit);
    if (stockInput) stockInput.addEventListener('input', autoSubmit);
    if (tagSelect) tagSelect.addEventListener('change', () => form.submit());
    if (categorySelect) categorySelect.addEventListener('change', () => form.submit());
})();
</script>
@endpush
