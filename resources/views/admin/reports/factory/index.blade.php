@extends('layouts.app')
@section('title', 'Rapor Fabrikası')
@section('page-title', 'Rapor Fabrikası')
@section('page-subtitle', 'Özel raporlarınızı oluşturun, kaydedin ve yönetin.')

@section('header-actions')
    <a href="{{ route('admin.reports.factory.create') }}" class="btn btn-primary">
        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Rapor
    </a>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Hızlı başlangıç --}}
    @if($reports->isEmpty())
        <div class="card p-10 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50">
                <svg class="h-8 w-8 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-slate-900 mb-2">Henüz rapor oluşturulmadı</h3>
            <p class="text-sm text-slate-500 mb-5 max-w-sm mx-auto">Boyutlar ve metrikler sürükleyerek birkaç saniyede özel raporlar oluşturun.</p>
            <a href="{{ route('admin.reports.factory.create') }}" class="btn btn-primary">İlk Raporu Oluştur</a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($reports as $report)
                <a href="{{ route('admin.reports.factory.show', $report) }}"
                   class="card p-5 hover:shadow-md transition-shadow group block">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 group-hover:bg-brand-100 transition-colors">
                            @php
                                $icons = ['bar' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'line' => 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z', 'pie' => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z', 'doughnut' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'];
                            @endphp
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons[$report->chart_type] ?? $icons['bar'] }}"/>
                            </svg>
                        </div>
                        <span class="text-xs text-slate-400 mt-0.5">{{ $report->updated_at->diffForHumans() }}</span>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900 mb-2 group-hover:text-brand-700 transition-colors">{{ $report->name }}</h3>
                    <div class="flex flex-wrap gap-1 mb-2">
                        @foreach($report->dimensions as $d)
                            <span class="inline-block rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-medium text-blue-700">{{ \App\Models\CustomReport::dimensionLabels()[$d] ?? $d }}</span>
                        @endforeach
                        @foreach($report->metrics as $m)
                            <span class="inline-block rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700">{{ \App\Models\CustomReport::metricLabels()[$m] ?? $m }}</span>
                        @endforeach
                    </div>
                    <p class="text-xs text-slate-400">{{ $report->createdBy->name }}</p>
                </a>
            @endforeach

            {{-- Yeni rapor kartı --}}
            <a href="{{ route('admin.reports.factory.create') }}"
               class="card flex flex-col items-center justify-center p-8 border-2 border-dashed border-slate-200 hover:border-brand-400 hover:bg-brand-50/30 transition-colors text-slate-400 hover:text-brand-600 group">
                <svg class="h-8 w-8 mb-2 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="text-sm font-medium">Yeni Rapor</span>
            </a>
        </div>
    @endif

</div>
@endsection
