@extends('layouts.app')
@section('title', 'Raporlar')
@section('page-title', 'Operasyonel Raporlar')

@section('content')
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <x-ui.card padding="p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Aktif Sipariş</p>
        <a href="{{ route('orders.index', ['status' => 'active']) }}" class="text-2xl font-semibold text-slate-900 hover:text-brand-700 transition-colors">{{ number_format($summary['active_orders']) }}</a>
        <p class="text-xs text-slate-400 mt-1">Siparişlere git →</p>
    </x-ui.card>
    <x-ui.card padding="p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Bekleyen Artwork</p>
        <a href="{{ route('admin.reports.pending') }}" class="text-2xl font-semibold text-amber-600 hover:text-amber-700 transition-colors">{{ number_format($summary['pending_artwork']) }}</a>
        <p class="text-xs text-slate-400 mt-1">Yaşlandırma raporu →</p>
    </x-ui.card>
    <x-ui.card padding="p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Bugün Yüklenen</p>
        <a href="{{ route('orders.index', ['artwork_status' => 'uploaded']) }}" class="text-2xl font-semibold text-emerald-600 hover:text-emerald-700 transition-colors">{{ number_format($summary['uploaded_today']) }}</a>
        <p class="text-xs text-slate-400 mt-1">Yüklenmiş siparişler →</p>
    </x-ui.card>
    <x-ui.card padding="p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">7 Gün İndirme</p>
        <a href="{{ route('admin.logs.index') }}" class="text-2xl font-semibold text-brand-600 hover:text-brand-700 transition-colors">{{ number_format($summary['recent_downloads']) }}</a>
        <p class="text-xs text-slate-400 mt-1">Sistem logları →</p>
    </x-ui.card>
</div>

<div class="grid xl:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.75fr)] gap-6">
    <div class="space-y-6">
        <div class="card">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">Siparişten İlk Artwork Yüklemesine Geçiş</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($orderLeadTimes as $item)
                    <div class="px-5 py-4 flex items-center justify-between gap-4">
                        <div>
                            <a href="{{ route('orders.index', ['search' => $item->order_no]) }}" class="text-sm font-medium text-slate-900 hover:text-brand-700 hover:underline">{{ $item->order_no }}</a>
                            <p class="text-xs text-slate-500">{{ $item->supplier_name }} · {{ $item->order_date->format('d.m.Y') }}</p>
                        </div>
                        <div class="text-right">
                            @if($item->first_upload_at)
                                <p class="text-sm font-semibold text-brand-700">{{ $item->lead_days }} gün</p>
                                <p class="text-xs text-slate-400">İlk yükleme {{ $item->first_upload_at->format('d.m.Y H:i') }}</p>
                            @else
                                <p class="text-sm font-semibold text-amber-600">Henüz yüklenmedi</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-slate-400 text-sm">Raporlanacak sipariş bulunamadı.</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">Operasyon Zaman Çizgisi</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach($recentTimeline as $item)
                    <div class="px-5 py-4 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $item['title'] }}</p>
                            <p class="text-xs text-slate-500">{{ $item['meta'] ?: 'Sipariş bilgisi yok' }}</p>
                            <p class="text-xs text-slate-400 mt-1">{{ $item['subject'] }}</p>
                        </div>
                        <span class="text-xs text-slate-400 whitespace-nowrap">{{ $item['at']->format('d.m.Y H:i') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">Tedarikçi Aktivite Özeti</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach($supplierActivity as $supplier)
                    <div class="px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-medium text-slate-900">{{ $supplier->name }}</p>
                            <span class="text-xs text-slate-400">{{ $supplier->purchase_orders_count }} sipariş</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">{{ $supplier->active_orders_count }} aktif sipariş</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card p-5">
            <h2 class="text-sm font-semibold text-slate-900">Mikro Sevk Notu</h2>
            <p class="text-xs text-slate-500 mt-2">Gerçek sevk doğrulaması için Mikro API sevk endpoint detayları gerekir. Bu sürüm yalnızca sevk durumu alanlarını, admin ayarlarını ve görünür badge yapısını hazırlar.</p>
        </div>
    </div>
</div>
@endsection
