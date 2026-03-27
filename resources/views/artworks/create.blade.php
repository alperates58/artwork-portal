@extends('layouts.app')
@section('title', 'Artwork Yükle')
@section('page-title', 'Artwork Yükle')

@section('header-actions')
    <a href="{{ route('orders.show', $line->purchaseOrder) }}" class="btn btn-secondary">← Siparişe dön</a>
@endsection

@section('content')
<div class="max-w-3xl space-y-5">
    {{-- Order info --}}
    <div class="card flex items-center gap-4 p-4">
        <div class="flex-1">
            <div class="mb-0.5 flex items-center gap-2">
                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-mono text-slate-600">{{ $line->line_no }}</span>
                <span class="text-sm font-semibold text-slate-900">{{ $line->product_code }}</span>
            </div>
            <p class="text-xs text-slate-500">
                {{ $line->purchaseOrder->order_no }} · {{ $line->purchaseOrder->supplier->name }}
            </p>
        </div>
        @if($line->artwork && $line->artwork->revisions->isNotEmpty())
            <div class="text-right">
                <p class="text-xs text-slate-500">Mevcut revizyon</p>
                <p class="text-sm font-semibold text-slate-900">Rev.{{ $line->artwork->revisions->first()->revision_no }}</p>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('artworks.store', $line) }}" enctype="multipart/form-data" id="uploadForm">
        @csrf
        <input type="hidden" name="source_type" id="source_type_input" value="{{ old('source_type', 'upload') }}">
        <input type="hidden" name="gallery_item_id" id="gallery_item_id_input" value="{{ old('gallery_item_id') }}">

        <div class="card space-y-5 p-6">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">
                    {{ $line->artwork ? 'Yeni revizyon oluştur' : 'İlk artwork yükle' }}
                </h2>
                <p class="mt-1 text-xs text-slate-500">Yeni dosya yükleyebilir veya galeri modalından mevcut artwork seçebilirsiniz.</p>
            </div>

            {{-- Gallery selected display --}}
            <div id="gallery-selected-display" class="{{ old('source_type') === 'gallery' ? '' : 'hidden' }} rounded-2xl border border-brand-200 bg-brand-50/70 p-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-brand-600 uppercase tracking-wide">Galeriden seçildi</p>
                        <p class="text-sm font-semibold text-slate-900 mt-0.5 truncate" id="selected-gallery-name">
                            {{ old('gallery_item_id') ? ($galleryItems->firstWhere('id', old('gallery_item_id'))?->display_name ?? '—') : '—' }}
                        </p>
                    </div>
                    <button type="button" id="clear-gallery-btn" class="btn btn-secondary text-xs shrink-0">Temizle</button>
                </div>
            </div>

            {{-- File input — outside dropzone so swapping innerHTML doesn't lose it --}}
            <input type="file" id="artwork_file" name="artwork_file" class="hidden"
                   accept=".pdf,.ai,.eps,.zip,.svg,.png,.jpg,.jpeg,.tif,.tiff,.psd,.indd">

            {{-- Upload zone --}}
            <div id="upload-panel" class="{{ old('source_type') === 'gallery' ? 'hidden' : '' }}">
                {{-- Drop zone --}}
                <div id="dropZone"
                     class="cursor-pointer rounded-2xl border-2 border-dashed border-slate-300 p-6 text-center transition-all hover:border-brand-400 hover:bg-brand-50/30"
                     onclick="document.getElementById('artwork_file').click()">

                    {{-- Empty state --}}
                    <div id="drop-empty">
                        <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="mt-3 text-sm font-medium text-slate-600">
                            Dosyayı sürükleyin veya <span class="text-brand-600">seçin</span>
                        </p>
                        <p class="mt-1 text-xs text-slate-400">PDF, AI, EPS, ZIP, PSD, INDD, PNG, TIF — Maks. 1.2 GB</p>
                    </div>

                    {{-- Selected state (hidden until file picked) --}}
                    <div id="drop-selected" class="hidden">
                        <svg class="mx-auto h-8 w-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="mt-2 text-sm font-semibold text-emerald-700" id="selected-file-name">—</p>
                        <p class="mt-0.5 text-xs text-emerald-600" id="selected-file-meta">—</p>
                        <p class="mt-2 text-xs text-slate-400 underline">Değiştirmek için tıklayın</p>
                    </div>
                </div>

                @error('artwork_file')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror

                <div id="progressWrapper" class="mt-4 hidden">
                    <div class="mb-1.5 flex items-center justify-between text-xs text-slate-500">
                        <span id="progressFilename" class="max-w-xs truncate"></span>
                        <span id="progressPercent">0%</span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                        <div id="progressBar" class="h-1.5 rounded-full bg-brand-600 transition-all duration-300" style="width:0%"></div>
                    </div>
                    <p class="mt-1 text-xs text-slate-400" id="progressSize"></p>
                </div>

                <button type="button"
                        id="open-gallery-modal-btn"
                        class="mt-3 w-full inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                        onclick="document.getElementById('gallery-select-dialog').showModal()">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-10h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Galeriden Seç
                </button>
            </div>

            {{-- Stock code --}}
            <div>
                <label class="label" for="stock_code">Stok Kodu</label>
                <input type="text" id="stock_code" name="stock_code" value="{{ old('stock_code') }}" class="input font-mono" placeholder="ERP stok kodu (opsiyonel)">
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="label" for="title">Başlık</label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" class="input" placeholder="Opsiyonel başlık">
                </div>
                <div>
                    <label class="label" for="gallery_name">Galeri adı</label>
                    <input type="text" id="gallery_name" name="gallery_name" value="{{ old('gallery_name') }}" class="input" placeholder="Boş kalırsa dosya adı kullanılır">
                </div>
            </div>

            <div>
                <label class="label" for="description">Açıklama</label>
                <input type="text" id="description" name="description" value="{{ old('description') }}" class="input" placeholder="Kısa açıklama (opsiyonel)">
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="label mb-0" for="category_id">Kategori</label>
                        @if(auth()->user()->hasPermission('gallery', 'manage'))
                            <button type="button" class="text-xs text-brand-600 hover:underline" onclick="document.getElementById('quick-category-form').classList.toggle('hidden')">+ Yeni</button>
                        @endif
                    </div>
                    <select id="category_id" name="category_id" class="input">
                        <option value="">Kategori seçin</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->display_name }}</option>
                        @endforeach
                    </select>
                    @if(auth()->user()->hasPermission('gallery', 'manage'))
                        <div id="quick-category-form" class="hidden mt-2">
                            <div class="flex gap-2">
                                <input type="text" name="name" form="qcat-form" class="input flex-1 text-sm py-1.5" placeholder="Kategori adı" required>
                                <button type="submit" form="qcat-form" class="btn btn-secondary text-xs py-1.5 px-3">Ekle</button>
                            </div>
                        </div>
                    @endif
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="label mb-0">Etiketler</label>
                        @if(auth()->user()->hasPermission('gallery', 'manage'))
                            <button type="button" class="text-xs text-brand-600 hover:underline" onclick="document.getElementById('quick-tag-form').classList.toggle('hidden')">+ Yeni</button>
                        @endif
                    </div>
                    @php $selectedTagIds = collect(old('tag_ids', [])); @endphp
                    <div class="flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-white p-3 min-h-[56px]">
                        @forelse($tags as $tag)
                            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition
                                {{ $selectedTagIds->contains($tag->id) ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300' }}">
                                <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}"
                                       class="sr-only"
                                       {{ $selectedTagIds->contains($tag->id) ? 'checked' : '' }}
                                       onchange="this.closest('label').classList.toggle('border-brand-500', this.checked);
                                                 this.closest('label').classList.toggle('bg-brand-50', this.checked);
                                                 this.closest('label').classList.toggle('text-brand-700', this.checked);
                                                 this.closest('label').classList.toggle('border-slate-200', !this.checked);
                                                 this.closest('label').classList.toggle('bg-slate-50', !this.checked);
                                                 this.closest('label').classList.toggle('text-slate-600', !this.checked);">
                                {{ $tag->display_name }}
                            </label>
                        @empty
                            <p class="text-xs text-slate-400">Henüz etiket yok.</p>
                        @endforelse
                    </div>
                    @if(auth()->user()->hasPermission('gallery', 'manage'))
                        <div id="quick-tag-form" class="hidden mt-2">
                            <div class="flex gap-2">
                                <input type="text" name="name" form="qtag-form" class="input flex-1 text-sm py-1.5" placeholder="Etiket adı" required>
                                <button type="submit" form="qtag-form" class="btn btn-secondary text-xs py-1.5 px-3">Ekle</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <label class="label" for="notes">Revizyon No</label>
                <input type="text" id="notes" name="notes" value="{{ old('notes') }}" class="input w-32" placeholder="01">
                <p class="hint">Revizyon numarası (örn: 01, 02, 03…)</p>
            </div>

            @if($line->artwork)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700">
                    <strong>Dikkat:</strong> Yeni revizyon mevcut aktif kaydı pasife alır, ancak eski revizyonlar arşivde kalır.
                </div>
            @endif

            <div class="flex gap-3">
                <button type="submit" id="submitBtn" class="btn btn-primary flex-1 justify-center py-2.5 disabled:cursor-not-allowed disabled:opacity-50">
                    Kaydet
                </button>
                <a href="{{ route('orders.show', $line->purchaseOrder) }}" class="btn btn-secondary px-6">İptal</a>
            </div>
        </div>
    </form>

    {{-- Quick category/tag forms — OUTSIDE main form to avoid nesting --}}
    @if(auth()->user()->hasPermission('gallery', 'manage'))
        <form id="qcat-form" method="POST" action="{{ route('admin.artwork-gallery.categories.store') }}" class="hidden">
            @csrf
            <input type="hidden" name="_redirect_back" value="1">
        </form>
        <form id="qtag-form" method="POST" action="{{ route('admin.artwork-gallery.tags.store') }}" class="hidden">
            @csrf
            <input type="hidden" name="_redirect_back" value="1">
        </form>
    @endif

    {{-- Revision history --}}
    @if($line->artwork && $line->artwork->revisions->isNotEmpty())
        <div class="card">
            <div class="border-b border-slate-100 px-5 py-3">
                <h3 class="text-sm font-semibold text-slate-900">Revizyon geçmişi</h3>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach($line->artwork->revisions as $rev)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <span class="shrink-0 rounded bg-slate-100 px-2 py-1 text-xs font-mono text-slate-700">Rev.{{ $rev->revision_no }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm text-slate-800">{{ $rev->original_filename }}</p>
                            <p class="text-xs text-slate-400">
                                {{ $rev->file_size_formatted }} · {{ $rev->uploadedBy->name }} · {{ $rev->created_at->format('d.m.Y H:i') }}
                                @if($rev->galleryItem)
                                    · Galeri: {{ $rev->galleryItem->display_name }}
                                @endif
                            </p>
                        </div>
                        @if($rev->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-gray">Arşiv</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

{{-- Gallery select modal --}}
<dialog id="gallery-select-dialog"
        class="w-full max-w-5xl rounded-3xl border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/40"
        style="max-height: 90vh;">
    <div class="border-b border-slate-200 px-6 py-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Galeriden Seç</h3>
                <p class="mt-0.5 text-xs text-slate-500">Stok kodu veya isimle arayın, artwork seçin</p>
            </div>
            <button type="button" class="btn btn-secondary text-xs" data-dialog-close
                    onclick="document.getElementById('gallery-select-dialog').close()">Kapat</button>
        </div>

        {{-- Filter form (GET, reloads page with modal params) --}}
        <form method="GET" action="{{ route('artworks.create', $line) }}" class="mt-3 flex flex-wrap items-end gap-2" id="gallery-modal-filter">
            <input type="hidden" name="open_modal" value="1">
            <div class="flex-1 min-w-[160px]">
                <input name="gallery_search" value="{{ request('gallery_search') }}" class="input text-sm" placeholder="Dosya adı veya stok kodu ara…">
            </div>
            <div class="w-36">
                <select name="gallery_category_id" class="input text-sm">
                    <option value="">Tüm kategoriler</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('gallery_category_id') === (string) $category->id)>{{ $category->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-secondary text-xs">Filtrele</button>
            @if(request('gallery_search') || request('gallery_category_id') || request('gallery_tag_id'))
                <a href="{{ route('artworks.create', $line) }}?open_modal=1" class="btn btn-secondary text-xs">Temizle</a>
            @endif
        </form>
    </div>

    <div class="overflow-y-auto p-5" style="max-height: calc(90vh - 140px)">
        @if($galleryItems->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-slate-400">
                <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-10h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="mt-3 text-sm">Filtrelere uygun galeri kaydı bulunamadı.</p>
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($galleryItems as $item)
                    <div class="gallery-modal-item rounded-2xl border border-slate-200 p-4 transition cursor-pointer hover:border-brand-300 hover:shadow-sm"
                         data-gallery-id="{{ $item->id }}"
                         data-gallery-name="{{ $item->display_name }}"
                         data-gallery-stock="{{ $item->stock_code ?? '' }}">
                        <div class="flex items-start gap-3">
                            @include('artwork-gallery.partials.file-visual', [
                                'artworkGallery' => $item,
                                'sizeClass' => 'h-14 w-14',
                            ])
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $item->display_name }}</p>
                                @if($item->stock_code)
                                    <p class="font-mono text-xs text-brand-600 mt-0.5">{{ $item->stock_code }}</p>
                                @endif
                                <p class="text-xs text-slate-400 mt-0.5">{{ $item->file_type_display }} · {{ $item->file_size_formatted }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $item->category?->display_name ?? 'Kategorisiz' }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-2">
                            <span class="text-xs text-slate-400">{{ $item->usage_count }} kullanım</span>
                            <button type="button"
                                    class="select-gallery-btn btn btn-primary text-xs py-1 px-3"
                                    data-gallery-id="{{ $item->id }}"
                                    data-gallery-name="{{ $item->display_name }}"
                                    data-gallery-stock="{{ $item->stock_code ?? '' }}">
                                Seç
                            </button>
                        </div>
                    </div>

                    @include('artwork-gallery.partials.preview-dialog', [
                        'artworkGallery' => $item,
                        'dialogId' => 'modal-preview-' . $item->id,
                    ])
                @endforeach
            </div>
        @endif
    </div>
</dialog>
@endsection

@push('scripts')
<script>
const fileInput    = document.getElementById('artwork_file');
const dropZone     = document.getElementById('dropZone');
const progressW    = document.getElementById('progressWrapper');
const progressBar  = document.getElementById('progressBar');
const progressPct  = document.getElementById('progressPercent');
const progressFn   = document.getElementById('progressFilename');
const progressSz   = document.getElementById('progressSize');
const submitBtn    = document.getElementById('submitBtn');
const form         = document.getElementById('uploadForm');
const uploadPanel  = document.getElementById('upload-panel');
const galleryDisp  = document.getElementById('gallery-selected-display');
const galleryName  = document.getElementById('selected-gallery-name');
const srcInput     = document.getElementById('source_type_input');
const gIdInput     = document.getElementById('gallery_item_id_input');
const clearGallBtn = document.getElementById('clear-gallery-btn');
const stockInput   = document.getElementById('stock_code');

// Gallery item selection from modal
document.querySelectorAll('.select-gallery-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const id    = btn.dataset.galleryId;
        const name  = btn.dataset.galleryName;
        const stock = btn.dataset.galleryStock;

        srcInput.value = 'gallery';
        gIdInput.value = id;
        galleryName.textContent = name;
        galleryDisp.classList.remove('hidden');
        uploadPanel.classList.add('hidden');

        if (stock && stockInput && !stockInput.value) {
            stockInput.value = stock;
        }

        document.getElementById('gallery-select-dialog').close();
    });
});

