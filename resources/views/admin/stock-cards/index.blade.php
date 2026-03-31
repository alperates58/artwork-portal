@extends('layouts.app')
@section('title', 'Stok Kartları')
@section('page-title', 'Stok Kartları')
@section('page-subtitle', 'Artwork ve galeri akışında kullanılan stok tanımlarını yönetin.')

@section('header-actions')
    @if(auth()->user()->hasPermission('stock_cards', 'create'))
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.stock-cards.import.form') }}" class="btn btn-secondary">Toplu İçe Aktar</a>
            <a href="{{ route('admin.stock-cards.create') }}" class="btn btn-primary">Yeni Stok Kartı</a>
        </div>
    @endif
@endsection

@section('content')
<div class="space-y-5">
    <form method="GET" class="card p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_240px_auto_auto]">
            <input type="text" name="search" value="{{ request('search') }}" class="input w-full" placeholder="Stok kodu veya stok adı ara...">

            <select name="category_id" class="input w-full">
                <option value="">Tüm kategoriler</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->display_name }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-secondary w-full xl:w-auto">Filtrele</button>

            @if(request('search') || request('category_id'))
                <a href="{{ route('admin.stock-cards.index') }}" class="btn btn-secondary w-full xl:w-auto">Temizle</a>
            @endif
        </div>
    </form>

    <div class="space-y-4 md:hidden">
        @forelse($stockCards as $stockCard)
            <div class="card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="font-mono text-sm font-semibold text-slate-900">{{ $stockCard->stock_code }}</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $stockCard->display_stock_name }}</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                        {{ $stockCard->gallery_items_count }} galeri
                    </span>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Kategori</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $stockCard->category?->display_name ?? '—' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Oluşturma</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $stockCard->created_at->format('d.m.Y H:i') }}</p>
                    </div>
                </div>

                @if(auth()->user()->hasPermission('stock_cards', 'edit'))
                    <div class="mt-4 border-t border-slate-100 pt-3">
                        <a href="{{ route('admin.stock-cards.edit', $stockCard) }}" class="text-sm font-medium text-brand-700 hover:underline">Düzenle</a>
                    </div>
                @endif
            </div>
        @empty
            <div class="card px-4 py-10 text-center text-sm text-slate-400">Stok kartı bulunamadı.</div>
        @endforelse
    </div>

    <div class="card hidden overflow-x-auto md:block">
        <table class="w-full min-w-[720px] text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Stok Kodu</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Stok Adı</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Kategori</th>
                    <th class="px-4 py-3 text-center font-medium text-slate-600">Galeri Kaydı</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Oluşturma</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($stockCards as $stockCard)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono font-semibold text-slate-800">{{ $stockCard->stock_code }}</td>
                        <td class="px-4 py-3 text-slate-900">{{ $stockCard->display_stock_name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $stockCard->category?->display_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-center text-slate-700">{{ $stockCard->gallery_items_count }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $stockCard->created_at->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            @if(auth()->user()->hasPermission('stock_cards', 'edit'))
                                <a href="{{ route('admin.stock-cards.edit', $stockCard) }}" class="text-xs font-medium text-brand-700 hover:underline">Düzenle</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-400">Stok kartı bulunamadı.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($stockCards->hasPages())
        <div>{{ $stockCards->links() }}</div>
    @endif
</div>
@endsection
