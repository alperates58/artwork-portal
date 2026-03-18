@extends('layouts.app')
@section('title', 'Satır Detayı')
@section('page-title', $line->product_code . ' — Satır Detayı')
@section('header-actions')
    <a href="{{ route('orders.show', $line->purchaseOrder) }}" class="btn-secondary">← Siparişe Dön</a>
    @if(auth()->user()->canUploadArtwork())
        <a href="{{ route('artworks.create', $line) }}" class="btn-primary">
            {{ $line->hasActiveArtwork() ? 'Yeni Revizyon' : 'Artwork Yükle' }}
        </a>
    @endif
@endsection
@section('content')
<div class="max-w-2xl space-y-5">

    {{-- Line info --}}
    <div class="card p-5 grid grid-cols-2 gap-4">
        <div><p class="text-xs text-slate-500 mb-0.5">Sipariş</p><p class="text-sm font-mono font-semibold">{{ $line->purchaseOrder->order_no }}</p></div>
        <div><p class="text-xs text-slate-500 mb-0.5">Tedarikçi</p><p class="text-sm">{{ $line->purchaseOrder->supplier->name }}</p></div>
        <div><p class="text-xs text-slate-500 mb-0.5">Ürün Kodu</p><p class="text-sm font-semibold">{{ $line->product_code }}</p></div>
        <div><p class="text-xs text-slate-500 mb-0.5">Satır No</p><p class="text-sm font-mono">{{ $line->line_no }}</p></div>
        <div class="col-span-2"><p class="text-xs text-slate-500 mb-0.5">Açıklama</p><p class="text-sm">{{ $line->description }}</p></div>
        <div><p class="text-xs text-slate-500 mb-0.5">Miktar</p><p class="text-sm">{{ $line->quantity }} {{ $line->unit }}</p></div>
        <div><p class="text-xs text-slate-500 mb-0.5">Artwork Durumu</p>
            @php $cls = match($line->artwork_status?->value ?? 'pending') {
                'uploaded'=>'badge-success','revision'=>'badge-danger','approved'=>'badge-info',default=>'badge-warning'
            }; @endphp
            <span class="badge {{ $cls }}">{{ $line->artwork_status?->label() ?? 'Bekliyor' }}</span>
        </div>
    </div>

    {{-- Active revision --}}
    @if($line->hasActiveArtwork())
        @php $rev = $line->activeRevision; @endphp
        <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-900">Güncel Artwork</h3>
                <span class="badge badge-success">Rev.{{ $rev->revision_no }}</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-slate-600">{{ $rev->extension }}</span>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-slate-900">{{ $rev->original_filename }}</p>
                    <p class="text-xs text-slate-500">
                        {{ $rev->file_size_formatted }} · {{ $rev->uploadedBy->name }} · {{ $rev->created_at->format('d.m.Y H:i') }}
                    </p>
                    @if($rev->notes)<p class="text-xs text-slate-400 mt-0.5 italic">{{ $rev->notes }}</p>@endif
                </div>
                <a href="{{ route('artwork.download', $rev) }}" class="btn-primary text-xs py-2">İndir</a>
            </div>
        </div>
    @endif

    {{-- All revisions --}}
    @if($line->artwork && $line->artwork->revisions->count() > 1)
        <div class="card">
            <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Tüm Revizyonlar</h3>
                <a href="{{ route('artworks.revisions', $line) }}" class="text-xs text-blue-600 hover:underline">Detay →</a>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach($line->artwork->revisions as $rev)
                <div class="px-5 py-3 flex items-center gap-3">
                    <span class="text-xs font-mono bg-slate-100 px-2 py-0.5 rounded">Rev.{{ $rev->revision_no }}</span>
                    <span class="text-sm text-slate-700 flex-1 truncate">{{ $rev->original_filename }}</span>
                    <span class="text-xs text-slate-400">{{ $rev->created_at->format('d.m.Y') }}</span>
                    @if($rev->is_active)<span class="badge badge-success">Aktif</span>
                    @else<span class="badge badge-gray">Arşiv</span>@endif
                </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
