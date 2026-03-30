@extends('layouts.app')
@section('title', 'Stok Kartı Düzenle')
@section('page-title', $stockCard->stock_code . ' — Stok Kartı')
@section('page-subtitle', 'Stok adı ve kategori değişiklikleri bağlı galeri kayıtlarına da yansıtılır.')

@section('header-actions')
    <a href="{{ route('admin.stock-cards.index') }}" class="btn btn-secondary">← Listeye Dön</a>
@endsection

@section('content')
<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_280px]">
    <div class="card p-6">
        <form method="POST" action="{{ route('admin.stock-cards.update', $stockCard) }}" class="space-y-4">
            @csrf
            @method('PATCH')
            @include('admin.stock-cards._form')

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Güncelle</button>
                <a href="{{ route('admin.stock-cards.index') }}" class="btn btn-secondary">İptal</a>
            </div>
        </form>
    </div>

    <aside class="space-y-4">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-slate-900">Özet</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-400">Stok Kodu</dt>
                    <dd class="font-mono font-semibold text-slate-900">{{ $stockCard->stock_code }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Kategori</dt>
                    <dd class="text-slate-900">{{ $stockCard->category?->display_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Bağlı galeri kaydı</dt>
                    <dd class="text-slate-900">{{ $stockCard->gallery_items_count }}</dd>
                </div>
            </dl>
        </div>

        @if(auth()->user()->hasPermission('stock_cards', 'delete'))
            <div class="card border border-red-100 p-5">
                <h3 class="text-sm font-semibold text-red-700">Stok Kartını Sil</h3>
                <p class="mt-2 text-xs text-slate-500">Bağlı galeri kaydı yoksa silinebilir.</p>
                <form method="POST" action="{{ route('admin.stock-cards.destroy', $stockCard) }}" class="mt-4" onsubmit="return confirm('Bu stok kartı silinsin mi?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-secondary border-red-200 text-red-600 hover:bg-red-50">Sil</button>
                </form>
            </div>
        @endif
    </aside>
</div>
@endsection
