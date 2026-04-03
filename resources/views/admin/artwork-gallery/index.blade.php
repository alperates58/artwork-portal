@extends('layouts.app')
@section('title', 'Artwork Galerisi')
@section('page-title', 'Artwork Galerisi')
@section('page-subtitle', 'Stok kodu, kategori ve revizyon geçmişi üzerinden tekrar kullanılabilir dosyaları yönetin.')

@php
    $typeFilter = request('type', '');
    $sort = $sort ?? 'created_desc';
    $status = $status ?? request('status', 'active');
    $canManageGallery = auth()->user()->isAdmin() || auth()->user()->hasPermission('gallery', 'manage');
    $hasDirectUploadRoute = app('router')->has('admin.artwork-gallery.direct-upload') && $canManageGallery;
    $typeTabs   = ['' => 'Tümü'];
    $statusOptions = [
        'active' => 'Aktif artworkler',
        'inactive' => 'Pasif artworkler',
        'all' => 'Tümü',
    ];
    foreach ($fileGroups as $group) {
        $typeTabs[$group['key']] = $group['label'];
    }

    $sortOptions = [
        'created_desc' => 'Yüklenme tarihi (Yeni → Eski)',
        'created_asc' => 'Yüklenme tarihi (Eski → Yeni)',
        'name_asc' => 'Dosya adı (A → Z)',
        'name_desc' => 'Dosya adı (Z → A)',
        'revision_desc' => 'Revizyon (Yüksek → Düşük)',
        'revision_asc' => 'Revizyon (Düşük → Yüksek)',
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
@endphp

@section('header-actions')
    @if($hasDirectUploadRoute)
        <button type="button" data-dialog-open="gallery-direct-upload-dialog" class="btn btn-primary gap-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            Artwork Yükle
        </button>
    @endif
@endsection

@section('content')
<div x-data="galleryPage()" x-init="init()" class="space-y-5">

    {{-- Filtre Paneli --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-4 shadow-[0_4px_16px_rgba(15,23,42,0.04)]">
        <form method="GET" action="{{ route('admin.artwork-gallery.index') }}" id="gallery-filter-form">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <div class="flex flex-wrap items-end gap-3">

                <div class="w-full sm:min-w-[260px] lg:min-w-[320px] lg:flex-[1.25]">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input name="stock_code" value="{{ request('stock_code') }}" placeholder="Stok kodu ile ara..." class="input pl-9 font-mono" autocomplete="off" id="gallery-stock-input">
                    </div>
                </div>

                <div class="w-full flex-1 sm:min-w-[180px]">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input name="search" value="{{ request('search') }}" placeholder="Dosya adı ile ara..." class="input pl-9" autocomplete="off" id="gallery-search-input">
                    </div>
                </div>

                <div class="w-full sm:w-48">
                    <select name="category_id" class="input" id="gallery-cat-select">
                        <option value="">Tüm kategoriler</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full sm:w-48">
                    <select name="status" class="input" id="gallery-status-select">
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Filtrele</button>
                @if(request()->hasAny(['search', 'stock_code', 'category_id', 'type']) || $sort !== 'created_desc' || $status !== 'active')
                    <a href="{{ route('admin.artwork-gallery.index') }}" class="btn btn-secondary">Temizle</a>
                @endif
            </div>

        </form>
    </div>

    {{-- Sayı + Yönetim Bağlantısı --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <p class="text-sm text-slate-500">
                <span class="font-semibold text-slate-800">{{ $galleryItems->total() }}</span> dosya bulundu
                @if($galleryItems->total() !== $totalCount)
                    <span class="text-slate-400"> / toplam {{ $totalCount }}</span>
                @endif
            </p>

            <form method="GET" action="{{ route('admin.artwork-gallery.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="stock_code" value="{{ request('stock_code') }}">
                <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                <input type="hidden" name="tag_id" value="{{ request('tag_id') }}">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="type" value="{{ request('type') }}">

                <select id="gallery-sort-select" name="sort" class="input text-sm" onchange="this.form.submit()">
                    @foreach($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="w-52">
            <input
                type="range"
                min="3"
                max="9"
                step="0.01"
                x-model.number="columnStep"
                @input="persistColumns()"
                class="h-2 w-full cursor-pointer appearance-none rounded-full bg-slate-200 accent-brand-600"
                aria-label="Kolon sayısını ayarla"
            >
        </div>
    </div>

    {{-- Stok Kodu Araması Sonuçları --}}
    @if($stockCodeFilter !== '')
        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
            <div class="card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Stok Koduna Göre Hızlı Eşleşmeler</h3>
                            <p class="mt-1 text-xs text-slate-500">Uygun artwork revizyonları öne çıkarılır.</p>
                        </div>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">{{ $stockQuickMatches->sum->count() }} artwork</span>
                    </div>
                </div>

                @if($stockQuickMatches->isEmpty())
                    <div class="px-5 py-8 text-sm text-slate-500">
                        <span class="font-mono font-semibold text-slate-700">{{ $stockCodeFilter }}</span> için hızlı eşleşme bulunamadı.
                    </div>
                @else
                    <div class="space-y-4 p-5">
                        @foreach($stockQuickMatches as $stockCode => $matches)
                            @php
                                $first = $matches->first();
                                $stockName    = $first?->stockCard?->stock_name ?? 'Stok adı bulunamadı';
                                $categoryName = $first?->stockCard?->category?->display_name ?? ($first?->category?->display_name ?? 'Kategorisiz');
                            @endphp
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $stockName }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            <span class="font-mono font-semibold text-brand-700">{{ $stockCode }}</span>
                                            · {{ $categoryName }}
                                        </p>
                                    </div>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ $matches->count() }} revizyon</span>
                                </div>

                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    @foreach($matches as $match)
                                        @php
                                            $revisionBadge  = 'REV' . str_pad((string) ((int) ($match->revision_no ?? 0)), 2, '0', STR_PAD_LEFT);
                                            $systemRevision = (int) ($match->revisions_max_revision_no ?? 0);
                                        @endphp
                                        <a href="{{ route('admin.artwork-gallery.edit', $match) }}" class="block rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-brand-300 hover:bg-brand-50/30 hover:shadow-sm">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex flex-col gap-2">
                                                    <span class="inline-flex w-fit rounded-lg bg-slate-900 px-2.5 py-1 text-[11px] font-semibold tracking-[0.14em] text-white">{{ $revisionBadge }}</span>
                                                    <span class="inline-flex w-fit rounded-full bg-brand-100 px-3 py-1 text-[11px] font-semibold text-brand-700">
                                                        {{ $systemRevision > 0 ? 'Sistemde Rev.' . str_pad((string) $systemRevision, 2, '0', STR_PAD_LEFT) : 'Henüz kullanılmadı' }}
                                                    </span>
                                                </div>
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-medium text-slate-500">{{ $match->created_at->format('d.m.Y') }}</span>
                                            </div>
                                            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50" style="aspect-ratio: 4 / 3;">
                                                @if($match->has_preview)
                                                    <img
                                                        src="{{ route('artworks.gallery.preview', $match, false) }}"
                                                        alt="{{ $match->display_name }}"
                                                        class="h-full w-full object-contain"
                                                        loading="lazy"
                                                    >
                                                @else
                                                    <div class="flex h-full w-full items-center justify-center">
                                                        <span class="text-xs font-bold text-slate-500">{{ $match->extension ?: 'DOSYA' }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <p class="mt-4 truncate text-base font-semibold text-slate-900">{{ $match->display_name }}</p>
                                            <p class="mt-2 font-mono text-sm text-brand-700">{{ $match->stock_code ?: 'Stok kodu yok' }}</p>
                                            <div class="mt-3 flex flex-wrap gap-2 text-[11px] text-slate-500">
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1">{{ $categoryName }}</span>
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1">{{ $match->usage_count }} kullanım</span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Arama Özeti</h3>
                    <p class="mt-1 text-xs text-slate-500">Stok kodu bazlı revizyon ve kullanım yoğunluğu.</p>
                </div>
                <div class="space-y-3 p-5 text-sm">
                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-500">Aranan stok kodu</p>
                        <p class="mt-1 font-mono text-sm font-semibold text-slate-900">{{ $stockCodeFilter }}</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <div class="rounded-2xl border border-slate-200 px-4 py-3">
                            <p class="text-xs text-slate-500">Galeri eşleşmesi</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">{{ $stockQuickMatches->sum->count() }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 px-4 py-3">
                            <p class="text-xs text-slate-500">Yükleme geçmişi</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">{{ $stockHistory->sum->count() }}</p>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-3 text-xs text-slate-500">
                        Stok kodunu değiştirdikçe tekrar kullanılabilir artwork kartları ve revizyon geçmişi güncellenir.
                    </div>
                </div>
            </div>
        </div>

        {{-- Artwork Geçmişi --}}
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Artwork Geçmişi</h3>
                        <p class="mt-1 text-xs text-slate-500">Bu stok kodunun hangi revizyonlarla hangi sipariş ve tedarikçilere yüklendiği.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $stockHistory->sum->count() }} kayıt</span>
                </div>
            </div>

            @if($stockHistory->isEmpty())
                <div class="px-5 py-8 text-sm text-slate-500">
                    <span class="font-mono font-semibold text-slate-700">{{ $stockCodeFilter }}</span> için artwork geçmişi bulunamadı.
                </div>
            @else
                <div class="space-y-5 p-5">
                    @foreach($stockHistory as $stockCode => $revisions)
                        @php
                            $firstRevision = $revisions->first();
                            $stockName     = $firstRevision?->galleryItem?->stockCard?->stock_name ?? 'Stok adı bulunamadı';
                            $categoryName  = $firstRevision?->galleryItem?->stockCard?->category?->display_name ?? ($firstRevision?->galleryItem?->category?->display_name ?? 'Kategorisiz');
                        @endphp
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $stockName }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        <span class="font-mono">{{ $stockCode }}</span> · {{ $categoryName }}
                                    </p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700">{{ $revisions->count() }} yükleme</span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[860px] text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-100 bg-white text-left">
                                            <th class="px-4 py-3 font-medium text-slate-600">Revizyon</th>
                                            <th class="px-4 py-3 font-medium text-slate-600">Artwork</th>
                                            <th class="px-4 py-3 font-medium text-slate-600">Sipariş</th>
                                            <th class="px-4 py-3 font-medium text-slate-600">Tedarikçi</th>
                                            <th class="px-4 py-3 font-medium text-slate-600">Yükleyen</th>
                                            <th class="px-4 py-3 font-medium text-slate-600">Tarih</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($revisions as $revision)
                                            <tr class="hover:bg-slate-50/70">
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-mono text-slate-700">Rev.{{ $revision->revision_no }}</span>
                                                        @if($revision->is_active)
                                                            <span class="badge badge-success">Aktif</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <p class="font-medium text-slate-900">{{ $revision->original_filename }}</p>
                                                    <p class="mt-0.5 text-xs text-slate-500">Galeri: {{ $revision->galleryItem?->display_name ?? '—' }}</p>
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if($revision->artwork?->orderLine?->purchaseOrder)
                                                        <a href="{{ route('orders.show', $revision->artwork->orderLine->purchaseOrder) }}" class="font-medium text-brand-700 hover:underline">
                                                            {{ $revision->artwork->orderLine->purchaseOrder->order_no }}
                                                        </a>
                                                    @else
                                                        <span class="text-slate-400">Sipariş yok</span>
                                                    @endif
                                                    <p class="mt-0.5 text-xs text-slate-500">
                                                        {{ $revision->artwork?->orderLine?->product_code ?? 'Ürün kodu yok' }}
                                                        · Satır {{ $revision->artwork?->orderLine?->line_no ?? '—' }}
                                                    </p>
                                                </td>
                                                <td class="px-4 py-3 text-slate-600">{{ $revision->artwork?->orderLine?->purchaseOrder?->supplier?->name ?? 'Bilinmiyor' }}</td>
                                                <td class="px-4 py-3 text-slate-600">{{ $revision->uploadedBy?->name ?? 'Bilinmiyor' }}</td>
                                                <td class="px-4 py-3 text-xs text-slate-500">
                                                    {{ $revision->created_at->format('d.m.Y H:i') }}
                                                    @if($revision->artwork?->orderLine?->purchaseOrder?->order_date)
                                                        <div class="mt-1 text-[11px] text-slate-400">Sipariş: {{ $revision->artwork->orderLine->purchaseOrder->order_date->format('d.m.Y') }}</div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Ana Galeri Izgarası --}}
    @if($galleryItems->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white/60 py-20">
            <svg class="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-10h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="mt-4 text-sm font-medium text-slate-500">Filtrelere uygun dosya bulunamadı</p>
            <a href="{{ route('admin.artwork-gallery.index') }}" class="mt-3 text-xs text-brand-600 hover:underline">Filtreleri temizle</a>
        </div>
    @else
        <div
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-6"
            :style="galleryGridStyle()"
        >
            @foreach($galleryItems as $item)
                @php
                    $ext        = strtoupper(pathinfo($item->file_path ?: $item->name, PATHINFO_EXTENSION));
                    $badgeClass = $fileTypeColors[$ext] ?? 'bg-slate-100 text-slate-600 border border-slate-200';
                    $stockName  = $item->stockCard?->stock_name ?? null;
                    $catName    = $item->stockCard?->category?->display_name ?? ($item->category?->display_name ?? null);
                    $canManageGalleryItem = auth()->user()->isAdmin() || auth()->user()->hasPermission('gallery', 'manage');
                @endphp

                <div class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white shadow-[0_2px_8px_rgba(15,23,42,0.04)] transition hover:border-slate-300 hover:shadow-[0_4px_16px_rgba(15,23,42,0.08)]">

                    {{-- Önizleme Alanı --}}
                    <div class="relative overflow-hidden rounded-t-2xl bg-slate-50" style="aspect-ratio:4/3;">
                        @if($item->has_preview)
                            <img
                                src="{{ route('artworks.gallery.preview', $item, false) }}"
                                alt="{{ $item->display_name }}"
                                class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-[1.03]"
                                loading="lazy"
                                onerror="this.parentElement.innerHTML='<div class=\'h-full w-full flex items-center justify-center text-slate-300\'><svg class=\'h-10 w-10\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-10h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'/></svg></div>';"
                            >
                        @else
                            <div class="flex h-full w-full items-center justify-center text-slate-300">
                                @if($item->file_type_icon === 'pdf')
                                    <svg class="h-14 w-14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l4 4V20.25A1.75 1.75 0 0 1 16.25 22h-8.5A1.75 1.75 0 0 1 6 20.25v-14.5A1.75 1.75 0 0 1 7.75 4Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 3.75v4h4M8 15.25h8M8 18h5"/>
                                    </svg>
                                @elseif($item->file_type_icon === 'design')
                                    <svg class="h-14 w-14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 6.75A2.75 2.75 0 0 1 7.5 4h9A2.75 2.75 0 0 1 19.25 6.75v10.5A2.75 2.75 0 0 1 16.5 20h-9a2.75 2.75 0 0 1-2.75-2.75Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16l2.5-3 2 2 3.5-5M8 9.5h.01"/>
                                    </svg>
                                @elseif($item->file_type_icon === 'image')
                                    <svg class="h-14 w-14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2 1.586-1.586a2 2 0 012.828 0L20 14m-6-10h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                @else
                                    <svg class="h-14 w-14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l4 4V20.25A1.75 1.75 0 0 1 16.25 22h-8.5A1.75 1.75 0 0 1 6 20.25v-14.5A1.75 1.75 0 0 1 7.75 4Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 3.75v4h4"/>
                                    </svg>
                                @endif
                            </div>
                            {{-- Önizleme üretilmekte rozeti --}}
                            @if($item->file_type_group !== 'image')
                                <div class="absolute bottom-2 left-2 right-2 rounded-lg bg-slate-900/60 px-2 py-1 text-center text-[10px] font-medium text-white backdrop-blur-sm">
                                    Önizleme bekleniyor…
                                </div>
                            @endif
                        @endif

                        {{-- Format Rozeti --}}
                        <div class="absolute left-2 top-2">
                            <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold tracking-wide {{ $badgeClass }}">{{ $ext ?: 'DOSYA' }}</span>
                        </div>

                        @if(! $item->is_active)
                            <div class="absolute right-2 top-2">
                                <span class="inline-flex items-center rounded-lg bg-amber-100 px-2 py-0.5 text-[10px] font-bold tracking-wide text-amber-700">PASİF</span>
                            </div>
                        @endif

                        {{-- Hover Aksiyonları --}}
                        <div class="pointer-events-none absolute inset-0 hidden items-center justify-center gap-2 bg-slate-900/0 transition-all group-hover:bg-slate-900/30 sm:flex">
                            <div class="pointer-events-auto flex translate-y-2 gap-2 opacity-0 transition-all group-hover:translate-y-0 group-hover:opacity-100">
                                <button
                                    type="button"
                                    data-dialog-open="gallery-preview-{{ $item->id }}"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/90 text-slate-700 shadow-lg backdrop-blur-sm transition hover:bg-white hover:text-brand-700"
                                    title="Önizle"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                <a
                                    href="{{ route('artworks.gallery.download', $item) }}"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/90 text-slate-700 shadow-lg backdrop-blur-sm transition hover:bg-white hover:text-emerald-700"
                                    title="İndir"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>
                                @if($canManageGalleryItem)
                                <a
                                    href="{{ route('admin.artwork-gallery.edit', $item) }}"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/90 text-slate-700 shadow-lg backdrop-blur-sm transition hover:bg-white hover:text-brand-700"
                                    title="Düzenle"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 border-t border-slate-200 bg-slate-50/80 p-2.5 sm:hidden">
                        <button
                            type="button"
                            data-dialog-open="gallery-preview-{{ $item->id }}"
                            class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-brand-200 hover:text-brand-700"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Görüntüle
                        </button>
                        <a
                            href="{{ route('artworks.gallery.download', $item) }}"
                            class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-emerald-200 hover:text-emerald-700"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            İndir
                        </a>
                        @if($canManageGalleryItem)
                            <a
                                href="{{ route('admin.artwork-gallery.edit', $item) }}"
                                class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-brand-200 hover:text-brand-700"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Düzenle
                            </a>
                        @endif
                    </div>

                    {{-- Kart Alt Bilgisi --}}
                    <div class="flex flex-1 flex-col gap-1.5 p-3">
                        <p class="truncate text-sm font-semibold leading-tight text-slate-900" title="{{ $item->display_name }}">
                            {{ $item->display_name }}
                        </p>

                        @if($item->stock_code)
                            <p class="flex items-center gap-1 text-xs">
                                <span class="font-mono font-semibold text-brand-700">{{ $item->stock_code }}</span>
                                <span class="text-slate-400">·</span>
                                <span class="font-medium text-slate-600">Rev.{{ $item->revision_no ?? '—' }}</span>
                            </p>
                        @endif

                        @if($stockName)
                            <p class="truncate text-xs text-slate-500" title="{{ $stockName }}">{{ $stockName }}</p>
                        @endif

                        @if($catName)
                            <p class="text-xs text-slate-400">{{ $catName }}</p>
                        @endif

                        <div class="mt-auto flex items-center justify-between pt-1.5">
                            <span class="text-[10px] text-slate-400">{{ $item->created_at->format('d.m.Y') }}</span>
                            @if($item->usage_count > 0)
                                @php
                                    $usageHistoryUrl = (auth()->user()->isAdmin() || auth()->user()->hasPermission('gallery', 'manage'))
                                        ? route('admin.artwork-gallery.edit', $item) . '#usage-history'
                                        : route('admin.artwork-gallery.index', ['stock_code' => $item->stock_code, 'status' => 'all']);
                                @endphp
                                <a href="{{ $usageHistoryUrl }}" class="inline-flex items-center gap-1 text-[10px] font-semibold text-brand-700 transition hover:text-brand-800">
                                    Sipariş Geçmişi
                                    <span class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-50 px-1 text-[9px] text-brand-700">{{ $item->usage_count }}</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @include('artwork-gallery.partials.preview-dialog', [
                    'artworkGallery' => $item,
                    'dialogId'       => 'gallery-preview-' . $item->id,
                ])
            @endforeach
        </div>

        <div class="pt-2">{{ $galleryItems->links() }}</div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function galleryPage() {
    return {
        columnStep: 6,
        viewportWidth: window.innerWidth,

        init() {
            const saved = Number(window.localStorage.getItem('artwork-gallery:column-step'));
            if (saved >= 3 && saved <= 9) {
                this.columnStep = saved;
            }

            window.addEventListener('resize', () => {
                this.viewportWidth = window.innerWidth;
            });
        },

        effectiveColumns() {
            const selected = Math.round(Number(this.columnStep));

            if (this.viewportWidth < 640) return Math.min(selected, 2);
            if (this.viewportWidth < 1024) return Math.min(selected, 3);
            if (this.viewportWidth < 1280) return Math.min(selected, 5);
            if (this.viewportWidth < 1536) return Math.min(selected, 7);

            return selected;
        },

        galleryGridStyle() {
            return {
                gridTemplateColumns: `repeat(${this.effectiveColumns()}, minmax(0, 1fr))`,
            };
        },

        persistColumns() {
            window.localStorage.setItem('artwork-gallery:column-step', String(this.columnStep));
        },
    };
}

