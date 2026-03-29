@extends('layouts.app')
@section('title', $customReport->name)
@section('page-title', $customReport->name)
@section('page-subtitle', 'Kayıtlı rapor — ' . implode(', ', array_map(fn($d) => \App\Models\CustomReport::dimensionLabels()[$d] ?? $d, $customReport->dimensions)))

@section('header-actions')
    <a href="{{ route('admin.reports.factory.index') }}" class="btn btn-secondary">← Raporlarım</a>
    @if($customReport->created_by === auth()->id() || auth()->user()->isAdmin())
        <a href="{{ route('admin.reports.factory.create') }}?clone={{ $customReport->id }}" class="btn btn-secondary">Klonla</a>
        <form method="POST" action="{{ route('admin.reports.factory.destroy', $customReport) }}"
              onsubmit="return confirm('Bu raporu silmek istediğinize emin misiniz?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-secondary text-red-600 hover:border-red-200 hover:bg-red-50">Sil</button>
        </form>
    @endif
@endsection

@section('content')
<div x-data="reportShow()" class="space-y-5">

    {{-- Bilgi satırı --}}
    <div class="card p-4 flex flex-wrap items-center gap-3">
        <div class="flex flex-wrap gap-1.5">
            @foreach($customReport->dimensions as $d)
                <span class="inline-flex items-center gap-1 rounded-lg bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                    {{ \App\Models\CustomReport::dimensionLabels()[$d] ?? $d }}
                </span>
            @endforeach
            @foreach($customReport->metrics as $m)
                <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                    {{ \App\Models\CustomReport::metricLabels()[$m] ?? $m }}
                </span>
            @endforeach
        </div>
        <span class="text-xs text-slate-400 ml-auto">{{ $data['row_count'] }} kayıt · {{ $customReport->createdBy->name }} · {{ $customReport->updated_at->format('d.m.Y H:i') }}</span>
    </div>

    {{-- Filtre override --}}
    <form method="GET" class="card p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-48">
                <label class="label">Tedarikçi</label>
                <select name="supplier_id" class="input">
                    <option value="">Tüm tedarikçiler</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-40">
                <label class="label">Başlangıç</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="input">
            </div>
            <div class="w-full sm:w-40">
                <label class="label">Bitiş</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="input">
            </div>
            <button type="submit" class="btn btn-primary">Filtrele</button>
            @if(request()->hasAny(['supplier_id','date_from','date_to']))
                <a href="{{ route('admin.reports.factory.show', $customReport) }}" class="btn btn-secondary">Temizle</a>
            @endif
        </div>
    </form>

    @if($data['row_count'] === 0)
        <div class="card p-12 text-center">
            <p class="text-slate-400">Bu filtre ile veri bulunamadı.</p>
        </div>
    @else
        {{-- Grafik --}}
        <div class="card p-5">
            <div style="height:340px">
                <canvas id="show-chart"></canvas>
            </div>
        </div>

        {{-- Tablo --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Veri Tablosu</h3>
                <span class="text-xs text-slate-400">{{ $data['row_count'] }} kayıt</span>
            </div>
            <div class="divide-y divide-slate-100 md:hidden">
                @foreach($data['table'] as $row)
                    <div class="px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">{{ $row['label'] }}</p>
                        <div class="mt-2 grid grid-cols-1 gap-1.5 text-xs text-slate-600">
                            @foreach($customReport->metrics as $m)
                                <p>
                                    <span class="text-slate-400">{{ \App\Models\CustomReport::metricLabels()[$m] ?? $m }}:</span>
                                    <span class="font-medium text-slate-700">{{ $row[$m] }}</span>
                                </p>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left">
                            @foreach($data['columns'] as $col)
                                <th class="px-4 py-3 font-medium text-slate-600 whitespace-nowrap">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($data['table'] as $row)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap">{{ $row['label'] }}</td>
                                @foreach($customReport->metrics as $m)
                                    <td class="px-4 py-3 text-slate-700 whitespace-nowrap">{{ $row[$m] }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function reportShow() { return {}; }

(function () {
    const ctx = document.getElementById('show-chart');
    if (!ctx) return;
    const chartType = '{{ $customReport->chart_type }}';
    const isPie     = ['pie', 'doughnut'].includes(chartType);
    const rawData   = {!! json_encode($data) !!};
    new Chart(ctx.getContext('2d'), {
        type: chartType,
        data: {
            labels:   rawData.labels,
            datasets: isPie ? [rawData.datasets[0]] : rawData.datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: isPie ? 'right' : 'top', labels: { font: { size: 11 }, boxWidth: 12 } },
            },
            scales: isPie ? {} : {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
            },
        },
    });
})();
</script>
@endpush