// Clear gallery selection → back to upload mode
if (clearGallBtn) {
    clearGallBtn.addEventListener('click', () => {
        srcInput.value = 'upload';
        gIdInput.value = '';
        galleryDisp.classList.add('hidden');
        uploadPanel.classList.remove('hidden');
    });
}

// File drag-drop
if (fileInput) {
    fileInput.addEventListener('change', e => showFile(e.target.files[0]));
}

if (dropZone) {
    dropZone.addEventListener('dragover', e => {
        e.preventDefault();
        dropZone.classList.add('border-brand-400', 'bg-brand-50/60');
    });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-brand-400', 'bg-brand-50/60'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('border-brand-400', 'bg-brand-50/60');
        const file = e.dataTransfer.files[0];
        if (file) { fileInput.files = e.dataTransfer.files; showFile(file); }
    });
}

function showFile(file) {
    if (!file) return;

    const mb      = (file.size / 1048576).toFixed(1);
    const ext     = file.name.split('.').pop().toUpperCase();
    const dropEmpty    = document.getElementById('drop-empty');
    const dropSelected = document.getElementById('drop-selected');

    // Switch to "selected" state
    dropEmpty.classList.add('hidden');
    dropSelected.classList.remove('hidden');
    document.getElementById('selected-file-name').textContent = file.name;
    document.getElementById('selected-file-meta').textContent = `${ext} · ${mb} MB`;

    // Green border
    dropZone.classList.remove('border-slate-300', 'border-dashed', 'hover:border-brand-400', 'hover:bg-brand-50/30');
    dropZone.classList.add('border-emerald-400', 'bg-emerald-50/40');

    // Progress bar info
    if (progressFn) progressFn.textContent = file.name;
    if (progressSz) progressSz.textContent = `${mb} MB`;
}

