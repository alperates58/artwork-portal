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
    <x-ui.input type="text" name="search" value="{{ request('search') }}" placeholder="Sipariş no ara..." class="w-full sm:w-52" />
    <select name="supplier_id" class="input w-full sm:w-52" onchange="this.form.submit()">
        <option value="">Tüm tedarikçiler</option>
        @foreach($suppliers as $id => $name)
            <option value="{{ $id }}" {{ request('supplier_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
    <select name="status" class="input w-full sm:w-40" onchange="this.form.submit()">
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

<div class="card overflow-x-auto">
    <table class="w-full min-w-[900px] text-xs">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50">
                <th class="w-8 px-3 py-2.5"></th>
                <th class="text-left px-3 py-2.5 font-semibold text-slate-500 uppercase tracking-wide">Sipariş No</th>
                <th class="text-left px-3 py-2.5 font-semibold text-slate-500 uppercase tracking-wide">Tedarikçi</th>
                <th class="text-left px-3 py-2.5 font-semibold text-slate-500 uppercase tracking-wide">Sipariş Tarihi</th>
                <th class="text-left px-3 py-2.5 font-semibold text-slate-500 uppercase tracking-wide">Artwork Tarihi</th>
                <th class="text-left px-3 py-2.5 font-semibold text-slate-500 uppercase tracking-wide">Geçen Gün</th>
                <th class="text-left px-3 py-2.5 font-semibold text-slate-500 uppercase tracking-wide">Durum</th>
                <th class="text-left px-3 py-2.5 font-semibold text-slate-500 uppercase tracking-wide">Sevk</th>
                <th class="text-left px-3 py-2.5 font-semibold text-slate-500 uppercase tracking-wide">Satır</th>
                <th class="text-left px-3 py-2.5 font-semibold text-slate-500 uppercase tracking-wide">Artwork</th>
                <th class="px-3 py-2.5"></th>
            </tr>
        </thead>
        @forelse($orders as $order)
                @php
                    $latestArtworkAt = $order->lines
                        ->map(fn($l) => $l->artwork?->activeRevision?->created_at)
                        ->filter()
                        ->max();
                    $daysElapsed = $order->order_date->diffInDays(now());
                @endphp
                <tbody x-data="{ open: false }">
                    <tr class="border-b border-slate-100 hover:bg-slate-50/70 transition-colors">
                        <td class="px-3 py-2.5 text-center">
                            <button
                                type="button"
                                @click="open = !open"
                                class="inline-flex h-6 w-6 items-center justify-center rounded-lg text-slate-400 transition-all hover:bg-slate-100 hover:text-slate-600"
                                :class="open ? 'bg-brand-50 text-brand-600 rotate-0' : ''"
                            >
                                <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </td>
                        <td class="px-3 py-2.5">
                            <a href="{{ route('orders.show', $order) }}" class="font-mono font-semibold text-slate-900 hover:text-brand-700 transition-colors">{{ $order->order_no }}</a>
                        </td>
                        <td class="px-3 py-2.5 text-slate-600">{{ $order->supplier->name }}</td>
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-600">{{ $order->order_date->format('d.m.Y') }}</span>
                                @can('update', $order)
                                    <a href="{{ route('orders.edit', $order) }}" class="text-slate-300 hover:text-brand-600 transition-colors" title="Tarihi güncelle">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </a>
                                @endcan
                            </div>
                        </td>
                        <td class="px-3 py-2.5">
                            @if($latestArtworkAt)
                                <span class="text-slate-600">{{ $latestArtworkAt->format('d.m.Y') }}</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5">
                            @php
                                $dayClass = match(true) {
                                    $daysElapsed > 30 => 'text-red-600 font-semibold',
                                    $daysElapsed > 14 => 'text-amber-600',
                                    default => 'text-slate-500',
                                };
                            @endphp
                            <span class="{{ $dayClass }}">{{ $daysElapsed }}g</span>
                        </td>
                        <td class="px-3 py-2.5">
                            <x-ui.badge :variant="match($order->status){'active' => 'success', 'draft' => 'gray', 'completed' => 'info', 'cancelled' => 'danger', default => 'gray'}">{{ $order->status_label }}</x-ui.badge>
                        </td>
                        <td class="px-3 py-2.5">
                            <x-ui.badge :variant="match($order->shipment_status){'dispatched' => 'info', 'delivered' => 'success', 'not_found' => 'danger', default => 'warning'}">{{ $order->shipment_status_label }}</x-ui.badge>
                        </td>
                        <td class="px-3 py-2.5 text-slate-500">{{ $order->lines_count }}</td>
                        <td class="px-3 py-2.5">
                            @if($order->pending_artwork_count > 0)
                                <x-ui.badge variant="warning">{{ $order->pending_artwork_count }} bekliyor</x-ui.badge>
                            @else
                                <x-ui.badge variant="success">Tamam</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-right">
                            <a href="{{ route('orders.show', $order) }}" class="font-medium text-brand-700 hover:text-brand-800">Detay →</a>
                        </td>
                    </tr>
                    <tr x-show="open" x-cloak class="border-b border-slate-100 bg-slate-50/50">
                        <td></td>
                        <td colspan="10" class="px-3 py-3">
                            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="border-b border-slate-100 bg-slate-50">
                                            <th class="px-3 py-2 text-left font-semibold text-slate-400 uppercase tracking-wide">Satır No</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-400 uppercase tracking-wide">Stok Kodu</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-400 uppercase tracking-wide">Açıklama</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-400 uppercase tracking-wide">Miktar</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-400 uppercase tracking-wide">Artwork</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-400 uppercase tracking-wide">Yüklenme</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        @foreach($order->lines as $line)
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-3 py-2 font-mono text-slate-500">{{ $line->line_no }}</td>
                                                <td class="px-3 py-2">
                                                    <a href="{{ route('order-lines.show', $line) }}" class="font-mono font-medium text-slate-800 hover:text-brand-700 hover:underline">{{ $line->product_code }}</a>
                                                </td>
                                                <td class="px-3 py-2 text-slate-600 max-w-[240px] truncate">{{ $line->description }}</td>
                                                <td class="px-3 py-2 text-slate-600">{{ $line->quantity }} {{ $line->unit }}</td>
                                                <td class="px-3 py-2">
                                                    <x-ui.badge :variant="match($line->artwork_status?->value ?? 'pending'){'uploaded' => 'success','revision' => 'danger','approved' => 'info',default => 'warning'}">{{ $line->artwork_status?->label() ?? 'Bekliyor' }}</x-ui.badge>
                                                </td>
                                                <td class="px-3 py-2 text-slate-400">
                                                    {{ $line->artwork?->activeRevision?->created_at?->format('d.m.Y') ?? '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                </tbody>
            @empty
                <tbody>
                    <tr>
                        <td colspan="11" class="px-4 py-10 text-center text-slate-400">Sipariş bulunamadı.</td>
                    </tr>
                </tbody>
            @endforelse
    </table>

    @if($orders->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
