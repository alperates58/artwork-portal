@extends('layouts.app')
@section('title', 'Artwork Yükle')
@section('page-title', 'Artwork Yükle')

@section('header-actions')
    <a href="{{ route('orders.show', $line->purchaseOrder) }}" class="btn-secondary">← Siparişe dön</a>
@endsection

@php
    $selectedSource = old('source_type', 'upload');
@endphp

@section('content')
<div class="max-w-7xl space-y-5">
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

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_440px]">
            <div class="space-y-5">
                <div class="card space-y-5 p-6">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">
                            {{ $line->artwork ? 'Yeni revizyon oluştur' : 'İlk artwork yükle' }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">Mevcut revizyon mantığı korunur. Kaynak olarak yeni dosya yükleyebilir veya galeriden seçim yapabilirsiniz.</p>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="cursor-pointer rounded-2xl border p-4 {{ $selectedSource === 'upload' ? 'border-brand-500 bg-brand-50/60' : 'border-slate-200' }}">
                            <input type="radio" name="source_type" value="upload" class="sr-only" {{ $selectedSource === 'upload' ? 'checked' : '' }}>
                            <p class="text-sm font-semibold text-slate-900">Yeni dosya yükle</p>
                            <p class="mt-1 text-xs text-slate-500">Dosya storage alanına yüklenir, galeriye kaydolur ve yeni revizyon oluşur.</p>
                        </label>
                        <label class="cursor-pointer rounded-2xl border p-4 {{ $selectedSource === 'gallery' ? 'border-brand-500 bg-brand-50/60' : 'border-slate-200' }}">
                            <input type="radio" name="source_type" value="gallery" class="sr-only" {{ $selectedSource === 'gallery' ? 'checked' : '' }}>
                            <p class="text-sm font-semibold text-slate-900">Galeriden seç</p>
                            <p class="mt-1 text-xs text-slate-500">Yeni fiziksel upload yapmadan mevcut galeri dosyası ile revizyon oluşturur.</p>
                        </label>
                    </div>

                    <div id="upload-panel" class="{{ $selectedSource === 'upload' ? '' : 'hidden' }}">
                        <div
                            id="dropZone"
                            class="mb-4 cursor-pointer rounded-2xl border-2 border-dashed border-slate-300 p-8 text-center transition-colors hover:border-blue-400"
                            onclick="document.getElementById('artwork_file').click()"
                        >
                            <div class="mb-3">
                                <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-slate-600" id="dropText">
                                Dosyayı sürükleyin veya <span class="text-blue-600">seçin</span>
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                PDF, AI, EPS, ZIP, PSD, INDD, PNG, TIF - Maks. 1.2 GB
                            </p>
                            <input type="file" id="artwork_file" name="artwork_file" class="hidden" accept=".pdf,.ai,.eps,.zip,.svg,.png,.jpg,.jpeg,.tif,.tiff,.psd,.indd">
                        </div>
                        @error('artwork_file')
                            <p class="-mt-2 mb-4 text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <div id="progressWrapper" class="mb-5 hidden">
                            <div class="mb-1.5 flex items-center justify-between text-xs text-slate-500">
                                <span id="progressFilename" class="max-w-xs truncate"></span>
                                <span id="progressPercent">0%</span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-slate-100">
                                <div id="progressBar" class="h-1.5 rounded-full bg-blue-600 transition-all duration-300" style="width:0%"></div>
                            </div>
                            <p class="mt-1 text-xs text-slate-400" id="progressSize"></p>
                        </div>
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

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="label" for="category_id">Kategori</label>
                            <select id="category_id" name="category_id" class="input">
                                <option value="">Kategori seçin</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label" for="tag_ids">Etiketler</label>
                            <select id="tag_ids" name="tag_ids[]" class="input min-h-32" multiple>
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}" @selected(collect(old('tag_ids', []))->contains($tag->id))>{{ $tag->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="label" for="notes">Revizyon notu</label>
                        <textarea id="notes" name="notes" rows="3" class="input resize-none" placeholder="Revizyon notları veya değişiklik açıklaması">{{ old('notes') }}</textarea>
                    </div>

                    @if($line->artwork)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700">
                            <strong>Dikkat:</strong> Yeni revizyon mevcut aktif kaydı pasife alır, ancak eski revizyonlar arşivde kalır.
                        </div>
                    @endif

                    <div class="flex gap-3">
                        <button type="submit" id="submitBtn" class="btn-primary flex-1 justify-center py-2.5 disabled:cursor-not-allowed disabled:opacity-50">
                            Kaydet
                        </button>
                        <a href="{{ route('orders.show', $line->purchaseOrder) }}" class="btn-secondary px-6">İptal</a>
                    </div>
                </div>

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

            <aside class="space-y-5">
                <div class="card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Galeriden seç</h3>
                            <p class="mt-1 text-xs text-slate-500">Kategori ve etiket bilgileriyle mevcut artwork dosyalarını filtreleyin.</p>
                        </div>
                        <span class="badge badge-info">Reuse</span>
                    </div>

                    <form method="GET" action="{{ route('artworks.create', $line) }}" class="mt-4 space-y-3">
                        <div>
                            <label class="label" for="gallery_search">Arama</label>
                            <input id="gallery_search" name="gallery_search" value="{{ request('gallery_search') }}" class="input" placeholder="Dosya adı veya artwork adı">
                        </div>
                        <div>
                            <label class="label" for="gallery_category_id">Kategori</label>
                            <select id="gallery_category_id" name="gallery_category_id" class="input">
                                <option value="">Tüm kategoriler</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) request('gallery_category_id') === (string) $category->id)>{{ $category->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label" for="gallery_tag_id">Etiket</label>
                            <select id="gallery_tag_id" name="gallery_tag_id" class="input">
                                <option value="">Tüm etiketler</option>
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}" @selected((string) request('gallery_tag_id') === (string) $tag->id)>{{ $tag->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="btn-secondary flex-1 justify-center">Filtrele</button>
                            <a href="{{ route('artworks.create', $line) }}" class="btn-secondary">Temizle</a>
                        </div>
                    </form>
                </div>

                <div id="gallery-panel" class="card p-5 {{ $selectedSource === 'gallery' ? '' : 'hidden' }}">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Galeri sonuçları</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ $galleryItems->count() }} kayıt listeleniyor</p>
                        </div>
                    </div>

                    <div class="space-y-3 max-h-[720px] overflow-y-auto">
                        @forelse($galleryItems as $item)
                            <div class="rounded-2xl border border-slate-200 p-4 transition hover:border-brand-300">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="gallery_item_id" value="{{ $item->id }}" class="mt-6" @checked((string) old('gallery_item_id') === (string) $item->id)>

                                    <div class="shrink-0">
                                        @include('artwork-gallery.partials.file-visual', [
                                            'artworkGallery' => $item,
                                            'sizeClass' => 'h-20 w-20',
                                        ])
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="truncate text-sm font-semibold text-slate-900">{{ $item->display_name }}</p>
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
                                                <dt class="font-medium text-slate-400">Oluşturulma</dt>
                                                <dd class="mt-1 text-slate-700">{{ $item->created_at->format('d.m.Y') }}</dd>
                                            </div>
                                        </dl>

                                        @if($item->tags->isNotEmpty())
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @foreach($item->tags as $tag)
                                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">{{ $tag->display_name }}</span>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if($item->display_revision_note)
                                            <p class="mt-3 text-xs text-slate-500">{{ $item->display_revision_note }}</p>
                                        @endif

                                        <div class="mt-4 flex flex-wrap items-center gap-2">
                                            <button type="button" class="btn-secondary px-3 py-2" data-dialog-open="gallery-preview-{{ $item->id }}">Görüntüle</button>
                                            <span class="text-xs text-slate-500">{{ $item->last_used_at ? \Illuminate\Support\Carbon::parse($item->last_used_at)->format('d.m.Y H:i') : 'Henüz kullanılmadı' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @include('artwork-gallery.partials.preview-dialog', [
                                'artworkGallery' => $item,
                                'dialogId' => 'gallery-preview-' . $item->id,
                            ])
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-200 p-5 text-sm text-slate-500">
                                Filtrelere uygun galeri kaydı bulunamadı.
                            </div>
                        @endforelse
                    </div>
                    @error('gallery_item_id')
                        <p class="mt-3 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </aside>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const fileInput = document.getElementById('artwork_file');
const dropZone = document.getElementById('dropZone');
const dropText = document.getElementById('dropText');
const progressW = document.getElementById('progressWrapper');
const progressBar = document.getElementById('progressBar');
const progressPct = document.getElementById('progressPercent');
const progressFn = document.getElementById('progressFilename');
const progressSz = document.getElementById('progressSize');
const submitBtn = document.getElementById('submitBtn');
const form = document.getElementById('uploadForm');
const uploadPanel = document.getElementById('upload-panel');
const galleryPanel = document.getElementById('gallery-panel');

document.querySelectorAll('input[name="source_type"]').forEach((input) => {
    input.addEventListener('change', () => {
        const isUpload = input.value === 'upload' && input.checked;
        uploadPanel.classList.toggle('hidden', !isUpload);
        galleryPanel.classList.toggle('hidden', isUpload);
    });
});

if (fileInput) {
    fileInput.addEventListener('change', (e) => showFile(e.target.files[0]));
}

if (dropZone) {
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-blue-400', 'bg-blue-50');
    });

    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-blue-400', 'bg-blue-50'));

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-400', 'bg-blue-50');
        const file = e.dataTransfer.files[0];

        if (file) {
            fileInput.files = e.dataTransfer.files;
            showFile(file);
        }
    });
}

function showFile(file) {
    if (!file) {
        return;
    }

    const mb = (file.size / 1048576).toFixed(1);
    dropText.innerHTML = `<span class="font-medium text-blue-600">${file.name}</span>`;
    progressFn.textContent = file.name;
    progressSz.textContent = `${mb} MB`;
}

form.addEventListener('submit', function (e) {
    const source = document.querySelector('input[name="source_type"]:checked')?.value;

    if (source !== 'upload' || !fileInput || !fileInput.files.length) {
        return;
    }

    e.preventDefault();
    progressW.classList.remove('hidden');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Yükleniyor...';

    const xhr = new XMLHttpRequest();
    const data = new FormData(form);

    xhr.upload.addEventListener('progress', (ev) => {
        if (!ev.lengthComputable) {
            return;
        }

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
</script>
@endpush
