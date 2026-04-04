@extends('layouts.app')
@section('title', 'Sipariş ' . $order->order_no)
@section('page-title', 'Sipariş Detayı')

@section('header-actions')
    <a href="{{ route('orders.index') }}" class="btn btn-secondary px-4 py-2 text-sm">Listeye Dön</a>
    <a href="{{ route('orders.pdf', $order) }}" target="_blank" class="btn btn-secondary px-4 py-2 text-sm inline-flex items-center gap-1.5">
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
        </svg>
        PDF İndir
    </a>
    @can('update', $order)
        <a href="{{ route('orders.edit', $order) }}" class="btn btn-secondary px-4 py-2 text-sm">Düzenle</a>
    @endcan
@endsection

@section('content')
<div x-data="{ showDelete: false }" class="space-y-6">
    <div class="card px-6 py-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Sipariş No</p>
                <p class="mt-1 font-mono text-[2rem] font-bold tracking-tight text-slate-900">{{ $order->order_no }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <x-ui.badge :variant="match($order->status){'active' => 'success', 'draft' => 'gray', 'completed' => 'info', 'cancelled' => 'danger', default => 'gray'}">{{ $order->status_label }}</x-ui.badge>
                    <x-ui.badge :variant="match($order->shipment_status){'dispatched' => 'info', 'delivered' => 'success', 'not_found' => 'danger', default => 'warning'}">{{ $order->shipment_status_label }}</x-ui.badge>
                    @if($order->pending_artwork_count > 0)
                        <x-ui.badge variant="warning">{{ $order->pending_artwork_count }} satır artwork bekliyor</x-ui.badge>
                    @else
                        <x-ui.badge variant="success">Artwork tamamlandı</x-ui.badge>
                    @endif
                    @if($order->manual_artwork_count > 0)
                        <x-ui.badge variant="info">{{ $order->manual_artwork_count }} satır manuel</x-ui.badge>
                    @endif
                </div>
            </div>
            @can('delete', $order)
                <button type="button" @click="showDelete = !showDelete" class="btn btn-secondary border-red-200 text-red-600 hover:bg-red-50" :class="showDelete ? 'bg-red-50' : ''">
                    <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Siparişi Sil
                </button>
            @endcan
        </div>

        @can('delete', $order)
            <div x-show="showDelete" x-cloak class="mt-5 border-t border-red-100 pt-5">
                <div class="rounded-2xl border border-red-100 bg-red-50/60 px-5 py-4">
                    <p class="text-sm font-semibold text-red-700">Siparişi kalıcı olarak sil</p>
                    <p class="mt-1 text-xs text-slate-500">Bu işlem bağlı satırları, artwork kayıtlarını ve revizyon loglarını da kaldırır. Onay için sipariş numarasını (<span class="font-mono font-semibold">{{ $order->order_no }}</span>) yazın.</p>
                    <form method="POST" action="{{ route('orders.destroy', $order) }}" class="mt-3 flex flex-wrap items-center gap-3" onsubmit="return confirm('Bu siparişi kalıcı olarak silmek istediğinize emin misiniz?');">
                        @csrf
                        @method('DELETE')
                        <input type="text" name="confirmation_text" class="input w-48 text-sm" placeholder="{{ $order->order_no }}">
                        <button type="submit" class="btn btn-secondary border-red-300 bg-red-600 text-white hover:bg-red-700">Evet, Sil</button>
                        <button type="button" class="btn btn-secondary" @click="showDelete = false">Vazgeç</button>
                    </form>
                </div>
            </div>
        @endcan
    </div>

    <div class="card overflow-hidden">
        <div class="grid gap-0 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,1fr)]">
            <div class="border-b border-slate-100 px-5 py-4 lg:border-b-0 lg:border-r">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Tedarikçi</p>
                <p class="mt-1 text-base font-semibold text-slate-900">{{ $order->supplier->name }}</p>
                <p class="text-sm text-slate-500">{{ $order->supplier->code ?: 'Kod yok' }}</p>
            </div>
            <div class="grid grid-cols-2 gap-4 border-b border-slate-100 px-5 py-4 md:grid-cols-4 lg:col-span-2 lg:border-b-0">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Sipariş Tarihi</p>
                    <p class="mt-1 text-sm text-slate-900">{{ $order->order_date->format('d.m.Y') }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Teslim Tarihi</p>
                    <p class="mt-1 text-sm text-slate-900">{{ $order->due_date?->format('d.m.Y') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Oluşturan</p>
                    <p class="mt-1 text-sm text-slate-900">{{ $order->createdBy->name }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Kayıt Tarihi</p>
                    <p class="mt-1 text-sm text-slate-900">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                </div>
            </div>
            @if($order->shipment_reference || $order->shipment_synced_at || $order->notes)
                <div class="px-5 py-4 lg:col-span-3">
                    <div class="grid gap-4 md:grid-cols-2">
                        @if($order->shipment_reference || $order->shipment_synced_at)
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Mikro Bilgisi</p>
                                <p class="mt-1 text-sm text-slate-700">{{ $order->shipment_reference ?: 'Referans bekleniyor' }}</p>
                                <p class="text-xs text-slate-400">{{ $order->shipment_synced_at?->format('d.m.Y H:i') ?? 'Henüz senkronlanmadı' }}</p>
                            </div>
                        @endif
                        @if($order->notes)
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Notlar</p>
                                <p class="mt-1 text-sm text-slate-700">{{ $order->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Sipariş Satırları <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">{{ $order->lines->count() }}</span></h2>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach($order->lines as $line)
                @php
                    $latestRejectedApproval = $line->latestRejectedApproval;
                @endphp
                <div class="px-5 py-5" x-data="{ showCreate: false, replyTo: {{ old('parent_id') ? (int) old('parent_id') : 'null' }}, editTarget: {{ old('edit_note_id') ? (int) old('edit_note_id') : 'null' }} }">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('order-lines.show', $line) }}" class="font-mono text-lg font-semibold text-slate-900 hover:text-brand-700 hover:underline">{{ $line->product_code }}</a>
                                @if($line->is_manual_artwork_completed && ! $line->hasActiveArtwork())
                                    <x-ui.badge variant="info">Manuel gönderildi</x-ui.badge>
                                @else
                                    <x-ui.badge :variant="match($line->artwork_status?->value ?? 'pending'){'uploaded' => 'success','revision' => 'danger','approved' => 'info',default => 'warning'}">{{ $line->artwork_status?->label() ?? 'Bekliyor' }}</x-ui.badge>
                                @endif
                            </div>
                            <p class="mt-2 text-base leading-7 text-slate-700">{{ $line->description }}</p>
                            <p class="mt-1.5 text-sm text-slate-500">
                                {{ $line->quantity }} {{ $line->unit }}
                                @if(! is_null($line->shipped_quantity))
                                    · Sevk edilen: {{ $line->shipped_quantity }}
                                @endif
                            </p>

                            @if($line->is_manual_artwork_completed)
                                <div class="mt-4 rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Manuel gönderim notu</p>
                                    <p class="mt-2 text-sm text-slate-700">{{ $line->manual_artwork_note }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $line->manualArtworkCompletedBy?->name ?? '—' }} · {{ $line->manual_artwork_completed_at?->format('d.m.Y H:i') }}</p>
                                </div>
                            @endif

                            @php
                                $allRejections = $line->allRejectedApprovals;
                                $isRevisionCompleted = ($line->artwork_status?->value === 'uploaded') && $line->latestRejectedApproval !== null;
                            @endphp
                            @if($line->requiresRevision() || $allRejections->isNotEmpty())
                                <div x-data="{ showCompleteForm: false }" class="mt-4">
                                    @if($line->requiresRevision())
                                        {{-- Active: revision required --}}
                                        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3">
                                            <div class="flex flex-wrap items-start justify-between gap-3">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-red-100">
                                                        <svg class="h-3.5 w-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-semibold text-red-800">Revizyon Gerekli</p>
                                                        <p class="text-xs text-red-600/80">{{ $allRejections->count() }} revizyon talebi · Tedarikçi düzeltme bekliyor</p>
                                                    </div>
                                                </div>
                                                @can('completeRevision', $line)
                                                    @if($line->hasActiveArtwork())
                                                        <button type="button" @click="showCompleteForm = !showCompleteForm"
                                                            class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 shadow-sm transition-colors hover:bg-emerald-50"
                                                            :class="showCompleteForm ? 'bg-emerald-50' : ''">
                                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                                            </svg>
                                                            Grafik Tamamlandı
                                                        </button>
                                                    @endif
                                                @endcan
                                            </div>

                                            {{-- Inline complete form --}}
                                            <div x-show="showCompleteForm" x-cloak class="mt-3 border-t border-red-100 pt-3">
                                                <form method="POST" action="{{ route('order-lines.revision-complete.store', $line) }}">
                                                    @csrf
                                                    <p class="mb-1.5 text-xs font-medium text-slate-700">Grafik düzenleme notu <span class="font-normal text-slate-400">(isteğe bağlı)</span></p>
                                                    <textarea name="summary" rows="2" class="input resize-none text-sm"
                                                        placeholder="Yapılan değişiklikleri kısaca açıklayın..."></textarea>
                                                    <div class="mt-2 flex items-center gap-2">
                                                        <button type="submit" class="btn btn-primary px-4 py-1.5 text-xs">Tamamlandı Olarak İşaretle</button>
                                                        <button type="button" @click="showCompleteForm = false" class="btn btn-secondary py-1.5 text-xs">Vazgeç</button>
                                                    </div>
                                                </form>
                                            </div>

                                            {{-- Rejection notes --}}
                                            <div class="mt-3 space-y-2">
                                                @foreach($allRejections as $rejection)
                                                    <div class="rounded-xl border {{ $loop->first ? 'border-red-200 bg-white' : 'border-red-100 bg-white/60 opacity-50' }} px-3 py-2.5">
                                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                                                            <span class="text-[11px] font-semibold text-slate-700">
                                                                {{ $rejection->user?->name ?? 'Tedarikçi kullanıcısı' }}
                                                                @if($rejection->supplier?->name)
                                                                    <span class="font-normal text-slate-500">· {{ $rejection->supplier->name }}</span>
                                                                @endif
                                                            </span>
                                                            <span class="text-[10px] text-slate-400">{{ $rejection->actioned_at?->format('d.m.Y H:i') }}</span>
                                                        </div>
                                                        <p class="text-sm text-slate-700">{{ filled($rejection->notes) ? $rejection->notes : '—' }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @elseif($isRevisionCompleted)
                                        {{-- Completed: same revision marked done --}}
                                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 px-4 py-3">
                                            <div class="flex items-center gap-2.5 mb-3">
                                                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100">
                                                    <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-emerald-800">Revizyon Tamamlandı</p>
                                                    <p class="text-xs text-emerald-600/80">Grafik tedarikçiye tekrar sunulmak üzere hazırlandı</p>
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                @foreach($allRejections as $rejection)
                                                    <div class="rounded-xl border border-emerald-100 bg-white/70 px-3 py-2.5 {{ !$loop->first ? 'opacity-50' : '' }}">
                                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                                                            <span class="text-[11px] font-semibold text-slate-600">
                                                                {{ $rejection->user?->name ?? 'Tedarikçi kullanıcısı' }}
                                                                @if($rejection->supplier?->name)
                                                                    <span class="font-normal text-slate-500">· {{ $rejection->supplier->name }}</span>
                                                                @endif
                                                            </span>
                                                            <span class="text-[10px] text-slate-400">{{ $rejection->actioned_at?->format('d.m.Y H:i') }}</span>
                                                        </div>
                                                        <p class="text-sm text-slate-500">{{ filled($rejection->notes) ? $rejection->notes : '—' }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        {{-- History only: after new revision was uploaded --}}
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-3">
                                            <div class="flex items-center gap-2 mb-3">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Önceki Revizyon Talepleri</p>
                                                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-medium text-slate-600">{{ $allRejections->count() }}</span>
                                            </div>
                                            <div class="space-y-2">
                                                @foreach($allRejections as $rejection)
                                                    <div class="rounded-xl border border-slate-100 bg-white/60 px-3 py-2.5 opacity-60">
                                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                                                            <span class="text-[11px] font-semibold text-slate-600">
                                                                {{ $rejection->user?->name ?? 'Tedarikçi kullanıcısı' }}
                                                                @if($rejection->supplier?->name)
                                                                    <span class="font-normal text-slate-500">· {{ $rejection->supplier->name }}</span>
                                                                @endif
                                                            </span>
                                                            <span class="text-[10px] text-slate-400">{{ $rejection->actioned_at?->format('d.m.Y H:i') }}</span>
                                                        </div>
                                                        <p class="text-sm text-slate-600">{{ filled($rejection->notes) ? $rejection->notes : '—' }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                            @if($line->hasActiveArtwork())
                                <a href="{{ route('artwork.download', $line->activeRevision) }}" class="btn btn-secondary py-1.5 text-xs">İndir Rev.{{ $line->activeRevision->revision_no }}</a>
                                <a href="{{ route('artworks.revisions', $line) }}" class="btn btn-secondary py-1.5 text-xs">Revizyonlar</a>
                            @else
                                <span class="text-sm text-slate-400">{{ $line->is_manual_artwork_completed ? 'Portal dışı gönderim ile tamamlandı' : 'Artwork yok' }}</span>
                            @endif

                            @if(auth()->user()->canUploadArtwork())
                                <a href="{{ route('artworks.create', $line) }}" class="btn btn-primary px-4 py-1.5 text-xs">
                                    {{ $line->hasActiveArtwork() ? 'Yeni Revizyon' : 'Yükle' }}
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($line->hasActiveArtwork())
                        <div class="mt-4 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center">
                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    @if($line->activeRevision->has_preview)
                                        <button type="button" data-dialog-open="order-line-preview-{{ $line->id }}" class="group relative h-16 w-16 flex-shrink-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-200 transition hover:border-brand-300">
                                            <img
                                                src="{{ route('artworks.preview', $line->activeRevision, false) }}"
                                                alt="{{ $line->activeRevision->original_filename }}"
                                                class="h-full w-full object-contain transition duration-300 group-hover:scale-[1.03]"
                                            >
                                        </button>
                                    @else
                                        <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                                            <span class="text-xs font-bold text-slate-600">{{ $line->activeRevision->extension }}</span>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="truncate text-sm font-semibold text-slate-800">{{ $line->activeRevision->original_filename }}</p>
                                            @include('artworks.partials.passive-gallery-badge', ['galleryItem' => $line->activeRevision->galleryItem])
                                        </div>
                                        <p class="text-xs text-slate-500">Rev.{{ $line->activeRevision->revision_no }} · {{ $line->activeRevision->file_size_formatted }} · {{ $line->activeRevision->uploadedBy->name }} · {{ $line->activeRevision->created_at->format('d.m.Y H:i') }}</p>
                                        @if($line->activeRevision->has_preview)
                                            <button type="button" data-dialog-open="order-line-preview-{{ $line->id }}" class="mt-1 text-[11px] font-semibold text-brand-700 hover:underline">Önizlemeyi aç</button>
                                        @endif
                                    </div>
                                </div>
                                <x-ui.badge variant="success" class="text-xs">Güncel</x-ui.badge>
                            </div>
                        </div>

                        @if($line->activeRevision->has_preview)
                            <dialog id="order-line-preview-{{ $line->id }}" class="max-h-[92vh] w-[min(96vw,1380px)] max-w-none overflow-hidden rounded-[32px] border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/70">
                                <div class="flex h-[min(92vh,920px)] min-h-0 flex-col bg-white">
                                    <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Satır önizleme</p>
                                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                                <p class="truncate text-lg font-semibold text-slate-900">{{ $line->activeRevision->original_filename }}</p>
                                                @include('artworks.partials.passive-gallery-badge', ['galleryItem' => $line->activeRevision->galleryItem])
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
                                            src="{{ route('artworks.preview', $line->activeRevision, false) }}"
                                            alt="{{ $line->activeRevision->original_filename }}"
                                            class="mx-auto h-full w-full rounded-[24px] bg-white object-contain"
                                        >
                                    </div>
                                </div>
                            </dialog>
                        @endif
                    @endif

                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/80">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Satır açıklamaları</p>
                                <p class="text-xs text-slate-500">Bu satıra özel not ve yanıtları burada takip edin.</p>
                            </div>
                            <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-800" @click="showCreate = !showCreate; if (showCreate) { replyTo = null; }">
                                <span class="text-base leading-none">+</span>
                                <span>Açıklama ekle</span>
                            </button>
                        </div>

                        <div class="space-y-3 px-4 py-4">
                            @forelse($line->lineNotes as $note)
                                @include('orders.partials.note-thread', ['note' => $note, 'order' => $order, 'line' => $line])
                            @empty
                                <p class="text-sm text-slate-400">Henüz açıklama eklenmemiş.</p>
                            @endforelse

                            <form method="POST" action="{{ route('orders.notes.store', $order) }}" class="rounded-xl border border-dashed border-slate-300 bg-white p-3" x-show="showCreate" x-cloak>
                                @csrf
                                <input type="hidden" name="purchase_order_line_id" value="{{ $line->id }}">
                                <label class="label">Sipariş açıklaması</label>
                                @include('orders.partials.mention-textarea', [
                                    'name' => 'body',
                                    'rows' => 3,
                                    'placeholder' => 'Bu satırla ilgili açıklamanızı yazın... (@isim ile kullanıcı etiketleyebilirsiniz)',
                                    'value' => old('purchase_order_line_id') == $line->id && ! old('parent_id') ? old('body') : '',
                                ])
                                @if((string) old('purchase_order_line_id') === (string) $line->id && ! old('parent_id'))
                                    @error('body')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                                <div class="mt-3 flex items-center justify-end gap-2">
                                    <button type="button" class="btn btn-secondary" @click="showCreate = false">Vazgeç</button>
                                    <button type="submit" class="btn btn-primary">Ekle</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    @can('manualArtwork', $line)
                        @if(! $line->hasActiveArtwork() && ! $line->is_manual_artwork_completed)
                            <form method="POST" action="{{ route('order-lines.manual-artwork.store', $line) }}" class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4">
                                @csrf
                                <label class="label">Bu satırı manuel gönderildi olarak işaretle</label>
                                <textarea name="manual_artwork_note" rows="3" class="input resize-none" placeholder="Örn: Bu ürünün tasarımı daha önce mail ile paylaşılmıştı, yeni siparişte aynı çalışma kullanılacak.">{{ old('manual_artwork_note') }}</textarea>
                                @error('manual_artwork_note')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-secondary border-emerald-200 text-emerald-700 hover:bg-emerald-100">Manuel gönderildi olarak işaretle</button>
                                </div>
                            </form>
                        @endif
                    @endcan
                </div>
            @endforeach
        </div>
    </div>

    <div class="card" x-data="{ showOrderNote: false, replyTo: {{ old('parent_id') ? (int) old('parent_id') : 'null' }}, editTarget: {{ old('edit_note_id') ? (int) old('edit_note_id') : 'null' }} }">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Sipariş Notları</h2>
            <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-800" @click="showOrderNote = !showOrderNote; if (showOrderNote) { replyTo = null; }">
                <span class="text-base leading-none">+</span>
                <span>Not ekle</span>
            </button>
        </div>

        @if($order->orderNotes->isNotEmpty())
            <div class="max-h-96 space-y-3 overflow-y-auto px-5 py-4">
                @foreach($order->orderNotes as $note)
                    @include('orders.partials.note-thread', ['note' => $note, 'order' => $order, 'line' => null])
                @endforeach
            </div>
        @else
            <div class="px-5 py-6 text-center text-sm text-slate-400">Henüz not eklenmemiş.</div>
        @endif

        <div class="border-t border-slate-100 px-5 py-4">
            <form method="POST" action="{{ route('orders.notes.store', $order) }}" class="space-y-3" x-show="showOrderNote" x-cloak>
                @csrf
                <div>
                    @include('orders.partials.mention-textarea', [
                        'name' => 'body',
                        'rows' => 2,
                        'placeholder' => 'Sipariş notu ekleyin... (@isim ile kullanıcı etiketleyebilirsiniz)',
                        'value' => old('purchase_order_line_id') ? '' : old('body'),
                    ])
                    @if(! old('purchase_order_line_id') && ! old('parent_id'))
                        @error('body')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="showOrderNote = false">Vazgeç</button>
                    <button type="submit" class="btn btn-primary">Ekle</button>
                </div>
            </form>
        </div>
    </div>

    @php
        $lineEventCounts = ['all' => $timeline->count()];
        foreach ($order->lines as $ln) {
            $lineEventCounts[(string) $ln->id] = $timeline->filter(
                fn ($e) => ($e['line_id'] ?? null) === null || (string) ($e['line_id'] ?? '') === (string) $ln->id
            )->count();
        }
    @endphp
    <div class="card" x-data="{ selectedLine: 'all', counts: {{ json_encode($lineEventCounts) }} }">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-4">
            <svg class="h-5 w-5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-sm font-semibold text-slate-800">Aktivite Zaman Çizelgesi</h3>
            <div class="ml-auto flex items-center gap-3">
                @if($order->lines->count() > 1)
                    <select
                        x-model="selectedLine"
                        class="rounded-lg border border-slate-200 bg-white py-1.5 pl-3 pr-8 text-xs text-slate-700 shadow-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-300"
                    >
                        <option value="all">Tüm Satırlar</option>
                        @foreach($order->lines as $line)
                            <option value="{{ $line->id }}">{{ $line->product_code }}{{ $line->description ? ' — ' . \Illuminate\Support\Str::limit($line->description, 40) : '' }}</option>
                        @endforeach
                    </select>
                @endif
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500" x-text="(counts[selectedLine] ?? 0) + ' olay'"></span>
            </div>
        </div>

        @if($timeline->isEmpty())
            <div class="px-5 py-8 text-center text-sm text-slate-400">Henüz aktivite yok.</div>
        @else
            <div class="px-5 py-6">
                <ol class="relative ml-3">
                    @foreach($timeline as $event)
                        @php $lineId = $event['line_id'] ?? null; @endphp
                        <li
                            class="relative ml-6 mb-0"
                            x-show="selectedLine === 'all' || {{ $lineId === null ? 'true' : 'false' }} || selectedLine === '{{ $lineId }}'"
                            x-cloak
                        >
                            <span class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white @if($event['color'] === 'violet') bg-violet-100 text-violet-600 @elseif($event['color'] === 'blue') bg-blue-100 text-blue-600 @elseif($event['color'] === 'amber') bg-amber-100 text-amber-600 @elseif($event['color'] === 'emerald') bg-emerald-100 text-emerald-600 @elseif($event['color'] === 'red') bg-red-100 text-red-600 @else bg-slate-100 text-slate-500 @endif">
                                @if($event['icon'] === 'plus')
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                @elseif($event['icon'] === 'upload')
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                @elseif($event['icon'] === 'reply')
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a4 4 0 014 4v5m0 0l-3-3m3 3l3-3"/></svg>
                                @elseif($event['icon'] === 'note')
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                @elseif($event['icon'] === 'mail')
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8m-2 10H5a2 2 0 01-2-2V8a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2z"/></svg>
                                @elseif($event['icon'] === 'check')
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                @elseif($event['icon'] === 'x')
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                @endif
                            </span>

                            <div class="pb-5 pl-8 pt-0.5">
                                <p class="text-sm font-semibold text-slate-800">{{ $event['title'] }}</p>
                                <p class="text-xs text-slate-500">{{ $event['sub'] }}</p>
                                @if(! empty($event['body']))
                                    <p class="mt-1.5 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">{{ $event['body'] }}</p>
                                @endif
                                <time class="mt-1 block text-[11px] text-slate-400">
                                    {{ $event['at']->format('d.m.Y H:i') }}
                                    <span class="ml-1 text-slate-300">({{ $event['at']->diffForHumans() }})</span>
                                </time>
                            </div>

                            @if(! $loop->last && ! is_null($event['days_gap']))
                                @php
                                    $gap = $event['days_gap'];
                                    [$barBg, $textCls] = match (true) {
                                        $gap < 1 => ['from-emerald-400 to-emerald-500', 'text-emerald-700'],
                                        $gap < 3 => ['from-amber-400 to-amber-500', 'text-amber-700'],
                                        $gap < 7 => ['from-orange-400 to-orange-500', 'text-orange-700'],
                                        default => ['from-red-400 to-red-600', 'text-red-700'],
                                    };
                                    $gapHeight = max(60, min(120, $gap <= 0 ? 60 : intval(log($gap + 1, 2) * 38 + 44)));
                                @endphp
                                <div
                                    class="relative mb-3"
                                    style="height: {{ $gapHeight }}px"
                                >
                                    <div class="absolute left-0 top-0 w-0.5 -translate-x-1/2 rounded-full bg-gradient-to-b {{ $barBg }}" style="height: calc(50% - 13px)"></div>
                                    <div class="absolute left-0 bottom-0 w-0.5 -translate-x-1/2 rounded-full bg-gradient-to-b {{ $barBg }}" style="height: calc(50% - 13px)"></div>
                                    <div class="absolute left-0 top-1/2 -translate-x-1/2 -translate-y-1/2">
                                        <span class="whitespace-nowrap rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold ring-1 ring-slate-200 {{ $textCls }}">{{ number_format($gap, 1, ',', '.') }} gün</span>
                                    </div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    </div>
</div>
@endsection
