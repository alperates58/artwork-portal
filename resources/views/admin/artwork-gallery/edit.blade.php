@extends('layouts.app')
@section('title', 'Artwork Galerisi')
@section('page-title', 'Artwork Galerisi Detayi')
@section('page-subtitle', 'Kategori, etiket ve kullanim gecmisi yonetimi.')
@section('header-actions')
    <a href="{{ route('admin.artwork-gallery.index') }}" class="btn-secondary">← Galeriye Don</a>
@endsection

@section('content')
<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
    <section class="card p-6 space-y-5">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ $artworkGallery->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $artworkGallery->file_size_formatted }} · {{ $artworkGallery->file_disk }} · {{ $artworkGallery->created_at->format('d.m.Y H:i') }}
            </p>
        </div>

        <form method="POST" action="{{ route('admin.artwork-gallery.update', $artworkGallery) }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <label class="label" for="name">Adi</label>
                <input id="name" name="name" class="input" value="{{ old('name', $artworkGallery->name) }}">
            </div>

            <div>
                <label class="label" for="category_id">Kategori</label>
                <select id="category_id" name="category_id" class="input">
                    <option value="">Kategori secin</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $artworkGallery->category_id) === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="tag_ids">Etiketler</label>
                <select id="tag_ids" name="tag_ids[]" class="input min-h-36" multiple>
                    @foreach($tags as $tag)
                        <option value="{{ $tag->id }}" @selected(collect(old('tag_ids', $artworkGallery->tags->pluck('id')->all()))->contains($tag->id))>{{ $tag->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="revision_note">Revizyon notu</label>
                <textarea id="revision_note" name="revision_note" rows="4" class="input resize-none">{{ old('revision_note', $artworkGallery->revision_note) }}</textarea>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                Yolu: <span class="font-mono text-xs">{{ $artworkGallery->file_path }}</span>
            </div>

            <button type="submit" class="btn-primary">Kaydet</button>
        </form>
    </section>

    <aside class="space-y-6">
        <section class="card p-5">
            <h3 class="text-sm font-semibold text-slate-900">Temel Bilgi</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-400">Yukleyen</dt>
                    <dd class="text-slate-900">{{ $artworkGallery->uploadedBy->name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Kategori</dt>
                    <dd class="text-slate-900">{{ $artworkGallery->category?->name ?? 'Kategorisiz' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Toplam kullanim</dt>
                    <dd class="text-slate-900">{{ $artworkGallery->usages->count() }}</dd>
                </div>
            </dl>
        </section>

        <section class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">Kullanim Gecmisi</h3>
            </div>
            <div class="divide-y divide-slate-100 max-h-[520px] overflow-y-auto">
                @forelse($artworkGallery->usages as $usage)
                    <div class="px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="badge {{ $usage->usage_type === 'reuse' ? 'badge-info' : 'badge-success' }}">{{ strtoupper($usage->usage_type) }}</span>
                            <span class="text-xs text-slate-400">{{ $usage->used_at?->format('d.m.Y H:i') }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-900">
                            {{ $usage->supplier?->name ?? 'Tedarikci yok' }}
                            @if($usage->order)
                                · {{ $usage->order->order_no }}
                            @endif
                            @if($usage->line)
                                · {{ $usage->line->product_code }} / Satir {{ $usage->line->line_no }}
                            @endif
                        </p>
                    </div>
                @empty
                    <div class="px-5 py-8 text-sm text-slate-500">Henuz kullanim kaydi yok.</div>
                @endforelse
            </div>
        </section>
    </aside>
</div>
@endsection
