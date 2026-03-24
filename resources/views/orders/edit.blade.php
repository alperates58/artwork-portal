@extends('layouts.app')
@section('title', 'Sipariş Düzenle')
@section('page-title', $order->order_no . ' — Düzenle')

@section('header-actions')
    <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary">← Siparişe Dön</a>
@endsection

@section('content')
<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
    <div class="card p-6">
        <form method="POST" action="{{ route('orders.update', $order) }}" class="space-y-5">
            @csrf @method('PATCH')
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Durum</label>
                    <select name="status" class="input">
                        @foreach(['draft'=>'Taslak','active'=>'Aktif','completed'=>'Tamamlandı','cancelled'=>'İptal'] as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $order->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
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
                <textarea name="notes" rows="4" class="input resize-none">{{ old('notes', $order->notes) }}</textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Güncelle</button>
                <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary">İptal</a>
            </div>
        </form>
    </div>

    <div class="space-y-4">
        <div class="card p-5 space-y-4">
            <div>
                <p class="text-xs text-slate-500">Tedarikçi</p>
                <p class="text-sm font-medium text-slate-900">{{ $order->supplier->name }}</p>
                <p class="text-xs text-slate-400">{{ $order->supplier->code }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <p class="text-xs text-slate-500">Sipariş Tarihi</p>
                    <p class="text-sm text-slate-900">{{ $order->order_date->format('d.m.Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Satır Sayısı</p>
                    <p class="text-sm text-slate-900">{{ $order->lines->count() }}</p>
                </div>
            </div>
            <div>
                <p class="text-xs text-slate-500">Sevk Durumu</p>
                <x-ui.badge :variant="match($order->shipment_status){'dispatched'=>'info','delivered'=>'success','not_found'=>'danger',default=>'warning'}">{{ $order->shipment_status_label }}</x-ui.badge>
                @if($order->shipment_reference)
                    <p class="text-xs text-slate-500 mt-2">Referans: {{ $order->shipment_reference }}</p>
                @endif
                @if($order->shipment_synced_at)
                    <p class="text-xs text-slate-400 mt-1">Son Mikro senkronu: {{ $order->shipment_synced_at->format('d.m.Y H:i') }}</p>
                @endif
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900">Satır Özeti</h2>
                <span class="text-xs text-slate-400">{{ $order->lines->count() }} satır</span>
            </div>
            <div class="space-y-3">
                @foreach($order->lines as $line)
                    <div class="rounded-xl border border-slate-200 px-3 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-mono text-slate-500">{{ $line->line_no }}</p>
                                <p class="text-sm font-medium text-slate-900">{{ $line->product_code }}</p>
                            </div>
                            <x-ui.badge :variant="$line->activeRevision ? 'success' : 'warning'">{{ $line->activeRevision ? 'Aktif revizyon var' : 'Artwork bekliyor' }}</x-ui.badge>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">{{ $line->description }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $line->quantity }} {{ $line->unit }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
