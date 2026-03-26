@extends('layouts.app')
@section('title', 'Artwork Yukle')
@section('page-title', 'Artwork Yukle')

@section('header-actions')
    <a href="{{ route('orders.show', $line->purchaseOrder) }}" class="btn-secondary">← Siparise Don</a>
@endsection

@php
    $selectedSource = old('source_type', 'upload');
@endphp

@section('content')
<div class="max-w-5xl space-y-5">
    <div class="card p-4 flex items-center gap-4">
        <div class="flex-1">
            <div class="flex items-center gap-2 mb-0.5">
                <span class="text-xs font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-600">{{ $line->line_no }}</span>
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

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
            <div class="space-y-5">
                <div class="card p-6 space-y-5">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">
                            {{ $line->artwork ? 'Yeni Revizyon Olustur' : 'Ilk Artwork Yukle' }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">Mevcut revizyon sistemi korunur. Kaynagi yeni dosya ya da galeri olabilir.</p>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="rounded-2xl border p-4 cursor-pointer {{ $selectedSource === 'upload' ? 'border-brand-500 bg-brand-50/60' : 'border-slate-200' }}">
                            <input type="radio" name="source_type" value="upload" class="sr-only" {{ $selectedSource === 'upload' ? 'checked' : '' }}>
                            <p class="text-sm font-semibold text-slate-900">Yeni dosya yukle</p>
                            <p class="mt-1 text-xs text-slate-500">Dosya storage'a yuklenir, galeriye kaydolur ve revizyon olusur.</p>
                        </label>
                        <label class="rounded-2xl border p-4 cursor-pointer {{ $selectedSource === 'gallery' ? 'border-brand-500 bg-brand-50/60' : 'border-slate-200' }}">
                            <input type="radio" name="source_type" value="gallery" class="sr-only" {{ $selectedSource === 'gallery' ? 'checked' : '' }}>
                            <p class="text-sm font-semibold text-slate-900">Galeriden sec</p>
                            <p class="mt-1 text-xs text-slate-500">Yeni fiziksel upload yapmadan mevcut dosya ile revizyon olusturur.</p>
                        </label>
                    </div>

                    <div id="upload-panel" class="{{ $selectedSource === 'upload' ? '' : 'hidden' }}">
                        <div
                            id="dropZone"
                            class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:border-blue-400 transition-colors cursor-pointer mb-4"
                            onclick="document.getElementById('artwork_file').click()"
                        >
                            <div class="mb-3">
                                <svg class="w-10 h-10 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-slate-600" id="dropText">
                                Dosyayi surukleyin veya <span class="text-blue-600">secin</span>
                            </p>
                            <p class="text-xs text-slate-400 mt-1">
                                PDF, AI, EPS, ZIP, PSD, INDD, PNG, TIF - Maks. 1.2 GB
                            </p>
                            <input type="file" id="artwork_file" name="artwork_file" class="hidden" accept=".pdf,.ai,.eps,.zip,.svg,.png,.jpg,.jpeg,.tif,.tiff,.psd,.indd">
                        </div>
                        @error('artwork_file')
                            <p class="text-xs text-red-600 -mt-2 mb-4">{{ $message }}</p>
                        @enderror

                        <div id="progressWrapper" class="hidden mb-5">
                            <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
                                <span id="progressFilename" class="truncate max-w-xs"></span>
                                <span id="progressPercent">0%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5">
                                <div id="progressBar" class="bg-blue-600 h-1.5 rounded-full transition-all duration-300" style="width:0%"></div>
                            </div>
                            <p class="text-xs text-slate-400 mt-1" id="progressSize"></p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="label" for="title">Baslik</label>
                            <input type="text" id="title" name="title" value="{{ old('title') }}" class="input" placeholder="Opsiyonel baslik">
                        </div>
                        <div>
                            <label class="label" for="gallery_name">Galeri adi</label>
                            <input type="text" id="gallery_name" name="gallery_name" value="{{ old('gallery_name') }}" class="input" placeholder="Bos kalirsa dosya adi kullanilir">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="label" for="category_id">Kategori</label>
                            <select id="category_id" name="category_id" class="input">
                                <option value="">Kategori secin</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label" for="tag_ids">Etiketler</label>
                            <select id="tag_ids" name="tag_ids[]" class="input min-h-32" multiple>
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}" @selected(collect(old('tag_ids', []))->contains($tag->id))>{{ $tag->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="label" for="notes">Revizyon notu</label>
                        <textarea id="notes" name="notes" rows="3" class="input resize-none" placeholder="Revizyon notlari, degisiklik aciklamasi...">{{ old('notes') }}</textarea>
                    </div>

                    @if($line->artwork)
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700">
                            <strong>Dikkat:</strong> Yeni revizyon mevcut aktif kaydi pasife alir, ancak eski revizyonlar arsivde kalir.
                        </div>
                    @endif

                    <div class="flex gap-3">
                        <button type="submit" id="submitBtn" class="btn-primary flex-1 justify-center py-2.5 disabled:opacity-50 disabled:cursor-not-allowed">
                            Kaydet
                        </button>
                        <a href="{{ route('orders.show', $line->purchaseOrder) }}" class="btn-secondary px-6">Iptal</a>
                    </div>
                </div>

                @if($line->artwork && $line->artwork->revisions->isNotEmpty())
                    <div class="card">
                        <div class="px-5 py-3 border-b border-slate-100">
                            <h3 class="text-sm font-semibold text-slate-900">Revizyon Gecmisi</h3>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @foreach($line->artwork->revisions as $rev)
                                <div class="px-5 py-3 flex items-center gap-3">
                                    <span class="text-xs font-mono bg-slate-100 px-2 py-1 rounded text-slate-700 flex-shrink-0">Rev.{{ $rev->revision_no }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-slate-800 truncate">{{ $rev->original_filename }}</p>
                                        <p class="text-xs text-slate-400">
                                            {{ $rev->file_size_formatted }} · {{ $rev->uploadedBy->name }} · {{ $rev->created_at->format('d.m.Y H:i') }}
                                            @if($rev->galleryItem)
                                                · Galeri: {{ $rev->galleryItem->name }}
                                            @endif
                                        </p>
                                    </div>
                                    @if($rev->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-gray">Arsiv</span>
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
                            <h3 class="text-sm font-semibold text-slate-900">Galeriden Sec</h3>
                            <p class="mt-1 text-xs text-slate-500">Arama, kategori ve etiket ile mevcut artwork kayitlarini kullanin.</p>
                        </div>
                        <span class="badge badge-info">Galeriden Sec</span>
                    </div>

                    <form method="GET" action="{{ route('artworks.create', $line) }}" class="mt-4 space-y-3">
                        <div>
                            <label class="label" for="gallery_search">Arama</label>
                            <input id="gallery_search" name="gallery_search" value="{{ request('gallery_search') }}" class="input" placeholder="Dosya veya artwork adi">
                        </div>
                        <div>
                            <label class="label" for="gallery_category_id">Kategori</label>
                            <select id="gallery_category_id" name="gallery_category_id" class="input">
                                <option value="">Tum kategoriler</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) request('gallery_category_id') === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label" for="gallery_tag_id">Etiket</label>
                            <select id="gallery_tag_id" name="gallery_tag_id" class="input">
                                <option value="">Tum etiketler</option>
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}" @selected((string) request('gallery_tag_id') === (string) $tag->id)>{{ $tag->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn-secondary w-full justify-center">Filtrele</button>
                    </form>
                </div>

                <div id="gallery-panel" class="card p-5 {{ $selectedSource === 'gallery' ? '' : 'hidden' }}">
                    <div class="space-y-3 max-h-[640px] overflow-y-auto">
                        @forelse($galleryItems as $item)
                            <label class="block rounded-2xl border border-slate-200 p-4 cursor-pointer hover:border-brand-300">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="gallery_item_id" value="{{ $item->id }}" class="mt-1" @checked((string) old('gallery_item_id') === (string) $item->id)>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-sm font-semibold text-slate-900 truncate">{{ $item->name }}</p>
                                            <span class="text-[11px] font-mono rounded bg-slate-100 px-2 py-1 text-slate-500">{{ $item->extension }}</span>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $item->category?->name ?? 'Kategorisiz' }} · {{ $item->file_size_formatted }} · {{ $item->uploadedBy->name }} · {{ $item->created_at->format('d.m.Y') }}
                                        </p>
                                        @if($item->tags->isNotEmpty())
                                            <div class="mt-2 flex flex-wrap gap-1">
                                                @foreach($item->tags as $tag)
                                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600">{{ $tag->name }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if($item->revision_note)
                                            <p class="mt-2 text-xs text-slate-500">{{ $item->revision_note }}</p>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-200 p-5 text-sm text-slate-500">
                                Filtrelere uygun galeri kaydi bulunamadi.
                            </div>
                        @endforelse
                    </div>
                    @error('gallery_item_id')
                        <p class="text-xs text-red-600 mt-3">{{ $message }}</p>
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
    fileInput.addEventListener('change', e => showFile(e.target.files[0]));
}

