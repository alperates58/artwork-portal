@extends('layouts.app')
@section('title', 'Artwork Revizyonu')
@section('page-title', 'Artwork Revizyonu')
@section('header-actions')
    <a href="{{ route('order-lines.show', $revision->artwork->orderLine) }}" class="btn btn-secondary">← Satıra Dön</a>
    <a href="{{ route('artwork.download', $revision) }}" class="btn btn-primary">İndir</a>
    @if(auth()->user()->canUploadArtwork())
        <form method="POST" action="{{ route('artworks.destroy', $revision) }}"
              onsubmit="return confirm('Bu artwork silinsin mi? Aktif revizyon silinirse sistem önce önceki revizyonu aktif yapmaya çalışır.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-secondary border-red-300 text-red-600 hover:bg-red-50">Sil</button>
        </form>
    @endif
@endsection

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold">Artwork Revizyonu</h1>
        <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-600">
            <p>
                <span class="font-mono">{{ $revision->artwork->orderLine->product_code }}</span> · Rev.{{ $revision->revision_no }} · {{ $revision->original_filename }}
            </p>
            @include('artworks.partials.passive-gallery-badge', ['galleryItem' => $revision->galleryItem])
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        @if($revision->has_preview)
            <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Artwork Önizleme</p>
                        <p class="text-xs text-slate-500">Orijinal dosya korunur, ekranda PNG önizleme gösterilir.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @include('artworks.partials.passive-gallery-badge', ['galleryItem' => $revision->galleryItem])
                        <a href="{{ route('artworks.preview', $revision) }}" class="btn btn-secondary text-xs">Önizlemeyi Aç</a>
                    </div>
                </div>
                <img src="{{ route('artworks.preview', $revision, false) }}" alt="Artwork önizleme" class="max-h-[520px] w-full object-contain bg-white">
            </div>
        @else
            <div class="mb-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                Bu revizyon için önizleme henüz hazır değil. Orijinal dosya üretim ve indirme için kullanılmaya devam eder.
            </div>
        @endif

        <dl class="grid gap-4 md:grid-cols-2">
            <div>
                <dt class="text-sm text-slate-500">Stok Kodu</dt>
                <dd class="font-mono font-medium">{{ $revision->artwork->orderLine->product_code }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Revizyon</dt>
                <dd class="font-medium">Rev.{{ $revision->revision_no }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Sipariş</dt>
                <dd class="font-medium">{{ $revision->artwork->orderLine->purchaseOrder->order_no }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Tedarikçi</dt>
                <dd class="font-medium">{{ $revision->artwork->orderLine->purchaseOrder->supplier->name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Yükleyen</dt>
                <dd class="font-medium">{{ $revision->uploadedBy?->name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Dosya Boyutu</dt>
                <dd class="font-medium">{{ $revision->file_size_formatted }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Önizleme PNG</dt>
                <dd class="font-medium">{{ $revision->has_preview ? ($revision->preview_original_filename ?: 'Hazır') : 'Henüz oluşturulmadı' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Orijinal Dosya</dt>
                <dd class="font-medium">{{ $revision->original_filename }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-sm text-slate-500">Notlar</dt>
                <dd class="font-medium">{{ $revision->notes ?: 'Not yok.' }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
