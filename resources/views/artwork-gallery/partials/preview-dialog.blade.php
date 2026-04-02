@php
    $canManageGallery = auth()->check() && (auth()->user()->isAdmin() || auth()->user()->hasPermission('gallery', 'manage'));
    $usageHistoryUrl = $artworkGallery->usage_count > 0
        ? ($canManageGallery
            ? route('admin.artwork-gallery.edit', $artworkGallery) . '#usage-history'
            : route('admin.artwork-gallery.index', ['stock_code' => $artworkGallery->stock_code, 'status' => 'all']))
        : null;
@endphp

<dialog id="{{ $dialogId }}" class="m-auto w-full max-w-[calc(100vw-1rem)] overflow-hidden border-0 bg-transparent p-2 shadow-none backdrop:bg-slate-950/60 sm:max-w-[min(96vw,1440px)] sm:p-4">
    <div class="flex max-h-[calc(100dvh-1rem)] min-h-0 flex-col overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-2xl sm:max-h-[92vh] sm:rounded-[32px]">
        <div class="shrink-0 border-b border-slate-200 px-4 py-4 sm:px-6 sm:py-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Artwork galerisi</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <h3 class="break-words text-lg font-semibold text-slate-950 sm:text-xl xl:text-2xl">{{ $artworkGallery->display_name }}</h3>
                        @include('artworks.partials.passive-gallery-badge', ['galleryItem' => $artworkGallery])
                    </div>
                    <p class="mt-2 break-words text-xs font-medium text-slate-500 sm:text-sm">
                        Orijinal dosya · {{ $artworkGallery->file_size_formatted }} · {{ $artworkGallery->file_type_display }}
                    </p>
                </div>

                <div class="flex w-full items-center gap-3 sm:w-auto sm:justify-end">
                    <a href="{{ route('artworks.gallery.download', $artworkGallery) }}" class="btn btn-primary min-w-0 flex-1 justify-center px-4 text-sm sm:flex-none">
                        <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        İndir
                    </a>
                    <button type="button" data-dialog-close class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900">
                        <span class="sr-only">Kapat</span>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="grid min-h-0 flex-1 gap-4 overflow-y-auto overscroll-contain px-4 py-4 sm:px-5 sm:py-5 xl:grid-cols-[minmax(0,1.9fr)_310px] xl:gap-5">
            <section class="min-h-0 min-w-0 space-y-3">
                <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-2 sm:rounded-[28px] sm:p-3">
                    @if($artworkGallery->has_preview)
                        <button type="button" data-dialog-open="{{ $dialogId }}-zoom" class="group relative flex h-[min(42dvh,22rem)] w-full items-center justify-center overflow-hidden rounded-[20px] bg-white p-2 text-left sm:h-[min(54dvh,30rem)] sm:rounded-[24px] sm:p-4 xl:h-[calc(92vh-19rem)] xl:min-h-[24rem]">
                            <img
                                src="{{ route('artworks.gallery.preview', $artworkGallery, false) }}"
                                alt="{{ $artworkGallery->display_name }}"
                                class="block h-full w-full max-h-full max-w-full object-contain transition duration-300 group-hover:scale-[1.01]"
                            >
                        </button>
                    @else
                        <div class="flex h-[min(42dvh,22rem)] items-center justify-center rounded-[20px] bg-white p-4 sm:h-[min(54dvh,30rem)] sm:rounded-[24px] xl:h-[calc(92vh-19rem)] xl:min-h-[24rem]">
                            @include('artwork-gallery.partials.file-visual', [
                                'artworkGallery' => $artworkGallery,
                                'sizeClass' => 'h-32 w-32 sm:h-40 sm:w-40',
                                'roundedClass' => 'rounded-[2rem]',
                            ])
                        </div>
                    @endif
                </div>
            </section>

            <aside class="min-w-0 space-y-3 xl:max-h-full xl:overflow-y-auto xl:pr-1">
                <div class="rounded-[24px] border border-slate-200 bg-white p-4 sm:rounded-[28px]">
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Stok kodu / Revizyon</dt>
                            <dd class="mt-1 break-all font-mono text-sm font-semibold text-slate-900 sm:text-[15px]">
                                {{ $artworkGallery->stock_code ?: '—' }} · Rev.{{ $artworkGallery->revision_no ?: '—' }}
                            </dd>
                        </div>

                        @if($artworkGallery->stockCard?->stock_name)
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Stok adı</dt>
                                <dd class="mt-1 break-words text-sm leading-6 text-slate-900">{{ $artworkGallery->stockCard->stock_name }}</dd>
                            </div>
                        @endif

                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Kategori</dt>
                            <dd class="mt-1 break-words text-sm text-slate-900">{{ $artworkGallery->category?->display_name ?? 'Kategorisiz' }}</dd>
                        </div>

                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Orijinal dosya boyutu</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $artworkGallery->file_size_formatted }}</dd>
                        </div>

                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Dosya tipi</dt>
                            <dd class="mt-1 break-words text-sm text-slate-900">{{ $artworkGallery->file_type_display }} · {{ $artworkGallery->file_type_description }}</dd>
                        </div>

                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Yükleyen</dt>
                            <dd class="mt-1 break-words text-sm text-slate-900">{{ $artworkGallery->uploadedBy->name }}</dd>
                        </div>

                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Oluşturulma</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $artworkGallery->created_at->format('d.m.Y H:i') }}</dd>
                        </div>

                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Son kullanım</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $artworkGallery->last_used_at ? \Illuminate\Support\Carbon::parse($artworkGallery->last_used_at)->format('d.m.Y H:i') : 'Henüz kullanılmadı' }}</dd>
                        </div>

                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Kullanım sayısı</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $artworkGallery->usage_count }}</dd>
                        </div>
                    </dl>

                    @if($usageHistoryUrl)
                        <a href="{{ $usageHistoryUrl }}" class="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-brand-200 bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-700 transition hover:border-brand-300 hover:bg-brand-100 sm:w-auto">
                            Sipariş Geçmişi
                        </a>
                    @endif
                </div>

                <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4 sm:rounded-[28px]">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Revizyon notu</p>
                    <p class="mt-2 break-words text-sm leading-5 text-slate-700">{{ $artworkGallery->display_revision_note ?: 'Not bulunmuyor.' }}</p>
                </div>
            </aside>
        </div>
    </div>