if (dropZone) {
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('border-blue-400','bg-blue-50'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-blue-400','bg-blue-50'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-400','bg-blue-50');
        const f = e.dataTransfer.files[0];
        if (f) {
            fileInput.files = e.dataTransfer.files;
            showFile(f);
        }
    });
}

function showFile(file) {
    if (!file) return;
    const mb = (file.size / 1048576).toFixed(1);
    dropText.innerHTML = `<span class="text-blue-600 font-medium">${file.name}</span>`;
    progressFn.textContent = file.name;
    progressSz.textContent = mb + ' MB';
}

form.addEventListener('submit', function(e) {
    const source = document.querySelector('input[name="source_type"]:checked')?.value;
    if (source !== 'upload' || !fileInput || !fileInput.files.length) {
        return;
    }

    e.preventDefault();
    progressW.classList.remove('hidden');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Yukleniyor...';

    const xhr = new XMLHttpRequest();
    const data = new FormData(form);

    xhr.upload.addEventListener('progress', ev => {
        if (ev.lengthComputable) {
            const pct = Math.round(ev.loaded / ev.total * 100);
            progressBar.style.width = pct + '%';
            progressPct.textContent = pct + '%';
        }
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
        alert('Yukleme basarisiz. Lutfen tekrar deneyin.');
    });

    xhr.open('POST', form.action);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send(data);
});
</script>
@endpush
