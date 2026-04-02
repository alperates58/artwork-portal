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
<div x-data="{ previewOpen: false, showCreate: false, replyTo: {{ old('parent_id') ? (int) old('parent_id') : 'null' }}, editTarget: {{ old('edit_note_id') ? (int) old('edit_note_id') : 'null' }} }" class="space-y-6">
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
        @if($line->requiresRevision())
            @php $latestRejectedApproval = $line->latestRejectedApproval; @endphp
            <div class="mx-6 mb-4 mt-0 rounded-2xl border border-red-100 bg-red-50/80 px-4 py-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Revizyon talebi</p>
                        <p class="mt-1 text-sm text-slate-700">Bu artwork için tedarikçi tarafı yeni revizyon istedi.</p>
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
                    <p class="mt-1 text-sm text-slate-700">{{ filled($latestRejectedApproval?->notes) ? $latestRejectedApproval->notes : 'Tedarikçi tarafından ek not bırakılmadı.' }}</p>
                </div>
            </div>
        @endif
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
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="break-all text-xl font-semibold text-slate-900">{{ $rev->original_filename }}</p>
                                @include('artworks.partials.passive-gallery-badge', ['galleryItem' => $rev->galleryItem])
                            </div>
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
                                    <div class="flex items-center gap-2">
                                        @if($rev->is_active)
                                            <x-ui.badge variant="success">Aktif</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="gray">Arşiv</x-ui.badge>
                                        @endif
                                        @include('artworks.partials.passive-gallery-badge', ['galleryItem' => $rev->galleryItem])
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Satır Açıklamaları --}}
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Satır Açıklamaları</h3>
                        <p class="text-xs text-slate-500">Bu satıra özel not ve yanıtlar</p>
                    </div>
                    <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-800" @click="showCreate = !showCreate; if (showCreate) { replyTo = null; }">
                        <span class="text-base leading-none">+</span>
                        <span>Ekle</span>
                    </button>
                </div>

                <div class="space-y-3 px-5 py-4">
                    @forelse($line->lineNotes as $note)
                        @include('orders.partials.note-thread', ['note' => $note, 'order' => $line->purchaseOrder, 'line' => $line])
                    @empty
                        <p class="text-sm text-slate-400">Henüz açıklama eklenmemiş.</p>
                    @endforelse

                    <form method="POST" action="{{ route('orders.notes.store', $line->purchaseOrder) }}" class="rounded-xl border border-dashed border-slate-300 bg-white p-3" x-show="showCreate" x-cloak>
                        @csrf
                        <input type="hidden" name="purchase_order_line_id" value="{{ $line->id }}">
                        @include('orders.partials.mention-textarea', [
                            'name' => 'body',
                            'rows' => 3,
                            'placeholder' => 'Bu satırla ilgili açıklamanızı yazın... (@isim ile etiketleyebilirsiniz)',
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
        </div>
    </div>

    {{-- Aktivite Zaman Çizelgesi --}}
    @if($timeline->isNotEmpty())
    <div class="card overflow-hidden">
        <div class="border-b border-slate-100 px-6 py-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Aktivite Zaman Çizelgesi</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $timeline->count() }} olay</p>
                </div>
            </div>
        </div>
        <div class="px-6 py-5">
            <ol class="relative space-y-0">
                @foreach($timeline as $event)
                    <li class="relative flex gap-4">
                        {{-- Connector line --}}
                        @if(!$loop->last)
                            <div class="absolute left-[15px] top-8 bottom-0 w-px bg-slate-200"></div>
                        @endif

                        {{-- Dot --}}
                        <div class="relative z-10 mt-1 flex h-8 w-8 flex-none items-center justify-center rounded-full
                            @if($event['color'] === 'violet') bg-violet-100 text-violet-600
                            @elseif($event['color'] === 'blue') bg-blue-100 text-blue-600
                            @elseif($event['color'] === 'amber') bg-amber-100 text-amber-600
                            @elseif($event['color'] === 'emerald') bg-emerald-100 text-emerald-600
                            @elseif($event['color'] === 'red') bg-red-100 text-red-600
                            @else bg-slate-100 text-slate-500
                            @endif">
                            @if($event['icon'] === 'upload')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            @elseif($event['icon'] === 'note')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            @elseif($event['icon'] === 'reply')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                            @elseif($event['icon'] === 'mail')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            @elseif($event['icon'] === 'x')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            @else
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/></svg>
                            @endif
                        </div>

                        <div class="flex-1 pb-6">
                            <div class="flex flex-wrap items-baseline gap-x-2">
                                <p class="text-sm font-semibold text-slate-800">{{ $event['title'] }}</p>
                                <p class="text-xs text-slate-400">{{ $event['at']->format('d.m.Y H:i') }}</p>
                            </div>
                            @if(!empty($event['sub']))
                                <p class="mt-0.5 text-xs text-slate-500">{{ $event['sub'] }}</p>
                            @endif
                            @if(!empty($event['body']))
                                <p class="mt-1 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">{{ $event['body'] }}</p>
                            @endif

                            @if(!$loop->last && ($event['days_gap'] ?? 0) >= 0.1)
                                <div class="mt-3 flex items-center gap-2">
                                    <div class="h-px flex-1 bg-slate-100"></div>
                                    <span class="text-[10px] font-medium text-slate-400">
                                        @if($event['days_gap'] >= 1)
                                            {{ round($event['days_gap']) }} gün
                                        @else
                                            {{ round($event['days_gap'] * 24) }} saat
                                        @endif
                                    </span>
                                    <div class="h-px flex-1 bg-slate-100"></div>
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
    @endif

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
                    <div class="mb-3 flex items-center justify-between gap-3 px-1">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $line->activeRevision->original_filename }}</p>
                        @include('artworks.partials.passive-gallery-badge', ['galleryItem' => $line->activeRevision->galleryItem])
                    </div>
                    <img src="{{ route('artworks.preview', $line->activeRevision, false) }}" alt="{{ $line->activeRevision->original_filename }}" class="max-h-[80vh] w-full rounded-2xl object-contain">
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
