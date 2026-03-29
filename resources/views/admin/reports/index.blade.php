@extends('layouts.app')
@section('title', 'Raporlar')
@section('page-title', 'Operasyonel Raporlar')

@section('content')
{{-- Quick nav to detailed reports --}}
<div class="flex flex-wrap gap-2 mb-6">
    @foreach([
        ['route' => 'admin.reports.lead-time',  'label' => 'Lead Time',          'icon' => '⏱'],
        ['route' => 'admin.reports.pending',     'label' => 'Bekleyen Artwork',   'icon' => '⏳'],
        ['route' => 'admin.reports.performance', 'label' => 'Performans',         'icon' => '🏆'],
        ['route' => 'admin.reports.category',    'label' => 'Kategori',           'icon' => '🗂'],
        ['route' => 'admin.reports.stock-code',  'label' => 'Stok Kodu',          'icon' => '📋'],
        ['route' => 'admin.reports.timeline',    'label' => 'Aktivite',           'icon' => '📅'],
    ] as $r)
        <a href="{{ route($r['route']) }}"
           class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-600 hover:border-brand-300 hover:text-brand-700 transition">
            {{ $r['icon'] }} {{ $r['label'] }}
        </a>
    @endforeach
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
