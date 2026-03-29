@extends('layouts.app')
@section('title', 'Kategori & Etiket Analizi')
@section('page-title', 'Kategori & Etiket Analizi')
@section('page-subtitle', 'Artwork galerisinin kategori ve etiket bazında kullanım dağılımı')

@section('header-actions')
    <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary">← Raporlar</a>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.reports.category') }}" class="card p-4" id="cat-filter-form">
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-56">
                <label class="label" for="cat_supplier">Tedarikçi</label>
                <select id="cat_supplier" name="supplier_id" class="input">
                    <option value="">Tüm tedarikçiler</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected($selectedSupplierId === $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-40">
                <label class="label" for="cat_date_from">Başlangıç</label>
                <input id="cat_date_from" name="date_from" type="date" value="{{ $dateFrom }}" class="input">
            </div>
            <div class="w-full sm:w-40">
                <label class="label" for="cat_date_to">Bitiş</label>
                <input id="cat_date_to" name="date_to" type="date" value="{{ $dateTo }}" class="input">
            </div>
            <button type="submit" class="btn btn-primary">Filtrele</button>
            @if($selectedSupplierId || $dateFrom || $dateTo)
                <a href="{{ route('admin.reports.category') }}" class="btn btn-secondary">Temizle</a>
            @endif
        </div>
    </form>

    {{-- Category chart + table --}}
    <div class="grid lg:grid-cols-2 gap-6">
        <div class="card p-5">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">Kategori Bazında Kullanım</h2>
            @if($categories->isNotEmpty())
                <div style="height:240px">
                    <canvas id="categoryChart"></canvas>
                </div>
            @else
                <p class="text-sm text-slate-400 py-10 text-center">Henüz kategori yok.</p>
            @endif
        </div>

        <div class="card p-5">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">Etiket Bazında Kullanım</h2>
            @if($tags->isNotEmpty())
                <div style="height:240px">
                    <canvas id="tagChart"></canvas>
                </div>
            @else
                <p class="text-sm text-slate-400 py-10 text-center">Henüz etiket yok.</p>
            @endif
        </div>
    </div>

    {{-- Category detail --}}
    <div class="card overflow-x-auto">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-900">Kategori Detayı</h2>
        </div>
        <table class="w-full min-w-[620px] text-xs sm:text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-left">
                    <th class="px-4 py-3 font-medium text-slate-600">Kategori</th>
                    <th class="px-4 py-3 font-medium text-slate-600 text-center">Dosya Sayısı</th>
                    <th class="px-4 py-3 font-medium text-slate-600 text-center">Kullanım</th>
                    <th class="px-4 py-3 font-medium text-slate-600">Son Kullanım</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($categories as $cat)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $cat->name }}</td>
                        <td class="px-4 py-3 text-center text-slate-700">{{ $cat->file_count }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $cat->usage_count > 0 ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $cat->usage_count }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $cat->last_used ? $cat->last_used->format('d.m.Y H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">Kategori bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Tag detail --}}
    <div class="card overflow-x-auto">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-900">Etiket Detayı</h2>
        </div>
        <table class="w-full min-w-[620px] text-xs sm:text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-left">
                    <th class="px-4 py-3 font-medium text-slate-600">Etiket</th>
                    <th class="px-4 py-3 font-medium text-slate-600 text-center">Dosya Sayısı</th>
                    <th class="px-4 py-3 font-medium text-slate-600 text-center">Toplam Kullanım</th>
                    <th class="px-4 py-3 font-medium text-slate-600 text-right">Kullanım Yoğunluğu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($tags as $tag)
                    @php $pct = $tags->max('usage_count') > 0 ? round($tag->usage_count / $tags->max('usage_count') * 100) : 0; @endphp
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $tag->name }}</td>
                        <td class="px-4 py-3 text-center text-slate-700">{{ $tag->file_count }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $tag->usage_count > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $tag->usage_count }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <div class="w-24 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-brand-500" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-xs text-slate-400 w-8 text-right">{{ $pct }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">Etiket bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pending by category --}}
    @if($pendingByCategory->isNotEmpty())
    <div class="card overflow-x-auto">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-900">Kategori Bazında Bekleyen Artwork Süreleri</h2>
        </div>
        <table class="w-full min-w-[620px] text-xs sm:text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-left">
                    <th class="px-4 py-3 font-medium text-slate-600">Kategori</th>
                    <th class="px-4 py-3 font-medium text-slate-600 text-center">Bekleyen</th>
                    <th class="px-4 py-3 font-medium text-slate-600 text-center">Ort. Bekleme</th>
                    <th class="px-4 py-3 font-medium text-slate-600 text-center">Max Bekleme</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($pendingByCategory as $row)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $row->category_name ?? 'Kategorisiz' }}</td>
                        <td class="px-4 py-3 text-center text-slate-700">{{ $row->pending_count }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-semibold {{ $row->avg_waiting_days > 14 ? 'text-red-600' : ($row->avg_waiting_days > 7 ? 'text-amber-600' : 'text-emerald-600') }}">
                                {{ round($row->avg_waiting_days, 1) }} gün
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-bold {{ $row->max_waiting_days > 14 ? 'text-red-700' : 'text-slate-700' }}">
                                {{ $row->max_waiting_days }} gün
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
(function() {
    const sel = document.getElementById('cat_supplier');
    const form = document.getElementById('cat-filter-form');
    if (sel) sel.addEventListener('change', () => form.submit());
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const palette = ['rgba(37,99,235,0.75)','rgba(16,185,129,0.75)','rgba(245,158,11,0.75)','rgba(239,68,68,0.75)','rgba(139,92,246,0.75)','rgba(20,184,166,0.75)','rgba(249,115,22,0.75)','rgba(236,72,153,0.75)'];

@if($categories->isNotEmpty())
new Chart(document.getElementById('categoryChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: {!! $categories->pluck('name')->toJson() !!},
        datasets: [{ data: {!! $categories->pluck('usage_count')->toJson() !!}, backgroundColor: palette, borderWidth: 2, borderColor: '#fff' }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'right', labels: { font: { size: 11 }, boxWidth: 12 } } }
    }
});
@endif

@if($tags->isNotEmpty())
new Chart(document.getElementById('tagChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: {!! $tags->pluck('name')->toJson() !!},
        datasets: [
            { label: 'Dosya', data: {!! $tags->pluck('file_count')->toJson() !!}, backgroundColor: 'rgba(37,99,235,0.6)', borderRadius: 5 },
            { label: 'Kullanım', data: {!! $tags->pluck('usage_count')->toJson() !!}, backgroundColor: 'rgba(16,185,129,0.6)', borderRadius: 5 },
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12 } } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 } } },
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { stepSize: 1, font: { size: 10 } } }
        }
    }
});
@endif
</script>
@endpush
