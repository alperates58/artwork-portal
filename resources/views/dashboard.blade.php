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
