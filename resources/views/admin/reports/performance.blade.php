@extends('layouts.app')
@section('title', 'Performans Raporu')
@section('page-title', 'Performans Raporu')
@section('page-subtitle', 'Tedarikçi ve grafik ekibi performansını 100 puan üzerinden değerlendirin.')

@section('header-actions')
    <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary">← Raporlar</a>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.reports.performance') }}" class="card p-4" id="perf-filter-form">
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-52">
                <label class="label" for="pf_supplier">Tedarikçi</label>
                <select id="pf_supplier" name="supplier_id" class="input">
                    <option value="">Tüm tedarikçiler</option>
                    @foreach($allSuppliers as $s)
                        <option value="{{ $s->id }}" @selected($supplierId === $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-40">
                <label class="label" for="pf_period">Dönem</label>
                <select id="pf_period" name="period" class="input">
                    <option value="30"  @selected($period === '30')>Son 30 gün</option>
                    <option value="90"  @selected($period === '90')>Son 90 gün</option>
                    <option value="180" @selected($period === '180')>Son 6 ay</option>
                    <option value="365" @selected($period === '365')>Son 1 yıl</option>
                    <option value="all" @selected($period === 'all')>Tümü</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Uygula</button>
        </div>
    </form>

    {{-- Grade distribution summary --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        @foreach([
            ['key' => 'excellent', 'label' => 'Mükemmel', 'sub' => '≥85 puan', 'color' => 'emerald'],
            ['key' => 'good',      'label' => 'İyi',       'sub' => '70–84',    'color' => 'blue'],
            ['key' => 'average',   'label' => 'Orta',      'sub' => '50–69',    'color' => 'amber'],
            ['key' => 'poor',      'label' => 'Zayıf',     'sub' => '30–49',    'color' => 'orange'],
            ['key' => 'critical',  'label' => 'Kritik',    'sub' => '<30 puan', 'color' => 'red'],
        ] as $g)
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-{{ $g['color'] }}-600">{{ $gradeDist[$g['key']] }}</p>
                <p class="text-xs font-semibold text-slate-700 mt-0.5">{{ $g['label'] }}</p>
                <p class="text-[10px] text-slate-400">{{ $g['sub'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Scoring legend --}}
    <div class="card p-4">
        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-3">Puanlama Kriterleri (Toplam 100 Puan)</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 text-xs">
            @foreach([
                ['score' => 30, 'label' => 'Hızlı İndirme',    'desc' => 'Yükleme→İndirme ort. (gün)', 'icon' => '⚡'],
                ['score' => 25, 'label' => 'Onay Oranı',        'desc' => 'Onaylanan artwork %\'si',    'icon' => '✅'],
                ['score' => 20, 'label' => 'Anlık Tepki',       'desc' => '≤3 günde indirme oranı',     'icon' => '🎯'],
                ['score' => 15, 'label' => 'Tamamlama',         'desc' => 'Yüklenen+onaylı satır %',    'icon' => '📊'],
                ['score' => 10, 'label' => 'Sipariş Hacmi',     'desc' => 'Toplam sipariş adedi',       'icon' => '📦'],
            ] as $c)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-base">{{ $c['icon'] }}</span>
                        <span class="font-bold text-brand-700">{{ $c['score'] }}p</span>
                    </div>
                    <p class="font-semibold text-slate-800">{{ $c['label'] }}</p>
                    <p class="text-slate-500 text-[11px] mt-0.5">{{ $c['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Supplier performance table --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Tedarikçi Performans Tablosu</h2>
            <span class="text-xs text-slate-400">{{ $suppliers->count() }} tedarikçi</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full" style="min-width:780px;font-size:12px">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left">
                        <th class="px-4 py-2.5 font-semibold text-slate-500">#</th>
                        <th class="px-4 py-2.5 font-semibold text-slate-500">Tedarikçi</th>
                        <th class="px-4 py-2.5 font-semibold text-slate-500 text-center">Sipariş</th>
                        <th class="px-4 py-2.5 font-semibold text-slate-500 text-center">Onay %</th>
                        <th class="px-4 py-2.5 font-semibold text-slate-500 text-center">Ort. İnd. Süresi</th>
                        <th class="px-4 py-2.5 font-semibold text-slate-500 text-center">Hızlı İnd. %</th>
                        <th class="px-4 py-2.5 font-semibold text-slate-500 text-center">Puan</th>
                        <th class="px-4 py-2.5 font-semibold text-slate-500 text-center">Derece</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($suppliers as $i => $s)
                        @php
                            $c = $s->grade['color'];
                            $rankColors = ['text-amber-500 font-bold', 'text-slate-500 font-semibold', 'text-orange-400 font-semibold'];
                        @endphp
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-2.5 {{ $rankColors[$i] ?? 'text-slate-400' }}">{{ $i + 1 }}</td>
                            <td class="px-4 py-2.5">
                                <p class="font-semibold text-slate-900">{{ $s->name }}</p>
                                <p class="text-[10px] text-slate-400">{{ $s->code }}</p>
                            </td>
                            <td class="px-4 py-2.5 text-center text-slate-600">{{ $s->order_count }}</td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="{{ $s->approved_pct >= 70 ? 'text-emerald-600' : ($s->approved_pct >= 40 ? 'text-amber-600' : 'text-red-600') }} font-semibold">
                                    {{ $s->approved_pct }}%
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                @if($s->avg_days !== null)
                                    <span class="{{ $s->avg_days <= 3 ? 'text-emerald-600' : ($s->avg_days <= 7 ? 'text-amber-600' : 'text-red-600') }} font-semibold">
                                        {{ $s->avg_days }}g
                                    </span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-center text-slate-600">{{ $s->fast_pct }}%</td>
                            <td class="px-4 py-2.5 text-center">
                                {{-- Mini score bar + number --}}
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full bg-{{ $c }}-500" style="width:{{ $s->total }}%"></div>
                                    </div>
                                    <span class="font-bold text-{{ $c }}-700 w-7 text-right">{{ $s->total }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold bg-{{ $c }}-50 text-{{ $c }}-700 border border-{{ $c }}-200">
                                    {{ $s->grade['label'] }}
                                </span>
                            </td>
                        </tr>
                        {{-- Score breakdown row --}}
                        <tr class="bg-slate-50/40 border-b border-slate-100">
                            <td colspan="2" class="px-4 pb-2 pt-0"></td>
                            <td colspan="6" class="px-4 pb-2 pt-0">
                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-[10px] text-slate-500">
                                    <span>⚡ İndirme: <strong class="text-slate-700">{{ $s->scores['downloadScore'] }}</strong>/30</span>
                                    <span>✅ Onay: <strong class="text-slate-700">{{ $s->scores['approvalScore'] }}</strong>/25</span>
                                    <span>🎯 Hız: <strong class="text-slate-700">{{ $s->scores['fastScore'] }}</strong>/20</span>
                                    <span>📊 Tam.: <strong class="text-slate-700">{{ $s->scores['completionScore'] }}</strong>/15</span>
                                    <span>📦 Hacim: <strong class="text-slate-700">{{ $s->scores['volumeScore'] }}</strong>/10</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Veri bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Score distribution chart --}}
    @if($suppliers->count() > 1)
    <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Puan Dağılımı</h2>
        <div style="height:240px">
            <canvas id="perfChart"></canvas>
        </div>
    </div>
    @endif

    {{-- Graphic team performance --}}
    @if($graphicPerformance->isNotEmpty())
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-900">Grafik Ekibi Yükleme Performansı</h2>
            <p class="text-xs text-slate-400 mt-0.5">Artwork yükleme hızı ve onaylanma oranı</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full" style="min-width:520px;font-size:12px">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left">
                        <th class="px-4 py-2.5 font-semibold text-slate-500">Kullanıcı</th>
                        <th class="px-4 py-2.5 font-semibold text-slate-500 text-center">Yükleme Sayısı</th>
                        <th class="px-4 py-2.5 font-semibold text-slate-500 text-center">Ort. Sipariş→Yükleme</th>
                        <th class="px-4 py-2.5 font-semibold text-slate-500 text-center">Onaylanan</th>
                        <th class="px-4 py-2.5 font-semibold text-slate-500 text-center">Onay Oranı</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($graphicPerformance as $g)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-2.5 font-semibold text-slate-900">{{ $g->name }}</td>
                            <td class="px-4 py-2.5 text-center text-slate-600">{{ $g->upload_count }}</td>
                            <td class="px-4 py-2.5 text-center">
                                @if($g->avg_days !== null)
                                    <span class="{{ $g->avg_days <= 5 ? 'text-emerald-600' : ($g->avg_days <= 10 ? 'text-amber-600' : 'text-red-600') }} font-semibold">
                                        {{ $g->avg_days }}g
                                    </span>
                                @else <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-center text-slate-600">{{ $g->approved_count }}</td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="{{ $g->approval_rate >= 70 ? 'text-emerald-600' : ($g->approval_rate >= 40 ? 'text-amber-600' : 'text-red-600') }} font-semibold">
                                    {{ $g->approval_rate }}%
                                </span>
                            </td>
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
@if($suppliers->count() > 1)
<script>
const perfLabels = {!! $suppliers->pluck('name')->map(fn($n) => mb_strlen($n) > 18 ? mb_substr($n, 0, 18) . '…' : $n)->toJson() !!};
const perfScores = {!! $suppliers->pluck('total')->toJson() !!};
const perfColors = {!! $suppliers->map(fn($s) => match($s->grade['color']) {
    'emerald' => 'rgba(16,185,129,0.8)',
    'brand'   => 'rgba(37,99,235,0.75)',
    'amber'   => 'rgba(251,191,36,0.85)',
    'orange'  => 'rgba(249,115,22,0.8)',
    default   => 'rgba(239,68,68,0.8)',
})->toJson() !!};

new Chart(document.getElementById('perfChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: perfLabels,
        datasets: [{
            label: 'Performans Puanı',
            data: perfScores,
            backgroundColor: perfColors,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.raw} / 100 puan`
                }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 } } },
            y: {
                min: 0, max: 100,
                grid: { color: 'rgba(0,0,0,0.04)' },
                ticks: { font: { size: 10 } }
            }
        }
    }
});
</script>
@endif
@endpush
