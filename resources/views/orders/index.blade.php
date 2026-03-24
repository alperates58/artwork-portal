@extends('layouts.app')
@section('title', 'Siparişler')
@section('page-title', 'Sipariş Listesi')

@section('header-actions')
    @can('create', App\Models\PurchaseOrder::class)
        <a href="{{ route('orders.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Sipariş
        </a>
    @endcan
@endsection

@section('content')
<form method="GET" class="flex flex-wrap gap-3 mb-5">
    <x-ui.input type="text" name="search" value="{{ request('search') }}" placeholder="Sipariş no ara..." class="w-52" />
    <select name="supplier_id" class="input w-52">
        <option value="">Tüm tedarikçiler</option>
        @foreach($suppliers as $id => $name)
            <option value="{{ $id }}" {{ request('supplier_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
    <select name="status" class="input w-40">
        <option value="">Tüm durumlar</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Taslak</option>
        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Tamamlandı</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>İptal</option>
    </select>
    <x-ui.button variant="secondary" type="submit">Filtrele</x-ui.button>
    @if(request()->hasAny(['search', 'supplier_id', 'status']))
        <a href="{{ route('orders.index') }}" class="btn btn-secondary text-slate-500">Temizle</a>
    @endif
</form>

<div class="card">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50">
                <th class="text-left px-4 py-3 font-medium text-slate-600">Sipariş No</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Tedarikçi</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Tarih</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Durum</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Sevk</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Satır</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Artwork</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($orders as $order)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3"><span class="font-mono font-medium text-slate-900">{{ $order->order_no }}</span></td>
                    <td class="px-4 py-3 text-slate-700">{{ $order->supplier->name }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $order->order_date->format('d.m.Y') }}</td>
                    <td class="px-4 py-3"><x-ui.badge :variant="match($order->status){'active'=>'success','draft'=>'gray','completed'=>'info','cancelled'=>'danger',default=>'gray'}">{{ $order->status_label }}</x-ui.badge></td>
                    <td class="px-4 py-3"><x-ui.badge :variant="match($order->shipment_status){'dispatched'=>'info','delivered'=>'success','not_found'=>'danger',default=>'warning'}">{{ $order->shipment_status_label }}</x-ui.badge></td>
                    <td class="px-4 py-3 text-slate-700">{{ $order->lines_count }} satır</td>
                    <td class="px-4 py-3">
                        @if($order->pending_artwork_count > 0)
                            <x-ui.badge variant="warning">{{ $order->pending_artwork_count }} bekliyor</x-ui.badge>
                        @else
                            <x-ui.badge variant="success">Tamamlandı</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('orders.show', $order) }}" class="text-brand-700 hover:text-brand-800 text-xs font-medium">Detay</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Sipariş bulunamadı.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($orders->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
