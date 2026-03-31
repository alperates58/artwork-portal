@extends('layouts.app')
@section('title', $line->product_code . ' — Satır Detayı')
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
<div class="max-w-3xl space-y-5">

    {{-- ─── Line Info ───────────────────────────────────────────────────────── --}}
    <div class="card overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Sipariş · Satır</p>
                    <div class="mt-1 flex items-center gap-2">
                        <a href="{{ route('orders.show', $line->purchaseOrder) }}" class="font-mono text-sm font-semibold text-slate-800 hover:text-brand-700 hover:underline">{{ $line->purchaseOrder->order_no }}</a>
                        <span class="text-slate-300">/</span>
                        <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-600">{{ $line->line_no }}</span>
                    </div>
                </div>
                <div class="text-right">
                    @if($line->is_manual_artwork_completed && ! $line->hasActiveArtwork())
                        <x-ui.badge variant="info">Manuel gönderildi</x-ui.badge>
                    @else
                        <x-ui.badge :variant="match($line->artwork_status?->value ?? 'pending'){'uploaded' => 'success','revision' => 'danger','approved' => 'info',default => 'warning'}">{{ $line->artwork_status?->label() ?? 'Bekliyor' }}</x-ui.badge>
                    @endif
                </div>
            </div>
        </div>
        <div class="grid grid-cols-2 divide-x divide-y divide-slate-100 sm:grid-cols-4">
            <div class="col-span-2 px-5 py-3 sm:col-span-2">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Ürün Kodu</p>
                <p class="mt-0.5 font-mono text-sm font-semibold text-slate-900">{{ $line->product_code }}</p>
            </div>
            <div class="col-span-2 px-5 py-3 sm:col-span-2">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Tedarikçi</p>
                <p class="mt-0.5 text-sm text-slate-700">{{ $line->purchaseOrder->supplier->name }}</p>
            </div>
            <div class="col-span-2 px-5 py-3 sm:col-span-4">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Açıklama</p>
                <p class="mt-0.5 text-sm text-slate-700">{{ $line->description }}</p>
            </div>
            <div class="px-5 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Miktar</p>
                <p class="mt-0.5 text-sm text-slate-700">{{ $line->quantity }} {{ $line->unit }}</p>
            </div>
            <div class="px-5 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Sipariş Tarihi</p>
                <p class="mt-0.5 text-sm text-slate-700">{{ $line->purchaseOrder->order_date->format('d.m.Y') }}</p>
            </div>
        </div>
    </div>

    {{-- ─── Active Artwork ──────────────────────────────────────────────────── --}}
    @if($line->hasActiveArtwork())
        @php $rev = $line->activeRevision; @endphp
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">Güncel Artwork</h3>
            </div>
            <div class="flex items-start gap-5 px-5 py-5">
                {{-- Preview thumbnail --}}
                <div class="relative flex-shrink-0">
                    <div class="h-28 w-28 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                        @if($rev->has_preview)
                            <img
                                src="{{ route('artworks.preview', $rev) }}"
                                alt="{{ $rev->original_filename }}"
                                class="h-full w-full object-cover"
                                onerror="this.parentElement.innerHTML='<div class=\'flex h-full w-full items-center justify-center\'><span class=\'text-xs font-bold text-slate-400\'>{{ $rev->extension }}</span></div>'"
                            >
                        @else
                            <div class="flex h-full w-full items-center justify-center">
                                <span class="text-sm font-bold text-slate-400">{{ $rev->extension }}</span>
                            </div>
                        @endif
                    </div>
                    <span class="absolute -bottom-1.5 -right-1.5 rounded-full border-2 border-white bg-slate-800 px-2 py-0.5 text-[10px] font-bold text-white">Rev.{{ $rev->revision_no }}</span>
                </div>

                {{-- Artwork meta --}}
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $rev->original_filename }}</p>
                    <div class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
                        <div>
                            <span class="text-slate-400">Boyut:</span>
                            <span class="ml-1 text-slate-700">{{ $rev->file_size_formatted }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400">Yükleyen:</span>
                            <span class="ml-1 text-slate-700">
                                @if(! auth()->user()->isSupplier())
                                    <a href="{{ route('profile.edit') }}" class="hover:text-brand-600 hover:underline">{{ $rev->uploadedBy->name }}</a>
                                @else
                                    {{ $rev->uploadedBy->name }}
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400">Tarih:</span>
                            <span class="ml-1 text-slate-700">{{ $rev->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400">Format:</span>
                            <span class="ml-1 font-mono text-slate-700">{{ $rev->extension }}</span>
                        </div>
                    </div>
                    @if($rev->notes)
                        <p class="mt-2 text-xs italic text-slate-400">{{ $rev->notes }}</p>
                    @endif
                    <div class="mt-4">
                        <a href="{{ route('artwork.download', $rev) }}" class="btn btn-primary text-xs">
                            <svg class="mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            İndir
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ─── Manual Artwork ─────────────────────────────────────────────────── --}}
    @if($line->is_manual_artwork_completed)
        <div class="card border border-sky-100 p-5">
            <h3 class="text-sm font-semibold text-sky-700">Manuel Gönderim</h3>
            <p class="mt-2 text-sm text-slate-700">{{ $line->manual_artwork_note }}</p>
            <p class="mt-1 text-xs text-slate-500">
                {{ $line->manualArtworkCompletedBy?->name ?? '—' }} · {{ $line->manual_artwork_completed_at?->format('d.m.Y H:i') }}
            </p>
        </div>
    @elseif(auth()->user()->canUploadArtwork() || auth()->user()->hasPermission('orders', 'edit'))
        <div class="card border border-emerald-100 p-5">
            <h3 class="text-sm font-semibold text-emerald-700">Manuel Gönderim</h3>
            <p class="mt-2 text-xs text-slate-500">Bu satırın tasarımı portal dışı kanalla tamamlandıysa açıklama notu ile manuel tamamlandı olarak işaretleyin.</p>
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

    {{-- ─── Revision History ────────────────────────────────────────────────── --}}
    @if($line->artwork && $line->artwork->revisions->count() > 1)
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">Revizyon Geçmişi</h3>
                <a href="{{ route('artworks.revisions', $line) }}" class="text-xs font-medium text-brand-700 hover:underline">Tümünü Gör →</a>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach($line->artwork->revisions as $rev)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <span class="w-16 font-mono text-xs text-slate-500">Rev.{{ $rev->revision_no }}</span>
                        <span class="min-w-0 flex-1 truncate text-sm text-slate-700">{{ $rev->original_filename }}</span>
                        <span class="text-xs text-slate-400">{{ $rev->created_at->format('d.m.Y') }}</span>
                        @if($rev->is_active)
                            <x-ui.badge variant="success">Aktif</x-ui.badge>
                        @else
                            <x-ui.badge variant="gray">Arşiv</x-ui.badge>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
