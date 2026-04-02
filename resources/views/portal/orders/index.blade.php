@extends('layouts.app')
@section('title', 'Siparişlerim')
@section('page-title', 'Siparişlerim')

@section('content')

{{-- Supplier welcome banner --}}
<div class="card p-4 mb-5 flex items-center gap-3 bg-gradient-to-br from-brand-50 to-white">
    <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
         style="background:linear-gradient(180deg,var(--brand-600),var(--brand-700))">
        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
        </svg>
    </div>
    <div>
        <p class="text-sm font-semibold text-slate-900">
            {{ $supplierDisplayName }}
        </p>
        <p class="text-xs text-slate-600">Güncel artwork dosyalarına bu sayfadan erişebilirsiniz.</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-3 mb-5">
    <x-ui.input type="text" name="search" value="{{ request('search') }}"
                placeholder="Sipariş no ara..." class="w-full sm:w-52" />
    <select name="status" class="input w-full sm:w-44">
        <option value="">Tüm durumlar</option>
        <option value="active"    {{ request('status') === 'active'    ? 'selected' : '' }}>Aktif</option>
        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Tamamlandı</option>
    </select>
    <x-ui.button variant="secondary" type="submit">Filtrele</x-ui.button>
</form>

{{-- Orders list --}}
<div class="space-y-3">
    @forelse($orders as $order)
        @php
            $revisionRequestCount = $order->lines->filter(fn ($line) => $line->requiresRevision())->count();
        @endphp
        <div class="card hover:shadow-sm transition-shadow">
            <div class="p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono font-semibold text-slate-900">{{ $order->order_no }}</span>
                            @php $cls = $order->status === 'active' ? 'badge-success' : 'badge-gray'; @endphp
                            <x-ui.badge :variant="str_replace('badge-', '', $cls)">{{ $order->status_label }}</x-ui.badge>
                            @if($revisionRequestCount > 0)
                                <x-ui.badge variant="danger">{{ $revisionRequestCount }} revizyon bekliyor</x-ui.badge>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500">
                            {{ $order->order_date->format('d.m.Y') }}
                            @if($order->due_date) · Son: {{ $order->due_date->format('d.m.Y') }} @endif
                            · {{ $order->lines->count() }} ürün satırı
                        </p>
                    </div>
                    <a href="{{ route('portal.orders.show', $order) }}" class="btn btn-primary text-xs py-2">
                        Artwork'leri Gör →
                    </a>
                </div>

                {{-- Lines summary --}}
                <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach($order->lines->take(6) as $line)
                        <div class="bg-slate-50 rounded-lg px-3 py-2">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-medium text-slate-700 truncate">{{ $line->product_code }}</p>
                                <x-ui.badge :variant="match($line->artwork_status?->value ?? 'pending'){'uploaded' => 'success','revision' => 'danger','approved' => 'info',default => 'warning'}">
                                    {{ $line->artwork_status?->label() ?? 'Bekliyor' }}
                                </x-ui.badge>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ $line->hasActiveArtwork() ? 'Hazır' : 'Bekliyor' }}
                            </p>
                        </div>
                    @endforeach
                    @if($order->lines->count() > 6)
                        <div class="bg-slate-50 rounded-lg px-3 py-2 flex items-center justify-center">
                            <p class="text-xs text-slate-400">+{{ $order->lines->count() - 6 }} daha</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="card p-12 text-center">
            <svg class="w-10 h-10 text-slate-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm text-slate-400">Henüz sipariş kaydı bulunmuyor.</p>
        </div>
    @endforelse
</div>

@if($orders->hasPages())
    <div class="mt-4">{{ $orders->links() }}</div>
@endif

@endsection
