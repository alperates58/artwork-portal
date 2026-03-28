{{-- Notification Bell Dropdown --}}
<div
    class="relative"
    x-data="notificationBell()"
    x-init="init()"
    @click.outside="open = false"
>
    <button
        type="button"
        @click="toggle()"
        class="relative inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
        title="Bildirimler"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span
            x-show="unread > 0"
            x-text="unread > 9 ? '9+' : unread"
            class="absolute -right-1 -top-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-0.5 text-[10px] font-bold leading-none text-white"
        ></span>
    </button>

    {{-- Dropdown panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
        class="absolute right-0 top-full z-50 mt-2 w-80 origin-top-right overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xl"
        x-cloak
    >
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <span class="text-sm font-semibold text-slate-800">Bildirimler</span>
            <button
                x-show="unread > 0"
                @click="markRead()"
                class="text-xs text-violet-600 hover:underline"
            >Tümünü okundu işaretle</button>
        </div>

        {{-- List --}}
        <div class="max-h-[380px] overflow-y-auto">
            <div x-show="loading" class="flex items-center justify-center gap-2 py-8 text-sm text-slate-400">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
            </div>

            <div x-show="!loading && items.length === 0" class="py-10 text-center text-sm text-slate-400">
                Bildirim yok
            </div>

            <template x-if="!loading && items.length > 0">
                <ul class="divide-y divide-slate-50">
                    <template x-for="item in items" :key="item.id">
                        <li>
                            <a
                                :href="item.url || '#'"
                                class="flex items-start gap-3 px-4 py-3 transition hover:bg-slate-50"
                                :class="{'bg-violet-50/50': !item.read}"
                                @click="open = false"
                            >
                                {{-- Dot indicator --}}
                                <span
                                    class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full"
                                    :class="item.read ? 'bg-slate-200' : 'bg-violet-500'"
                                ></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-slate-800" x-text="item.title"></p>
                                    <p class="mt-0.5 text-xs text-slate-500" x-text="item.body" x-show="item.body"></p>
                                    <p class="mt-1 text-[11px] text-slate-400" x-text="item.created_at"></p>
                                </div>
                            </a>
                        </li>
                    </template>
                </ul>
            </template>
        </div>
    </div>
</div>

<script>
function notificationBell() {
    return {
        open: false,
        loading: false,
        unread: 0,
        items: [],
        _pollTimer: null,

        async fetchNotifications() {
            this.loading = true;
            try {
                const res = await fetch('/bildirimler', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.unread = data.unread ?? 0;
                this.items  = data.items ?? [];
            } catch {
                // silent fail
            } finally {
                this.loading = false;
            }
        },

        async toggle() {
            this.open = !this.open;
            if (this.open && this.items.length === 0) {
                await this.fetchNotifications();
            }
        },

        async markRead() {
            await fetch('/bildirimler/okundu', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                }
            });
            this.unread = 0;
            this.items = this.items.map(n => ({ ...n, read: true }));
        },

        init() {
            // Initial unread count
            this.fetchNotifications();
            // Poll every 60 seconds for new notifications
            this._pollTimer = setInterval(() => this.fetchNotifications(), 60000);
        },
    };
}
</script>
