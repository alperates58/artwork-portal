@extends('layouts.app')
@section('title', $report ? 'Raporu Düzenle' : 'Yeni Rapor')
@section('page-title', $report ? 'Raporu Düzenle' : 'Rapor Fabrikası')
@section('page-subtitle', 'Alanları sürükleyerek özel raporlar oluşturun.')

@section('header-actions')
    <a href="{{ route('admin.reports.factory.index') }}" class="btn btn-secondary">← Raporlarım</a>
@endsection

@section('content')
<div x-data="reportBuilder()" class="space-y-5">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ── Sol: Kullanılabilir Alanlar ── --}}
        <div class="space-y-4">

            {{-- Boyutlar --}}
            <div class="card p-4">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">Boyutlar (X Ekseni)</h3>
                <div class="space-y-1.5">
                    @foreach(['supplier' => 'Tedarikçi', 'month' => 'Ay', 'year' => 'Yıl', 'quarter' => 'Çeyrek', 'order_status' => 'Sipariş Durumu', 'artwork_status' => 'Artwork Durumu'] as $key => $label)
                    <div class="field-chip dim-chip"
                         draggable="true"
                         data-key="{{ $key }}"
                         data-type="dimension"
                         @dragstart="startDrag($event, '{{ $key }}', 'dimension')"
                         :class="selectedDimensions.includes('{{ $key }}') ? 'opacity-40 cursor-not-allowed' : 'cursor-grab hover:shadow-md hover:-translate-y-0.5'"
                         class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 transition-all select-none">
                        <svg class="h-4 w-4 text-blue-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                        <span class="flex-1">{{ $label }}</span>
                        <svg class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                        </svg>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Metrikler --}}
            <div class="card p-4">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">Metrikler (Y Ekseni)</h3>
                <div class="space-y-1.5">
                    @foreach(['order_count' => 'Sipariş Sayısı', 'line_count' => 'Satır Sayısı', 'pending_artwork' => 'Bekleyen Artwork', 'uploaded_artwork' => 'Yüklenen Artwork', 'revision_count' => 'Revizyon Sayısı', 'avg_days_to_upload' => 'Ort. Yükleme (Gün)'] as $key => $label)
                    <div class="field-chip metric-chip"
                         draggable="true"
                         data-key="{{ $key }}"
                         data-type="metric"
                         @dragstart="startDrag($event, '{{ $key }}', 'metric')"
                         :class="selectedMetrics.includes('{{ $key }}') ? 'opacity-40 cursor-not-allowed' : 'cursor-grab hover:shadow-md hover:-translate-y-0.5'"
                         class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 transition-all select-none">
                        <svg class="h-4 w-4 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                        </svg>
                        <span class="flex-1">{{ $label }}</span>
                        <svg class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                        </svg>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- ── Sağ: Yapılandırma ── --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Drop Zones --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Boyutlar drop zone --}}
                <div class="card p-4">
                    <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-2">X Ekseni — Boyutlar</h3>
                    <div @dragover.prevent="dragOver($event, 'dimension')"
                         @dragleave="dragLeave($event)"
                         @drop.prevent="onDrop($event, 'dimension')"
                         :class="dropTarget === 'dimension' ? 'border-blue-400 bg-blue-50/40' : 'border-slate-200'"
                         class="min-h-[80px] rounded-xl border-2 border-dashed p-2 transition-colors">
                        <template x-if="selectedDimensions.length === 0">
                            <p class="py-4 text-center text-xs text-slate-400">Boyut sürükleyin</p>
                        </template>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="key in selectedDimensions" :key="key">
                                <span class="inline-flex items-center gap-1 rounded-lg bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                    <span x-text="dimLabels[key]"></span>
                                    <button type="button" @click="removeDimension(key)" class="ml-0.5 rounded text-blue-400 hover:text-blue-700">✕</button>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Metrikler drop zone --}}
                <div class="card p-4">
                    <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-2">Y Ekseni — Metrikler</h3>
                    <div @dragover.prevent="dragOver($event, 'metric')"
                         @dragleave="dragLeave($event)"
                         @drop.prevent="onDrop($event, 'metric')"
                         :class="dropTarget === 'metric' ? 'border-emerald-400 bg-emerald-50/40' : 'border-slate-200'"
                         class="min-h-[80px] rounded-xl border-2 border-dashed p-2 transition-colors">
                        <template x-if="selectedMetrics.length === 0">
                            <p class="py-4 text-center text-xs text-slate-400">Metrik sürükleyin</p>
                        </template>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="key in selectedMetrics" :key="key">
                                <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    <span x-text="metricLabels[key]"></span>
                                    <button type="button" @click="removeMetric(key)" class="ml-0.5 rounded text-emerald-400 hover:text-emerald-700">✕</button>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filtreler + Grafik Tipi --}}
            <div class="card p-4 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Tedarikçi filtresi --}}
                    <div>
                        <label class="label">Tedarikçi Filtresi</label>
                        <select x-model="filters.supplier_id" class="input">
                            <option value="">Tüm tedarikçiler</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Sipariş durumu --}}
                    <div>
                        <label class="label">Sipariş Durumu</label>
                        <select x-model="filters.order_status" class="input">
                            <option value="">Tümü</option>
                            <option value="active">Aktif</option>
                            <option value="draft">Taslak</option>
                            <option value="completed">Tamamlandı</option>
                            <option value="cancelled">İptal</option>
                        </select>
                    </div>
                    {{-- Tarih aralığı --}}
                    <div>
                        <label class="label">Başlangıç Tarihi</label>
                        <input type="date" x-model="filters.date_from" class="input">
                    </div>
                    <div>
                        <label class="label">Bitiş Tarihi</label>
                        <input type="date" x-model="filters.date_to" class="input">
                    </div>
                </div>

                {{-- Grafik tipi --}}
                <div>
                    <label class="label">Grafik Tipi</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['bar' => ['Bar', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'], 'line' => ['Çizgi', 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z'], 'pie' => ['Pasta', 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z'], 'doughnut' => ['Halka', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z']] as $type => [$label, $icon])
                        <button type="button"
                                @click="chartType = '{{ $type }}'"
                                :class="chartType === '{{ $type }}' ? 'bg-brand-600 text-white border-brand-600 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:border-brand-400'"
                                class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                            </svg>
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Önizle butonu --}}
            <div class="flex flex-wrap items-center gap-3">
                <button type="button"
                        @click="runPreview()"
                        :disabled="loading || selectedDimensions.length === 0 || selectedMetrics.length === 0"
                        class="btn btn-primary">
                    <template x-if="!loading">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Önizle
                        </span>
                    </template>
                    <template x-if="loading">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                            Çalışıyor…
                        </span>
                    </template>
                </button>
                <p x-show="selectedDimensions.length === 0 || selectedMetrics.length === 0" class="text-xs text-slate-400">
                    En az bir boyut ve bir metrik seçin
                </p>
                <p x-show="hasResults" class="text-xs text-slate-500" x-text="rowCount + ' kayıt bulundu'"></p>
            </div>

            {{-- Önizleme: Grafik --}}
            <div x-show="hasResults" x-cloak class="card p-5">
                <div style="height:320px">
                    <canvas id="builder-chart"></canvas>
                </div>
            </div>

            {{-- Önizleme: Tablo --}}
            <div x-show="hasResults" x-cloak class="card overflow-x-auto">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h3 class="text-sm font-semibold text-slate-900">Veri Tablosu</h3>
                </div>
                <table class="w-full text-sm" style="min-width:400px">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left">
                            <template x-for="col in tableColumns" :key="col">
                                <th class="px-4 py-3 font-medium text-slate-600 whitespace-nowrap" x-text="col"></th>
                            </template>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(row, i) in tableRows" :key="i">
                            <tr class="hover:bg-slate-50">
                                <template x-for="col in tableColumnKeys" :key="col">
                                    <td class="px-4 py-3 text-slate-700 whitespace-nowrap" x-text="row[col]"></td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Raporu Kaydet --}}
            <div x-show="hasResults" x-cloak class="card p-5">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Raporu Kaydet</h3>
                <form id="save-form" method="POST" action="{{ $report ? route('admin.reports.factory.update', $report) : route('admin.reports.factory.store') }}" @submit.prevent="submitSave()">
                    @csrf
                    @if($report)
                        @method('PATCH')
                    @endif
                    <input type="hidden" name="dimensions" id="f-dimensions">
                    <input type="hidden" name="metrics" id="f-metrics">
                    <input type="hidden" name="chart_type" id="f-chart_type">
                    <input type="hidden" name="filters" id="f-filters">
                    <div class="flex flex-wrap gap-3">
                        <input type="text" name="name" x-model="reportName"
                               placeholder="Rapor adı girin…"
                               value="{{ $report?->name }}"
                               class="input flex-1 min-w-[200px]">
                        <button type="submit" class="btn btn-primary">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            {{ $report ? 'Güncelle' : 'Kaydet' }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function reportBuilder() {
    return {
        dimLabels: {
            supplier: 'Tedarikçi', month: 'Ay', year: 'Yıl', quarter: 'Çeyrek',
            order_status: 'Sipariş Durumu', artwork_status: 'Artwork Durumu',
        },
        metricLabels: {
            order_count: 'Sipariş Sayısı', line_count: 'Satır Sayısı',
            pending_artwork: 'Bekleyen Artwork', uploaded_artwork: 'Yüklenen Artwork',
            revision_count: 'Revizyon Sayısı', avg_days_to_upload: 'Ort. Yükleme (Gün)',
        },

        selectedDimensions: {!! json_encode($report?->dimensions ?? []) !!},
        selectedMetrics:    {!! json_encode($report?->metrics ?? []) !!},
        chartType:          '{{ $report?->chart_type ?? 'bar' }}',
        filters: {
            supplier_id:  '{{ $report?->filters['supplier_id'] ?? '' }}',
            order_status: '{{ $report?->filters['order_status'] ?? '' }}',
            date_from:    '{{ $report?->filters['date_from'] ?? '' }}',
            date_to:      '{{ $report?->filters['date_to'] ?? '' }}',
        },

        dragKey: null,
        dragType: null,
        dropTarget: null,
        loading: false,
        hasResults: false,
        rowCount: 0,
        tableColumns: [],
        tableColumnKeys: [],
        tableRows: [],
        chartInstance: null,
        reportName: '{{ $report?->name ?? '' }}',

        startDrag(event, key, type) {
            if (type === 'dimension' && this.selectedDimensions.includes(key)) { event.preventDefault(); return; }
            if (type === 'metric' && this.selectedMetrics.includes(key)) { event.preventDefault(); return; }
            this.dragKey  = key;
            this.dragType = type;
            event.dataTransfer.effectAllowed = 'copy';
        },

        dragOver(event, zone) {
            if (this.dragType === zone || (zone === 'dimension' && this.dragType === 'dimension') || (zone === 'metric' && this.dragType === 'metric')) {
                this.dropTarget = zone;
            }
        },

        dragLeave(event) {
            this.dropTarget = null;
        },

        onDrop(event, zone) {
            this.dropTarget = null;
            if (!this.dragKey) return;
            if (zone === 'dimension' && this.dragType === 'dimension' && !this.selectedDimensions.includes(this.dragKey)) {
                this.selectedDimensions.push(this.dragKey);
            }
            if (zone === 'metric' && this.dragType === 'metric' && !this.selectedMetrics.includes(this.dragKey)) {
                this.selectedMetrics.push(this.dragKey);
            }
            this.dragKey  = null;
            this.dragType = null;
        },

        removeDimension(key) {
            this.selectedDimensions = this.selectedDimensions.filter(d => d !== key);
            this.hasResults = false;
        },

        removeMetric(key) {
            this.selectedMetrics = this.selectedMetrics.filter(m => m !== key);
            this.hasResults = false;
        },

        async runPreview() {
            if (!this.selectedDimensions.length || !this.selectedMetrics.length) return;
            this.loading = true;
            try {
                const res = await fetch('{{ route('admin.reports.factory.preview') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        dimensions: this.selectedDimensions,
                        metrics:    this.selectedMetrics,
                        chart_type: this.chartType,
                        filters:    this.filters,
                    }),
                });
                const data = await res.json();
                if (!res.ok) { alert(data.error || 'Hata'); return; }
                this.renderChart(data);
                this.rowCount = data.row_count;
                // Build table columns: first dim labels, then metric labels
                this.tableColumns    = data.columns;
                const dimKeys        = this.selectedDimensions.map(d => 'label_' + d);
                this.tableColumnKeys = ['label', ...this.selectedMetrics];
                // Rebuild table rows with combined label + per-metric keys
                this.tableRows = data.table;
                this.hasResults = true;
            } catch (e) {
                alert('Önizleme sırasında bir hata oluştu.');
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        renderChart(data) {
            const ctx = document.getElementById('builder-chart');
            if (!ctx) return;
            if (this.chartInstance) this.chartInstance.destroy();
            const isPie = ['pie', 'doughnut'].includes(this.chartType);
            this.chartInstance = new Chart(ctx.getContext('2d'), {
                type: this.chartType,
                data: {
                    labels:   data.labels,
                    datasets: isPie ? [data.datasets[0]] : data.datasets,
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
        },

        submitSave() {
            if (!this.reportName.trim()) { alert('Rapor adı girin.'); return; }
            document.getElementById('f-dimensions').value  = JSON.stringify(this.selectedDimensions);
            document.getElementById('f-metrics').value     = JSON.stringify(this.selectedMetrics);
            document.getElementById('f-chart_type').value  = this.chartType;
            document.getElementById('f-filters').value     = JSON.stringify(this.filters);
            document.getElementById('save-form').submit();
        },
    };
}
</script>
@endpush
