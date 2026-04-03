@extends('layouts.app')
@section('title', 'Artwork Yükle')
@section('page-title', 'Artwork Yükle')
@section('page-subtitle', 'Stok kartı doğrulaması ile yeni dosya yükleyin veya galeriden revizyon seçin.')

@section('header-actions')
    <a href="{{ route('orders.show', $line->purchaseOrder) }}" class="btn btn-secondary">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Siparişe Dön
    </a>
@endsection

@php
    use App\Support\DisplayText;

    $initialSourceType = old('source_type', 'upload');
    $initialGalleryItemId = old('gallery_item_id', '');
    $currentMaxRevision = $line->artwork?->revisions->max('revision_no');
    $nextRevisionNoValue = $nextRevisionNo;
    $resolvedStockName = old('stock_name', DisplayText::normalize($resolvedStockCard?->stock_name));
    $resolvedCategoryName = old('category_name', DisplayText::normalize($resolvedStockCard?->category?->display_name));
@endphp

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-sm font-bold text-slate-600">{{ $line->line_no }}</span>
                    <div>
                        <p class="text-2xl font-semibold text-slate-900">{{ $line->purchaseOrder->order_no }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ DisplayText::normalize($line->purchaseOrder->supplier->name) }}</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                    <span class="rounded-full bg-slate-100 px-3 py-1 font-mono text-slate-600">Ürün: {{ $line->product_code }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">Mevcut revizyon: Rev.{{ $currentMaxRevision ?? '—' }}</span>
                    <span class="rounded-full bg-brand-50 px-3 py-1 font-semibold text-brand-700">Önerilen: Rev.{{ $nextRevisionNo }}</span>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('artworks.store', $line) }}" enctype="multipart/form-data" id="uploadForm">
        @csrf
        <input type="hidden" name="source_type" id="source_type_input" value="{{ $initialSourceType }}">
        <input type="hidden" name="gallery_item_id" id="gallery_item_id_input" value="{{ $initialGalleryItemId }}">

        <div class="card space-y-5 p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ $line->artwork ? 'Yeni revizyon yükle' : 'İlk artwork kaydını oluştur' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Dosya yükleme, stok kartı doğrulaması ve revizyon numarası tek akışta yönetilir.</p>
                </div>
                <div id="selectedGallerySummary" class="hidden rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-xs text-brand-700">
                    <p class="font-semibold">Galeriden seçim aktif</p>
                    <p id="selectedGalleryName" class="mt-1">—</p>
                </div>
            </div>

            <div class="flex justify-center">
                <button type="button" id="gallery-modal-open" class="inline-flex min-w-[260px] items-center justify-center rounded-2xl border border-brand-200 bg-brand-50 px-6 py-3 text-sm font-semibold text-brand-700 shadow-sm transition hover:border-brand-300 hover:bg-brand-100">
                    Galeriden Seç
                </button>
            </div>

            <div id="upload-panel" class="space-y-4">
                <input type="file" id="artwork_file" name="artwork_file" class="hidden" accept="{{ $allowedUploadAccept }}">

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label class="label mb-0">Orijinal Kaynak Dosya</label>
                        <span class="text-xs text-slate-400">Zorunlu · PDF, AI, EPS, ZIP, PSD, INDD, PNG, TIF</span>
                    </div>
                    <div id="dropZone" class="cursor-pointer rounded-3xl border-2 border-dashed border-slate-300 bg-slate-50/40 p-6 text-center transition hover:border-brand-400 hover:bg-brand-50/20" onclick="document.getElementById('artwork_file').click()">
                        <div id="drop-empty">
                            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <p class="mt-3 text-sm font-medium text-slate-700">Dosyayı sürükleyin veya <span class="text-brand-600">buradan seçin</span></p>
                            <p class="mt-1 text-xs text-slate-400">Maksimum 1.2 GB</p>
                        </div>
                        <div id="drop-selected" class="hidden">
                            <svg class="mx-auto h-9 w-9 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="mt-2 text-sm font-semibold text-emerald-700" id="selected-file-name">—</p>
                            <p class="mt-0.5 text-xs text-emerald-600" id="selected-file-meta">—</p>
                            <p class="mt-2 text-xs text-slate-400 underline">Dosyayı değiştirmek için tıklayın</p>
                        </div>
                    </div>
                    @error('artwork_file')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-4 text-sm text-slate-600">
                    Yükleme tamamlandıktan sonra sistem desteklenen formatlar için önizleme PNG dosyasını otomatik üretir.
                    Önizleme oluşmazsa artwork kaydı geçerli kalır ve orijinal dosya indirilmeye devam eder.
                </div>

                <div id="progressWrapper" class="hidden">
                    <div class="mb-1.5 flex items-center justify-between text-xs text-slate-500">
                        <span id="progressFilename" class="max-w-xs truncate"></span>
                        <span id="progressPercent">0%</span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                        <div id="progressBar" class="h-1.5 rounded-full bg-brand-600 transition-all duration-300" style="width:0%"></div>
                    </div>
                    <div class="mt-1.5 flex items-center justify-between">
                        <p class="text-xs text-slate-400" id="progressSize"></p>
                        <button type="button" class="text-xs text-slate-400 underline hover:text-red-500" onclick="if(activeXhr){activeXhr.abort();}">İptal</button>
                    </div>
                    <div id="upload-error-msg" class="mt-2 hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                        Bağlantı hatası oluştu; form verisi korunur, yeniden deneyebilirsiniz.
                    </div>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px]">
                <div>
                    <label class="label" for="stock_code">Stok Kodu</label>
                    <input type="text" id="stock_code" name="stock_code" value="{{ old('stock_code', $resolvedStockCard?->stock_code ?? $line->product_code) }}" class="input font-mono" placeholder="Stok kodu girin" required>
                    <p class="mt-1 text-xs text-slate-400">Stok kodu tamamlanmadan upload tamamlanamaz; doğrulandığında stok adı ve kategori otomatik doldurulur.</p>
                    @error('stock_code')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p id="stockLookupState" class="mt-2 hidden text-xs"></p>
                </div>
                <div>
                    <label class="label" for="revision_no">Revizyon No</label>
                    <input type="number" id="revision_no" name="revision_no" value="{{ old('revision_no', $nextRevisionNo) }}" min="{{ $nextRevisionNo }}" class="input w-full" required>
                    <p id="revision_hint" class="mt-1 text-xs text-slate-400">Revizyon numarası tamamlanmadan upload tamamlanamaz. En az Rev.{{ $nextRevisionNo }} olmalıdır.</p>
                    @error('revision_no')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-[minmax(0,1.4fr)_minmax(220px,0.8fr)]">
                <div>
                    <label class="label" for="stock_name">Stok Adı</label>
                    <input type="text" id="stock_name" name="stock_name" value="{{ $resolvedStockName }}" class="input bg-slate-50" readonly>
                </div>
                <div>
                    <label class="label" for="category_name">Kategori</label>
                    <input type="text" id="category_name" name="category_name" value="{{ $resolvedCategoryName }}" class="input bg-slate-50" readonly>
                </div>
            </div>

            <div>
                <label class="label" for="notes">Operasyon Notu</label>
                <textarea id="notes" name="notes" rows="3" class="input resize-none" placeholder="Revizyon veya baskı ile ilgili not ekleyebilirsiniz.">{{ old('notes') }}</textarea>
            </div>

            @if($line->artwork && $line->artwork->revisions->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-900">Revizyon Geçmişi</h3>
                        <span class="text-xs text-slate-400">{{ $line->artwork->revisions->count() }} kayıt</span>
                    </div>
                    <div class="space-y-2">
                        @foreach($line->artwork->revisions as $rev)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-mono text-slate-700">REV{{ str_pad((string) $rev->revision_no, 2, '0', STR_PAD_LEFT) }}</span>
                                    @if($rev->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @endif
                                    <span class="text-sm text-slate-700">{{ DisplayText::normalize($rev->original_filename) }}</span>
                                </div>
                                <span class="text-xs text-slate-400">{{ $rev->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex flex-wrap gap-3 pt-1">
                <button type="submit" id="submitBtn" class="btn btn-primary min-w-[180px] justify-center py-2.5 disabled:cursor-not-allowed disabled:opacity-50">Kaydet</button>
                <a href="{{ route('orders.show', $line->purchaseOrder) }}" class="btn btn-secondary px-6">İptal</a>
            </div>
        </div>
    </form>
</div>

<div id="galleryModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/45"></div>
    <div id="galleryModalViewport" class="relative mx-auto flex min-h-full max-w-5xl items-center justify-center p-6">
        <div class="w-full rounded-[24px] border border-slate-200 bg-white shadow-[0_20px_64px_rgba(15,23,42,0.22)]">
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Galeriden Seç</h3>
                    <p class="mt-1 text-sm text-slate-500">Stok kodu veya kategori ile filtreleyin, uygun artwork kaydını seçin.</p>
                </div>
                <button type="button" id="gallery-modal-close" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">×</button>
            </div>

            <div class="space-y-4 px-6 py-5">
                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_240px]">
                    <div>
                        <label class="label" for="galleryFilterInput">Stok Kodu / Dosya Adı</label>
                        <input type="text" id="galleryFilterInput" class="input font-mono" placeholder="Örn: 5010118005440-5335">
                    </div>
                    <div>
                        <label class="label" for="galleryCategoryFilter">Kategori</label>
                        <select id="galleryCategoryFilter" class="input">
                            <option value="">Tüm kategoriler</option>
                            @foreach($galleryCategories as $category)
                                <option value="{{ $category->id }}">{{ DisplayText::normalize($category->display_name) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="galleryEmptyState" class="hidden rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-400">
                    Filtreye uygun galeri kaydı bulunamadı.
                </div>

                <div id="galleryGrid" class="grid max-h-[50vh] gap-3 overflow-y-auto pr-1 md:grid-cols-2">
                    @foreach($galleryCandidates as $candidate)
                        @php
                            $candidateStockName = DisplayText::normalize($candidate->stockCard?->stock_name ?? 'Stok kartı eşleşmedi');
                            $candidateCategoryName = DisplayText::normalize($candidate->stockCard?->category?->display_name ?? 'Kategorisiz');
                            $revisionBadge = 'REV' . str_pad((string) ((int) ($candidate->revision_no ?? 0)), 2, '0', STR_PAD_LEFT);
                            $systemRevisionBadge = ((int) ($candidate->revisions_max_revision_no ?? 0)) > 0
                                ? 'Sistemde Rev.' . str_pad((string) ((int) $candidate->revisions_max_revision_no), 2, '0', STR_PAD_LEFT)
                                : 'Henüz kullanılmadı';
                        @endphp
                        <button
                            type="button"
                            class="gallery-select-card block w-full rounded-2xl border border-slate-200 bg-slate-50/50 p-4 text-left transition hover:border-brand-300 hover:bg-brand-50/30 hover:shadow-sm"
                            data-gallery-item
                            data-gallery-id="{{ $candidate->id }}"
                            data-stock-code="{{ $candidate->stock_code }}"
                            data-revision-no="{{ (int) ($candidate->revision_no ?? 0) }}"
                            data-stock-name="{{ e($candidateStockName) }}"
                            data-category-id="{{ $candidate->stockCard?->category_id ?? $candidate->category_id }}"
                            data-category-name="{{ e($candidateCategoryName) }}"
                            data-file-name="{{ e($candidate->display_name) }}"
                            data-search="{{ mb_strtolower($candidate->display_name . ' ' . $candidate->stock_code . ' ' . $candidateStockName . ' ' . $candidateCategoryName) }}"
                        >
                            <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white/80">
                                <div class="aspect-[16/9] w-full bg-slate-100">
                                    @if($candidate->has_preview)
                                        <img
                                            src="{{ route('artworks.gallery.preview', $candidate, false) }}"
                                            alt="{{ $candidate->display_name }}"
                                            class="h-full w-full object-contain"
                                            loading="lazy"
                                            onerror="this.style.display='none';this.nextElementSibling.classList.remove('hidden');"
                                        >
                                        <div class="hidden h-full w-full items-center justify-center text-slate-300">
                                            <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2 1.586-1.586a2 2 0 012.828 0L20 14m-6-10h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-slate-300">
                                            <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 3.75h7l4 4V20.25A1.75 1.75 0 0 1 16.25 22h-8.5A1.75 1.75 0 0 1 6 20.25v-14.5A1.75 1.75 0 0 1 7.75 4Z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 3.75v4h4"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex flex-col gap-2">
                                    <span class="inline-flex w-fit rounded-lg bg-slate-900 px-2.5 py-1 text-[11px] font-semibold tracking-[0.14em] text-white">{{ $revisionBadge }}</span>
                                    <span class="inline-flex w-fit rounded-full bg-brand-100 px-3 py-1 text-[11px] font-semibold text-brand-700">{{ $systemRevisionBadge }}</span>
                                </div>
                                <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-medium text-slate-500">{{ $candidate->created_at->format('d.m.Y') }}</span>
                            </div>
                            <p class="mt-4 truncate text-base font-semibold text-slate-900">{{ $candidate->display_name }}</p>
                            <p class="mt-2 font-mono text-sm text-brand-700">{{ $candidate->stock_code }}</p>
                            <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ $candidateStockName }}</p>
                            <div class="mt-4 flex items-center justify-between gap-3">
                                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600">{{ $candidateCategoryName }}</span>
                                <span class="text-[11px] font-medium text-slate-400">Seçmek için tıklayın</span>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const fileInput = document.getElementById('artwork_file');
const dropZone = document.getElementById('dropZone');
const progressW = document.getElementById('progressWrapper');
const progressBar = document.getElementById('progressBar');
const progressPct = document.getElementById('progressPercent');
const progressFn = document.getElementById('progressFilename');
const progressSz = document.getElementById('progressSize');
const submitBtn = document.getElementById('submitBtn');
const form = document.getElementById('uploadForm');
const stockInput = document.getElementById('stock_code');
const stockNameInput = document.getElementById('stock_name');
const categoryNameInput = document.getElementById('category_name');
const stockLookupState = document.getElementById('stockLookupState');
const revisionInput = document.getElementById('revision_no');
const revisionHint = document.getElementById('revision_hint');
const sourceTypeInput = document.getElementById('source_type_input');
const galleryItemInput = document.getElementById('gallery_item_id_input');
const uploadPanel = document.getElementById('upload-panel');
const selectedGallerySummary = document.getElementById('selectedGallerySummary');
const selectedGalleryName = document.getElementById('selectedGalleryName');
const modalOpenBtn = document.getElementById('gallery-modal-open');
const modalCloseBtn = document.getElementById('gallery-modal-close');
const galleryModal = document.getElementById('galleryModal');
const galleryModalViewport = document.getElementById('galleryModalViewport');
const galleryFilterInput = document.getElementById('galleryFilterInput');
const galleryCategoryFilter = document.getElementById('galleryCategoryFilter');
const galleryEmptyState = document.getElementById('galleryEmptyState');
const galleryCards = Array.from(document.querySelectorAll('[data-gallery-item]'));
const uploadFormatsHint = document.querySelector('#upload-panel .mb-2 span.text-xs.text-slate-400');
const allowedUploadExtensionsLabel = @json($allowedUploadExtensionsLabel);
const nextRevisionNo = {{ $nextRevisionNoValue }};

let stockLookupTimer = null;
let uploadInProgress = false;
let activeXhr = null;
let currentSuggestedUploadRevision = nextRevisionNo;

function setLookupState(message, tone = 'muted') {
    if (!stockLookupState) return;
    stockLookupState.textContent = message;
    stockLookupState.classList.remove('hidden', 'text-slate-400', 'text-emerald-600', 'text-red-600');
    stockLookupState.classList.add(tone === 'success' ? 'text-emerald-600' : (tone === 'error' ? 'text-red-600' : 'text-slate-400'));
    if (!message) stockLookupState.classList.add('hidden');
}

function applySuggestedUploadRevision(nextRevision) {
    const suggestedRevision = Math.max(nextRevisionNo, Number(nextRevision ?? nextRevisionNo));
    currentSuggestedUploadRevision = suggestedRevision;

    if (revisionHint) {
        revisionHint.textContent = `Revizyon numarası tamamlanmadan upload tamamlanamaz. En az Rev.${String(suggestedRevision).padStart(2, '0')} olmalıdır.`;
    }

    if (!revisionInput) return;

    revisionInput.min = String(suggestedRevision);
    const currentValue = Number(revisionInput.value || 0);

    if (!currentValue || currentValue < suggestedRevision) {
        revisionInput.value = String(suggestedRevision);
    }
}

async function resolveStockCard() {
    const stockCode = stockInput.value.trim().toUpperCase();

    if (!stockCode) {
        stockNameInput.value = '';
        categoryNameInput.value = '';
        applySuggestedUploadRevision(nextRevisionNo);
        setLookupState('');
        return;
    }

    setLookupState('Stok kartı kontrol ediliyor...');

    try {
        const response = await fetch(`{{ route('stock-cards.lookup') }}?stock_code=${encodeURIComponent(stockCode)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            stockNameInput.value = '';
            categoryNameInput.value = '';
            setLookupState(payload.message ?? 'Stok kartı bulunamadı.', 'error');
            return;
        }

        const payload = await response.json();
        stockInput.value = payload.stock_code;
        stockNameInput.value = payload.stock_name ?? '';
        categoryNameInput.value = payload.category_name ?? '';
        applySuggestedUploadRevision(payload.next_upload_revision_no ?? nextRevisionNo);
        setLookupState('Stok kartı doğrulandı.', 'success');
        syncGalleryFilterFromStock();
    } catch (_error) {
        stockNameInput.value = '';
        categoryNameInput.value = '';
        setLookupState('Stok kartı kontrolü sırasında bağlantı hatası oluştu.', 'error');
    }
}

function syncGalleryFilterFromStock() {
    if (galleryFilterInput && stockInput.value.trim() !== '') {
        galleryFilterInput.value = stockInput.value.trim();
        filterGalleryCards();
    }
}

function setSourceMode(mode) {
    const isGallery = mode === 'gallery';
    sourceTypeInput.value = mode;
    uploadPanel.classList.toggle('hidden', isGallery);
    selectedGallerySummary.classList.toggle('hidden', !isGallery || !galleryItemInput.value);
    revisionInput.readOnly = isGallery;
    revisionInput.classList.toggle('bg-slate-50', isGallery);
    if (!isGallery) {
        galleryItemInput.value = '';
        applySuggestedUploadRevision(currentSuggestedUploadRevision);
    }
}

function applyGallerySelection(card) {
    galleryItemInput.value = card.dataset.galleryId;
    sourceTypeInput.value = 'gallery';
    stockInput.value = card.dataset.stockCode || '';
    revisionInput.value = card.dataset.revisionNo || '';
    stockNameInput.value = card.dataset.stockName || '';
    categoryNameInput.value = card.dataset.categoryName || '';
    selectedGalleryName.textContent = `${card.dataset.fileName} · ${card.dataset.stockCode || 'Stok kodu yok'}`;
    selectedGallerySummary.classList.remove('hidden');
    setLookupState('Galeri kaydı seçildi.', 'success');
    setSourceMode('gallery');
    closeGalleryModal();
    galleryCards.forEach(item => item.classList.remove('border-brand-400', 'bg-brand-50/50', 'ring-1', 'ring-brand-200'));
    card.classList.add('border-brand-400', 'bg-brand-50/50', 'ring-1', 'ring-brand-200');
}

function filterGalleryCards() {
    const needle = (galleryFilterInput.value || '').trim().toLowerCase();
    const categoryId = galleryCategoryFilter.value || '';
    let visibleCount = 0;

    galleryCards.forEach(card => {
        const matchesText = needle === '' || card.dataset.search.includes(needle);
        const matchesCategory = categoryId === '' || card.dataset.categoryId === categoryId;
        const visible = matchesText && matchesCategory;
        card.classList.toggle('hidden', !visible);
        if (visible) visibleCount++;
    });

    galleryEmptyState.classList.toggle('hidden', visibleCount > 0);
}

function openGalleryModal() {
    if (galleryModalViewport) {
        const sidebarWrap = document.getElementById('sidebar-wrap');
        const sidebarWidth = window.innerWidth >= 1024 && sidebarWrap ? sidebarWrap.getBoundingClientRect().width : 0;
        galleryModalViewport.style.transform = sidebarWidth > 0 ? `translateX(${sidebarWidth / 2}px)` : 'translateX(0)';
    }

    galleryModal.classList.remove('hidden');
    galleryModal.setAttribute('aria-hidden', 'false');
    syncGalleryFilterFromStock();
    filterGalleryCards();
    setTimeout(() => galleryFilterInput.focus(), 50);
}

function closeGalleryModal() {
    galleryModal.classList.add('hidden');
    galleryModal.setAttribute('aria-hidden', 'true');
}

if (modalOpenBtn) modalOpenBtn.addEventListener('click', openGalleryModal);
if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeGalleryModal);

if (galleryModal) {
    galleryModal.addEventListener('click', event => {
        if (event.target === galleryModal || event.target.classList.contains('bg-slate-900/45')) {
            closeGalleryModal();
        }
    });
}

document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !galleryModal.classList.contains('hidden')) {
        closeGalleryModal();
    }
});

galleryCards.forEach(card => {
    card.addEventListener('click', () => applyGallerySelection(card));
});

if (galleryFilterInput) galleryFilterInput.addEventListener('input', filterGalleryCards);
if (galleryCategoryFilter) galleryCategoryFilter.addEventListener('change', filterGalleryCards);

if (stockInput) {
    stockInput.addEventListener('input', () => {
        clearTimeout(stockLookupTimer);
        stockLookupTimer = setTimeout(resolveStockCard, 300);
    });

    stockInput.addEventListener('blur', () => {
        clearTimeout(stockLookupTimer);
        resolveStockCard();
    });
}

if (fileInput) {
    fileInput.addEventListener('change', event => {
        setSourceMode('upload');
        showFile(event.target.files[0]);
    });
}

if (dropZone) {
    dropZone.addEventListener('dragover', event => {
        event.preventDefault();
        dropZone.classList.add('border-brand-400', 'bg-brand-50/60');
    });

    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-brand-400', 'bg-brand-50/60'));
    dropZone.addEventListener('drop', event => {
        event.preventDefault();
        dropZone.classList.remove('border-brand-400', 'bg-brand-50/60');
        const file = event.dataTransfer.files[0];

        if (file) {
            fileInput.files = event.dataTransfer.files;
            setSourceMode('upload');
            showFile(file);
        }
    });
}

function showFile(file) {
    if (!file) return;

    const mb = (file.size / 1048576).toFixed(1);
    const ext = file.name.split('.').pop().toUpperCase();

    document.getElementById('drop-empty').classList.add('hidden');
    document.getElementById('drop-selected').classList.remove('hidden');
    document.getElementById('selected-file-name').textContent = file.name;
    document.getElementById('selected-file-meta').textContent = `${ext} · ${mb} MB`;
    dropZone.classList.remove('border-slate-300', 'hover:border-brand-400', 'hover:bg-brand-50/20');
    dropZone.classList.add('border-emerald-400', 'bg-emerald-50/40');
    progressFn.textContent = file.name;
    progressSz.textContent = `${mb} MB`;
}

window.addEventListener('beforeunload', function (event) {
    if (uploadInProgress) {
        event.preventDefault();
        event.returnValue = 'Dosya yükleniyor. Sayfadan ayrılırsanız yükleme iptal olur.';
    }
});

function resetUploadUI() {
    uploadInProgress = false;
    activeXhr = null;
    submitBtn.disabled = false;
    submitBtn.textContent = 'Kaydet';
    progressW.classList.add('hidden');
}

function setUploadError(message) {
    const errorBox = document.getElementById('upload-error-msg');
    if (!errorBox) return;
    errorBox.textContent = message;
    errorBox.classList.remove('hidden');
}

function startUpload() {
    const data = new FormData(form);
    const xhr = new XMLHttpRequest();
    activeXhr = xhr;

    uploadInProgress = true;
    progressW.classList.remove('hidden');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Yükleniyor...';
    progressBar.style.width = '0%';
    progressPct.textContent = '0%';

    xhr.upload.addEventListener('progress', event => {
        if (!event.lengthComputable) return;
        const pct = Math.round(event.loaded / event.total * 100);
        progressBar.style.width = `${pct}%`;
        progressPct.textContent = `${pct}%`;

        if (pct === 100) {
            submitBtn.textContent = 'Kaydediliyor...';
        }
    });

    xhr.addEventListener('load', () => {
        uploadInProgress = false;

        if (xhr.status >= 200 && xhr.status < 300) {
            if (xhr.responseURL && xhr.responseURL !== window.location.href) {
                window.location.href = xhr.responseURL;
            } else {
                window.location.href = "{{ route('order-lines.show', $line) }}";
            }

            return;
        }

        activeXhr = null;
        submitBtn.disabled = false;
        submitBtn.textContent = 'Tekrar dene';
        progressBar.classList.add('bg-red-500');
        progressBar.classList.remove('bg-brand-600');
        progressPct.textContent = 'Hata';

        let errorMessage = 'Yükleme sırasında bir hata oluştu. Form verisi korunur, yeniden deneyebilirsiniz.';

        if (xhr.status === 422) {
            try {
                const payload = JSON.parse(xhr.responseText);
                const firstError = payload?.errors ? Object.values(payload.errors).flat()[0] : null;
                errorMessage = firstError || payload?.message || 'Lütfen form alanlarını kontrol edin.';
            } catch (_error) {
                errorMessage = 'Lütfen form alanlarını kontrol edin.';
            }
        }

        setUploadError(errorMessage);

        if (xhr.status === 422 && stockInput) {
            stockInput.focus();
        }
    });

    xhr.addEventListener('error', () => {
        uploadInProgress = false;
        activeXhr = null;
        submitBtn.disabled = false;
        submitBtn.textContent = 'Tekrar dene';
        progressBar.classList.add('bg-red-500');
        progressBar.classList.remove('bg-brand-600');
        progressPct.textContent = 'Hata';
        setUploadError('Bağlantı hatası oluştu; form verisi korunur, yeniden deneyebilirsiniz.');
    });

    xhr.addEventListener('abort', resetUploadUI);
    xhr.open('POST', form.action);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send(data);
}

form.addEventListener('submit', function (event) {
    if (!stockNameInput.value || !categoryNameInput.value) {
        event.preventDefault();
        setLookupState('Kaydetmeden önce geçerli bir stok kartı seçin.', 'error');
        stockInput.focus();
        return;
    }

    if (sourceTypeInput.value === 'gallery' && !galleryItemInput.value) {
        event.preventDefault();
        setLookupState('Galeriden kullanım için bir kayıt seçin.', 'error');
        return;
    }

    if (sourceTypeInput.value === 'upload' && (!fileInput || !fileInput.files.length)) {
        return;
    }

    if (sourceTypeInput.value === 'gallery') {
        return;
    }

    event.preventDefault();
    progressBar.classList.remove('bg-red-500');
    progressBar.classList.add('bg-brand-600');
    setUploadError('');
    document.getElementById('upload-error-msg').classList.add('hidden');
    startUpload();
});

window.addEventListener('DOMContentLoaded', () => {
    if (uploadFormatsHint) {
        uploadFormatsHint.textContent = `Zorunlu · ${allowedUploadExtensionsLabel}`;
    }

    if (stockInput.value.trim()) resolveStockCard();
    applySuggestedUploadRevision(nextRevisionNo);
    setSourceMode(sourceTypeInput.value || 'upload');
    filterGalleryCards();

    if (galleryItemInput.value) {
        const selectedCard = galleryCards.find(card => card.dataset.galleryId === galleryItemInput.value);
        if (selectedCard) applyGallerySelection(selectedCard);
    }
});
</script>
@endpush
