@extends('layouts.app')
@section('title', 'Sipariş Düzenle')
@section('page-title', $order->order_no . ' — Düzenle')
@section('header-actions')
    <a href="{{ route('orders.show', $order) }}" class="btn-secondary">← Siparişe Dön</a>
@endsection
@section('content')
<div class="max-w-xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('orders.update', $order) }}" class="space-y-4">
            @csrf @method('PATCH')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Durum</label>
                    <select name="status" class="input">
                        @foreach(['draft'=>'Taslak','active'=>'Aktif','completed'=>'Tamamlandı','cancelled'=>'İptal'] as $val => $label)
                            <option value="{{ $val }}" {{ $order->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Teslim Tarihi</label>
                    <input type="date" name="due_date" value="{{ old('due_date', $order->due_date?->format('Y-m-d')) }}" class="input">
                </div>
            </div>
            <div>
                <label class="label">Notlar</label>
                <textarea name="notes" rows="3" class="input resize-none">{{ old('notes', $order->notes) }}</textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Güncelle</button>
                <a href="{{ route('orders.show', $order) }}" class="btn-secondary">İptal</a>
            </div>
        </form>
    </div>
</div>
@endsection
