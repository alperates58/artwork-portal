@extends('layouts.app')
@section('title', 'Galeri Kategorileri')
@section('page-title', 'Galeri Kategorileri')
@section('page-subtitle', 'Artwork galerisi için kullanılan kategorileri yönetin.')

@section('header-actions')
    <a href="{{ route('admin.artwork-gallery.index') }}" class="btn btn-secondary">← Galeriye Dön</a>
@endsection

@section('content')
<div class="max-w-3xl">

    {{-- ── Kategoriler ── --}}
    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white/95 shadow-[0_4px_16px_rgba(15,23,42,0.04)]">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Kategoriler</h2>
                <p class="mt-1 text-xs text-slate-500">Artwork yüklemelerinde ve galeri filtrelerinde kullanılır.</p>
            </div>

            {{-- Yeni kategori formu --}}
            <div class="px-5 py-4">
                <form method="POST" action="{{ route('admin.artwork-gallery.categories.store') }}">
                    @csrf
                    <div class="flex gap-2">
                        <input name="name" class="input flex-1" placeholder="Kategori adı… (Örn. Kutu, Etiket)" value="{{ old('name') }}">
                        <button type="submit" class="btn btn-primary flex-shrink-0">Ekle</button>
                    </div>
                    @error('name', 'storeCategory')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </form>
            </div>

            {{-- Kategori listesi --}}
            <div class="divide-y divide-slate-100">
                @forelse($categories as $category)
                    <div class="flex items-center justify-between gap-3 px-5 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $category->display_name }}</p>
                                <p class="text-xs text-slate-400">{{ $category->gallery_items_count }} dosya</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.artwork-gallery.categories.destroy', $category) }}">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    onclick="return confirm('\"{{ addslashes($category->display_name) }}\" kategorisi silinsin mi?')"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-transparent text-slate-400 transition hover:border-red-200 hover:bg-red-50 hover:text-red-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-400">Henüz kategori eklenmemiş.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
