@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold">{{ $supplier->name }}</h1>
        <p class="text-sm text-slate-600">Kod: {{ $supplier->code }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Firma Bilgileri</h2>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm text-slate-500">E-posta</dt>
                    <dd>{{ $supplier->email ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Telefon</dt>
                    <dd>{{ $supplier->phone ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Durum</dt>
                    <dd>{{ $supplier->is_active ? 'Aktif' : 'Pasif' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Adres</dt>
                    <dd>{{ $supplier->address ?: '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Bagli Kullanicilar</h2>
            <div class="space-y-2">
                @forelse($supplier->users as $user)
                    <div class="rounded border border-slate-200 px-3 py-2">
                        <div class="font-medium">{{ $user->name }}</div>
                        <div class="text-sm text-slate-500">{{ $user->email }}</div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Bagli aktif kullanici yok.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold">Son Siparisler</h2>
        <div class="space-y-2">
            @forelse($supplier->purchaseOrders as $order)
                <div class="flex items-center justify-between rounded border border-slate-200 px-3 py-2">
                    <div>
                        <div class="font-medium">{{ $order->order_no }}</div>
                        <div class="text-sm text-slate-500">{{ $order->order_date?->format('d.m.Y') }}</div>
                    </div>
                    <div class="text-sm text-slate-600">{{ $order->lines_count }} satir</div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Siparis bulunmuyor.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
