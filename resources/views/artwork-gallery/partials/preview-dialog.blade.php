<dialog id="{{ $dialogId }}" class="max-h-[92vh] w-[min(96vw,1440px)] max-w-none overflow-hidden rounded-[32px] border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/60">
    <div class="flex h-[min(92vh,960px)] min-h-0 flex-col">
    <div class="shrink-0 border-b border-slate-200 px-6 py-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Artwork galerisi</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <h3 class="break-words text-2xl font-semibold text-slate-950">{{ $artworkGallery->display_name }}</h3>
                    @include('artworks.partials.passive-gallery-badge', ['galleryItem' => $artworkGallery])
                </div>
                <p class="mt-2 text-sm text-slate-500">{{ $artworkGallery->file_type_description }} · {{ $artworkGallery->file_size_formatted }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('artworks.gallery.download', $artworkGallery) }}" class="btn btn-primary px-4 text-sm">
                    <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    İndir
                </a>
                <button type="button" data-dialog-close class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900">
                    <span class="sr-only">Kapat</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="grid min-h-0 flex-1 gap-6 overflow-y-auto px-6 py-6 xl:grid-cols-[minmax(0,1.8fr)_340px]">
        <section class="min-h-0 space-y-3">
            <div class="rounded-[28px] border border-slate-200 bg-slate-50 p-3">
                @if($artworkGallery->has_preview)
                    <button type="button" data-dialog-open="{{ $dialogId }}-zoom" class="group relative block h-full max-h-[calc(92vh-15rem)] w-full overflow-hidden rounded-[24px] bg-white text-left">
                        <img
                            src="{{ route('artworks.gallery.preview', $artworkGallery, false) }}"
                            alt="{{ $artworkGallery->display_name }}"
                            class="h-full w-full object-contain transition duration-300 group-hover:scale-[1.01]"
                        >
                    </button>
                @else
                    <div class="flex min-h-[28rem] items-center justify-center rounded-[24px] bg-white">
                        @include('artwork-gallery.partials.file-visual', [
                            'artworkGallery' => $artworkGallery,
                            'sizeClass' => 'h-40 w-40',
                            'roundedClass' => 'rounded-[2rem]',
                        ])
                    </div>
                @endif
            </div>
        </section>

        <aside class="space-y-4 xl:max-h-full xl:overflow-y-auto xl:pr-1">
            <div class="rounded-[28px] border border-slate-200 bg-white p-5">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Stok kodu / Revizyon</dt>
                        <dd class="mt-1 font-mono text-base font-semibold text-slate-900">
                            {{ $artworkGallery->stock_code ?: '—' }} · Rev.{{ $artworkGallery->revision_no ?: '—' }}
                        </dd>
                    </div>
                    @if($artworkGallery->stockCard?->stock_name)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Stok adı</dt>
                        <dd class="mt-1 text-base text-slate-900">{{ $artworkGallery->stockCard->stock_name }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kategori</dt>
                        <dd class="mt-1 text-base text-slate-900">{{ $artworkGallery->category?->display_name ?? 'Kategorisiz' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Dosya boyutu</dt>
                        <dd class="mt-1 text-base text-slate-900">{{ $artworkGallery->file_size_formatted }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Dosya tipi</dt>
                        <dd class="mt-1 text-base text-slate-900">{{ $artworkGallery->file_type_display }} · {{ $artworkGallery->file_type_description }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Yükleyen</dt>
                        <dd class="mt-1 text-base text-slate-900">{{ $artworkGallery->uploadedBy->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Oluşturulma</dt>
                        <dd class="mt-1 text-base text-slate-900">{{ $artworkGallery->created_at->format('d.m.Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Son kullanım</dt>
                        <dd class="mt-1 text-base text-slate-900">{{ $artworkGallery->last_used_at ? \Illuminate\Support\Carbon::parse($artworkGallery->last_used_at)->format('d.m.Y H:i') : 'Henüz kullanılmadı' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kullanım sayısı</dt>
                        <dd class="mt-1 text-base text-slate-900">{{ $artworkGallery->usage_count }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-[28px] border border-slate-200 bg-slate-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Revizyon notu</p>
                <p class="mt-2 text-sm leading-6 text-slate-700">{{ $artworkGallery->display_revision_note ?: 'Not bulunmuyor.' }}</p>
            </div>
        </aside>
    </div>
    </div>
</dialog>

@if($artworkGallery->has_preview)
    <dialog id="{{ $dialogId }}-zoom" class="max-h-[94vh] w-[min(96vw,1500px)] max-w-none overflow-hidden rounded-[32px] border border-white/10 bg-slate-950/95 p-0 shadow-2xl backdrop:bg-slate-950/85">
        <div class="relative flex h-[min(94vh,980px)] min-h-0 flex-col">
            <button type="button" data-dialog-close class="absolute right-3 top-3 z-10 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/95 text-slate-600 shadow-sm transition hover:bg-white hover:text-slate-950">
                <span class="sr-only">Kapat</span>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div class="min-h-0 flex-1 overflow-auto p-3">
                <img
                    src="{{ route('artworks.gallery.preview', $artworkGallery, false) }}"
                    alt="{{ $artworkGallery->display_name }}"
                    class="h-full w-full rounded-[24px] bg-white object-contain"
                >
            </div>
        </div>
    </dialog>
@endif
