{{-- Theme Switcher --}}
<div
    class="relative"
    x-data="themeSwitcher()"
    x-init="init()"
    @click.outside="open = false"
>
    <button
        type="button"
        @click="open = !open"
        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
        title="Tema Seç"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 top-full z-50 mt-2 w-52 origin-top-right overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-2 shadow-xl"
        x-cloak
    >
        <p class="mb-1.5 px-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Tema</p>
        <template x-for="t in themes" :key="t.id">
            <button
                type="button"
                @click="apply(t); open = false"
                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition hover:bg-slate-50"
                :class="current === t.id ? 'bg-slate-100 font-semibold' : ''"
            >
                <span class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full"
                      :style="`background: ${t.color}`"></span>
                <span x-text="t.label" class="flex-1 text-left text-slate-700"></span>
                <svg x-show="current === t.id" class="h-3.5 w-3.5 text-slate-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
            </button>
        </template>
    </div>
</div>

<script>
const PORTAL_THEMES = [
    {
        id: 'violet',
        label: 'Violet (Varsayılan)',
        color: '#8b5cf6',
        vars: {
            '--brand-500': '#8b5cf6',
            '--brand-600': '#7c3aed',
            '--brand-700': '#6d28d9',
            '--ring': 'rgba(139,92,246,0.22)',
            '--sidebar-bg': '#1a2035',
            '--sidebar-active-bg': 'rgba(139,92,246,0.18)',
            '--sidebar-active-border': '#8b5cf6',
            '--btn-primary-from': '#8b5cf6',
            '--btn-primary-to': '#6d28d9',
            '--btn-primary-shadow': 'rgba(124,58,237,0.32)',
        },
        sidebar: '#1a2035',
        btnFrom: '#8b5cf6',
        btnTo: '#6d28d9',
    },
    {
        id: 'rose',
        label: 'Pembe / Lotus',
        color: '#f43f5e',
        vars: {
            '--brand-500': '#f43f5e',
            '--brand-600': '#e11d48',
            '--brand-700': '#be123c',
            '--ring': 'rgba(244,63,94,0.22)',
            '--sidebar-bg': '#1f1520',
            '--sidebar-active-bg': 'rgba(244,63,94,0.18)',
            '--sidebar-active-border': '#f43f5e',
            '--btn-primary-from': '#f43f5e',
            '--btn-primary-to': '#be123c',
            '--btn-primary-shadow': 'rgba(225,29,72,0.32)',
        },
        sidebar: '#1f1520',
        btnFrom: '#f43f5e',
        btnTo: '#be123c',
    },
    {
        id: 'teal',
        label: 'Yeşil / Yaprak',
        color: '#0d9488',
        vars: {
            '--brand-500': '#0d9488',
            '--brand-600': '#0f766e',
            '--brand-700': '#115e59',
            '--ring': 'rgba(13,148,136,0.22)',
            '--sidebar-bg': '#132621',
            '--sidebar-active-bg': 'rgba(13,148,136,0.18)',
            '--sidebar-active-border': '#0d9488',
            '--btn-primary-from': '#0d9488',
            '--btn-primary-to': '#115e59',
            '--btn-primary-shadow': 'rgba(15,118,110,0.32)',
        },
        sidebar: '#132621',
        btnFrom: '#0d9488',
        btnTo: '#115e59',
    },
    {
        id: 'amber',
        label: 'Altın / Sıcak',
        color: '#d97706',
        vars: {
            '--brand-500': '#d97706',
            '--brand-600': '#b45309',
            '--brand-700': '#92400e',
            '--ring': 'rgba(217,119,6,0.22)',
            '--sidebar-bg': '#1c1507',
            '--sidebar-active-bg': 'rgba(217,119,6,0.18)',
            '--sidebar-active-border': '#d97706',
            '--btn-primary-from': '#f59e0b',
            '--btn-primary-to': '#b45309',
            '--btn-primary-shadow': 'rgba(180,83,9,0.32)',
        },
        sidebar: '#1c1507',
        btnFrom: '#f59e0b',
        btnTo: '#b45309',
    },
    {
        id: 'navy',
        label: 'Lacivert / Klasik',
        color: '#2563eb',
        vars: {
            '--brand-500': '#3b82f6',
            '--brand-600': '#2563eb',
            '--brand-700': '#1d4ed8',
            '--ring': 'rgba(59,130,246,0.22)',
            '--sidebar-bg': '#0f172a',
            '--sidebar-active-bg': 'rgba(59,130,246,0.18)',
            '--sidebar-active-border': '#3b82f6',
            '--btn-primary-from': '#3b82f6',
            '--btn-primary-to': '#1d4ed8',
            '--btn-primary-shadow': 'rgba(37,99,235,0.32)',
        },
        sidebar: '#0f172a',
        btnFrom: '#3b82f6',
        btnTo: '#1d4ed8',
    },
];

