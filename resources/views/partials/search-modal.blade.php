{{-- Global Search Modal (Ctrl+K) --}}
<div
    id="search-modal"
    class="fixed inset-0 z-[200] hidden"
    role="dialog"
    aria-modal="true"
    aria-label="Global Arama"
    x-data="searchModal()"
    x-show="open"
    x-cloak
    @keydown.escape.window="close()"
    @search-open.window="openModal()"
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
        @click="close()"
    ></div>

    {{-- Modal panel --}}
    <div class="relative z-10 mx-auto mt-[12vh] max-w-xl px-4">
        <div class="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-2xl">

            {{-- Search input --}}
            <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3.5">
                <svg class="h-5 w-5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
                <input
                    id="search-input"
                    type="text"
                    placeholder="Sipariş, tedarikçi veya tasarım ara…"
                    class="flex-1 bg-transparent text-sm text-slate-800 placeholder-slate-400 outline-none"
                    autocomplete="off"
                    x-model="query"
                    @input.debounce.250ms="fetch()"
                    x-ref="input"
                />
                <kbd class="hidden rounded border border-slate-200 bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-400 sm:inline">ESC</kbd>
            </div>

            {{-- Results --}}
            <div id="search-results" class="max-h-[52vh] overflow-y-auto">

                {{-- Empty state --}}
                <div x-show="!loading && query.length >= 2 && results.length === 0" class="py-10 text-center text-sm text-slate-400">
                    Sonuç bulunamadı
                </div>

                {{-- Hint --}}
                <div x-show="query.length < 2" class="py-6 text-center text-sm text-slate-400">
                    En az 2 karakter yazın
                </div>

                {{-- Loading --}}
                <div x-show="loading" class="flex items-center justify-center gap-2 py-8 text-sm text-slate-400">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    Aranıyor…
                </div>

                {{-- Result items --}}
                <template x-if="!loading && results.length > 0">
                    <ul class="divide-y divide-slate-50 py-1">
                        <template x-for="(item, index) in results" :key="index">
                            <li>
                                <a
                                    :href="item.url"
                                    class="flex items-center gap-3 px-4 py-3 text-sm transition hover:bg-slate-50"
                                    @click="close()"
                                >
                                    {{-- Type icon --}}
                                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg"
                                         :class="{
                                            'bg-violet-100 text-violet-600': item.type === 'order',
                                            'bg-blue-100 text-blue-600': item.type === 'supplier',
                                            'bg-emerald-100 text-emerald-600': item.type === 'artwork',
                                         }">
                                        {{-- Order icon --}}
                                        <svg x-show="item.type === 'order'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        {{-- Supplier icon --}}
                                        <svg x-show="item.type === 'supplier'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        {{-- Artwork icon --}}
                                        <svg x-show="item.type === 'artwork'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="truncate font-medium text-slate-800" x-text="item.label"></div>
                                        <div class="truncate text-xs text-slate-400" x-text="item.sub" x-show="item.sub"></div>
                                    </div>

                                    <span
                                        x-show="item.badge"
                                        x-text="item.badge"
                                        class="flex-shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                        :class="{
                                            'bg-violet-50 text-violet-700': item.type === 'order',
                                            'bg-blue-50 text-blue-700': item.type === 'supplier',
                                            'bg-emerald-50 text-emerald-700': item.type === 'artwork',
                                        }"
                                    ></span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </template>
            </div>

            {{-- Footer --}}
            <div class="flex items-center gap-4 border-t border-slate-100 bg-slate-50/50 px-4 py-2.5 text-[11px] text-slate-400">
                <span><kbd class="rounded border border-slate-200 bg-white px-1.5 py-0.5 font-medium">↑↓</kbd> gezin</span>
                <span><kbd class="rounded border border-slate-200 bg-white px-1.5 py-0.5 font-medium">↵</kbd> aç</span>
                <span><kbd class="rounded border border-slate-200 bg-white px-1.5 py-0.5 font-medium">ESC</kbd> kapat</span>
            </div>
        </div>
    </div>
</div>

<script>
function searchModal() {
    return {
        open: false,
        query: '',
        results: [],
        loading: false,
        _ctrl: null,

        openModal() {
            this.open = true;
            this.$nextTick(() => this.$refs.input?.focus());
        },

        close() {
            this.open = false;
            this.query = '';
            this.results = [];
        },

        async fetch() {
            if (this.query.length < 2) {
                this.results = [];
                return;
            }
            this.loading = true;
            try {
                const res = await fetch(`/search?q=${encodeURIComponent(this.query)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.results = data.results ?? [];
            } catch {
                this.results = [];
            } finally {
                this.loading = false;
            }
        },

        init() {
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    this.openModal();
                }
            });
        },
    };
}
</script>
