@extends('layouts.app')
@section('title', $report ? 'Raporu Düzenle' : 'Yeni Rapor')
@section('page-title', $report ? 'Raporu Düzenle' : 'Rapor Fabrikası')
@section('page-subtitle', 'Alanlara tıklayarak veya sürükleyerek özel raporlar oluşturun.')

@section('header-actions')
    <a href="{{ route('admin.reports.factory.index') }}" class="btn btn-secondary">← Raporlarım</a>
@endsection

@section('content')
<div x-data="reportBuilder()" class="space-y-4">

    {{-- ─── MOBILE: Alan Seçici (accordion) ─────────────────────────────── --}}
    <div class="lg:hidden">
        <button type="button"
                @click="mobileFieldsOpen = !mobileFieldsOpen"
                class="w-full card p-4 flex items-center justify-between text-sm font-semibold text-slate-700">
            <span class="flex items-center gap-2">
                <svg class="h-4 w-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Alan Seçici
                <span x-show="selectedDimensions.length + selectedMetrics.length > 0"
                      class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-[10px] font-bold text-white"
                      x-text="selectedDimensions.length + selectedMetrics.length"></span>
            </span>
            <svg class="h-4 w-4 text-slate-400 transition-transform" :class="mobileFieldsOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="mobileFieldsOpen" class="mt-2 space-y-3">
            {{-- Boyutlar --}}
            <div class="card p-4">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">Boyutlar (X Ekseni) — Tıkla ekle</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach(['supplier' => 'Tedarikçi', 'month' => 'Ay', 'year' => 'Yıl', 'quarter' => 'Çeyrek', 'order_status' => 'Sipariş Durumu', 'artwork_status' => 'Artwork Durumu'] as $key => $label)
                    <button type="button"
                            @click="toggleField('{{ $key }}', 'dimension')"
                            :class="selectedDimensions.includes('{{ $key }}')
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'bg-white text-slate-700 border-slate-200 hover:border-blue-400'"
                            class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-medium transition-all">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  :d="selectedDimensions.includes('{{ $key }}') ? 'M6 18L18 6M6 6l12 12' : 'M12 4v16m8-8H4'"/>
                        </svg>
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
            </div>
            {{-- Metrikler --}}
            <div class="card p-4">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">Metrikler (Y Ekseni) — Tıkla ekle</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach(['order_count' => 'Sipariş Sayısı', 'line_count' => 'Satır Sayısı', 'pending_artwork' => 'Bekleyen Artwork', 'uploaded_artwork' => 'Yüklenen Artwork', 'revision_count' => 'Revizyon Sayısı', 'avg_days_to_upload' => 'Ort. Yükleme (Gün)'] as $key => $label)
                    <button type="button"
                            @click="toggleField('{{ $key }}', 'metric')"
                            :class="selectedMetrics.includes('{{ $key }}')
                                ? 'bg-emerald-600 text-white border-emerald-600'
                                : 'bg-white text-slate-700 border-slate-200 hover:border-emerald-400'"
                            class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-medium transition-all">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  :d="selectedMetrics.includes('{{ $key }}') ? 'M6 18L18 6M6 6l12 12' : 'M12 4v16m8-8H4'"/>
                        </svg>
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ─── DESKTOP + MOBILE layout ──────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ── Sol: Alan Seçici (sadece desktop'ta görünür) ── --}}
        <div class="hidden lg:block space-y-4">
            {{-- Boyutlar --}}
            <div class="card p-4">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">Boyutlar (X Ekseni)</h3>
                <p class="text-[11px] text-slate-400 mb-2">Sürükle veya tıkla ekle</p>
                <div class="space-y-1.5">
                    @foreach(['supplier' => 'Tedarikçi', 'month' => 'Ay', 'year' => 'Yıl', 'quarter' => 'Çeyrek', 'order_status' => 'Sipariş Durumu', 'artwork_status' => 'Artwork Durumu'] as $key => $label)
                    <div class="field-chip"
                         draggable="true"
                         data-key="{{ $key }}"
                         data-type="dimension"
                         @dragstart="startDrag($event, '{{ $key }}', 'dimension')"
                         @click="toggleField('{{ $key }}', 'dimension')"
                         :class="selectedDimensions.includes('{{ $key }}')
                            ? 'opacity-40 cursor-not-allowed'
                            : 'cursor-grab hover:shadow-md hover:-translate-y-0.5 hover:border-blue-300'"
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
                <p class="text-[11px] text-slate-400 mb-2">Sürükle veya tıkla ekle</p>
                <div class="space-y-1.5">
                    @foreach(['order_count' => 'Sipariş Sayısı', 'line_count' => 'Satır Sayısı', 'pending_artwork' => 'Bekleyen Artwork', 'uploaded_artwork' => 'Yüklenen Artwork', 'revision_count' => 'Revizyon Sayısı', 'avg_days_to_upload' => 'Ort. Yükleme (Gün)'] as $key => $label)
                    <div class="field-chip"
                         draggable="true"
                         data-key="{{ $key }}"
                         data-type="metric"
                         @dragstart="startDrag($event, '{{ $key }}', 'metric')"
                         @click="toggleField('{{ $key }}', 'metric')"
                         :class="selectedMetrics.includes('{{ $key }}')
                            ? 'opacity-40 cursor-not-allowed'
                            : 'cursor-grab hover:shadow-md hover:-translate-y-0.5 hover:border-emerald-300'"
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

            {{-- Seçili Alanlar --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Boyutlar drop zone --}}
                <div class="card p-4">
                    <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-2">X Ekseni — Boyutlar</h3>
                    <div @dragover.prevent="dragOver($event, 'dimension')"
                         @dragleave="dragLeave($event)"
                         @drop.prevent="onDrop($event, 'dimension')"
                         :class="dropTarget === 'dimension' ? 'border-blue-400 bg-blue-50/40' : 'border-slate-200'"
                         class="min-h-[72px] rounded-xl border-2 border-dashed p-2 transition-colors">
                        <template x-if="selectedDimensions.length === 0">
                            <p class="py-3 text-center text-xs text-slate-400">Boyut seçin / sürükleyin</p>
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
                         class="min-h-[72px] rounded-xl border-2 border-dashed p-2 transition-colors">
                        <template x-if="selectedMetrics.length === 0">
                            <p class="py-3 text-center text-xs text-slate-400">Metrik seçin / sürükleyin</p>
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

            {{-- Grafik Tipi --}}
            <div class="card p-4">
                <label class="label mb-2">Grafik Tipi</label>
                <div class="flex flex-wrap gap-2">
                    @foreach(['bar' => ['Bar', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'], 'line' => ['Çizgi', 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z'], 'pie' => ['Pasta', 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z'], 'doughnut' => ['Halka', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z']] as $type => [$label, $icon])
                    <button type="button"
                            @click="chartType = '{{ $type }}'"
                            :class="chartType === '{{ $type }}' ? 'bg-brand-600 text-white border-brand-600 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:border-brand-400'"
                            class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-medium transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                        </svg>
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Filtreler --}}
            <div class="card p-4">
                <label class="label mb-3">Filtreler <span class="text-slate-400 font-normal">(isteğe bağlı)</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="label">Tedarikçi</label>
                        <select x-model="filters.supplier_id" class="input">
                            <option value="">Tüm tedarikçiler</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
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
                    <div>
                        <label class="label">Başlangıç Tarihi</label>
                        <input type="date" x-model="filters.date_from" class="input">
                    </div>
                    <div>
                        <label class="label">Bitiş Tarihi</label>
                        <input type="date" x-model="filters.date_to" class="input">
                    </div>
                </div>
            </div>

            {{-- Önizle butonu --}}
            <div class="flex flex-wrap items-center gap-3">
                <button type="button"
                        @click="runPreview()"
                        :disabled="loading || selectedDimensions.length === 0 || selectedMetrics.length === 0"
                        :class="(selectedDimensions.length === 0 || selectedMetrics.length === 0) ? 'opacity-50 cursor-not-allowed' : ''"
                        class="btn btn-primary">
                    <template x-if="!loading">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Önizle
                        </span>
                    </template>
                    <template x-if="loading">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            Çalışıyor…
                        </span>
                    </template>
                </button>
                <p x-show="selectedDimensions.length === 0 || selectedMetrics.length === 0"
                   class="text-xs text-slate-400">En az bir boyut ve bir metrik seçin</p>
                <p x-show="hasResults && !loading"
                   class="text-xs text-emerald-600 font-medium"
                   x-text="rowCount + ' kayıt bulundu'"></p>
                <p x-show="errorMsg" class="text-xs text-red-500" x-text="errorMsg"></p>
            </div>

            {{-- Önizleme: Grafik --}}
            <div x-show="hasResults" style="display:none" class="card p-5">
                <div style="height:320px; position:relative;">
                    <canvas id="builder-chart"></canvas>
                </div>
            </div>

            {{-- Önizleme: Tablo --}}
            <div x-show="hasResults" style="display:none" class="card overflow-x-auto">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Veri Tablosu</h3>
                    <span class="text-xs text-slate-400" x-text="rowCount + ' kayıt'"></span>
                </div>
                <table class="w-full text-sm" style="min-width:380px">
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
                                <template x-for="colKey in tableColumnKeys" :key="colKey">
                                    <td class="px-4 py-3 text-slate-700 whitespace-nowrap" x-text="row[colKey]"></td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Raporu Kaydet --}}
            <div x-show="hasResults" style="display:none" class="card p-5">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Raporu Kaydet</h3>
                <form id="save-form"
                      method="POST"
                      action="{{ $report ? route('admin.reports.factory.update', $report) : route('admin.reports.factory.store') }}"
                      @submit.prevent="submitSave()">
                    @csrf
                    @if($report) @method('PATCH') @endif
                    <input type="hidden" name="dimensions"  id="f-dimensions">
                    <input type="hidden" name="metrics"     id="f-metrics">
                    <input type="hidden" name="chart_type"  id="f-chart_type">
                    <input type="hidden" name="filters"     id="f-filters">
                    <div class="flex flex-wrap gap-3">
                        <input type="text" name="name" x-model="reportName"
                               placeholder="Rapor adı girin…"
                               class="input flex-1 min-w-[200px]">
                        <button type="submit" class="btn btn-primary flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
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

        mobileFieldsOpen: false,
        dragKey:  null,
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
        errorMsg: '',

        // ── tap-to-toggle (mobile & desktop click) ──
        toggleField(key, type) {
            if (type === 'dimension') {
                if (this.selectedDimensions.includes(key)) {
                    this.removeDimension(key);
                } else {
                    this.selectedDimensions.push(key);
                }
            } else {
                if (this.selectedMetrics.includes(key)) {
                    this.removeMetric(key);
                } else {
                    this.selectedMetrics.push(key);
                }
            }
        },

        // ── drag & drop ──
        startDrag(event, key, type) {
            if (type === 'dimension' && this.selectedDimensions.includes(key)) { event.preventDefault(); return; }
            if (type === 'metric'    && this.selectedMetrics.includes(key))    { event.preventDefault(); return; }
            this.dragKey  = key;
            this.dragType = type;
            event.dataTransfer.effectAllowed = 'copy';
        },

        dragOver(event, zone) {
            if (this.dragType === zone) this.dropTarget = zone;
        },

        dragLeave() { this.dropTarget = null; },

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
        },

        removeMetric(key) {
            this.selectedMetrics = this.selectedMetrics.filter(m => m !== key);
        },

        // ── preview ──
        async runPreview() {
            if (!this.selectedDimensions.length || !this.selectedMetrics.length) return;
            this.loading  = true;
            this.errorMsg = '';
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
                if (!res.ok) {
                    this.errorMsg = data.error || 'Bir hata oluştu.';
                    return;
                }

                // Show result sections FIRST so canvas is in visible DOM
                this.hasResults      = true;
                this.rowCount        = data.row_count;
                this.tableColumns    = data.columns;
                this.tableColumnKeys = ['label', ...this.selectedMetrics];
                this.tableRows       = data.table;

                // Wait for Alpine to update DOM, then render chart
                await this.$nextTick();
                this.renderChart(data);

            } catch (e) {
                this.errorMsg = 'Önizleme sırasında bir hata oluştu.';
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        renderChart(data) {
            const canvas = document.getElementById('builder-chart');
            if (!canvas) return;

            // Destroy existing chart instance if any
            if (this.chartInstance) {
                this.chartInstance.destroy();
                this.chartInstance = null;
            }

            const isPie = ['pie', 'doughnut'].includes(this.chartType);
            this.chartInstance = new Chart(canvas.getContext('2d'), {
                type: this.chartType,
                data: {
                    labels:   data.labels,
                    datasets: isPie ? [data.datasets[0]] : data.datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: isPie ? 'right' : 'top',
                            labels: { font: { size: 11 }, boxWidth: 12 },
                        },
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
            document.getElementById('f-dimensions').value = JSON.stringify(this.selectedDimensions);
            document.getElementById('f-metrics').value    = JSON.stringify(this.selectedMetrics);
            document.getElementById('f-chart_type').value = this.chartType;
            document.getElementById('f-filters').value    = JSON.stringify(this.filters);
            document.getElementById('save-form').submit();
        },
    };
}
</script>
@endpush