(function () {
    const form         = document.getElementById('gallery-filter-form');
    const searchInput  = document.getElementById('gallery-search-input');
    const stockInput   = document.getElementById('gallery-stock-input');
    const catSelect    = document.getElementById('gallery-cat-select');
    const statusSelect = document.getElementById('gallery-status-select');
    let   timer        = null;

    function autoSubmit() {
        clearTimeout(timer);
        timer = setTimeout(() => form && form.submit(), 350);
    }

    if (searchInput) searchInput.addEventListener('input', autoSubmit);
    if (stockInput)  stockInput.addEventListener('input', autoSubmit);
    if (catSelect)   catSelect.addEventListener('change', () => form && form.submit());
    if (statusSelect) statusSelect.addEventListener('change', () => form && form.submit());
})();

// ── Doğrudan galeri yükleme diyaloğu ──────────────────────────────────────
(function () {
    const dialog      = document.getElementById('gallery-direct-upload-dialog');
    const fileInput   = document.getElementById('gdu-file');
    const dropZone    = document.getElementById('gdu-drop-zone');
    const emptyState  = document.getElementById('gdu-drop-empty');
    const selState    = document.getElementById('gdu-drop-selected');
    const selName     = document.getElementById('gdu-selected-name');
    const selMeta     = document.getElementById('gdu-selected-meta');
    const stockInput  = document.getElementById('gdu-stock-code');
    const stockName   = document.getElementById('gdu-stock-name');
    const catName     = document.getElementById('gdu-category-name');
    const revisionInput = document.getElementById('gdu-revision-no');
    const revisionHelp  = document.getElementById('gdu-revision-help');
    const lookupState = document.getElementById('gdu-lookup-state');
    const submitBtn   = document.getElementById('gdu-submit');
    const formatsHint = document.querySelector('#gallery-direct-upload-dialog .mb-2 span.text-xs.text-slate-400');
    const allowedUploadExtensionsLabel = @json($allowedUploadExtensionsLabel);

    if (!dialog) return;

    if (formatsHint) {
        formatsHint.textContent = `${allowedUploadExtensionsLabel} · Maks. 1.2 GB`;
    }

    let lookupTimer = null;
    let suggestedRevisionNo = Math.max(0, Number(revisionInput?.value ?? 0));

    function setLookup(msg, tone) {
        lookupState.textContent = msg;
        lookupState.className = 'mt-1 text-xs ' + (tone === 'ok' ? 'text-emerald-600' : tone === 'err' ? 'text-red-600' : 'text-slate-400');
        lookupState.classList.toggle('hidden', !msg);
    }

    function syncRevisionHelp() {
        if (!revisionHelp || !revisionInput) return;

        revisionHelp.textContent = `Bu stok kodu için en düşük yeni revizyon Rev.${String(suggestedRevisionNo).padStart(2, '0')} olmalıdır.`;
        revisionInput.min = String(suggestedRevisionNo);

        const currentValue = Number(revisionInput.value || 0);
        if (!currentValue || currentValue < suggestedRevisionNo) {
            revisionInput.value = String(suggestedRevisionNo);
        }
    }

    async function lookupStock() {
        const code = stockInput.value.trim().toUpperCase();
        if (!code) {
            stockName.value = '';
            catName.value = '';
            suggestedRevisionNo = 0;
            syncRevisionHelp();
            setLookup('', '');
            return;
        }
        setLookup('Stok kartı kontrol ediliyor…', '');
        try {
            const res = await fetch(`{{ route('stock-cards.lookup') }}?stock_code=${encodeURIComponent(code)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) {
                const p = await res.json().catch(() => ({}));
                stockName.value = ''; catName.value = '';
                suggestedRevisionNo = Math.max(0, Number(revisionInput?.value ?? 0));
                setLookup(p.message ?? 'Stok kartı bulunamadı.', 'err');
                return;
            }
            const p = await res.json();
            stockInput.value = p.stock_code;
            stockName.value = p.stock_name ?? '';
            catName.value = p.category_name ?? '';
            suggestedRevisionNo = Math.max(0, Number(p.next_upload_revision_no ?? 0));
            syncRevisionHelp();
            setLookup('Stok kartı doğrulandı.', 'ok');
        } catch {
            stockName.value = ''; catName.value = '';
            setLookup('Bağlantı hatası oluştu.', 'err');
        }
    }

    stockInput.addEventListener('input', () => {
        clearTimeout(lookupTimer);
        lookupTimer = setTimeout(lookupStock, 500);
    });

    function showFile(file) {
        emptyState.classList.add('hidden');
        selState.classList.remove('hidden');
        selName.textContent = file.name;
        selMeta.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
    }

    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', e => {
        e.preventDefault();
        dropZone.classList.add('border-brand-400', 'bg-brand-50/20');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-brand-400', 'bg-brand-50/20');
    });

    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('border-brand-400', 'bg-brand-50/20');
        const file = e.dataTransfer.files[0];
        if (file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            showFile(file);
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) showFile(fileInput.files[0]);
    });

    document.getElementById('gdu-form').addEventListener('submit', () => {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Yükleniyor…';
    });

    syncRevisionHelp();

    @if($errors->hasAny(['artwork_file', 'stock_code', 'revision_no', 'notes']))
        if (typeof dialog.showModal === 'function' && !dialog.open) {
            dialog.showModal();
        }
    @endif
})();
</script>
@endpush

{{-- Doğrudan Galeri Yükleme Diyaloğu --}}
@if($hasDirectUploadRoute)
<dialog id="gallery-direct-upload-dialog" class="max-h-[96vh] w-[min(96vw,680px)] max-w-none overflow-hidden rounded-[32px] border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/60">
    <div class="flex max-h-[96vh] flex-col bg-white">
        {{-- Başlık --}}
        <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Artwork Galerisi</p>
                <h3 class="mt-1 text-lg font-semibold text-slate-900">Galeriye Artwork Yükle</h3>
            </div>
            <button type="button" data-dialog-close class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Form --}}
        <div class="flex-1 overflow-y-auto px-6 py-5">
            <form id="gdu-form" method="POST" action="{{ route('admin.artwork-gallery.direct-upload') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                {{-- Dosya --}}
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="label mb-0">Dosya</label>
                        <span class="text-xs text-slate-400">PDF, AI, EPS, ZIP, PSD, INDD, PNG, TIF · Maks. 1.2 GB</span>
                    </div>
                    <input type="file" id="gdu-file" name="artwork_file" class="hidden" accept="{{ $allowedUploadAccept }}">
                    <div id="gdu-drop-zone" class="cursor-pointer rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50/40 px-5 py-6 text-center transition hover:border-brand-400 hover:bg-brand-50/20">
                        <div id="gdu-drop-empty">
                            <svg class="mx-auto h-9 w-9 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <p class="mt-2.5 text-sm font-medium text-slate-700">Dosyayı sürükleyin veya <span class="text-brand-600">seçin</span></p>
                        </div>
                        <div id="gdu-drop-selected" class="hidden">
                            <svg class="mx-auto h-8 w-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="mt-2 text-sm font-semibold text-emerald-700" id="gdu-selected-name">—</p>
                            <p class="mt-0.5 text-xs text-emerald-600" id="gdu-selected-meta">—</p>
                            <p class="mt-2 text-xs text-slate-400 underline">Dosyayı değiştirmek için tıklayın</p>
                        </div>
                    </div>
                    @error('artwork_file')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Stok Kodu + Revizyon --}}
                <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_140px]">
                    <div>
                        <label class="label" for="gdu-stock-code">Stok Kodu</label>
                        <input type="text" id="gdu-stock-code" name="stock_code" value="{{ old('stock_code') }}" class="input font-mono" placeholder="Örn: 5010118005440" required autocomplete="off">
                        <p id="gdu-lookup-state" class="mt-1 hidden text-xs text-slate-400"></p>
                        @error('stock_code')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label" for="gdu-revision-no">Revizyon No</label>
                        <input type="number" id="gdu-revision-no" name="revision_no" value="{{ old('revision_no', 0) }}" min="0" max="99" class="input" required>
                        <p id="gdu-revision-help" class="mt-1 text-xs text-slate-400">Bu stok kodu için en düşük yeni revizyon Rev.01 olmalıdır.</p>
                        @error('revision_no')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Stok Adı + Kategori (otomatik dolu, salt okunur) --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="gdu-stock-name">Stok Adı</label>
                        <input type="text" id="gdu-stock-name" class="input bg-slate-50" readonly placeholder="Stok kodu girilince otomatik dolar">
                    </div>
                    <div>
                        <label class="label" for="gdu-category-name">Kategori</label>
                        <input type="text" id="gdu-category-name" class="input bg-slate-50" readonly placeholder="Otomatik">
                    </div>
                </div>

                {{-- Açıklama --}}
                <div>
                    <label class="label" for="gdu-notes">Açıklama / Revizyon Notu</label>
                    <textarea id="gdu-notes" name="notes" rows="3" class="input resize-none" placeholder="Revizyon veya baskı ile ilgili not ekleyebilirsiniz.">{{ old('notes') }}</textarea>
                </div>

                {{-- Bilgi kutusu --}}
                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-3 text-xs text-slate-500">
                    Desteklenen formatlar için önizleme PNG'si otomatik üretilir. Dosya galeriye bağımsız kaydedilir; siparişe atamak için galeri kullanımı üzerinden yapılır.
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="submit" id="gdu-submit" class="btn btn-primary min-w-[160px] justify-center">Galeriye Ekle</button>
                    <button type="button" data-dialog-close class="btn btn-secondary px-5">İptal</button>
                </div>
            </form>
        </div>
    </div>
</dialog>
@endif
