@extends('layouts.app')
@section('title', $line->product_code . ' - Satır Detayı')
@section('page-title', 'Satır Detayı')

@section('header-actions')
    <a href="{{ route('orders.show', $line->purchaseOrder) }}" class="btn btn-secondary">← Siparişe Dön</a>
    @if(auth()->user()->canUploadArtwork())
        <a href="{{ route('artworks.create', $line) }}" class="btn btn-primary">
            {{ $line->hasActiveArtwork() ? 'Yeni Revizyon' : 'Artwork Yükle' }}
        </a>
    @endif
@endsection

@section('content')
<div x-data="{ previewOpen: false }" class="space-y-6">
    <div class="card overflow-hidden">
        <div class="border-b border-slate-100 px-6 py-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Sipariş · Satır</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <a href="{{ route('orders.show', $line->purchaseOrder) }}" class="font-mono text-lg font-semibold text-slate-800 hover:text-brand-700 hover:underline">{{ $line->purchaseOrder->order_no }}</a>
                        <span class="text-slate-300">/</span>
                        <span class="font-mono text-lg font-semibold text-slate-900">{{ $line->product_code }}</span>
                    </div>
                </div>
                <div>
                    @if($line->is_manual_artwork_completed && ! $line->hasActiveArtwork())
                        <x-ui.badge variant="info">Manuel gönderildi</x-ui.badge>
                    @else
                        <x-ui.badge :variant="match($line->artwork_status?->value ?? 'pending'){'uploaded' => 'success','revision' => 'danger','approved' => 'info',default => 'warning'}">{{ $line->artwork_status?->label() ?? 'Bekliyor' }}</x-ui.badge>
                    @endif
                </div>
            </div>
        </div>
        <div class="grid gap-0 md:grid-cols-2 xl:grid-cols-4">
            <div class="border-b border-slate-100 px-6 py-4 xl:border-b-0 xl:border-r">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Ürün Kodu</p>
                <p class="mt-1 font-mono text-lg font-semibold text-slate-900">{{ $line->product_code }}</p>
            </div>
            <div class="border-b border-slate-100 px-6 py-4 xl:border-b-0 xl:border-r">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Tedarikçi</p>
                <p class="mt-1 text-base text-slate-800">{{ $line->purchaseOrder->supplier->name }}</p>
            </div>
            <div class="border-b border-slate-100 px-6 py-4 md:border-r xl:border-b-0 xl:border-r">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Miktar</p>
                <p class="mt-1 text-base text-slate-800">{{ $line->quantity }} {{ $line->unit }}</p>
            </div>
            <div class="px-6 py-4">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Sipariş Tarihi</p>
                <p class="mt-1 text-base text-slate-800">{{ $line->purchaseOrder->order_date->format('d.m.Y') }}</p>
            </div>
            <div class="px-6 py-4 md:col-span-2 xl:col-span-4">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Açıklama</p>
                <p class="mt-1 text-lg leading-8 text-slate-700">{{ $line->description }}</p>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
        <div class="space-y-6">
            @if($line->hasActiveArtwork())
                @php $rev = $line->activeRevision; @endphp
                <div class="card overflow-hidden">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
                        <h3 class="text-base font-semibold text-slate-900">Güncel Artwork</h3>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('artwork.download', $rev) }}" class="btn btn-primary text-xs">İndir</a>
                            @if(auth()->user()->canUploadArtwork())
                                <form method="POST" action="{{ route('artworks.destroy', $rev) }}"
                                      onsubmit="return confirm('Bu artwork silinsin mi? Aktif revizyon silinirse sistem önce önceki revizyonu aktif yapmaya çalışır.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary border-red-300 text-red-600 hover:bg-red-50 text-xs">Sil</button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <div class="grid gap-6 px-6 py-6 lg:grid-cols-[minmax(240px,320px)_minmax(0,1fr)]">
                        <div>
                            <button type="button" class="group block w-full overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 text-left transition hover:border-brand-200 hover:shadow-md" @if($rev->has_preview) @click="previewOpen = true" @endif>
                                <div class="aspect-[4/3] w-full">
                                    @if($rev->has_preview)
                                        <img src="{{ route('artworks.preview', $rev, false) }}" alt="{{ $rev->original_filename }}" class="h-full w-full object-contain transition duration-300 group-hover:scale-[1.02]" onerror="this.parentElement.innerHTML='<div class=\'flex h-full w-full items-center justify-center bg-slate-100\'><span class=\'text-lg font-bold text-slate-400\'>{{ $rev->extension }}</span></div>'">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center">
                                            <span class="text-lg font-bold text-slate-400">{{ $rev->extension }}</span>
                                        </div>
                                    @endif
                                </div>
                            </button>
                            @if($rev->has_preview)
                                <p class="mt-3 text-xs font-medium text-brand-700">Önizlemeyi büyütmek için artwork’e tıklayın.</p>
                            @endif
                        </div>

                        <div class="min-w-0">
                            <p class="break-all text-xl font-semibold text-slate-900">{{ $rev->original_filename }}</p>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Boyut</p>
                                    <p class="mt-1 text-sm text-slate-700">{{ $rev->file_size_formatted }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Format</p>
                                    <p class="mt-1 font-mono text-sm text-slate-700">{{ $rev->extension }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Yükleyen</p>
                                    <p class="mt-1 text-sm text-slate-700">{{ $rev->uploadedBy->name }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Yüklenme Tarihi</p>
                                    <p class="mt-1 text-sm text-slate-700">{{ $rev->created_at->format('d.m.Y H:i') }}</p>
                                </div>
                            </div>
                            @if($rev->notes)
                                <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Not</p>
                                    <p class="mt-1 text-sm text-slate-700">{{ $rev->notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if($line->is_manual_artwork_completed)
                <div class="card border border-sky-100 p-5">
                    <h3 class="text-sm font-semibold text-sky-700">Manuel Gönderim</h3>
                    <p class="mt-2 text-sm text-slate-700">{{ $line->manual_artwork_note }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $line->manualArtworkCompletedBy?->name ?? '—' }} · {{ $line->manual_artwork_completed_at?->format('d.m.Y H:i') }}</p>
                </div>
            @elseif(auth()->user()->canUploadArtwork() || auth()->user()->hasPermission('orders', 'edit'))
                <div class="card border border-emerald-100 p-5">
                    <h3 class="text-sm font-semibold text-emerald-700">Manuel Gönderim</h3>
                    <p class="mt-2 text-sm text-slate-500">Bu satırın tasarımı portal dışı kanalda tamamlandıysa açıklama notu ile manuel tamamlandı olarak işaretleyin.</p>
                    <form method="POST" action="{{ route('order-lines.manual-artwork.store', $line) }}" class="mt-4 space-y-3">
                        @csrf
                        <textarea name="manual_artwork_note" rows="4" class="input resize-none" placeholder="Örn: Bu ürün için daha önce mail ile onaylanan tasarım yeniden kullanılacak.">{{ old('manual_artwork_note') }}</textarea>
                        @error('manual_artwork_note')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="btn btn-secondary border-emerald-200 text-emerald-700 hover:bg-emerald-50">Manuel gönderildi olarak işaretle</button>
                    </form>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            @if($line->artwork && $line->artwork->revisions->isNotEmpty())
                <div class="card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h3 class="text-sm font-semibold text-slate-900">Revizyon Geçmişi</h3>
                        @if($line->artwork->revisions->count() > 1)
                            <a href="{{ route('artworks.revisions', $line) }}" class="text-xs font-medium text-brand-700 hover:underline">Tümünü Gör</a>
                        @endif
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach($line->artwork->revisions as $rev)
                            <div class="px-5 py-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800">Rev.{{ $rev->revision_no }} · {{ $rev->original_filename }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $rev->uploadedBy->name }} · {{ $rev->created_at->format('d.m.Y H:i') }}</p>
                                    </div>
                                    @if($rev->is_active)
                                        <x-ui.badge variant="success">Aktif</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="gray">Arşiv</x-ui.badge>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if($line->hasActiveArtwork() && $line->activeRevision->has_preview)
        <div x-show="previewOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 py-6" @click.self="previewOpen = false" @keydown.escape.window="previewOpen = false">
            <div class="relative w-full max-w-6xl overflow-hidden rounded-3xl bg-white shadow-2xl">
                <button type="button" class="absolute right-4 top-4 z-10 inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-slate-600 shadow-sm transition hover:bg-white hover:text-slate-900" @click="previewOpen = false">
                    <span class="sr-only">Kapat</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <div class="bg-slate-100 p-4">
                    <img src="{{ route('artworks.preview', $line->activeRevision, false) }}" alt="{{ $line->activeRevision->original_filename }}" class="max-h-[80vh] w-full rounded-2xl object-contain">
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