function themeSwitcher() {
    return {
        open: false,
        themes: PORTAL_THEMES,
        current: 'violet',

        apply(theme) {
            this.current = theme.id;
            localStorage.setItem('portal_theme', theme.id);
            this._applyVars(theme);
        },

        _applyVars(theme) {
            const root = document.documentElement;
            // CSS vars
            Object.entries(theme.vars).forEach(([k, v]) => root.style.setProperty(k, v));
            // Sidebar bg
            const sb = document.getElementById('main-sidebar');
            if (sb) sb.style.background = theme.sidebar;
            // Sidebar active links (runtime)
            document.querySelectorAll('#main-sidebar .sidebar-link.active').forEach(el => {
                el.style.setProperty('box-shadow', `inset 3px 0 0 ${theme.vars['--sidebar-active-border']}`);
                el.style.setProperty('background', theme.vars['--sidebar-active-bg']);
            });
            // Btn-primary via injected style
            let styleEl = document.getElementById('__theme_style');
            if (!styleEl) {
                styleEl = document.createElement('style');
                styleEl.id = '__theme_style';
                document.head.appendChild(styleEl);
            }
            const from = theme.vars['--btn-primary-from'] || theme.vars['--brand-500'];
            const to = theme.vars['--btn-primary-to'] || theme.vars['--brand-700'];
            const shadow = theme.vars['--btn-primary-shadow'] || 'rgba(0,0,0,0.2)';
            const brand5 = theme.vars['--brand-500'];
            const brand6 = theme.vars['--brand-600'];
            const brand7 = theme.vars['--brand-700'];
            styleEl.textContent = `
                .btn-primary, button.btn-primary, a.btn-primary {
                    background: linear-gradient(170deg, ${from}, ${to}) !important;
                    box-shadow: 0 2px 8px ${shadow}, 0 1px 3px ${shadow} !important;
                }
                .btn-primary:hover, button.btn-primary:hover, a.btn-primary:hover {
                    background: linear-gradient(170deg, ${from}dd, ${to}) !important;
                }
                #main-sidebar .sidebar-link.active {
                    background: ${theme.vars['--sidebar-active-bg']} !important;
                    color: #fff !important;
                    box-shadow: inset 3px 0 0 ${theme.vars['--sidebar-active-border']} !important;
                }
                #main-sidebar [data-nav-group-chevron].rotate-180 { color: ${brand5} !important; }
                .text-brand-500 { color: ${brand5}; }
                .text-brand-600 { color: ${brand6}; }
                .bg-brand-500 { background-color: ${brand5}; }
                .bg-brand-600 { background-color: ${brand6}; }
                .border-brand-200 { border-color: ${brand7}33; }
                .badge-info { border-color: ${brand5}33; background: ${brand5}11; color: ${brand6}; }
                input:focus, textarea:focus, select:focus { box-shadow: 0 0 0 4px ${theme.vars['--ring']} !important; border-color: ${brand5} !important; }
            `;
        },

        init() {
            const saved = localStorage.getItem('portal_theme') || 'violet';
            const theme = PORTAL_THEMES.find(t => t.id === saved) || PORTAL_THEMES[0];
            this.current = theme.id;
            this._applyVars(theme);
        },
    };
}
</script>
