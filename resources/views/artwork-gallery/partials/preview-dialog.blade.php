<dialog id="{{ $dialogId }}" class="w-full max-w-4xl rounded-3xl border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/40">
    <div class="border-b border-slate-200 px-6 py-4">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Artwork galerisi</p>
                <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $artworkGallery->display_name }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $artworkGallery->file_type_description }} · {{ $artworkGallery->file_size_formatted }}</p>
            </div>
            <button type="button" class="btn-secondary px-3" data-dialog-close>Kapat</button>
        </div>
    </div>

    <div class="grid gap-6 px-6 py-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(280px,1fr)]">
        <section class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
            @if($artworkGallery->is_image)
                <img
                    src="{{ route('artworks.gallery.preview', $artworkGallery) }}"
                    alt="{{ $artworkGallery->display_name }}"
                    class="max-h-[28rem] w-full rounded-2xl bg-white object-contain"
                >
            @else
                <div class="flex min-h-[18rem] items-center justify-center rounded-2xl bg-white">
                    @include('artwork-gallery.partials.file-visual', [
                        'artworkGallery' => $artworkGallery,
                        'sizeClass' => 'h-32 w-32',
                        'roundedClass' => 'rounded-[2rem]',
                    ])
                </div>
            @endif
        </section>

        <section class="space-y-5">
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Dosya adı</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $artworkGallery->display_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kategori</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $artworkGallery->category?->display_name ?? 'Kategorisiz' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Etiketler</dt>
                    <dd class="mt-2 flex flex-wrap gap-2">
                        @forelse($artworkGallery->tags as $tag)
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $tag->display_name }}</span>
                        @empty
                            <span class="text-sm text-slate-500">Etiket yok</span>
                        @endforelse
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Yükleyen</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $artworkGallery->uploadedBy->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Oluşturulma</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $artworkGallery->created_at->format('d.m.Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Son kullanım</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $artworkGallery->last_used_at ? \Illuminate\Support\Carbon::parse($artworkGallery->last_used_at)->format('d.m.Y H:i') : 'Henüz kullanılmadı' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kullanım sayısı</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $artworkGallery->usage_count }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Dosya tipi</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $artworkGallery->file_type_display }} · {{ $artworkGallery->file_type_description }}</dd>
                </div>
            </dl>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Revizyon notu</p>
                <p class="mt-1 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    {{ $artworkGallery->display_revision_note ?: 'Not bulunmuyor.' }}
                </p>
            </div>
        </section>
    </div>
</dialog>
