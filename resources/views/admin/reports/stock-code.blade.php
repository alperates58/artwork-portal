@extends('layouts.app')
@section('title', 'Stok Kodu Kullanım Analizi')
@section('page-title', 'Stok Kodu Kullanım Analizi')
@section('page-subtitle', 'Stok kodu bazında revizyon sayıları, kullanıldığı tedarikçiler ve geçmiş artwork akışı')

@section('header-actions')
    <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary">← Raporlar</a>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.reports.stock-code') }}" id="stock-code-form" class="card p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px] relative">
                <label class="label" for="stock_code_search">Stok Kodu Ara</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/></svg>
                    <input id="stock_code_search" name="stock_code" value="{{ $searchCode }}"
                           class="input font-mono pl-9" placeholder="Yazmaya başlayın…" autocomplete="off">
                    <span id="stock-search-spinner" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 hidden">
                        <svg class="h-4 w-4 animate-spin text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </span>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Filtrele</button>
            @if($searchCode)
                <a href="{{ route('admin.reports.stock-code') }}" class="btn btn-secondary">Temizle</a>
            @endif
        </div>
    </form>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Usage per stock code --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Stok Kodu Kullanım Geçmişi</h2>
                <span class="text-xs text-slate-400">{{ $items->count() }} kayıt</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-xs sm:text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left">
                            <th class="px-4 py-3 font-medium text-slate-600">Stok Kodu</th>
                            <th class="px-4 py-3 font-medium text-slate-600">Dosya Adı</th>
                            <th class="px-4 py-3 font-medium text-slate-600 text-center">Kullanım</th>
                            <th class="px-4 py-3 font-medium text-slate-600">Tedarikçiler</th>
                            <th class="px-4 py-3 font-medium text-slate-600">Son Kullanım</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($items as $item)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.artwork-gallery.edit', $item->id) }}"
                                       class="font-mono text-xs font-bold text-brand-700 hover:underline">{{ $item->stock_code }}</a>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-700 max-w-[140px] truncate">{{ $item->name }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold
                                        {{ $item->usage_count >= 5 ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $item->usage_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600 max-w-[160px]">
                                    @if($item->suppliers->isNotEmpty())
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($item->suppliers->take(3) as $s)
                                                <span class="truncate max-w-[100px]">{{ $s }}</span>
                                            @endforeach
                                            @if($item->suppliers->count() > 3)
                                                <span class="text-slate-400">+{{ $item->suppliers->count() - 3 }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                                    {{ $item->last_used_at ? \Carbon\Carbon::parse($item->last_used_at)->format('d.m.Y') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">
                                @if($searchCode)
                                    "{{ $searchCode }}" için kayıt bulunamadı.
                                @else
                                    Henüz stok kodu girilmemiş.
                                @endif
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Revision count per stock code --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Stok Kodu Başına Revizyon Sayısı</h2>
                <span class="text-xs text-slate-400">{{ $revisionCounts->count() }} stok kodu</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-xs sm:text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left">
                            <th class="px-4 py-3 font-medium text-slate-600">Stok Kodu</th>
                            <th class="px-4 py-3 font-medium text-slate-600 text-center">Revizyon</th>
                            <th class="px-4 py-3 font-medium text-slate-600 text-center">Tedarikçi</th>
                            <th class="px-4 py-3 font-medium text-slate-600">Son Revizyon</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($revisionCounts as $row)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-3 font-mono text-xs font-bold text-slate-800">{{ $row->stock_code }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold
                                        {{ $row->revision_count >= 3 ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $row->revision_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-slate-600">{{ $row->supplier_count }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                                    {{ $row->last_revision_at ? \Carbon\Carbon::parse($row->last_revision_at)->format('d.m.Y') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">Veri bulunamadı.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($items->isNotEmpty())
    {{-- Chart: top 10 stock codes by usage --}}
    <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">En Çok Kullanılan Stok Kodları (Top 10)</h2>
        <div style="height:260px">
            <canvas id="stockCodeChart"></canvas>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
(function () {
    const input   = document.getElementById('stock_code_search');
    const spinner = document.getElementById('stock-search-spinner');
    const form    = document.getElementById('stock-code-form');
    if (!input || !form) return;
    let timer;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        spinner && spinner.classList.remove('hidden');
        timer = setTimeout(function () { form.submit(); }, 400);
    });
})();
</script>
@endpush

@if($items->isNotEmpty())
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const top10 = {!! $items->take(10)->map(fn($i) => ['code' => $i->stock_code, 'count' => $i->usage_count])->values()->toJson() !!};
const ctx = document.getElementById('stockCodeChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: top10.map(r => r.code),
        datasets: [{
            label: 'Kullanım Sayısı',
            data: top10.map(r => r.count),
            backgroundColor: 'rgba(37,99,235,0.75)',
            borderRadius: 6,
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
@endif
