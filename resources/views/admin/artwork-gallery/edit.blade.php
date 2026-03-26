@extends('layouts.app')
@section('title', 'Artwork Galerisi')
@section('page-title', 'Artwork Galerisi Detayı')
@section('page-subtitle', 'Kategori, etiket ve kullanım geçmişi yönetimi.')

@section('header-actions')
    <a href="{{ route('admin.artwork-gallery.index') }}" class="btn-secondary">← Galeriye dön</a>
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
                        <button type="button" class="btn-secondary" data-dialog-open="gallery-preview-{{ $artworkGallery->id }}">Görüntüle</button>
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

                <div>
                    <label class="label" for="name">Dosya adı</label>
                    <input id="name" name="name" class="input" value="{{ old('name', $artworkGallery->name) }}">
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="label" for="category_id">Kategori</label>
                        <select id="category_id" name="category_id" class="input">
                            <option value="">Kategori seçin</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $artworkGallery->category_id) === (string) $category->id)>{{ $category->display_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label" for="tag_ids">Etiketler</label>
                        <select id="tag_ids" name="tag_ids[]" class="input min-h-36" multiple>
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}" @selected(collect(old('tag_ids', $artworkGallery->tags->pluck('id')->all()))->contains($tag->id))>{{ $tag->display_name }}</option>
                            @endforeach
                        </select>
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

                <button type="submit" class="btn-primary">Kaydet</button>
            </form>
        </section>
    </section>

    <aside class="space-y-6">
        <section class="card p-5">
            <h3 class="text-sm font-semibold text-slate-900">Temel bilgi</h3>
            <dl class="mt-4 space-y-3 text-sm">
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
