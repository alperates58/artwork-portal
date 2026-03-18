@extends('layouts.app')
@section('title', 'Yeni Sipariş')
@section('page-title', 'Yeni Sipariş Oluştur')
@section('header-actions')
    <a href="{{ route('orders.index') }}" class="btn-secondary">← Listeye Dön</a>
@endsection
@section('content')
<div class="max-w-3xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('orders.store') }}" id="orderForm" class="space-y-6">
            @csrf

            {{-- Order header --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Tedarikçi *</label>
                    <select name="supplier_id" required class="input">
                        <option value="">Seçin...</option>
                        @foreach($suppliers as $id => $name)
                            <option value="{{ $id }}" {{ old('supplier_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Sipariş No *</label>
                    <input type="text" name="order_no" value="{{ old('order_no') }}" required class="input font-mono" placeholder="PO-2024-001">
                    @error('order_no')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Sipariş Tarihi *</label>
                    <input type="date" name="order_date" value="{{ old('order_date', today()->format('Y-m-d')) }}" required class="input">
                </div>
                <div>
                    <label class="label">Teslim Tarihi</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" class="input">
                </div>
            </div>
            <div>
                <label class="label">Notlar</label>
                <textarea name="notes" rows="2" class="input resize-none">{{ old('notes') }}</textarea>
            </div>

            {{-- Order lines --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-slate-900">Sipariş Satırları</h3>
                    <button type="button" onclick="addLine()" class="btn-secondary text-xs py-1.5">+ Satır Ekle</button>
                </div>

                <div id="linesContainer" class="space-y-3">
                    {{-- JS ile doldurulacak --}}
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Siparişi Oluştur</button>
                <a href="{{ route('orders.index') }}" class="btn-secondary">İptal</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let lineCount = 0;

function addLine() {
    const i = lineCount++;
    const container = document.getElementById('linesContainer');
    const div = document.createElement('div');
    div.className = 'bg-slate-50 border border-slate-200 rounded-lg p-4 relative';
    div.id = 'line_' + i;
    div.innerHTML = `
        <button type="button" onclick="removeLine(${i})"
                class="absolute top-3 right-3 text-slate-400 hover:text-red-500 text-lg leading-none">×</button>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <label class="label text-xs">Satır No *</label>
                <input type="text" name="lines[${i}][line_no]" required class="input text-sm" placeholder="001">
            </div>
            <div>
                <label class="label text-xs">Ürün Kodu *</label>
                <input type="text" name="lines[${i}][product_code]" required class="input text-sm" placeholder="AMB-001">
            </div>
            <div class="col-span-2">
                <label class="label text-xs">Açıklama *</label>
                <input type="text" name="lines[${i}][description]" required class="input text-sm" placeholder="Karton kutu, 30x20x15 cm">
            </div>
            <div>
                <label class="label text-xs">Miktar *</label>
                <input type="number" name="lines[${i}][quantity]" required min="1" value="1" class="input text-sm">
            </div>
            <div>
                <label class="label text-xs">Birim</label>
                <input type="text" name="lines[${i}][unit]" class="input text-sm" placeholder="adet">
            </div>
        </div>
    `;
    container.appendChild(div);
}

function removeLine(i) {
    document.getElementById('line_' + i)?.remove();
}

// Başlangıçta bir satır ekle
addLine();
</script>
@endpush