// XHR upload with progress
form.addEventListener('submit', function(e) {
    const source = srcInput.value;
    if (source !== 'upload' || !fileInput || !fileInput.files.length) return;

    e.preventDefault();
    progressW.classList.remove('hidden');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Yükleniyor…';

    const xhr  = new XMLHttpRequest();
    const data = new FormData(form);

    xhr.upload.addEventListener('progress', ev => {
        if (!ev.lengthComputable) return;
        const pct = Math.round(ev.loaded / ev.total * 100);
        progressBar.style.width = `${pct}%`;
        progressPct.textContent = `${pct}%`;
    });

    xhr.addEventListener('load', () => {
        if (xhr.status === 302 || xhr.responseURL) {
            window.location.href = xhr.responseURL || "{{ route('orders.show', $line->purchaseOrder) }}";
            return;
        }
        form.submit();
    });

    xhr.addEventListener('error', () => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Kaydet';
        alert('Yükleme başarısız. Lütfen tekrar deneyin.');
    });

    xhr.open('POST', form.action);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send(data);
});

// Auto-open modal if redirected back after filtering
@if(request('open_modal'))
window.addEventListener('DOMContentLoaded', () => {
    document.getElementById('gallery-select-dialog')?.showModal();
});
@endif
</script>
@endpush
