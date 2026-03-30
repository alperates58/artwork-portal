@extends('layouts.app')
@section('title', 'Aktivite Akışı')
@section('page-title', 'Aktivite Akışı')
@section('page-subtitle', 'Sipariş, artwork ve not olaylarını tarih ve tür filtresiyle tek ekranda takip edin.')

@section('content')

{{-- Filters --}}
<div class="card p-5 mb-6">
    <form method="GET" action="{{ route('admin.reports.timeline') }}" class="flex flex-wrap items-end gap-4">

        <div class="flex flex-col gap-1 min-w-[180px]">
            <label class="label">Tedarikçi</label>
            <select name="supplier_id" class="input">
                <option value="">Tüm tedarikçiler</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" @selected($selectedSupplier == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label class="label">Başlangıç</label>
            <input type="date" name="date_from" class="input" value="{{ $dateFrom }}">
        </div>

        <div class="flex flex-col gap-1">
            <label class="label">Bitiş</label>
            <input type="date" name="date_to" class="input" value="{{ $dateTo }}">
        </div>

        <div class="flex flex-col gap-1">
            <label class="label">Olay tipi</label>
            <div class="flex gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 h-10 items-center">
                <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer">
                    <input type="checkbox" name="types[]" value="order" @checked(in_array('order', $types)) class="rounded">
                    <span class="text-violet-600 font-medium">Sipariş</span>
                </label>
                <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer">
                    <input type="checkbox" name="types[]" value="artwork" @checked(in_array('artwork', $types)) class="rounded">
                    <span class="text-blue-600 font-medium">Artwork</span>
                </label>
                <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer">
                    <input type="checkbox" name="types[]" value="note" @checked(in_array('note', $types)) class="rounded">
                    <span class="text-amber-600 font-medium">Not</span>
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary h-10">Filtrele</button>
        <a href="{{ route('admin.reports.timeline') }}" class="btn btn-secondary h-10">Sıfırla</a>
    </form>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
    <div class="card p-5">
        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Toplam Olay</p>
        <p class="mt-1 text-3xl font-bold text-slate-800">{{ $stats['total'] }}</p>
    </div>
    <div class="card p-5">
        <p class="text-xs font-medium text-violet-500 uppercase tracking-wide">Sipariş</p>
        <p class="mt-1 text-3xl font-bold text-violet-700">{{ $stats['order'] }}</p>
    </div>
    <div class="card p-5">
        <p class="text-xs font-medium text-blue-500 uppercase tracking-wide">Artwork Yükleme</p>
        <p class="mt-1 text-3xl font-bold text-blue-700">{{ $stats['artwork'] }}</p>
    </div>
    <div class="card p-5">
        <p class="text-xs font-medium text-amber-500 uppercase tracking-wide">Not</p>
        <p class="mt-1 text-3xl font-bold text-amber-700">{{ $stats['note'] }}</p>
    </div>
</div>

{{-- Chart --}}
@if(count($chartDays) > 0 && $stats['total'] > 0)
<div class="card p-5 mb-6">
    <h3 class="text-sm font-semibold text-slate-700 mb-4">Günlük Aktivite Dağılımı</h3>
    @php
        $maxVal = max(1, collect($chartDays)->map(fn($d) => $d['order'] + $d['artwork'] + $d['note'])->max());
        $showEvery = count($chartDays) > 14 ? (int) ceil(count($chartDays) / 14) : 1;
    @endphp
    <div class="flex items-end gap-px overflow-x-auto pb-2" style="min-height: 100px;">
        @foreach($chartDays as $i => $day)
            @php
                $total = $day['order'] + $day['artwork'] + $day['note'];
                $pct   = $maxVal > 0 ? round($total / $maxVal * 80) : 0;
            @endphp
            <div class="flex flex-col items-center gap-1 flex-shrink-0" style="min-width: {{ count($chartDays) > 30 ? '14px' : '22px' }}">
                <div class="w-full rounded-t transition-all"
                     style="height: {{ max(2, $pct) }}px; background: {{ $total === 0 ? '#e2e8f0' : 'linear-gradient(to top, #7c3aed, #a78bfa)' }}"
                     title="{{ $day['label'] }}: {{ $total }} olay ({{ $day['order'] }} sipariş, {{ $day['artwork'] }} artwork, {{ $day['note'] }} not)">
                </div>
                @if($i % $showEvery === 0)
                    <span class="text-[9px] text-slate-400 rotate-0">{{ $day['label'] }}</span>
                @endif
            </div>
        @endforeach
    </div>
    <div class="mt-3 flex items-center gap-4 text-xs text-slate-400">
        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-violet-500 inline-block"></span>Sipariş</span>
        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-blue-500 inline-block"></span>Artwork</span>
        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-400 inline-block"></span>Not</span>
    </div>
</div>
@endif

{{-- Timeline --}}
<div class="card">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h3 class="text-sm font-semibold text-slate-800">
            Olaylar
            @if($stats['total'] > 0)
                <span class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $stats['total'] }}</span>
            @endif
        </h3>
    </div>

    @if($timeline->isEmpty())
        <div class="px-5 py-16 text-center">
            <svg class="mx-auto h-10 w-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="mt-3 text-sm text-slate-400">Seçilen tarih aralığında aktivite bulunamadı.</p>
        </div>
    @else
        <div class="px-5 py-5">
            <ol class="relative border-l border-slate-200 ml-3 space-y-0">
                @foreach($timeline as $event)
                <li class="mb-6 ml-6">
                    <span class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white
                        @if($event['color'] === 'violet') bg-violet-100 text-violet-600
                        @elseif($event['color'] === 'blue') bg-blue-100 text-blue-600
                        @elseif($event['color'] === 'amber') bg-amber-100 text-amber-600
                        @else bg-slate-100 text-slate-500 @endif
                    ">
                        @if($event['icon'] === 'plus')
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        @elseif($event['icon'] === 'upload')
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        @elseif($event['icon'] === 'note')
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        @endif
                    </span>
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800">
                                @if(!empty($event['link']))
                                    <a href="{{ $event['link'] }}" class="hover:text-brand-600 hover:underline">{{ $event['title'] }}</a>
                                @else
                                    {{ $event['title'] }}
                                @endif
                            </p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $event['sub'] }}</p>
                            @if(!empty($event['body']))
                                <p class="mt-1 text-xs text-slate-600 bg-slate-50 rounded-lg px-3 py-2">{{ $event['body'] }}</p>
                            @endif
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="text-xs text-slate-500">{{ $event['user'] }}</p>
                            <time class="text-[11px] text-slate-400">{{ $event['at']->format('d.m.Y H:i') }}</time>
                            <p class="text-[10px] text-slate-300">{{ $event['at']->diffForHumans() }}</p>
                        </div>
                    </div>
                </li>
                @endforeach
            </ol>
        </div>
    @endif
</div>

@endsection
