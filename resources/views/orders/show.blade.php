@extends('layouts.app')
@section('title', 'Sipariş ' . $order->order_no)
@section('page-title', 'Sipariş Detayı')

@section('header-actions')
    <a href="{{ route('orders.index') }}" class="btn btn-secondary">← Listeye Dön</a>
    @can('update', $order)
        <a href="{{ route('orders.edit', $order) }}" class="btn btn-secondary">Düzenle</a>
    @endcan
@endsection

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-1">
        <div class="card space-y-4 p-5">
            <div>
                <p class="mb-0.5 text-xs text-slate-500">Sipariş No</p>
                <p class="font-mono text-lg font-semibold text-slate-900">{{ $order->order_no }}</p>
            </div>
            <div>
                <p class="mb-0.5 text-xs text-slate-500">Tedarikçi</p>
                <p class="text-sm font-medium text-slate-900">{{ $order->supplier->name }}</p>
                <p class="text-xs text-slate-500">{{ $order->supplier->code }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <p class="mb-0.5 text-xs text-slate-500">Sipariş Tarihi</p>
                    <p class="text-sm text-slate-900">{{ $order->order_date->format('d.m.Y') }}</p>
                </div>
                <div>
                    <p class="mb-0.5 text-xs text-slate-500">Teslim Tarihi</p>
                    <p class="text-sm text-slate-900">{{ $order->due_date?->format('d.m.Y') ?? '—' }}</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <p class="mb-0.5 text-xs text-slate-500">Durum</p>
                    <x-ui.badge :variant="match($order->status){'active' => 'success', 'draft' => 'gray', 'completed' => 'info', 'cancelled' => 'danger', default => 'gray'}">{{ $order->status_label }}</x-ui.badge>
                </div>
                <div>
                    <p class="mb-0.5 text-xs text-slate-500">Sevk</p>
                    <x-ui.badge :variant="match($order->shipment_status){'dispatched' => 'info', 'delivered' => 'success', 'not_found' => 'danger', default => 'warning'}">{{ $order->shipment_status_label }}</x-ui.badge>
                </div>
            </div>
            @if($order->shipment_reference || $order->shipment_synced_at)
                <div>
                    <p class="mb-0.5 text-xs text-slate-500">Mikro Bilgisi</p>
                    <p class="text-sm text-slate-700">{{ $order->shipment_reference ?: 'Referans bekleniyor' }}</p>
                    <p class="text-xs text-slate-400">{{ $order->shipment_synced_at?->format('d.m.Y H:i') ?? 'Henüz senkronlanmadı' }}</p>
                </div>
            @endif
            @if($order->notes)
                <div>
                    <p class="mb-0.5 text-xs text-slate-500">Notlar</p>
                    <p class="text-sm text-slate-700">{{ $order->notes }}</p>
                </div>
            @endif
            <div>
                <p class="mb-0.5 text-xs text-slate-500">Artwork Süreci</p>
                <div class="flex flex-wrap gap-1.5">
                    @if($order->pending_artwork_count > 0)
                        <x-ui.badge variant="warning">{{ $order->pending_artwork_count }} satır bekliyor</x-ui.badge>
                    @else
                        <x-ui.badge variant="success">Tamamlandı</x-ui.badge>
                    @endif

                    @if($order->manual_artwork_count > 0)
                        <x-ui.badge variant="info">{{ $order->manual_artwork_count }} satır manuel</x-ui.badge>
                    @endif
                </div>
            </div>
            <div>
                <p class="mb-0.5 text-xs text-slate-500">Oluşturan</p>
                <p class="text-sm text-slate-700">{{ $order->createdBy->name }}</p>
                <p class="text-xs text-slate-400">{{ $order->created_at->format('d.m.Y H:i') }}</p>
            </div>
        </div>

        @can('delete', $order)
            <div class="card border border-red-100 p-5">
                <h2 class="text-sm font-semibold text-red-700">Siparişi Sil</h2>
                <p class="mt-2 text-xs text-slate-500">Bu işlem bağlı satırları, artwork kayıtlarını ve revizyon loglarını da kaldırır. Onay için sipariş numarasını yazın.</p>
                <form method="POST" action="{{ route('orders.destroy', $order) }}" class="mt-4 space-y-3" onsubmit="return confirm('Bu siparişi kalıcı olarak silmek istediğinize emin misiniz?');">
                    @csrf
                    @method('DELETE')
                    <input type="text" name="confirmation_text" class="input" placeholder="{{ $order->order_no }}">
                    <button type="submit" class="btn btn-secondary border-red-200 text-red-600 hover:bg-red-50">Siparişi Sil</button>
                </form>
            </div>
        @endcan
    </div>

    <div class="space-y-6 lg:col-span-2">
        <div class="card">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Sipariş Satırları ({{ $order->lines->count() }})</h2>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($order->lines as $line)
                    <div class="px-5 py-4" x-data="{ showCreate: false, replyTo: {{ old('parent_id') ? (int) old('parent_id') : 'null' }}, editTarget: {{ old('edit_note_id') ? (int) old('edit_note_id') : 'null' }} }">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="mb-1 flex flex-wrap items-center gap-2">
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-600">{{ $line->line_no }}</span>
                                    <a href="{{ route('order-lines.show', $line) }}" class="text-sm font-medium text-slate-900 hover:text-brand-700 hover:underline">{{ $line->product_code }}</a>
                                    @if($line->is_manual_artwork_completed && ! $line->hasActiveArtwork())
                                        <x-ui.badge variant="info">Manuel gönderildi</x-ui.badge>
                                    @else
                                        <x-ui.badge :variant="match($line->artwork_status?->value ?? 'pending'){'uploaded' => 'success','revision' => 'danger','approved' => 'info',default => 'warning'}">{{ $line->artwork_status?->label() ?? 'Bekliyor' }}</x-ui.badge>
                                    @endif
                                </div>
                                <p class="text-sm text-slate-600">{{ $line->description }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    {{ $line->quantity }} {{ $line->unit }}
                                    @if(! is_null($line->shipped_quantity))
                                        · Sevk edilen: {{ $line->shipped_quantity }}
                                    @endif
                                </p>

                                @if($line->is_manual_artwork_completed)
                                    <div class="mt-3 rounded-lg border border-sky-100 bg-sky-50 px-3 py-2">
                                        <p class="text-xs font-semibold text-sky-700">Manuel gönderim notu</p>
                                        <p class="mt-1 text-sm text-slate-700">{{ $line->manual_artwork_note }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $line->manualArtworkCompletedBy?->name ?? '—' }} · {{ $line->manual_artwork_completed_at?->format('d.m.Y H:i') }}
                                        </p>
                                    </div>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @if($line->hasActiveArtwork())
                                    <a href="{{ route('artwork.download', $line->activeRevision) }}" class="btn btn-secondary py-1.5 text-xs">İndir Rev.{{ $line->activeRevision->revision_no }}</a>
                                    <a href="{{ route('artworks.revisions', $line) }}" class="text-xs font-medium text-brand-700 hover:underline">Revizyonlar</a>
                                @else
                                    <span class="text-xs text-slate-400">{{ $line->is_manual_artwork_completed ? 'Portal dışı gönderim ile tamamlandı' : 'Artwork yok' }}</span>
                                @endif

                                @if(auth()->user()->canUploadArtwork())
                                    <a href="{{ route('artworks.create', $line) }}" class="btn btn-primary py-1.5 text-xs">
                                        {{ $line->hasActiveArtwork() ? 'Yeni Revizyon' : 'Yükle' }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50/80">
                            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">Satır açıklamaları</p>
                                    <p class="text-xs text-slate-500">Bu satıra özel not ve yanıtları burada takip edin.</p>
                                </div>
                                <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-800" @click="showCreate = !showCreate; if (showCreate) { replyTo = null; }">
                                    <span class="text-base leading-none">+</span>
                                    <span>Sipariş açıklama ekle</span>
                                </button>
                            </div>

                            <div class="space-y-3 px-4 py-4">
                                @forelse($line->lineNotes as $note)
                                    @include('orders.partials.note-thread', ['note' => $note, 'order' => $order, 'line' => $line])
                                @empty
                                    <p class="text-sm text-slate-400">Bu satır için henüz açıklama eklenmemiş.</p>
                                @endforelse

                                <form method="POST" action="{{ route('orders.notes.store', $order) }}" class="rounded-lg border border-dashed border-slate-300 bg-white p-3" x-show="showCreate" x-cloak>
                                    @csrf
                                    <input type="hidden" name="purchase_order_line_id" value="{{ $line->id }}">
                                    <label class="label">Sipariş açıklaması</label>
                                    <textarea name="body" rows="3" class="input resize-none" placeholder="Bu satırla ilgili açıklamanızı yazın...">{{ old('purchase_order_line_id') == $line->id && ! old('parent_id') ? old('body') : '' }}</textarea>
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
                                <form method="POST" action="{{ route('order-lines.manual-artwork.store', $line) }}" class="mt-4 rounded-lg border border-emerald-100 bg-emerald-50/50 p-3">
                                    @csrf
                                    <label class="label">Bu satırı manuel gönderildi olarak işaretle</label>
                                    <textarea
                                        name="manual_artwork_note"
                                        rows="3"
                                        class="input resize-none"
                                        placeholder="Örn: Bu ürünün tasarımı daha önce mail ile paylaşılmıştı, yeni siparişte aynı çalışma kullanılacak.">{{ old('manual_artwork_note') }}</textarea>
                                    @error('manual_artwork_note')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-secondary border-emerald-200 text-emerald-700 hover:bg-emerald-100">Manuel gönderildi olarak işaretle</button>
                                    </div>
                                </form>
                            @endif
                        @endcan

                        @if($line->hasActiveArtwork())
                            <div class="mt-3 flex items-center gap-3 rounded-lg bg-slate-50 px-3 py-2">
                                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded bg-slate-200">
                                    <span class="text-xs font-bold text-slate-600">{{ $line->activeRevision->extension }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-medium text-slate-700">{{ $line->activeRevision->original_filename }}</p>
                                    <p class="text-xs text-slate-400">
                                        Rev.{{ $line->activeRevision->revision_no }} · {{ $line->activeRevision->file_size_formatted }} · {{ $line->activeRevision->uploadedBy->name }} · {{ $line->activeRevision->created_at->format('d.m.Y H:i') }}
                                    </p>
                                </div>
                                <x-ui.badge variant="success" class="text-xs">Güncel</x-ui.badge>
                            </div>
                        @endif
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
                        <textarea name="body" rows="2" class="input resize-none" placeholder="Sipariş notu ekleyin...">{{ old('purchase_order_line_id') ? '' : old('body') }}</textarea>
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
    </div>

    <div class="lg:col-span-3">
        <div class="card">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-sm font-semibold text-slate-800">Aktivite Zaman Çizelgesi</h3>
            </div>

            @if($timeline->isEmpty())
                <div class="px-5 py-8 text-center text-sm text-slate-400">Henüz aktivite yok.</div>
            @else
                <div class="px-5 py-4">
                    <ol class="relative ml-3 space-y-0 border-l border-slate-200">
                        @foreach($timeline as $event)
                            <li class="mb-6 ml-6">
                                <span class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white
                                    @if($event['color'] === 'violet') bg-violet-100 text-violet-600
                                    @elseif($event['color'] === 'blue') bg-blue-100 text-blue-600
                                    @elseif($event['color'] === 'amber') bg-amber-100 text-amber-600
                                    @elseif($event['color'] === 'emerald') bg-emerald-100 text-emerald-600
                                    @else bg-slate-100 text-slate-500 @endif">
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
                                    @endif
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-slate-800">{{ $event['title'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $event['sub'] }}</p>
                                    @if(! empty($event['body']))
                                        <p class="mt-1 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">{{ $event['body'] }}</p>
                                    @endif
                                    <time class="mt-0.5 block text-[11px] text-slate-400">
                                        {{ $event['at']->format('d.m.Y H:i') }}
                                        <span class="ml-1">({{ $event['at']->diffForHumans() }})</span>
                                    </time>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