</dialog>

@if($artworkGallery->has_preview)
    <dialog id="{{ $dialogId }}-zoom" class="m-auto w-full max-w-[calc(100vw-1rem)] overflow-hidden border-0 bg-transparent p-2 shadow-none backdrop:bg-slate-950/85 sm:max-w-[min(96vw,1500px)] sm:p-4">
        <div class="relative flex max-h-[calc(100dvh-1rem)] min-h-0 flex-col overflow-hidden rounded-[24px] border border-white/10 bg-slate-950/95 shadow-2xl sm:max-h-[94vh] sm:rounded-[32px]">
            <button type="button" data-dialog-close class="absolute right-2 top-2 z-10 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/95 text-slate-600 shadow-sm transition hover:bg-white hover:text-slate-950 sm:right-3 sm:top-3">
                <span class="sr-only">Kapat</span>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <div class="min-h-0 flex-1 overflow-auto overscroll-contain p-2 sm:p-3">
                <div class="flex min-h-full items-center justify-center">
                    <img
                        src="{{ route('artworks.gallery.preview', $artworkGallery, false) }}"
                        alt="{{ $artworkGallery->display_name }}"
                        class="block h-auto max-h-full w-auto max-w-full rounded-[20px] bg-white object-contain sm:rounded-[24px]"
                    >
                </div>
            </div>
        </div>
    </dialog>
@endif
