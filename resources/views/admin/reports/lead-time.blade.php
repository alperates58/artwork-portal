@extends('layouts.app')
@section('title', 'Lead Time Raporu')
@section('page-title', 'Lead Time Raporu')
@section('page-subtitle', 'Sipariş → Artwork yükleme → Tedarikçi indirme süreleri')

@section('header-actions')
    <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary">← Raporlar</a>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Supplier filter --}}
    <form method="GET" action="{{ route('admin.reports.lead-time') }}" class="card p-4" id="lead-time-filter-form">
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-64">
                <label class="label" for="lt_supplier">Tedarikçi</label>
                <select id="lt_supplier" name="supplier_id" class="input">
                    <option value="">Tüm tedarikçiler</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected($selectedSupplierId === $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filtrele</button>
            @if($selectedSupplierId)
                <a href="{{ route('admin.reports.lead-time') }}" class="btn btn-secondary">Temizle</a>
            @endif
        </div>
    </form>

    {{-- Chart --}}
    @if($supplierAvgs->isNotEmpty())
    <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Tedarikçi Bazında Ortalama Lead Time (Gün)</h2>
        <div style="height:260px">
            <canvas id="leadTimeChart"></canvas>
        </div>
    </div>
    @endif

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Sipariş Lead Time Detayı</h2>
            <span class="text-xs text-slate-400">Son 50 sipariş</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left">
                        <th class="px-4 py-3 font-medium text-slate-600">Sipariş No</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Tedarikçi</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Sipariş Tarihi</th>
                        <th class="px-4 py-3 font-medium text-slate-600">İlk Artwork Yükleme</th>
                        <th class="px-4 py-3 font-medium text-slate-600 text-center">Sipariş → Yükleme</th>
                        <th class="px-4 py-3 font-medium text-slate-600">İlk İndirme</th>
                        <th class="px-4 py-3 font-medium text-slate-600 text-center">Yükleme → İndirme</th>
                        <th class="px-4 py-3 font-medium text-slate-600 text-center">Toplam</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        @php
                            $uploadClass = match(true) {
                                $row->order_to_upload_d === null => 'text-amber-500',
                                $row->order_to_upload_d <= 3    => 'text-emerald-600',
                                $row->order_to_upload_d <= 7    => 'text-brand-600',
                                default                          => 'text-red-600',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 font-mono text-xs font-semibold">
                                <a href="{{ route('orders.index', ['search' => $row->order_no]) }}" class="text-brand-700 hover:underline">{{ $row->order_no }}</a>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600 max-w-[160px] truncate">{{ $row->supplier_name }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ $row->order_date->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                                {{ $row->upload_at ? $row->upload_at->format('d.m.Y H:i') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($row->order_to_upload_d !== null)
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold {{ $uploadClass }} bg-slate-50">
                                        {{ $row->order_to_upload_d }} gün
                                    </span>
                                @else
                                    <span class="text-xs text-amber-500 font-medium">Bekliyor</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                                {{ $row->download_at ? $row->download_at->format('d.m.Y H:i') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-slate-600">
                                {{ $row->upload_to_download_d !== null ? $row->upload_to_download_d . ' gün' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($row->total_d !== null)
                                    <span class="text-xs font-bold text-slate-800">{{ $row->total_d }} gün</span>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Veri bulunamadı.</td></tr>
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
    const sel = document.getElementById('lt_supplier');
    if (sel) sel.addEventListener('change', () => document.getElementById('lead-time-filter-form').submit());
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
@if($supplierAvgs->isNotEmpty())
const ctx = document.getElementById('leadTimeChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: {!! $supplierAvgs->pluck('label')->map(fn($l) => strlen($l) > 25 ? substr($l, 0, 25) . '…' : $l)->toJson() !!},
        datasets: [
            {
                label: 'Sipariş → Yükleme (gün)',
                data: {!! $supplierAvgs->pluck('avg_to_upload')->toJson() !!},
                backgroundColor: 'rgba(37,99,235,0.75)',
                borderRadius: 6,
            },
            {
                label: 'Yükleme → İndirme (gün)',
                data: {!! $supplierAvgs->pluck('avg_to_download')->toJson() !!},
                backgroundColor: 'rgba(16,185,129,0.65)',
                borderRadius: 6,
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
        scales: {
            x: { stacked: false, grid: { display: false }, ticks: { font: { size: 10 } } },
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } }
        }
    }
});
@endif
</script>
@endpush
