@extends('layouts.app')
@section('title', 'Revizyon Geçmişi')
@section('page-title', 'Revizyon Geçmişi')
@section('header-actions')
    <a href="{{ route('orders.show', $line->purchaseOrder) }}" class="btn btn-secondary px-4 py-2 text-sm">Siparişe Dön</a>
    @if(auth()->user()->canUploadArtwork())
        <a href="{{ route('artworks.create', $line) }}" class="btn btn-primary px-4 py-2 text-sm">Yeni Revizyon</a>
    @endif
@endsection

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center gap-4 px-6 py-5">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-sm font-semibold text-slate-700">
                {{ $line->line_no }}
            </div>
            <div class="min-w-0">
                <p class="font-mono text-2xl font-semibold text-slate-900">{{ $line->product_code }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $line->purchaseOrder->order_no }} · {{ $line->purchaseOrder->supplier->name }}</p>
            </div>
        </div>
    </div>

    @if($line->artwork && $line->artwork->revisions->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">{{ $line->artwork->revisions->count() }} Revizyon</h2>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($line->artwork->revisions as $rev)
                    <div class="px-6 py-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex min-w-0 flex-1 items-start gap-5">
                                {{-- Görsel --}}
                                @if($rev->has_preview)
                                    <button
                                        type="button"
                                        data-dialog-open="revision-preview-{{ $rev->id }}"
                                        class="group relative flex-shrink-0 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 transition hover:border-brand-300 hover:shadow-md"
                                        style="width:160px;height:160px;"
                                    >
                                        <img
                                            src="{{ route('artworks.preview', $rev, false) }}"
                                            alt="{{ $rev->original_filename }}"
                                            class="h-full w-full object-contain transition duration-300 group-hover:scale-[1.03]"
                                        >
                                    </button>
                                @else
                                    <div class="flex flex-shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50" style="width:160px;height:160px;">
                                        <span class="text-2xl font-bold text-slate-400">{{ $rev->extension }}</span>
                                    </div>
                                @endif

                                {{-- Bilgiler --}}
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-base font-semibold text-slate-900">Rev.{{ $rev->revision_no }}</span>
                                        @if($rev->is_active)
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Aktif</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">Arşiv</span>
                                        @endif
                                        @include('artworks.partials.passive-gallery-badge', ['galleryItem' => $rev->galleryItem])
                                    </div>

                                    <p class="mt-1.5 break-all text-sm font-medium text-slate-700">{{ $rev->original_filename }}</p>

                                    <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-slate-500">
                                        <span class="font-mono font-semibold text-brand-600">{{ $line->product_code }}</span>
                                        <span>{{ $rev->file_size_formatted }}</span>
                                        <span>{{ $rev->has_preview ? 'PNG önizleme var' : 'PNG önizleme yok' }}</span>
                                        <span>{{ $rev->uploadedBy->name }}</span>
                                        <span>{{ $rev->created_at->format('d.m.Y H:i') }}</span>
                                    </div>

                                    @if($rev->notes)
                                        <p class="mt-3 rounded-xl bg-slate-50 px-3.5 py-2.5 text-xs leading-5 text-slate-600">{{ $rev->notes }}</p>
                                    @endif

                                    {{-- Aksiyon butonları --}}
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        @if($rev->has_preview)
                                            <button type="button" data-dialog-open="revision-preview-{{ $rev->id }}" class="btn btn-secondary py-1.5 text-xs">Önizleme</button>
                                        @endif
                                        <a href="{{ route('artwork.download', $rev) }}" class="btn btn-secondary py-1.5 text-xs">İndir</a>
                                        @if(auth()->user()->canUploadArtwork())
                                            @if(! $rev->is_active)
                                                <form method="POST" action="{{ route('artworks.activate', $rev) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-secondary py-1.5 text-xs text-emerald-700">Aktif Yap</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('artworks.destroy', $rev) }}" onsubmit="return confirm('Rev.{{ $rev->revision_no }} silinsin mi? Bu işlem geri alınamaz.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-secondary py-1.5 text-xs text-red-600 hover:border-red-300 hover:bg-red-50">Sil</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($rev->has_preview)
                        <dialog id="revision-preview-{{ $rev->id }}" class="max-h-[92vh] w-[min(96vw,1380px)] max-w-none overflow-hidden rounded-[32px] border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/70">
                            <div class="flex h-[min(92vh,920px)] min-h-0 flex-col bg-white">
                                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Revizyon önizleme</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <p class="truncate text-lg font-semibold text-slate-900">{{ $rev->original_filename }}</p>
                                            @include('artworks.partials.passive-gallery-badge', ['galleryItem' => $rev->galleryItem])
                                        </div>
                                    </div>
                                    <button type="button" data-dialog-close class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900">
                                        <span class="sr-only">Kapat</span>
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>

                                <div class="min-h-0 flex-1 overflow-auto bg-slate-100 p-4">
                                    <img
                                        src="{{ route('artworks.preview', $rev, false) }}"
                                        alt="{{ $rev->original_filename }}"
                                        class="mx-auto h-full w-full rounded-[24px] bg-white object-contain"
                                    >
                                </div>
                            </div>
                        </dialog>
                    @endif
                @endforeach
            </div>
        </div>
    @else
        <div class="card p-10 text-center text-slate-400">
            Bu satır için henüz artwork yüklenmemiş.
        </div>
    @endif
</div>
@endsection
