@extends('layouts.app')
@section('title', 'Siparişler')
@section('page-title', 'Sipariş Listesi')

@section('header-actions')
    @can('create', App\Models\PurchaseOrder::class)
        <a href="{{ route('orders.create') }}" class="btn btn-primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Sipariş
        </a>
    @endcan
@endsection

@section('content')
<form method="GET" class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:grid-cols-2 xl:grid-cols-[minmax(0,1.1fr)_280px_220px_auto_auto]">
    <x-ui.input type="text" name="search" value="{{ request('search') }}" placeholder="Sipariş no ara..." class="w-full min-w-0" />
    <select name="supplier_id" class="input w-full min-w-0" onchange="this.form.submit()">
        <option value="">Tüm tedarikçiler</option>
        @foreach($suppliers as $id => $name)
            <option value="{{ $id }}" {{ request('supplier_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
    <select name="status" class="input w-full min-w-0" onchange="this.form.submit()">
        <option value="">Tüm durumlar</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Taslak</option>
        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Tamamlandı</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>İptal</option>
    </select>
    <x-ui.button variant="secondary" type="submit" class="w-full justify-center sm:w-auto">Filtrele</x-ui.button>
    @if(request()->hasAny(['search', 'supplier_id', 'status']))
        <a href="{{ route('orders.index') }}" class="btn btn-secondary w-full justify-center text-slate-500 sm:w-auto">Temizle</a>
    @endif
</form>

<div class="space-y-4 lg:hidden">
    @forelse($orders as $order)
        @php
            $latestArtworkAt = $order->lines->map(fn ($line) => $line->artwork?->activeRevision?->created_at)->filter()->max();
            $daysElapsed = round($order->order_date->diffInMinutes(now(), false) / 1440, 1);
            $daysElapsedLabel = number_format($daysElapsed, 1, ',', '.');
            $daysElapsedAbs = abs($daysElapsed);
            $dayClass = match (true) {
                $daysElapsedAbs > 30 => 'text-red-600 font-semibold',
                $daysElapsedAbs > 14 => 'text-amber-600',
                default => 'text-slate-500',
            };
        @endphp
        <div x-data="{ open: false }" class="card overflow-hidden">
            <div class="px-4 py-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('orders.show', $order) }}" class="font-mono text-lg font-semibold text-slate-900 hover:text-brand-700">{{ $order->order_no }}</a>
                        <p class="mt-1 text-sm text-slate-600">{{ $order->supplier->name }}</p>
                    </div>
                    <button type="button" @click="open = !open" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700">
                        <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <x-ui.badge :variant="match($order->status){'active' => 'success', 'draft' => 'gray', 'completed' => 'info', 'cancelled' => 'danger', default => 'gray'}">{{ $order->status_label }}</x-ui.badge>
                    <x-ui.badge :variant="match($order->shipment_status){'dispatched' => 'info', 'delivered' => 'success', 'not_found' => 'danger', default => 'warning'}">{{ $order->shipment_status_label }}</x-ui.badge>
                    @if($order->pending_artwork_count > 0)
                        <x-ui.badge variant="warning">{{ $order->pending_artwork_count }} artwork bekliyor</x-ui.badge>
                    @else
                        <x-ui.badge variant="success">Artwork tamam</x-ui.badge>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Sipariş Tarihi</p>
                        <p class="mt-1 text-slate-700">{{ $order->order_date->format('d.m.Y') }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Satır</p>
                        <p class="mt-1 text-slate-700">{{ $order->lines_count }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Artwork Tarihi</p>
                        <p class="mt-1 text-slate-700" x-show="open">{{ $latestArtworkAt?->format('d.m.Y') ?? '—' }}</p>
                        <p class="mt-1 text-slate-400" x-show="!open">Detayı açın</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Geçen Gün</p>
                        <p class="mt-1 {{ $dayClass }}" x-show="open">{{ $daysElapsedLabel }} gün</p>
                        <p class="mt-1 text-slate-400" x-show="!open">Detayı açın</p>
                    </div>
                </div>
            </div>

            <div x-show="open" x-cloak class="border-t border-slate-100 bg-slate-50/70 px-4 py-4">
                <div class="space-y-3">
                    @foreach($order->lines as $line)
                        @php
                            $latestRejectedApproval = $line->latestRejectedApproval;
                        @endphp
                        <a href="{{ route('order-lines.show', $line) }}" class="block rounded-2xl border border-slate-200 bg-white px-4 py-3 transition hover:border-brand-200 hover:shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-sm font-semibold text-slate-900">{{ $line->product_code }}</span>
                                        <x-ui.badge :variant="match($line->artwork_status?->value ?? 'pending'){'uploaded' => 'success','revision' => 'danger','approved' => 'info',default => 'warning'}">{{ $line->artwork_status?->label() ?? 'Bekliyor' }}</x-ui.badge>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-600">{{ $line->description }}</p>
                                    @if($line->requiresRevision())
                                        <p class="mt-1 text-xs text-red-600">
                                            Talep eden: {{ $latestRejectedApproval?->user?->name ?? $latestRejectedApproval?->supplier?->name ?? 'Tedarikçi kullanıcısı' }}
                                            @if($latestRejectedApproval?->actioned_at)
                                                · {{ $latestRejectedApproval->actioned_at->format('d.m.Y H:i') }}
                                            @endif
                                        </p>
                                    @endif
                                </div>
                                <div class="text-right text-sm text-slate-500">
                                    <p>{{ $line->quantity }} {{ $line->unit }}</p>
                                    <p class="mt-1">{{ $line->artwork?->activeRevision?->created_at?->format('d.m.Y') ?? '—' }}</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-4">
                    <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-800">
                        Sipariş detayına git
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="card px-4 py-10 text-center text-slate-400">Sipariş bulunamadı.</div>
    @endforelse
</div>

<div class="hidden lg:block">
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="w-14 px-4 py-3"></th>
                        <th class="min-w-[150px] px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-500">Sipariş No</th>
                        <th class="min-w-[220px] px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-500">Tedarikçi</th>
                        <th class="min-w-[140px] px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-500">Sipariş Tarihi</th>
                        <th class="min-w-[120px] px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-500">Durum</th>
                        <th class="min-w-[170px] px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-500">Sevk</th>
                        <th class="min-w-[70px] px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-500">Satır</th>
                        <th class="min-w-[130px] px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-500">Artwork</th>
                        <th class="min-w-[90px] px-4 py-3"></th>
                    </tr>
                </thead>
                @forelse($orders as $order)
                    @php
                    @endphp
                    <tbody x-data="{ open: false }">
                        <tr class="border-b border-slate-100 align-top transition hover:bg-slate-50/60">
                            <td class="px-4 py-4 text-center">
                                <button type="button" @click="open = !open" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700" :class="open ? 'border-brand-200 bg-brand-50 text-brand-700' : ''">
                                    <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            </td>
                            <td class="px-4 py-4">
                                <a href="{{ route('orders.show', $order) }}" class="font-mono text-base font-semibold text-slate-900 hover:text-brand-700">{{ $order->order_no }}</a>
                            </td>
                            <td class="px-4 py-4 text-slate-600">
                                <p class="line-clamp-2 leading-6">{{ $order->supplier->name }}</p>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-600">{{ $order->order_date->format('d.m.Y') }}</span>
                                    @can('update', $order)
                                        <a href="{{ route('orders.edit', $order) }}" class="text-slate-300 transition hover:text-brand-600" title="Tarihi güncelle">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <x-ui.badge :variant="match($order->status){'active' => 'success', 'draft' => 'gray', 'completed' => 'info', 'cancelled' => 'danger', default => 'gray'}">{{ $order->status_label }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-4">
                                <x-ui.badge :variant="match($order->shipment_status){'dispatched' => 'info', 'delivered' => 'success', 'not_found' => 'danger', default => 'warning'}">{{ $order->shipment_status_label }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-4 text-slate-500">{{ $order->lines_count }}</td>
                            <td class="px-4 py-4">
                                @if($order->pending_artwork_count > 0)
                                    <x-ui.badge variant="warning">{{ $order->pending_artwork_count }} bekliyor</x-ui.badge>
                                @else
                                    <x-ui.badge variant="success">Tamam</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right">
                                <a href="{{ route('orders.show', $order) }}" class="font-medium text-brand-700 hover:text-brand-800">Detay →</a>
                            </td>
                        </tr>
                        <tr x-show="open" x-cloak class="border-b border-slate-100 bg-slate-50/60">
                            <td></td>
                            <td colspan="8" class="px-4 py-4">
                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-[13px]">
                                            <thead>
                                                <tr class="border-b border-slate-100 bg-slate-50 text-left">
                                                    <th class="px-4 py-3 font-semibold uppercase tracking-wide text-slate-400">Stok Kodu</th>
                                                    <th class="px-4 py-3 font-semibold uppercase tracking-wide text-slate-400">Açıklama</th>
                                                    <th class="px-4 py-3 font-semibold uppercase tracking-wide text-slate-400">Miktar</th>
                                                    <th class="px-4 py-3 font-semibold uppercase tracking-wide text-slate-400">Sipariş Tarihi</th>
                                                    <th class="px-4 py-3 font-semibold uppercase tracking-wide text-slate-400">Artwork</th>
                                                    <th class="px-4 py-3 font-semibold uppercase tracking-wide text-slate-400">Artwork Tarihi</th>
                                                    <th class="px-4 py-3 font-semibold uppercase tracking-wide text-slate-400">Geçen Gün</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($order->lines as $line)
                                                    @php
                                                        $lineUploadAt = $line->artwork?->activeRevision?->created_at;
                                                        $latestRejectedApproval = $line->latestRejectedApproval;
                                                        $lineDays = $lineUploadAt
                                                            ? round($order->order_date->diffInMinutes($lineUploadAt, false) / 1440, 1)
                                                            : null;
                                                        $lineDaysAbs = abs((float) $lineDays);
                                                        $lineDayClass = match (true) {
                                                            $lineDaysAbs > 30 => 'text-red-600 font-semibold',
                                                            $lineDaysAbs > 14 => 'text-amber-600',
                                                            default => 'text-slate-500',
                                                        };
                                                        [$lineBarBg, $lineBarPct] = match (true) {
                                                            $lineDaysAbs <= 1 => ['bg-emerald-500', max(10, (int) round($lineDaysAbs * 25))],
                                                            $lineDaysAbs <= 7 => ['bg-amber-500', min(100, max(20, (int) round($lineDaysAbs * 12)))],
                                                            default => ['bg-red-500', min(100, max(30, (int) round(log($lineDaysAbs + 1, 2) * 20)))],
                                                        };
                                                    @endphp
                                                    <tr class="hover:bg-slate-50">
                                                        <td class="px-4 py-3">
                                                            <a href="{{ route('order-lines.show', $line) }}" class="font-mono font-medium text-slate-800 hover:text-brand-700 hover:underline">{{ $line->product_code }}</a>
                                                        </td>
                                                        <td class="px-4 py-3 text-slate-600"><p class="line-clamp-2">{{ $line->description }}</p></td>
                                                        <td class="px-4 py-3 text-slate-600">{{ $line->quantity }} {{ $line->unit }}</td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-slate-500">{{ $order->order_date->format('d.m.Y') }}</td>
                                                        <td class="px-4 py-3">
                                                            <x-ui.badge :variant="match($line->artwork_status?->value ?? 'pending'){'uploaded' => 'success','revision' => 'danger','approved' => 'info',default => 'warning'}">{{ $line->artwork_status?->label() ?? 'Bekliyor' }}</x-ui.badge>
                                                            @if($line->requiresRevision())
                                                                <p class="mt-1 text-[11px] text-red-600">
                                                                    {{ $latestRejectedApproval?->user?->name ?? $latestRejectedApproval?->supplier?->name ?? 'Tedarikçi kullanıcısı' }}
                                                                    @if($latestRejectedApproval?->actioned_at)
                                                                        · {{ $latestRejectedApproval->actioned_at->format('d.m.Y H:i') }}
                                                                    @endif
                                                                </p>
                                                                @if(filled($latestRejectedApproval?->notes))
                                                                    <p class="mt-1 line-clamp-1 text-[11px] text-slate-500">Not: {{ $latestRejectedApproval->notes }}</p>
                                                                @endif
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-slate-500">{{ $lineUploadAt?->format('d.m.Y') ?? '—' }}</td>
                                                        <td class="px-4 py-3 min-w-[150px]">
                                                            @if(! is_null($lineDays))
                                                                <div class="space-y-1.5">
                                                                    <span class="{{ $lineDayClass }} block whitespace-nowrap text-xs">{{ number_format($lineDays, 1, ',', '.') }} gün</span>
                                                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                                                        <div class="{{ $lineBarBg }} h-full rounded-full" style="width: {{ $lineBarPct }}%"></div>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <span class="text-slate-300">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-slate-400">Sipariş bulunamadı.</td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        @if($orders->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $orders->links() }}</div>
        @endif
    </div>
</div>

@if($orders->hasPages() && ! $orders->isEmpty())
    <div class="mt-4 lg:hidden">{{ $orders->links() }}</div>
@endif
@endsection
