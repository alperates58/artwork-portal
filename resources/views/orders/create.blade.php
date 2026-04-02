@extends('layouts.app')
@section('title', 'Yeni Sipariş')
@section('page-title', 'Yeni Sipariş Oluştur')
@section('page-subtitle', 'Tedarikçi, teslim planı ve sipariş satırlarını tek ekranda hazırlayın.')

@section('header-actions')
    <a href="{{ route('orders.index') }}" class="btn btn-secondary">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Listeye Dön
    </a>
@endsection

@php
    $initialLines = collect(old('lines', [[
        'line_no' => '001',
        'product_code' => '',
        'description' => '',
        'quantity' => 1,
        'unit' => 'adet',
    ]]))->values()->all();
@endphp

@section('content')
<form method="POST" action="{{ route('orders.store') }}" id="orderForm" class="space-y-6">
    @csrf

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
            <p class="font-semibold">Formu kaydetmeden önce aşağıdaki alanları kontrol edin.</p>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div class="space-y-6">
            <section class="card p-6 lg:p-7">
                <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Sipariş Bilgileri</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Temel bilgileri girin</h2>
                        <p class="mt-1 text-sm text-slate-500">Önce tedarikçiyi ve sipariş tarihlerini tanımlayın, ardından satırları ekleyin.</p>
                    </div>
                    <div class="rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-700">
                        <p class="font-semibold">Hazır olduğunuzda satır ekleyin</p>
                        <p class="mt-1 text-xs text-brand-600">Kaydetmeden önce en az bir sipariş satırı zorunludur.</p>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div>
                        <label class="label" for="supplier_id">Tedarikçi *</label>
                        <select id="supplier_id" name="supplier_id" required class="input">
                            <option value="">Seçin...</option>
                            @foreach($suppliers as $id => $name)
                                <option value="{{ $id }}" {{ old('supplier_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="label" for="order_no">Sipariş No *</label>
                        <input id="order_no" type="text" name="order_no" value="{{ old('order_no') }}" required class="input font-mono" placeholder="PO-2026-001">
                        @error('order_no')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="label" for="order_date">Sipariş Tarihi *</label>
                        <input id="order_date" type="date" name="order_date" value="{{ old('order_date', today()->format('Y-m-d')) }}" required class="input">
                        @error('order_date')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="label" for="due_date">Teslim Tarihi</label>
                        <input id="due_date" type="date" name="due_date" value="{{ old('due_date') }}" class="input">
                        @error('due_date')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-5">
                    <label class="label" for="notes">Notlar</label>
                    <textarea id="notes" name="notes" rows="4" class="input resize-none" placeholder="Tedarik, teslim veya operasyon için kısa bir not ekleyebilirsiniz.">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <section class="card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Sipariş Satırları</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-900">Ürünleri ekleyin</h2>
                        <p class="mt-1 text-sm text-slate-500">Her satır için ürün kodu, açıklama ve miktar bilgisi gereklidir.</p>
                    </div>
                    <button type="button" id="addLineBtn" class="btn btn-secondary">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Satır Ekle
                    </button>
                </div>

                <div class="space-y-4 px-6 py-6" id="linesContainer">
                    {{-- Satırlar JS ile doldurulur --}}
                </div>
            </section>

            <section class="card px-6 py-4">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Kaydetmeye hazırsanız siparişi oluşturun</p>
                        <p class="mt-1 text-xs text-slate-500">Sipariş oluşturulduktan sonra satırlara artwork yükleyebilirsiniz.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="btn btn-primary min-w-[190px] justify-center">Siparişi Oluştur</button>
                        <a href="{{ route('orders.index') }}" class="btn btn-secondary min-w-[140px] justify-center">İptal</a>
                    </div>
                </div>
            </section>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
            <section class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Özet</p>
                <div class="mt-4 grid gap-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-500">Eklenen satır</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-900" id="lineCountValue">0</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-500">Varsayılan durum</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">Aktif</p>
                        <p class="mt-1 text-xs text-slate-500">Yeni siparişler aktif olarak açılır.</p>
                    </div>
                </div>
            </section>

            <section class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Hızlı Kontrol</p>
                <ul class="mt-4 space-y-3 text-sm text-slate-600">
                    <li>Tedarikçi seçimi doğru mu?</li>
                    <li>Sipariş numarası benzersiz mi?</li>
                    <li>Her satırda ürün kodu ve açıklama var mı?</li>
                    <li>Miktar alanları boş değil mi?</li>
                </ul>
            </section>
        </aside>
    </div>
</form>
@endsection

@push('scripts')
<script>
const linesContainer = document.getElementById('linesContainer');
const lineCountValue = document.getElementById('lineCountValue');
const initialLines = @json($initialLines);
let lineCount = 0;

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function updateLineSummary() {
    const total = linesContainer.querySelectorAll('[data-order-line-card]').length;
    lineCountValue.textContent = String(total);
}

function buildLineTemplate(index, values = {}) {
    const lineNo = escapeHtml(values.line_no || '');
    const productCode = escapeHtml(values.product_code || '');
    const description = escapeHtml(values.description || '');
    const quantity = escapeHtml(values.quantity || 1);
    const unit = escapeHtml(values.unit || '');

    return `
        <div class="rounded-3xl border border-slate-200 bg-slate-50/80 p-5" id="line_${index}" data-order-line-card>
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Satır</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">Sipariş satırı</p>
                </div>
                <button
                    type="button"
                    onclick="removeLine(${index})"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-400 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                    aria-label="Satırı sil"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="grid gap-4 lg:grid-cols-[140px_180px_minmax(0,1fr)]">
                <div>
                    <label class="label text-xs" for="line_no_${index}">Satır No *</label>
                    <input id="line_no_${index}" type="text" name="lines[${index}][line_no]" value="${lineNo}" required class="input text-sm font-mono" placeholder="001">
                </div>
                <div>
                    <label class="label text-xs" for="product_code_${index}">Ürün Kodu *</label>
                    <input id="product_code_${index}" type="text" name="lines[${index}][product_code]" value="${productCode}" required class="input text-sm font-mono" placeholder="AMB-001">
                </div>
                <div>
                    <label class="label text-xs" for="description_${index}">Açıklama *</label>
                    <input id="description_${index}" type="text" name="lines[${index}][description]" value="${description}" required class="input text-sm" placeholder="Karton kutu, 30x20x15 cm">
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:max-w-[360px]">
                <div>
                    <label class="label text-xs" for="quantity_${index}">Miktar *</label>
                    <input id="quantity_${index}" type="number" name="lines[${index}][quantity]" value="${quantity}" required min="1" class="input text-sm">
                </div>
                <div>
                    <label class="label text-xs" for="unit_${index}">Birim</label>
                    <input id="unit_${index}" type="text" name="lines[${index}][unit]" value="${unit}" class="input text-sm" placeholder="adet">
                </div>
            </div>
        </div>
    `;
}

function addLine(values = {}) {
    const index = lineCount++;
    const wrapper = document.createElement('div');
    wrapper.innerHTML = buildLineTemplate(index, values);
    linesContainer.appendChild(wrapper.firstElementChild);
    updateLineSummary();
}

function removeLine(index) {
    document.getElementById(`line_${index}`)?.remove();
    updateLineSummary();
}

document.getElementById('addLineBtn')?.addEventListener('click', () => addLine({
    line_no: String(lineCount + 1).padStart(3, '0'),
    quantity: 1,
    unit: 'adet',
}));

window.addEventListener('DOMContentLoaded', () => {
    if (Array.isArray(initialLines) && initialLines.length > 0) {
        initialLines.forEach(line => addLine(line));
        return;
    }

    addLine({
        line_no: '001',
        quantity: 1,
        unit: 'adet',
    });
});
</script>
@endpush
