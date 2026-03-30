@extends('layouts.app')
@section('title', 'Tedarik Süreci Analizi')
@section('page-title', 'Tedarik Süreci Analizi')
@section('page-subtitle', 'Sipariş → Artwork yükleme → Tedarikçi indirme aşamaları ve süre metrikleri')

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
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Satır Bazlı Lead Time Detayı</h2>
            <span class="text-xs text-slate-400">{{ $rows->count() }} satır</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left">
                        <th class="px-3 py-2 font-semibold text-slate-500 whitespace-nowrap">Sipariş No</th>
                        <th class="px-3 py-2 font-semibold text-slate-500 whitespace-nowrap">Tedarikçi</th>
                        <th class="px-3 py-2 font-semibold text-slate-500 whitespace-nowrap">Sip. Tarihi</th>
                        <th class="px-3 py-2 font-semibold text-slate-500 whitespace-nowrap">Stok Kodu</th>
                        <th class="px-3 py-2 font-semibold text-slate-500">Stok İsmi</th>
                        <th class="px-3 py-2 font-semibold text-slate-500 whitespace-nowrap">İlk Yükleme</th>
                        <th class="px-3 py-2 font-semibold text-slate-500 text-center whitespace-nowrap">Sip.→Yük.</th>
                        <th class="px-3 py-2 font-semibold text-slate-500 whitespace-nowrap">İlk İndirme</th>
                        <th class="px-3 py-2 font-semibold text-slate-500 text-center whitespace-nowrap">Yük.→İnd.</th>
                        <th class="px-3 py-2 font-semibold text-slate-500 text-center whitespace-nowrap">Toplam</th>
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
                            $statusBadge = match($row->artwork_status) {
                                'approved' => 'bg-emerald-50 text-emerald-700',
                                'uploaded' => 'bg-blue-50 text-blue-700',
                                'revision' => 'bg-red-50 text-red-700',
                                default    => 'bg-amber-50 text-amber-700',
                            };
                            $statusLabel = match($row->artwork_status) {
                                'approved' => 'Onay',
                                'uploaded' => 'Yüklendi',
                                'revision' => 'Revizyon',
                                default    => 'Bekliyor',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-3 py-1.5 font-mono font-semibold whitespace-nowrap">
                                <a href="{{ route('orders.show', $row->order_id) }}" class="text-brand-700 hover:underline">{{ $row->order_no }}</a>
                                <span class="text-slate-400">#{{ $row->line_no }}</span>
                            </td>
                            <td class="px-3 py-1.5 text-slate-600 whitespace-nowrap" style="max-width:120px;overflow:hidden;text-overflow:ellipsis" title="{{ $row->supplier_name }}">{{ $row->supplier_name }}</td>
                            <td class="px-3 py-1.5 text-slate-500 whitespace-nowrap">{{ $row->order_date->format('d.m.Y') }}</td>
                            <td class="px-3 py-1.5 font-mono text-slate-700 whitespace-nowrap">{{ $row->product_code ?: '—' }}</td>
                            <td class="px-3 py-1.5 text-slate-600" style="max-width:160px">
                                <span class="block truncate" title="{{ $row->description }}">{{ $row->description ?: '—' }}</span>
                                <span class="inline-block rounded px-1 py-px font-medium {{ $statusBadge }}" style="font-size:10px">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-3 py-1.5 text-slate-500 whitespace-nowrap">{{ $row->upload_at ? $row->upload_at->format('d.m.Y') : '—' }}</td>
                            <td class="px-3 py-1.5 text-center">
                                @if($row->order_to_upload_d !== null)
                                    <span class="inline-flex items-center rounded-full px-1.5 py-0.5 font-semibold {{ $uploadClass }} bg-slate-50">{{ $row->order_to_upload_d }}g</span>
                                @else
                                    <span class="text-amber-500 font-medium">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-1.5 text-slate-500 whitespace-nowrap">{{ $row->download_at ? $row->download_at->format('d.m.Y') : '—' }}</td>
                            <td class="px-3 py-1.5 text-center text-slate-600">
                                {{ $row->upload_to_download_d !== null ? $row->upload_to_download_d . 'g' : '—' }}
                            </td>
                            <td class="px-3 py-1.5 text-center">
                                @if($row->total_d !== null)
                                    <span class="font-bold text-slate-800">{{ $row->total_d }}g</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-10 text-center text-slate-400">Veri bulunamadı.</td></tr>
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
