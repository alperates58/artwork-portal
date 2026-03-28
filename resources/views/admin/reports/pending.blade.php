@extends('layouts.app')
@section('title', 'Bekleyen Artwork Raporu')
@section('page-title', 'Bekleyen Artwork Yaşlandırma')
@section('page-subtitle', 'Artwork yüklemesi beklenen satırların sipariş tarihinden itibaren bekleme süreleri')

@section('header-actions')
    <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary">← Raporlar</a>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.reports.pending') }}" class="card p-4" id="pending-filter-form">
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-56">
                <label class="label" for="pend_supplier">Tedarikçi</label>
                <select id="pend_supplier" name="supplier_id" class="input">
                    <option value="">Tüm tedarikçiler</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected($selectedSupplierId === $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-44">
                <label class="label" for="pend_product">Ürün Kodu</label>
                <input id="pend_product" name="product_code" value="{{ $searchProduct }}" class="input font-mono" placeholder="Ürün kodu ara…" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-primary">Filtrele</button>
            @if($selectedSupplierId || $searchProduct)
                <a href="{{ route('admin.reports.pending') }}" class="btn btn-secondary">Temizle</a>
            @endif
        </div>
    </form>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @php
            $bandColors = [
                '0-3 gün'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                '4-7 gün'   => 'bg-blue-50 text-blue-700 border-blue-200',
                '8-14 gün'  => 'bg-amber-50 text-amber-700 border-amber-200',
                '15-30 gün' => 'bg-orange-50 text-orange-700 border-orange-200',
                '30+ gün'   => 'bg-red-50 text-red-700 border-red-200',
            ];
        @endphp
        @foreach($chartBands as $band)
            <div class="card p-4 border {{ $bandColors[$band['label']] ?? 'border-slate-200' }}">
                <p class="text-xs font-medium uppercase tracking-wide opacity-70">{{ $band['label'] }}</p>
                <p class="text-2xl font-bold mt-1">{{ $band['count'] }}</p>
                <p class="text-xs mt-0.5 opacity-60">bekleyen satır</p>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-[1fr_320px] gap-6">
        {{-- Chart --}}
        <div class="card p-5">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">Yaşlandırma Dağılımı</h2>
            <div style="height:220px">
                <canvas id="agingChart"></canvas>
            </div>
        </div>

        {{-- Supplier breakdown --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">Tedarikçi Bazında</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($supplierCounts as $s)
                    <div class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ $s['name'] }}</p>
                            <p class="text-xs text-slate-400">Max {{ $s['max_wait'] }} gün bekleme</p>
                        </div>
                        <span class="shrink-0 text-sm font-bold {{ $s['max_wait'] > 14 ? 'text-red-600' : ($s['max_wait'] > 7 ? 'text-amber-600' : 'text-slate-700') }}">
                            {{ $s['count'] }} satır
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-400">Bekleyen satır yok.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Detail table --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Bekleyen Satır Detayı</h2>
            <span class="text-xs text-slate-400">{{ $lines->count() }} satır</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left">
                        <th class="px-4 py-3 font-medium text-slate-600">Sipariş No</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Satır</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Ürün Kodu</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Tedarikçi</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Sipariş Tarihi</th>
                        <th class="px-4 py-3 font-medium text-slate-600 text-center">Bekleme</th>
                        <th class="px-4 py-3 font-medium text-slate-600 text-center">Durum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($lines->sortByDesc('waiting_days') as $line)
                        @php
                            $d = $line->waiting_days;
                            $cls = match(true) {
                                $d <= 3  => 'bg-emerald-50 text-emerald-700',
                                $d <= 7  => 'bg-blue-50 text-blue-700',
                                $d <= 14 => 'bg-amber-50 text-amber-700',
                                $d <= 30 => 'bg-orange-50 text-orange-700',
                                default  => 'bg-red-50 text-red-700',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 font-mono text-xs">
                                <a href="{{ route('orders.index', ['search' => $line->order_no]) }}" class="text-brand-700 hover:underline">{{ $line->order_no }}</a>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $line->line_no }}</td>
                            <td class="px-4 py-3 text-xs font-medium text-slate-800">{{ $line->product_code }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600 max-w-[160px] truncate">{{ $line->supplier_name }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $line->order_date->format('d.m.Y') }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $cls }}">
                                    {{ number_format($d, 1) }} gün
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="badge {{ $d > 14 ? 'badge-danger' : ($d > 7 ? 'badge-warning' : 'badge-info') }}">
                                    {{ $line->band }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Bekleyen artwork satırı yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const sel = document.getElementById('pend_supplier');
    const prod = document.getElementById('pend_product');
    const form = document.getElementById('pending-filter-form');
    if (sel) sel.addEventListener('change', () => form.submit());
    if (prod) {
        let t;
        prod.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => form.submit(), 400); });
    }
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const agingCtx = document.getElementById('agingChart').getContext('2d');
new Chart(agingCtx, {
    type: 'bar',
    data: {
        labels: {!! collect($chartBands)->pluck('label')->toJson() !!},
        datasets: [{
            label: 'Bekleyen Satır Sayısı',
            data: {!! collect($chartBands)->pluck('count')->toJson() !!},
            backgroundColor: ['rgba(16,185,129,0.75)','rgba(37,99,235,0.75)','rgba(245,158,11,0.75)','rgba(249,115,22,0.75)','rgba(239,68,68,0.75)'],
            borderRadius: 8,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { stepSize: 1, font: { size: 11 } } }
        }
    }
});
</script>
@endpush
