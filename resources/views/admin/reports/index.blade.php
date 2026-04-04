@extends('layouts.app')
@section('title', 'Rapor Merkezi')
@section('page-title', 'Rapor Merkezi')
@section('page-subtitle', 'Operasyonel raporlar, analiz araçları ve özel rapor oluşturucu')

@section('content')
{{-- Rapor grupları --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 mb-8">
    {{-- Tedarik Süreci --}}
    <a href="{{ route('admin.reports.lead-time') }}" class="card p-4 flex items-start gap-3 hover:border-brand-200 hover:shadow-sm transition group">
        <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-brand-50 flex items-center justify-center group-hover:bg-brand-100 transition">
            <svg class="w-4.5 h-4.5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-slate-800 group-hover:text-brand-700 transition">Tedarik Süreci</p>
            <p class="text-xs text-slate-500 mt-0.5">Sipariş → Yükleme → İndirme aşamaları, lead time analizi</p>
        </div>
    </a>
    {{-- Bekleyen İşler --}}
    <a href="{{ route('admin.reports.pending') }}" class="card p-4 flex items-start gap-3 hover:border-amber-200 hover:shadow-sm transition group">
        <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center group-hover:bg-amber-100 transition">
            <svg class="w-4.5 h-4.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-slate-800 group-hover:text-amber-700 transition">Bekleyen İşler & Yaşlandırma</p>
            <p class="text-xs text-slate-500 mt-0.5">Artwork bekleyen satırlar, yaşlandırma bantları, kritik bekleyenler</p>
        </div>
    </a>
    {{-- Tedarikçi Performansı --}}
    <a href="{{ route('admin.reports.performance') }}" class="card p-4 flex items-start gap-3 hover:border-emerald-200 hover:shadow-sm transition group">
        <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center group-hover:bg-emerald-100 transition">
            <svg class="w-4.5 h-4.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-slate-800 group-hover:text-emerald-700 transition">Tedarikçi Performansı</p>
            <p class="text-xs text-slate-500 mt-0.5">100 puan üzerinden performans skoru, onay oranları, hız metrikleri</p>
        </div>
    </a>
    {{-- Kategori & İçerik --}}
    <a href="{{ route('admin.reports.category') }}" class="card p-4 flex items-start gap-3 hover:border-violet-200 hover:shadow-sm transition group">
        <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-violet-50 flex items-center justify-center group-hover:bg-violet-100 transition">
            <svg class="w-4.5 h-4.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-slate-800 group-hover:text-violet-700 transition">Kategori & İçerik Analizi</p>
            <p class="text-xs text-slate-500 mt-0.5">Artwork galerisi kategori ve etiket dağılımı, bekleyen içerikler</p>
        </div>
    </a>
    {{-- Stok Kodu --}}
    <a href="{{ route('admin.reports.stock-code') }}" class="card p-4 flex items-start gap-3 hover:border-slate-300 hover:shadow-sm transition group">
        <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center group-hover:bg-slate-200 transition">
            <svg class="w-4.5 h-4.5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-slate-800 group-hover:text-slate-700 transition">Stok Kodu Kullanımı</p>
            <p class="text-xs text-slate-500 mt-0.5">Stok kodu bazında revizyon sayıları ve tedarikçi kullanım geçmişi</p>
        </div>
    </a>
    {{-- Aktivite Akışı --}}
    <a href="{{ route('admin.reports.traceability') }}" class="card p-4 flex items-start gap-3 hover:border-emerald-200 hover:shadow-sm transition group">
        <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center group-hover:bg-emerald-100 transition">
            <svg class="w-4.5 h-4.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-slate-800 group-hover:text-emerald-700 transition">İzlenebilirlik</p>
            <p class="text-xs text-slate-500 mt-0.5">Stok kodu veya adıyla son basım izi, revizyon akışı ve gün farkları</p>
        </div>
    </a>
    <a href="{{ route('admin.reports.timeline') }}" class="card p-4 flex items-start gap-3 hover:border-sky-200 hover:shadow-sm transition group">
        <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-sky-50 flex items-center justify-center group-hover:bg-sky-100 transition">
            <svg class="w-4.5 h-4.5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-slate-800 group-hover:text-sky-700 transition">Aktivite Akışı</p>
            <p class="text-xs text-slate-500 mt-0.5">Sipariş, artwork ve not olayları zaman çizelgesi, tarih/tür filtresi</p>
        </div>
    </a>
</div>

{{-- Özel Raporlar bölümü --}}
<div class="flex items-center justify-between mb-3">
    <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Güncel Operasyon</h2>
    <a href="{{ route('admin.reports.factory.index') }}" class="text-xs text-brand-600 hover:underline font-medium">Özel Raporlar →</a>
</div>

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

        {{-- Firma Bazlı Sipariş Raporu Grafiği --}}
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-slate-900">Firma Bazlı Sipariş Dağılımı</h2>
                <div class="flex items-center gap-3 text-[10px] text-slate-500">
                    <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-brand-500"></span> Aktif</span>
                    <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-400"></span> Tamamlandı</span>
                    <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-slate-200"></span> Diğer</span>
                </div>
            </div>
            <canvas id="supplierOrderChart" class="w-full" style="max-height:200px"></canvas>

            <div class="mt-5 border-t border-slate-100 pt-4">
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-3">Artwork Satır Durumu</p>
                <canvas id="artworkStatusChart" class="w-full" style="max-height:160px"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
@php
    $chartLabels   = $supplierOrderChart->pluck('name');
    $activeData    = $supplierOrderChart->pluck('active_count');
    $completedData = $supplierOrderChart->pluck('completed_count');
    $otherData     = $supplierOrderChart->pluck('other_count');
    $pendingLines  = $supplierOrderChart->pluck('pending_lines');
    $uploadedLines = $supplierOrderChart->pluck('uploaded_lines');
    $approvedLines = $supplierOrderChart->pluck('approved_lines');
@endphp
const supplierLabels = {!! json_encode($chartLabels) !!};

// Grouped bar — sipariş durumu
new Chart(document.getElementById('supplierOrderChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: supplierLabels,
        datasets: [
            {
                label: 'Aktif',
                data: {!! json_encode($activeData) !!},
                backgroundColor: 'rgba(99,102,241,0.85)',
                borderRadius: 4,
            },
            {
                label: 'Tamamlandı',
                data: {!! json_encode($completedData) !!},
                backgroundColor: 'rgba(52,211,153,0.75)',
                borderRadius: 4,
            },
            {
                label: 'Diğer',
                data: {!! json_encode($otherData) !!},
                backgroundColor: 'rgba(226,232,240,0.9)',
                borderRadius: 4,
            },
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: 'rgba(0,0,0,0.04)' } }
        }
    }
});

// Stacked bar — artwork satır durumu
new Chart(document.getElementById('artworkStatusChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: supplierLabels,
        datasets: [
            {
                label: 'Bekleyen',
                data: {!! json_encode($pendingLines) !!},
                backgroundColor: 'rgba(251,191,36,0.85)',
                borderRadius: 4,
            },
            {
                label: 'Yüklendi',
                data: {!! json_encode($uploadedLines) !!},
                backgroundColor: 'rgba(99,102,241,0.7)',
                borderRadius: 4,
            },
            {
                label: 'Onaylı',
                data: {!! json_encode($approvedLines) !!},
                backgroundColor: 'rgba(52,211,153,0.8)',
                borderRadius: 4,
            },
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: { font: { size: 10 }, boxWidth: 10, padding: 10 }
            }
        },
        scales: {
            x: { stacked: true, grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: 'rgba(0,0,0,0.04)' } }
        }
    }
});
</script>
@endpush
@endsection
