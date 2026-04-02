@extends('layouts.app')
@section('title', 'Sipariş ' . $order->order_no)
@section('page-title', $order->order_no)

@section('header-actions')
    <a href="{{ route('portal.orders.index') }}" class="btn btn-secondary">← Siparişlerime Dön</a>
@endsection

@section('content')
<div class="max-w-4xl">
    <div class="card p-4 mb-5 grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="md:col-span-2">
            <p class="text-xs text-slate-500 mb-0.5">Sipariş</p>
            <p class="font-mono font-semibold text-slate-900">{{ $order->order_no }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $order->supplier->name }}</p>
        </div>
        <div class="text-center">
            <p class="text-xs text-slate-500 mb-0.5">Tarih</p>
            <p class="text-sm text-slate-700">{{ $order->order_date->format('d.m.Y') }}</p>
        </div>
        <div class="text-center">
            <p class="text-xs text-slate-500 mb-0.5">Toplam Satır</p>
            <p class="text-sm text-slate-700">{{ $order->lines->count() }}</p>
        </div>
        <div class="text-center">
            <p class="text-xs text-slate-500 mb-0.5">Sevk</p>
            <x-ui.badge :variant="match($order->shipment_status){'dispatched'=>'info','delivered'=>'success','not_found'=>'danger',default=>'warning'}">{{ $order->shipment_status_label }}</x-ui.badge>
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
                <div class="px-5 py-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-lg font-semibold text-slate-900">{{ $line->product_code }}</span>
                                <x-ui.badge :variant="match($line->artwork_status?->value ?? 'pending'){'uploaded' => 'success','revision' => 'danger','approved' => 'info',default => 'warning'}">
                                    {{ $line->artwork_status?->label() ?? 'Bekliyor' }}
                                </x-ui.badge>
                            </div>
                            <p class="mt-2 text-base leading-7 text-slate-700">{{ $line->description }}</p>
                            <p class="mt-1.5 text-sm text-slate-500">
                                {{ $line->quantity }} {{ $line->unit }}
                                @if(!is_null($line->shipped_quantity))
                                    · Sevk edilen: {{ $line->shipped_quantity }}
                                @endif
                            </p>

                            @if($line->requiresRevision())
                                <div class="mt-4 rounded-2xl border border-red-100 bg-red-50/80 px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Revizyon talebi</p>
                                            <p class="mt-1 text-sm text-slate-700">Bu dosya için yeni revizyon talebi oluşturuldu.</p>
                                        </div>
                                        <x-ui.badge variant="danger">Revizyon Gerekli</x-ui.badge>
                                    </div>
                                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                                        <div>
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Talep eden</p>
                                            <p class="mt-1 text-sm text-slate-700">{{ $latestRejectedApproval?->user?->name ?? $latestRejectedApproval?->supplier?->name ?? 'Tedarikçi kullanıcısı' }}</p>
                                            @if($latestRejectedApproval?->supplier?->name && $latestRejectedApproval?->user?->name)
                                                <p class="text-xs text-slate-500">{{ $latestRejectedApproval->supplier->name }}</p>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Talep tarihi</p>
                                            <p class="mt-1 text-sm text-slate-700">{{ $latestRejectedApproval?->actioned_at?->format('d.m.Y H:i') ?? 'Kayıt tarihi bulunamadı' }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-3 rounded-xl border border-red-100 bg-white/80 px-3 py-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Tedarikçi notu</p>
                                        <p class="mt-1 text-sm text-slate-700">{{ filled($latestRejectedApproval?->notes) ? $latestRejectedApproval->notes : 'Bu revizyon talebi için ek not bırakılmadı.' }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if($line->hasActiveArtwork())
                            <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                                <a href="{{ route('portal.download', $line->activeRevision) }}" class="btn btn-secondary py-1.5 text-xs">İndir Rev.{{ $line->activeRevision->revision_no }}</a>
                                <button type="button"
                                    onclick="openRevisionModal('{{ $line->activeRevision->id }}')"
                                    class="btn btn-secondary py-1.5 text-xs text-red-700 border-red-200 hover:bg-red-50">
                                    Revizyon Talebi
                                </button>
                                <form method="POST" action="{{ route('approval.approve', $line->activeRevision) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary py-1.5 text-xs">Onayladım</button>
                                </form>
                            </div>
                        @endif
                    </div>

                    @if($line->hasActiveArtwork())
                        @php $rev = $line->activeRevision; @endphp
                        <div class="mt-4 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center">
                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    @if($rev->has_preview)
                                        <button type="button" data-dialog-open="portal-line-preview-{{ $line->id }}" class="group relative h-16 w-16 flex-shrink-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-200 transition hover:border-brand-300">
                                            <img
                                                src="{{ route('portal.preview', $rev) }}"
                                                alt="{{ $rev->original_filename }}"
                                                class="h-full w-full object-contain transition duration-300 group-hover:scale-[1.03]"
                                            >
                                        </button>
                                    @else
                                        <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                                            <span class="text-xs font-bold text-slate-600">{{ $rev->extension }}</span>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="truncate text-sm font-semibold text-slate-800">{{ $rev->original_filename }}</p>
                                            @include('artworks.partials.passive-gallery-badge', ['galleryItem' => $rev->galleryItem])
                                        </div>
                                        <p class="text-xs text-slate-500">Rev.{{ $rev->revision_no }} · {{ $rev->file_size_formatted }} · {{ $rev->created_at->format('d.m.Y H:i') }}</p>
                                        @if($rev->has_preview)
                                            <button type="button" data-dialog-open="portal-line-preview-{{ $line->id }}" class="mt-1 text-[11px] font-semibold text-brand-700 hover:underline">Önizlemeyi aç</button>
                                        @endif
                                    </div>
                                </div>
                                <x-ui.badge variant="success" class="text-xs">Güncel</x-ui.badge>
                            </div>
                        </div>

                        @if($rev->has_preview)
                            <dialog id="portal-line-preview-{{ $line->id }}" class="max-h-[92vh] w-[min(96vw,1380px)] max-w-none overflow-hidden rounded-[32px] border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/70">
                                <div class="flex h-[min(92vh,920px)] min-h-0 flex-col bg-white">
                                    <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Satır önizleme</p>
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
                                            src="{{ route('portal.preview', $rev) }}"
                                            alt="{{ $rev->original_filename }}"
                                            class="mx-auto h-full w-full rounded-[24px] bg-white object-contain"
                                        >
                                    </div>
                                </div>
                            </dialog>
                        @endif
                    @else
                        <div class="mt-4 rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 flex items-center gap-3">
                            <svg class="w-8 h-8 text-amber-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-amber-800">Artwork henüz hazır değil</p>
                                <p class="text-xs text-amber-600">Grafik departmanı dosyayı yükledikten sonra buradan indirebilirsiniz.</p>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Revizyon Talebi Modal --}}
