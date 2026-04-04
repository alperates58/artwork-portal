@extends('layouts.app')
@section('title', 'İzlenebilirlik Raporu')
@section('page-title', 'İzlenebilirlik Raporu')
@section('page-subtitle', 'Stok kodu veya stok adıyla son basım izini, kullanılan revizyonu ve iş akışının gün bazlı ilerleyişini izleyin.')

@section('header-actions')
    <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary">← Raporlar</a>
@endsection

@php
    $formatDays = static fn (?float $value): string => $value === null
        ? '—'
        : number_format($value, 1, ',', '.') . ' gün';

    $printedRatio = $summary['matches'] > 0
        ? round(($summary['printed'] / $summary['matches']) * 100)
        : 0;
@endphp

@section('content')
<div class="space-y-6">
    <form method="GET" action="{{ route('admin.reports.traceability') }}" id="traceability-form" class="card p-5">
        <div class="flex flex-wrap items-end gap-4">
            <div class="min-w-[280px] flex-1">
                <label class="label" for="traceability-query">Stok kodu veya stok adı</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                    <input
                        id="traceability-query"
                        name="query"
                        value="{{ $search }}"
                        class="input pl-9 pr-10 font-mono"
                        placeholder="Örn. STK-1001 veya ürün adı"
                        autocomplete="off"
                    >
                    <span id="traceability-spinner" class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2">
                        <svg class="h-4 w-4 animate-spin text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-2 text-xs text-slate-500">Kod yapıştırdığınız anda sonuç otomatik yenilenir.</p>
            </div>

            <div class="min-w-[220px]">
                <label class="label" for="traceability-supplier">Tedarikçi</label>
                <select id="traceability-supplier" name="supplier_id" class="input">
                    <option value="">Tüm tedarikçiler</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected($selectedSupplierId === $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Ara</button>
            @if($search !== '' || $selectedSupplierId)
                <a href="{{ route('admin.reports.traceability') }}" class="btn btn-secondary">Temizle</a>
            @endif
        </div>
        <p class="mt-3 text-xs text-slate-500">
            Sevk görmüş satırlar basım / sevk sinyali olarak değerlendirilir. Revizyon bilgisi son onaylanan veya aktif revizyon üzerinden izlenir.
        </p>
    </form>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div class="card p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Eşleşen İş</p>
            <p class="mt-2 text-3xl font-semibold text-slate-900">{{ number_format($summary['matches']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Aranan stokla ilişkilenen sipariş satırı</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-emerald-500">Basım / Sevk Gören</p>
            <p class="mt-2 text-3xl font-semibold text-emerald-700">{{ number_format($summary['printed']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Toplamın %{{ $printedRatio }} kadarında sevk izi var</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-brand-500">Ort. Sipariş → Basım</p>
            <p class="mt-2 text-3xl font-semibold text-brand-700">{{ $summary['avg_print_days'] !== null ? number_format($summary['avg_print_days'], 1, ',', '.') : '—' }}</p>
            <p class="mt-1 text-xs text-slate-500">Basım / sevk izi olan işler için gün ortalaması</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-amber-500">Tedarikçi Aksiyonu Bekleyen</p>
            <p class="mt-2 text-3xl font-semibold text-amber-700">{{ number_format($summary['awaiting_supplier_action']) }}</p>
            <p class="mt-1 text-xs text-slate-500">İlk revizyon yüklenmiş ama tedarikçi hareketi yok</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-red-500">Açık Revizyon Talebi</p>
            <p class="mt-2 text-3xl font-semibold text-red-700">{{ number_format($summary['revision_requested']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Şu an revizyon bekleyen satırlar</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Son Hareket</p>
            @if($summary['last_activity_at'])
                <p class="mt-2 text-lg font-semibold text-slate-900">{{ $summary['last_activity_at']->format('d.m.Y H:i') }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $summary['last_activity_at']->diffForHumans() }}</p>
            @else
                <p class="mt-2 text-lg font-semibold text-slate-500">Henüz yok</p>
                <p class="mt-1 text-xs text-slate-400">Arama yaptığınızda burada son aktivite görünür</p>
            @endif
        </div>
    </div>

    @if($latestPrinted)
        <div class="card overflow-hidden border-emerald-200">
            <div class="bg-gradient-to-r from-emerald-600 via-emerald-500 to-brand-600 px-5 py-5 text-white">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-100">Son Basım İzi</p>
                <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('order-lines.show', $latestPrinted->line_id) }}" class="text-2xl font-semibold hover:text-emerald-50 hover:underline">
                                {{ $latestPrinted->product_code }}
                            </a>
                            <span class="rounded-full bg-white/15 px-2.5 py-1 text-xs font-semibold text-white/90">
                                Revizyon #{{ $latestPrinted->print_revision_no ?? '—' }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-emerald-50">{{ $latestPrinted->stock_name }}</p>
                        <p class="mt-1 text-sm text-white/90">{{ $latestPrinted->supplier_name }} · {{ $latestPrinted->order_no }} · Satır {{ $latestPrinted->line_no }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-white">{{ $latestPrinted->shipment_synced_at?->format('d.m.Y H:i') ?? 'Tarih yok' }}</p>
                        <p class="mt-1 text-xs text-emerald-50">{{ $latestPrinted->print_reference }}</p>
                    </div>
                </div>
            </div>
            <div class="grid gap-4 px-5 py-4 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Sipariş → İlk Revizyon</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $formatDays($latestPrinted->metrics['order_to_first_upload_days']) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">İlk Revizyon → İlk Tedarikçi Aksiyonu</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $formatDays($latestPrinted->metrics['first_upload_to_first_supplier_action_days']) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Son Revizyon → Onay</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $formatDays($latestPrinted->metrics['last_upload_to_approval_days']) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Sipariş → Basım / Sevk</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $formatDays($latestPrinted->metrics['order_to_print_days']) }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($items->isEmpty())
        <div class="card px-5 py-16 text-center">
            <svg class="mx-auto h-10 w-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="mt-3 text-sm text-slate-500">Seçilen kriterler için izlenebilirlik verisi bulunamadı.</p>
        </div>
    @endif

    @foreach($items as $item)
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('order-lines.show', $item->line_id) }}" class="text-lg font-semibold text-slate-900 hover:text-brand-700 hover:underline">
                                {{ $item->product_code }}
                            </a>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $item->stock_name }}</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $item->artwork_status_label }}</span>
                            @if($item->has_print_signal)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Basım / sevk sinyali</span>
                            @endif
                        </div>
                        <p class="mt-2 text-sm text-slate-500">{{ $item->supplier_name }} · {{ $item->order_no }} · Satır {{ $item->line_no }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Son Hareket</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $item->last_activity_at?->format('d.m.Y H:i') ?? '—' }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $item->print_reference }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 border-b border-slate-100 bg-slate-50/80 px-5 py-4 sm:grid-cols-2 xl:grid-cols-5">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Aktif Revizyon</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">Revizyon #{{ $item->active_revision_no ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Basım Revizyonu</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">Revizyon #{{ $item->print_revision_no ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Sevk Durumu</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $item->shipment_status_label ?? 'Henüz yok' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Sevk Miktarı</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $item->shipped_quantity ? number_format($item->shipped_quantity, 0, ',', '.') . ' adet' : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Basım / Sevk Tarihi</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $item->shipment_synced_at?->format('d.m.Y H:i') ?? '—' }}</p>
                </div>
            </div>

            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">Aşama Süreleri</h3>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="inline-flex items-center rounded-full bg-brand-50 px-3 py-1.5 text-xs font-medium text-brand-700">
                        Sipariş → ilk revizyon: {{ $formatDays($item->metrics['order_to_first_upload_days']) }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700">
                        İlk revizyon → ilk tedarikçi aksiyonu: {{ $formatDays($item->metrics['first_upload_to_first_supplier_action_days']) }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700">
                        Son revizyon → onay: {{ $formatDays($item->metrics['last_upload_to_approval_days']) }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-violet-50 px-3 py-1.5 text-xs font-medium text-violet-700">
                        Onay → basım / sevk: {{ $formatDays($item->metrics['approval_to_print_days']) }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700">
                        Sipariş → basım / sevk: {{ $formatDays($item->metrics['order_to_print_days']) }}
                    </span>
                </div>
            </div>

            <div class="px-5 py-5">
                <h3 class="text-sm font-semibold text-slate-900">Aktivite Zaman Çizelgesi</h3>

                @if($item->timeline->isEmpty())
                    <p class="mt-3 text-sm text-slate-400">Bu iş için zaman çizelgesi oluşturacak aktivite kaydı bulunamadı.</p>
                @else
                    <ol class="relative mt-4 space-y-0">
                        @foreach($item->timeline as $event)
                            <li class="relative flex gap-4">
                                @if(! $loop->last)
                                    <div class="absolute left-[15px] top-8 bottom-0 w-px bg-slate-200"></div>
                                @endif

                                <div class="relative z-10 mt-1 flex h-8 w-8 flex-none items-center justify-center rounded-full
                                    @if($event['color'] === 'violet') bg-violet-100 text-violet-600
                                    @elseif($event['color'] === 'blue') bg-blue-100 text-blue-600
                                    @elseif($event['color'] === 'amber') bg-amber-100 text-amber-600
                                    @elseif($event['color'] === 'emerald') bg-emerald-100 text-emerald-600
                                    @elseif($event['color'] === 'red') bg-red-100 text-red-600
                                    @else bg-slate-100 text-slate-500
                                    @endif">
                                    @if($event['icon'] === 'upload')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    @elseif($event['icon'] === 'eye')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M1.5 12s3.75-7.5 10.5-7.5S22.5 12 22.5 12s-3.75 7.5-10.5 7.5S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                    @elseif($event['icon'] === 'download')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-8-2l4-4m0 0l-4-4m4 4H8"/></svg>
                                    @elseif($event['icon'] === 'mail')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    @elseif($event['icon'] === 'check')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    @elseif($event['icon'] === 'x')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    @elseif($event['icon'] === 'truck')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17H6a2 2 0 01-2-2V7a2 2 0 012-2h8a2 2 0 012 2v2m0 8h1m-1 0a2 2 0 104 0m-4 0H9m0 0a2 2 0 11-4 0m4 0V9m0 8H5m11-8h2l3 3v5h-1"/></svg>
                                    @else
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/></svg>
                                    @endif
                                </div>

                                <div class="flex-1 pb-6">
                                    <div class="flex flex-wrap items-baseline gap-x-2">
                                        <p class="text-sm font-semibold text-slate-800">{{ $event['title'] }}</p>
                                        <p class="text-xs text-slate-400">{{ $event['at']->format('d.m.Y H:i') }}</p>
                                    </div>
                                    @if(! empty($event['sub']))
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $event['sub'] }}</p>
                                    @endif
                                    @if(! empty($event['body']))
                                        <p class="mt-1 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">{{ $event['body'] }}</p>
                                    @endif

                                    @if(! $loop->last && ($event['days_gap'] ?? 0) >= 0.1)
                                        <div class="mt-3 flex items-center gap-2">
                                            <div class="h-px flex-1 bg-slate-100"></div>
                                            <span class="text-[10px] font-medium text-slate-400">
                                                @if($event['days_gap'] >= 1)
                                                    {{ round($event['days_gap']) }} gün
                                                @else
                                                    {{ round($event['days_gap'] * 24) }} saat
                                                @endif
                                            </span>
                                            <div class="h-px flex-1 bg-slate-100"></div>
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('traceability-form');
    const queryInput = document.getElementById('traceability-query');
    const supplierSelect = document.getElementById('traceability-supplier');
    const spinner = document.getElementById('traceability-spinner');

    if (!form || !queryInput || !supplierSelect) {
        return;
    }

    let timer;
    let lastSubmitted = queryInput.value + '|' + supplierSelect.value;

    const submitSoon = (delay = 250) => {
        clearTimeout(timer);
        spinner && spinner.classList.remove('hidden');
        timer = setTimeout(() => {
            const current = queryInput.value + '|' + supplierSelect.value;

            if (current === lastSubmitted) {
                spinner && spinner.classList.add('hidden');
                return;
            }

            lastSubmitted = current;
            form.submit();
        }, delay);
    };

    queryInput.addEventListener('input', () => submitSoon(250));
    queryInput.addEventListener('paste', () => submitSoon(60));
    supplierSelect.addEventListener('change', () => submitSoon(0));
})();
</script>
@endpush
