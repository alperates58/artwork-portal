@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <x-ui.card padding="p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Artwork Bekliyor</p>
        <a href="{{ route('orders.index', ['artwork_status' => 'pending']) }}"
           class="text-2xl font-semibold text-slate-900 hover:text-brand-700 transition-colors">{{ number_format($metrics['pending_artwork']) }}</a>
        <p class="text-xs text-amber-600 mt-1">Yüklenmemiş satır</p>
    </x-ui.card>
    <x-ui.card padding="p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Artwork Yüklendi</p>
        <a href="{{ route('orders.index', ['artwork_status' => 'uploaded']) }}"
           class="text-2xl font-semibold text-slate-900 hover:text-brand-700 transition-colors">{{ number_format($metrics['uploaded_artwork']) }}</a>
        <p class="text-xs text-emerald-600 mt-1">Aktif revizyonu olan</p>
    </x-ui.card>
    <x-ui.card padding="p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Onay Bekliyor</p>
        <a href="{{ route('orders.index', ['artwork_status' => 'revision']) }}"
           class="text-2xl font-semibold text-slate-900 hover:text-brand-700 transition-colors">{{ number_format($metrics['pending_approval'] ?? 0) }}</a>
        <p class="text-xs text-blue-600 mt-1">Tedarikçi onayı beklenen</p>
    </x-ui.card>
    <x-ui.card padding="p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Aktif Siparişler</p>
        <a href="{{ route('orders.index', ['status' => 'active']) }}"
           class="text-2xl font-semibold text-slate-900 hover:text-brand-700 transition-colors">{{ number_format($metrics['active_orders']) }}</a>
        <p class="text-xs text-slate-400 mt-1">Toplam aktif PO</p>
    </x-ui.card>
</div>

@php
    $flowPressure = (float) ($metrics['flow_pressure_pct'] ?? 0);
    $flowTone = match (true) {
        $flowPressure >= 55 => [
            'label' => 'Kritik birikme',
            'desc' => 'Açık sipariş satırlarına göre bekleyen artwork yoğunluğu çok yüksek.',
            'class' => 'bg-red-50 border-red-200 text-red-700',
            'dot' => 'bg-red-500',
        ],
        $flowPressure >= 35 => [
            'label' => 'Yüksek yoğunluk',
            'desc' => 'Operasyonda birikme artıyor, yakın takip önerilir.',
            'class' => 'bg-amber-50 border-amber-200 text-amber-700',
            'dot' => 'bg-amber-500',
        ],
        default => [
            'label' => 'Akış dengede',
            'desc' => 'Bekleyen yük, açık satır hacmine göre yönetilebilir seviyede.',
            'class' => 'bg-emerald-50 border-emerald-200 text-emerald-700',
            'dot' => 'bg-emerald-500',
        ],
    };

    $stalledCount = (int) ($metrics['stalled_pending_artwork'] ?? 0);
    $stalledTone = match (true) {
        $stalledCount >= 30 => 'text-red-600',
        $stalledCount >= 10 => 'text-amber-600',
        default => 'text-emerald-600',
    };
@endphp

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
    <x-ui.card padding="p-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Operasyon Nabzı</p>
                <p class="mt-2 text-lg font-semibold text-slate-900">{{ $flowTone['label'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $flowTone['desc'] }}</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold {{ $flowTone['class'] }}">
                <span class="h-2 w-2 rounded-full {{ $flowTone['dot'] }}"></span>
                {{ number_format($flowPressure, 1, ',', '.') }}%
            </span>
        </div>
        <div class="mt-4 grid grid-cols-3 gap-3 text-xs">
            <div class="rounded-xl bg-slate-50 px-3 py-2">
                <p class="text-slate-400">Açık satır</p>
                <p class="mt-1 text-sm font-semibold text-slate-800">{{ number_format($metrics['active_order_lines'] ?? 0) }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-3 py-2">
                <p class="text-slate-400">Bekleyen</p>
                <p class="mt-1 text-sm font-semibold text-slate-800">{{ number_format($metrics['pending_artwork'] ?? 0) }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-3 py-2">
                <p class="text-slate-400">7+ gün bekleyen</p>
                <p class="mt-1 text-sm font-semibold {{ $stalledTone }}">{{ number_format($stalledCount) }}</p>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card padding="p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Darboğaz Alarmı</p>
        <div class="mt-2 flex items-end justify-between gap-3">
            <p class="text-3xl font-semibold text-slate-900">{{ number_format($metrics['blocked_orders'] ?? 0) }}</p>
            <p class="text-xs text-slate-500 text-right">7 günden eski ve hâlâ bekleyen satırı olan aktif sipariş</p>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('orders.index', ['status' => 'active', 'artwork_status' => 'pending']) }}" class="btn btn-secondary text-xs">Bekleyen siparişleri aç</a>
            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('reports', 'view'))
                <a href="{{ route('admin.reports.pending') }}" class="btn btn-secondary text-xs">Bekleyen artwork raporu</a>
            @endif
        </div>
    </x-ui.card>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
            <h2 class="text-sm font-semibold text-slate-900">Son Yüklenen Artwork</h2>
            <a href="{{ route('orders.index') }}" class="text-xs text-brand-700 hover:underline">Tümü</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($panels['recent_revisions'] as $revision)
                <div class="px-5 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <span class="text-xs font-bold text-slate-500">{{ $revision['extension'] }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ $revision['filename'] }}</p>
                        <p class="text-xs text-slate-500">{{ $revision['order_no'] }} · Rev.{{ $revision['revision_no'] }}</p>
                    </div>
                    <p class="text-xs text-slate-400 flex-shrink-0">{{ $revision['created_at_human'] }}</p>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-sm text-slate-400">Henüz artwork yüklenmemiş.</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
            <h2 class="text-sm font-semibold text-slate-900">Son İndirmeler</h2>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.logs.index') }}" class="text-xs text-brand-700 hover:underline">Loglar</a>
            @endif
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($panels['recent_downloads'] as $download)
                <div class="px-5 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-xs font-semibold text-blue-600">{{ strtoupper(substr($download['user_name'] ?: '?', 0, 2)) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-900 truncate">{{ $download['user_name'] }}</p>
                        <p class="text-xs text-slate-500">{{ $download['filename'] }}</p>
                    </div>
                    <p class="text-xs text-slate-400 flex-shrink-0">{{ $download['created_at_human'] }}</p>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-sm text-slate-400">Henüz indirme yok.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
