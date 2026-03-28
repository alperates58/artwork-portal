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
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 space-y-4">
        <div class="card p-5 space-y-4">
            <div>
                <p class="text-xs text-slate-500 mb-0.5">Sipariş No</p>
                <p class="font-mono font-semibold text-slate-900 text-lg">{{ $order->order_no }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500 mb-0.5">Tedarikçi</p>
                <p class="text-sm font-medium text-slate-900">{{ $order->supplier->name }}</p>
                <p class="text-xs text-slate-500">{{ $order->supplier->code }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <p class="text-xs text-slate-500 mb-0.5">Sipariş Tarihi</p>
                    <p class="text-sm text-slate-900">{{ $order->order_date->format('d.m.Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 mb-0.5">Teslim Tarihi</p>
                    <p class="text-sm text-slate-900">{{ $order->due_date?->format('d.m.Y') ?? '—' }}</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <p class="text-xs text-slate-500 mb-0.5">Durum</p>
                    <x-ui.badge :variant="match($order->status){'active'=>'success','draft'=>'gray','completed'=>'info','cancelled'=>'danger',default=>'gray'}">{{ $order->status_label }}</x-ui.badge>
                </div>
                <div>
                    <p class="text-xs text-slate-500 mb-0.5">Sevk</p>
                    <x-ui.badge :variant="match($order->shipment_status){'dispatched'=>'info','delivered'=>'success','not_found'=>'danger',default=>'warning'}">{{ $order->shipment_status_label }}</x-ui.badge>
                </div>
            </div>
            @if($order->shipment_reference || $order->shipment_synced_at)
                <div>
                    <p class="text-xs text-slate-500 mb-0.5">Mikro Bilgisi</p>
                    <p class="text-sm text-slate-700">{{ $order->shipment_reference ?: 'Referans bekleniyor' }}</p>
                    <p class="text-xs text-slate-400">{{ $order->shipment_synced_at?->format('d.m.Y H:i') ?? 'Henüz senkronlanmadı' }}</p>
                </div>
            @endif
            @if($order->notes)
                <div>
                    <p class="text-xs text-slate-500 mb-0.5">Notlar</p>
                    <p class="text-sm text-slate-700">{{ $order->notes }}</p>
                </div>
            @endif
            <div>
                <p class="text-xs text-slate-500 mb-0.5">Oluşturan</p>
                <p class="text-sm text-slate-700">{{ $order->createdBy->name }}</p>
                <p class="text-xs text-slate-400">{{ $order->created_at->format('d.m.Y H:i') }}</p>
            </div>
        </div>

        @can('delete', $order)
            <div class="card p-5 border border-red-100">
                <h2 class="text-sm font-semibold text-red-700">Siparişi Sil</h2>
                <p class="text-xs text-slate-500 mt-2">Bu işlem bağlı satırları, artwork kayıtlarını ve revizyon loglarını da kaldırır. Onay için sipariş numarasını yazın.</p>
                <form method="POST" action="{{ route('orders.destroy', $order) }}" class="mt-4 space-y-3" onsubmit="return confirm('Bu siparişi kalıcı olarak silmek istediğinize emin misiniz?');">
                    @csrf @method('DELETE')
                    <input type="text" name="confirmation_text" class="input" placeholder="{{ $order->order_no }}">
                    <button type="submit" class="btn btn-secondary text-red-600 border-red-200 hover:bg-red-50">Siparişi Sil</button>
                </form>
            </div>
        @endcan
    </div>

    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">Sipariş Satırları ({{ $order->lines->count() }})</h2>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($order->lines as $line)
                    <div class="px-5 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-600">{{ $line->line_no }}</span>
                                    <span class="text-sm font-medium text-slate-900">{{ $line->product_code }}</span>
                                    <x-ui.badge :variant="match($line->artwork_status?->value ?? 'pending'){'uploaded'=>'success','revision'=>'danger','approved'=>'info',default=>'warning'}">{{ $line->artwork_status?->label() ?? 'Bekliyor' }}</x-ui.badge>
                                </div>
                                <p class="text-sm text-slate-600">{{ $line->description }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    {{ $line->quantity }} {{ $line->unit }}
                                    @if(!is_null($line->shipped_quantity))
                                        · Sevk edilen: {{ $line->shipped_quantity }}
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-2 flex-shrink-0">
                                @if($line->hasActiveArtwork())
                                    <a href="{{ route('artwork.download', $line->activeRevision) }}" class="btn btn-secondary text-xs py-1.5">İndir Rev.{{ $line->activeRevision->revision_no }}</a>
                                    <a href="{{ route('artworks.revisions', $line) }}" class="text-xs text-brand-700 hover:underline font-medium">Revizyonlar</a>
                                @else
                                    <span class="text-xs text-slate-400">Artwork yok</span>
                                @endif

                                @if(auth()->user()->canUploadArtwork())
                                    <a href="{{ route('artworks.create', $line) }}" class="btn btn-primary text-xs py-1.5">
                                        {{ $line->hasActiveArtwork() ? 'Yeni Revizyon' : 'Yükle' }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if($line->hasActiveArtwork())
                            <div class="mt-3 flex items-center gap-3 bg-slate-50 rounded-lg px-3 py-2">
                                <div class="w-7 h-7 bg-slate-200 rounded flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-slate-600">{{ $line->activeRevision->extension }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-slate-700 truncate">{{ $line->activeRevision->original_filename }}</p>
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

        {{-- Sipariş Notları --}}
        <div class="card">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">Sipariş Notları</h2>
            </div>

            @if($order->orderNotes->isNotEmpty())
                <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                    @foreach($order->orderNotes as $note)
                        <div class="px-5 py-4 flex gap-3">
                            <div class="w-8 h-8 bg-brand-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <span class="text-xs font-semibold text-brand-700">{{ strtoupper(substr($note->user->name, 0, 2)) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-semibold text-slate-900">{{ $note->user->name }}</span>
                                    <span class="text-xs text-slate-400">{{ $note->created_at->format('d.m.Y H:i') }}</span>
                                </div>
                                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $note->body }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-5 py-6 text-center text-sm text-slate-400">Henüz not eklenmemiş.</div>
            @endif

            @if(!auth()->user()->isSupplier())
                <div class="px-5 py-4 border-t border-slate-100">
                    <form method="POST" action="{{ route('orders.notes.store', $order) }}" class="flex gap-3">
                        @csrf
                        <div class="flex-1">
                            <textarea name="body" rows="2"
                                class="input resize-none"
                                placeholder="Not ekle…">{{ old('body') }}</textarea>
                            @error('body')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary self-end">Ekle</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- Activity Timeline --}}
    @if(!auth()->user()->isSupplier())
    <div class="lg:col-span-3">
        <div class="card">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-sm font-semibold text-slate-800">Aktivite Zaman Çizelgesi</h3>
            </div>

            @if($timeline->isEmpty())
                <div class="px-5 py-8 text-center text-sm text-slate-400">Henüz aktivite yok.</div>
            @else
                <div class="px-5 py-4">
                    <ol class="relative border-l border-slate-200 ml-3 space-y-0">
                        @foreach($timeline as $event)
                        <li class="mb-6 ml-6">
                            <span class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white
                                @if($event['color'] === 'violet') bg-violet-100 text-violet-600
                                @elseif($event['color'] === 'blue') bg-blue-100 text-blue-600
                                @elseif($event['color'] === 'amber') bg-amber-100 text-amber-600
                                @else bg-slate-100 text-slate-500 @endif
                            ">
                                @if($event['icon'] === 'plus')
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                @elseif($event['icon'] === 'upload')
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                @elseif($event['icon'] === 'note')
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                @endif
                            </span>
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ $event['title'] }}</p>
                                <p class="text-xs text-slate-500">{{ $event['sub'] }}</p>
                                @if(!empty($event['body']))
                                    <p class="mt-1 text-xs text-slate-600 bg-slate-50 rounded-lg px-3 py-2">{{ $event['body'] }}</p>
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
    @endif
</div>
@endsection
