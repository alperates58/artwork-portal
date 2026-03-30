@extends('layouts.app')
@section('title', 'Stok Kartları')
@section('page-title', 'Stok Kartları')
@section('page-subtitle', 'Artwork ve galeri akışında kullanılan stok tanımlarını yönetin.')

@section('header-actions')
    @if(auth()->user()->hasPermission('stock_cards', 'create'))
        <a href="{{ route('admin.stock-cards.import.form') }}" class="btn btn-secondary">Toplu İçe Aktar</a>
        <a href="{{ route('admin.stock-cards.create') }}" class="btn btn-primary">Yeni Stok Kartı</a>
    @endif
@endsection

@section('content')
<div class="space-y-5">
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="search" value="{{ request('search') }}" class="input w-full sm:w-72" placeholder="Stok kodu veya stok adı ara...">
        <select name="category_id" class="input w-full sm:w-56">
            <option value="">Tüm kategoriler</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->display_name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary">Filtrele</button>
        @if(request('search') || request('category_id'))
            <a href="{{ route('admin.stock-cards.index') }}" class="btn btn-secondary">Temizle</a>
        @endif
    </form>

    <div class="card overflow-x-auto">
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

        @if($stockCards->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $stockCards->links() }}</div>
        @endif
    </div>
</div>
@endsection
