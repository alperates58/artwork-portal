@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">{{ $supplier->name }}</h1>
            <p class="text-sm text-slate-600">Kod: {{ $supplier->code }}</p>
        </div>
        <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn-secondary">Düzenle</a>
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
                <div>
                    <dt class="text-sm text-slate-500">Notlar</dt>
                    <dd>{{ $supplier->notes ?: '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">Tedarikçi Kullanıcı Eşleşmeleri</h2>
                <span class="text-xs text-slate-400">{{ $supplier->allUsers->count() }} kullanıcı</span>
            </div>
            <div class="space-y-3">
                @forelse($supplier->allUsers as $user)
                    <div class="rounded border border-slate-200 px-4 py-3">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="font-medium">{{ $user->name }}</div>
                                <div class="text-sm text-slate-500">{{ $user->email }}</div>
                            </div>
                            <div class="text-right text-xs text-slate-500">
                                <div>{{ $user->is_active ? 'Aktif' : 'Pasif' }}</div>
                                @if($user->pivot->is_primary)
                                    <div class="mt-1 inline-flex rounded bg-emerald-100 px-2 py-0.5 text-emerald-700">Birincil</div>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3 grid gap-2 text-xs text-slate-600 md:grid-cols-3">
                            <div>Unvan: {{ $user->pivot->title ?: '-' }}</div>
                            <div>İndirme: {{ $user->pivot->can_download ? 'Evet' : 'Hayır' }}</div>
                            <div>Onay: {{ $user->pivot->can_approve ? 'Evet' : 'Hayır' }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Bu tedarikçi ile eşleştirilmiş kullanıcı bulunmuyor.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">Son Siparişler</h2>
            <a href="{{ route('orders.index', ['supplier_id' => $supplier->id]) }}" class="text-sm text-blue-600 hover:underline">Tümünü gör</a>
        </div>
        <div class="space-y-2">
            @forelse($supplier->purchaseOrders as $order)
                <div class="flex items-center justify-between rounded border border-slate-200 px-4 py-3">
                    <div>
                        <div class="font-medium">{{ $order->order_no }}</div>
                        <div class="text-sm text-slate-500">{{ $order->order_date?->format('d.m.Y') }}</div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-slate-600">{{ $order->lines_count }} satır</div>
                        <a href="{{ route('orders.show', $order) }}" class="text-sm text-blue-600 hover:underline">Detay</a>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Sipariş bulunmuyor.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
