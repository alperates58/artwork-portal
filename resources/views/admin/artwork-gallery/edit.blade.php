@extends('layouts.app')
@section('title', 'Artwork Galerisi')
@section('page-title', 'Artwork Galerisi Detayı')
@section('page-subtitle', 'Kategori, etiket ve kullanım geçmişi yönetimi.')

@section('header-actions')
    <a href="{{ route('admin.artwork-gallery.index') }}" class="btn btn-secondary">← Galeriye dön</a>
    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('gallery', 'manage'))
        <form method="POST" action="{{ route('admin.artwork-gallery.destroy', $artworkGallery) }}"
              onsubmit="return confirm('{{ $artworkGallery->display_name }} galeriden kalıcı olarak silinsin mi? Bu işlem geri alınamaz.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-secondary text-red-600 hover:border-red-300 hover:bg-red-50">Sil</button>
        </form>
    @endif
@endsection

@section('content')
<div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_420px]">
    <section class="space-y-6">
        <div class="card p-6">
            <div class="flex flex-col gap-5 md:flex-row">
                @include('artwork-gallery.partials.file-visual', [
                    'artworkGallery' => $artworkGallery,
                    'sizeClass' => 'h-28 w-28',
                    'roundedClass' => 'rounded-3xl',
                ])

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold text-slate-900">{{ $artworkGallery->display_name }}</h2>
                        <span class="badge badge-gray">{{ $artworkGallery->file_type_display }}</span>
                        <span class="badge badge-gray">{{ $artworkGallery->category?->display_name ?? 'Kategorisiz' }}</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ $artworkGallery->file_size_formatted }} · {{ $artworkGallery->file_disk }} · {{ $artworkGallery->created_at->format('d.m.Y H:i') }}
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" class="btn btn-secondary" data-dialog-open="gallery-preview-{{ $artworkGallery->id }}">Görüntüle</button>
                    </div>
                </div>
            </div>
        </div>

        <section class="card p-6 space-y-5">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Galeri kaydını düzenle</h3>
                <p class="mt-1 text-xs text-slate-500">Mevcut upload ve reuse akışlarını bozmadan yalnız metadata alanlarını günceller.</p>
            </div>

            <form method="POST" action="{{ route('admin.artwork-gallery.update', $artworkGallery) }}" class="space-y-5">
                @csrf
                @method('PATCH')

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="label" for="name">Dosya adı</label>
                        <input id="name" name="name" class="input" value="{{ old('name', $artworkGallery->name) }}">
                    </div>
                    <div>
                        <label class="label" for="stock_code">Stok Kodu</label>
                        <input id="stock_code" name="stock_code" class="input font-mono" value="{{ old('stock_code', $artworkGallery->stock_code) }}" placeholder="ERP stok kodu">
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="label mb-0" for="category_id">Kategori</label>
                            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('gallery', 'manage'))
                                <button type="button" class="text-xs text-brand-600 hover:underline"
                                        onclick="document.getElementById('edit-quick-cat-form').classList.toggle('hidden')">+ Yeni kategori</button>
                            @endif
                        </div>
                        <select id="category_id" name="category_id" class="input">
                            <option value="">Kategori seçin</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $artworkGallery->category_id) === (string) $category->id)>{{ $category->display_name }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('gallery', 'manage'))
                            <div id="edit-quick-cat-form" class="hidden mt-2">
                                <div class="flex gap-2">
                                    <input type="text" name="name" form="edit-qcat-form" class="input flex-1 text-sm py-1.5" placeholder="Kategori adı" required>
                                    <button type="submit" form="edit-qcat-form" class="btn btn-secondary text-xs py-1.5 px-3">Ekle</button>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="label mb-0">Etiketler</label>
                            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('gallery', 'manage'))
                                <button type="button" class="text-xs text-brand-600 hover:underline"
                                        onclick="document.getElementById('edit-quick-tag-form').classList.toggle('hidden')">+ Yeni etiket</button>
                            @endif
                        </div>
                        @php $selectedTagIds = collect(old('tag_ids', $artworkGallery->tags->pluck('id')->all())); @endphp
                        <div class="flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-white p-3 min-h-[56px]">
                            @forelse($tags as $tag)
                                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition
                                    {{ $selectedTagIds->contains($tag->id) ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300' }}">
                                    <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}"
                                           class="sr-only"
                                           {{ $selectedTagIds->contains($tag->id) ? 'checked' : '' }}
                                           onchange="this.closest('label').classList.toggle('border-brand-500', this.checked);
                                                     this.closest('label').classList.toggle('bg-brand-50', this.checked);
                                                     this.closest('label').classList.toggle('text-brand-700', this.checked);
                                                     this.closest('label').classList.toggle('border-slate-200', !this.checked);
                                                     this.closest('label').classList.toggle('bg-slate-50', !this.checked);
                                                     this.closest('label').classList.toggle('text-slate-600', !this.checked);">
                                    {{ $tag->display_name }}
                                </label>
                            @empty
                                <p class="text-xs text-slate-400 w-full">Henüz etiket yok. "Yeni etiket" ile ekleyin.</p>
                            @endforelse
                        </div>
                        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('gallery', 'manage'))
                            <div id="edit-quick-tag-form" class="hidden mt-2">
                                <div class="flex gap-2">
                                    <input type="text" name="name" form="edit-qtag-form" class="input flex-1 text-sm py-1.5" placeholder="Etiket adı" required>
                                    <button type="submit" form="edit-qtag-form" class="btn btn-secondary text-xs py-1.5 px-3">Ekle</button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="label" for="revision_note">Revizyon notu</label>
                    <textarea id="revision_note" name="revision_note" rows="4" class="input resize-none">{{ old('revision_note', $artworkGallery->revision_note) }}</textarea>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    <p class="font-medium text-slate-700">Dosya yolu</p>
                    <p class="mt-1 break-all font-mono text-xs">{{ $artworkGallery->file_path }}</p>
                </div>

                <button type="submit" class="btn btn-primary">Kaydet</button>
            </form>

            {{-- Quick category/tag forms — OUTSIDE main form to avoid nesting --}}
            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('gallery', 'manage'))
                <form id="edit-qcat-form" method="POST" action="{{ route('admin.artwork-gallery.categories.store') }}" class="hidden">
                    @csrf
                    <input type="hidden" name="_redirect_back" value="1">
                </form>
                <form id="edit-qtag-form" method="POST" action="{{ route('admin.artwork-gallery.tags.store') }}" class="hidden">
                    @csrf
                    <input type="hidden" name="_redirect_back" value="1">
                </form>
            @endif
        </section>
    </section>

    <aside class="space-y-6">
        <section class="card p-5">
            <h3 class="text-sm font-semibold text-slate-900">Temel bilgi</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-400">Stok Kodu</dt>
                    <dd class="font-mono font-semibold text-slate-900">{{ $artworkGallery->stock_code ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Yükleyen</dt>
                    <dd class="text-slate-900">{{ $artworkGallery->uploadedBy->name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Kategori</dt>
                    <dd class="text-slate-900">{{ $artworkGallery->category?->display_name ?? 'Kategorisiz' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Kullanım sayısı</dt>
                    <dd class="text-slate-900">{{ $artworkGallery->usage_count }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Son kullanım</dt>
                    <dd class="text-slate-900">{{ $artworkGallery->last_used_at ? \Illuminate\Support\Carbon::parse($artworkGallery->last_used_at)->format('d.m.Y H:i') : 'Henüz kullanılmadı' }}</dd>
                </div>
            </dl>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">Kullanım geçmişi</h3>
            </div>
            <div class="max-h-[520px] divide-y divide-slate-100 overflow-y-auto">
                @forelse($artworkGallery->usages as $usage)
                    <div class="px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="badge {{ $usage->usage_type === 'reuse' ? 'badge-info' : 'badge-success' }}">{{ strtoupper($usage->usage_type) }}</span>
                            <span class="text-xs text-slate-400">{{ $usage->used_at?->format('d.m.Y H:i') }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-900">
                            {{ $usage->supplier?->name ?? 'Tedarikçi yok' }}
                            @if($usage->order)
                                · {{ $usage->order->order_no }}
                            @endif
                            @if($usage->line)
                                · {{ $usage->line->product_code }} / Satır {{ $usage->line->line_no }}
                            @endif
                        </p>
                    </div>
                @empty
                    <div class="px-5 py-8 text-sm text-slate-500">Henüz kullanım kaydı yok.</div>
                @endforelse
            </div>
        </section>
    </aside>
</div>

@include('artwork-gallery.partials.preview-dialog', [
    'artworkGallery' => $artworkGallery,
    'dialogId' => 'gallery-preview-' . $artworkGallery->id,
])
@endsection