<div id="revision-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
        <div class="p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-1">Revizyon Talebi</h2>
            <p class="text-sm text-slate-500 mb-4">Lütfen revizyon nedenini açıklayın. Bu not grafik ekibine iletilecektir.</p>

            <form id="revision-form" method="POST" action="">
                @csrf
                <input type="hidden" name="_revision_id" id="revision-id-field">
                <div class="mb-4">
                    <label for="revision-notes" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5">Açıklama <span class="text-red-500">*</span></label>
                    <textarea
                        id="revision-notes"
                        name="notes"
                        rows="4"
                        required
                        minlength="10"
                        maxlength="1000"
                        placeholder="Örn: Renk tonları düzeltilmeli, logo boyutu küçültülmeli..."
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-100 resize-none"
                    ></textarea>
                    @error('notes')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @if(!$errors->has('notes'))
                        <p class="mt-1 text-xs text-slate-400">En az 10 karakter giriniz.</p>
                    @endif
                </div>

                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeRevisionModal()" class="btn btn-secondary text-sm">İptal</button>
                    <button type="submit" class="btn text-sm bg-red-600 text-white hover:bg-red-700 border-red-600 hover:border-red-700">Talebi Gönder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
@if($errors->has('notes'))
document.addEventListener('DOMContentLoaded', function() {
    const revisionId = '{{ old('_revision_id') }}';
    if (revisionId) openRevisionModal(revisionId);
});
@endif

function openRevisionModal(revisionId) {
    const form = document.getElementById('revision-form');
    form.action = '/revizyon/' + revisionId + '/reddet';
    document.getElementById('revision-id-field').value = revisionId;
    document.getElementById('revision-notes').value = '';
    const modal = document.getElementById('revision-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('revision-notes').focus();
}

function closeRevisionModal() {
    const modal = document.getElementById('revision-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.getElementById('revision-modal').addEventListener('click', function(e) {
    if (e.target === this) closeRevisionModal();
});
</script>
@endsection
