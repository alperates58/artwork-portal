@extends('layouts.app')
@section('title', 'Tedarikçiler')
@section('page-title', 'Tedarikçi Yönetimi')

@section('header-actions')
    @if(auth()->user()->hasPermission('suppliers', 'create'))
        <a href="{{ route('admin.suppliers.import.form') }}" class="btn btn-secondary shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Toplu İçe Aktar
        </a>
        <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Yeni Tedarikçi
        </a>
    @endif
@endsection

@section('content')
<form method="GET" class="flex flex-wrap gap-3 mb-5">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="İsim veya kod ara..." class="input w-full sm:w-64">
    <button type="submit" class="btn btn-secondary">Ara</button>
    @if(request('search'))
        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary text-slate-500">Temizle</a>
    @endif
</form>

<div class="card overflow-x-auto">
    <table class="w-full min-w-[640px] text-xs">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50">
                <th class="w-8 px-3 py-2.5"></th>
                <th class="text-left px-3 py-2.5 font-semibold text-slate-500 uppercase tracking-wide">Tedarikçi</th>
                <th class="text-left px-3 py-2.5 font-semibold text-slate-500 uppercase tracking-wide">Kod</th>
                <th class="text-left px-3 py-2.5 font-semibold text-slate-500 uppercase tracking-wide">İletişim</th>
                <th class="text-center px-3 py-2.5 font-semibold text-slate-500 uppercase tracking-wide">Kullanıcı</th>
                <th class="text-center px-3 py-2.5 font-semibold text-slate-500 uppercase tracking-wide">Sipariş</th>
                <th class="text-left px-3 py-2.5 font-semibold text-slate-500 uppercase tracking-wide">Durum</th>
                <th class="px-3 py-2.5"></th>
            </tr>
        </thead>

        @forelse($suppliers as $supplier)
            @php $recentOrders = $recentOrdersBySupplier->get($supplier->id, collect()); @endphp
            <tbody x-data="{ open: false }">
                <tr class="border-b border-slate-100 hover:bg-slate-50/70 transition-colors">
                    <td class="px-3 py-3 text-center">
                        @if($recentOrders->isNotEmpty())
                            <button
                                type="button"
                                @click="open = !open"
                                class="inline-flex h-6 w-6 items-center justify-center rounded-lg text-slate-400 transition-all hover:bg-slate-100 hover:text-slate-600"
                            >
                                <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        @else
                            <span class="inline-block h-6 w-6"></span>
                        @endif
                    </td>
                    <td class="px-3 py-3">
                        <p class="font-semibold text-slate-900">{{ $supplier->name }}</p>
                    </td>
                    <td class="px-3 py-3 font-mono text-slate-500">{{ $supplier->code }}</td>
                    <td class="px-3 py-3 text-slate-500">
                        @if($supplier->email)
                            <p>{{ $supplier->email }}</p>
                        @endif
                        @if($supplier->phone)
                            <p class="text-slate-400">{{ $supplier->phone }}</p>
                        @endif
                        @if(! $supplier->email && ! $supplier->phone)
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-center">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 font-semibold text-slate-600">{{ $supplier->users_count }}</span>
                    </td>
                    <td class="px-3 py-3 text-center">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 font-semibold text-slate-600">{{ $supplier->purchase_orders_count }}</span>
                    </td>
                    <td class="px-3 py-3">
                        @if($supplier->is_active)
                            <x-ui.badge variant="success">Aktif</x-ui.badge>
                        @else
                            <x-ui.badge variant="gray">Pasif</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.suppliers.show', $supplier) }}" class="font-medium text-slate-600 hover:underline">Detay</a>
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="font-medium text-brand-700 hover:underline">Düzenle</a>
                            @endif
                        </div>
                    </td>
                </tr>

                {{-- Expanded: Recent Orders --}}
                @if($recentOrders->isNotEmpty())
                    <tr x-show="open" x-cloak class="border-b border-slate-100 bg-slate-50/40">
                        <td></td>
                        <td colspan="7" class="px-3 py-3">
                            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                                <div class="border-b border-slate-100 px-4 py-2.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Son Siparişler</p>
                                </div>
                                <table class="w-full text-xs">
                                    <tbody class="divide-y divide-slate-50">
                                        @foreach($recentOrders as $po)
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-4 py-2">
                                                    <a href="{{ route('orders.show', $po) }}" class="font-mono font-semibold text-slate-800 hover:text-brand-700 hover:underline">{{ $po->order_no }}</a>
                                                </td>
                                                <td class="px-4 py-2 text-slate-500">{{ $po->order_date->format('d.m.Y') }}</td>
                                                <td class="px-4 py-2">
                                                    <x-ui.badge :variant="match($po->status){'active' => 'success', 'draft' => 'gray', 'completed' => 'info', 'cancelled' => 'danger', default => 'gray'}">{{ $po->status_label }}</x-ui.badge>
                                                </td>
                                                <td class="px-4 py-2">
                                                    <x-ui.badge :variant="match($po->shipment_status){'dispatched' => 'info', 'delivered' => 'success', 'not_found' => 'danger', default => 'warning'}">{{ $po->shipment_status_label }}</x-ui.badge>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        @empty
            <tbody>
                <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Tedarikçi bulunamadı.</td></tr>
            </tbody>
        @endforelse
    </table>

    @if($suppliers->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $suppliers->links() }}</div>
    @endif
</div>
@endsection
