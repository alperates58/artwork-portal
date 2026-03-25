@extends('layouts.app')

@section('title', $supplier->name)
@section('page-title', 'Tedarikçi Detayı')

@section('header-actions')
    @if(auth()->user()->isAdmin())
        <form method="POST" action="{{ route('admin.suppliers.sync', $supplier) }}" class="inline">
            @csrf
            <button type="submit" class="btn btn-secondary">Şimdi Senkronla</button>
        </form>
        <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn btn-secondary">Düzenle</a>
    @endif
@endsection

@section('content')
<div class="space-y-6">
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Firma Bilgileri</h2>
            <dl class="space-y-3">
                <div><dt class="text-sm text-slate-500">E-posta</dt><dd>{{ $supplier->email ?: '-' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Telefon</dt><dd>{{ $supplier->phone ?: '-' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Durum</dt><dd>{{ $supplier->is_active ? 'Aktif' : 'Pasif' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Adres</dt><dd>{{ $supplier->address ?: '-' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Notlar</dt><dd>{{ $supplier->notes ?: '-' }}</dd></div>
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
            <h2 class="text-lg font-semibold">Mikro Hesapları</h2>
            <span class="text-xs text-slate-400">{{ $supplier->mikroAccounts->count() }} hesap</span>
        </div>
        <div class="space-y-3">
            @forelse($supplier->mikroAccounts as $account)
                <div class="rounded border border-slate-200 px-4 py-3">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="font-medium">{{ $account->mikro_cari_kod }}</div>
                            <div class="text-sm text-slate-500">
                                Şirket: {{ $account->mikro_company_code ?: '-' }} · Yıl: {{ $account->mikro_work_year ?: '-' }}
                            </div>
                        </div>
                        <div class="text-right text-xs text-slate-500">
                            <div>{{ $account->is_active ? 'Aktif' : 'Pasif' }}</div>
                            <div class="mt-1">Son sync: {{ $account->last_sync_at?->format('d.m.Y H:i') ?: 'Yok' }}</div>
                        </div>
                    </div>
                    <div class="mt-3 grid gap-2 text-xs text-slate-600 md:grid-cols-3">
                        <div>Durum: {{ $account->last_sync_status?->value ?: 'Henüz çalışmadı' }}</div>
                        <div class="md:col-span-2">Hata özeti: {{ $account->last_sync_error ?: '-' }}</div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Bu tedarikçi için tanımlı Mikro hesabı bulunmuyor.</p>
            @endforelse
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">Son Siparişler</h2>
            <a href="{{ route('orders.index', ['supplier_id' => $supplier->id]) }}" class="text-sm text-brand-700 hover:underline">Tümünü gör</a>
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
                        <a href="{{ route('orders.show', $order) }}" class="text-sm text-brand-700 hover:underline">Detay</a>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Sipariş bulunmuyor.</p>
            @endforelse
        </div>
    </div>

    @if(auth()->user()->isAdmin())
        <div class="card border border-red-100 p-5">
            <h2 class="text-sm font-semibold text-red-700">Tedarikçiyi Sil</h2>
            <p class="mt-2 text-xs text-slate-500">Bu işlem yumuşak silme uygular. Ancak bağlı sipariş varsa güvenlik için silme engellenir.</p>
            <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" class="mt-4" onsubmit="return confirm('Bu tedarikçiyi arşivlemek istediğinize emin misiniz?');">
                @csrf @method('DELETE')
                <label class="mb-3 flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="confirmation" value="1" class="rounded border-slate-300 text-brand-600">
                    Arşivleme işlemini onaylıyorum
                </label>
                <button type="submit" class="btn btn-secondary border-red-200 text-red-600 hover:bg-red-50">Tedarikçiyi Arşivle</button>
            </form>
        </div>
    @endif
</div>
@endsection
