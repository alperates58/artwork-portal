@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Metric cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="card p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Artwork Bekliyor</p>
        <p class="text-2xl font-semibold text-slate-900">{{ number_format($metrics['pending_artwork']) }}</p>
        <p class="text-xs text-amber-600 mt-1">Yüklenmemiş sipariş satırı</p>
    </div>

    <div class="card p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Artwork Yüklendi</p>
        <p class="text-2xl font-semibold text-slate-900">{{ number_format($metrics['uploaded_artwork']) }}</p>
        <p class="text-xs text-emerald-600 mt-1">Aktif revizyonu olan satır</p>
    </div>

    <div class="card p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Aktif Siparişler</p>
        <p class="text-2xl font-semibold text-slate-900">{{ number_format($metrics['active_orders']) }}</p>
        <p class="text-xs text-slate-400 mt-1">Toplam aktif PO</p>
    </div>

    <div class="card p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Toplam Revizyon</p>
        <p class="text-2xl font-semibold text-slate-900">{{ number_format($metrics['total_revisions']) }}</p>
        <p class="text-xs text-slate-400 mt-1">Tüm zamanlar</p>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Son yüklenen dosyalar --}}
    <div class="card">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Son Yüklenen Artwork</h2>
            <a href="{{ route('orders.index') }}" class="text-xs text-blue-600 hover:underline">Tümünü gör</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($recentRevisions as $rev)
                <div class="px-5 py-3 flex items-start gap-3">
                    <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-xs font-semibold text-slate-500">{{ $rev->extension }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ $rev->original_filename }}</p>
                        <p class="text-xs text-slate-500">
                            {{ $rev->artwork->orderLine->purchaseOrder->order_no }} ·
                            {{ $rev->artwork->orderLine->purchaseOrder->supplier->name }} ·
                            Rev.{{ $rev->revision_no }}
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-xs text-slate-500">{{ $rev->file_size_formatted }}</p>
                        <p class="text-xs text-slate-400">{{ $rev->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-sm text-slate-400">
                    Henüz artwork yüklenmemiş.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Son indirmeler --}}
    <div class="card">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Son İndirmeler</h2>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.logs.index') }}" class="text-xs text-blue-600 hover:underline">Log görüntüle</a>
            @endif
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($recentDownloads as $log)
                <div class="px-5 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-xs font-semibold text-blue-600">
                            {{ strtoupper(substr($log->user?->name ?? '?', 0, 2)) }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-900 truncate">
                            {{ $log->user?->name ?? 'Silinmiş kullanıcı' }}
                        </p>
                        <p class="text-xs text-slate-500">
                            {{ $log->payload['original_filename'] ?? '—' }}
                        </p>
                    </div>
                    <p class="text-xs text-slate-400 flex-shrink-0">{{ $log->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-sm text-slate-400">
                    Henüz indirme kaydı yok.
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
