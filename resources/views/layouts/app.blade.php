<!DOCTYPE html>
<html lang="tr">
<head>
    @include('partials.ui-head', ['title' => trim($__env->yieldContent('title', config('portal.brand_name')))])
    <style>
        /* ── Desktop: Sidebar transitions ── */
        #sidebar-wrap {
            width: 272px;
            flex-shrink: 0;
            transition: width .25s cubic-bezier(.4,0,.2,1);
        }
        #main-sidebar {
            width: 272px;
            transition: width .25s cubic-bezier(.4,0,.2,1);
            overflow: hidden;
            background: #1a2035;
        }

        /* ── Collapsed: icon strip (88px) ── */
        #sidebar-wrap.collapsed,
        #sidebar-wrap.collapsed #main-sidebar {
            width: 88px;
        }

        /* Hide text labels when collapsed */
        #sidebar-wrap.collapsed .sb-label,
        #sidebar-wrap.collapsed .sb-section-title,
        #sidebar-wrap.collapsed .sb-logo-text,
        #sidebar-wrap.collapsed .sb-user-info,
        #sidebar-wrap.collapsed .sb-chevron,
        #sidebar-wrap.collapsed .nav-group-items {
            display: none !important;
        }

        /* Center icons when collapsed */
        #sidebar-wrap.collapsed .sidebar-link,
        #sidebar-wrap.collapsed [data-nav-group-toggle] {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }

        /* Logo: center icon when collapsed */
        #sidebar-wrap.collapsed .sb-logo-wrap {
            padding: 0.75rem 0.25rem;
            justify-content: center;
            border: none;
            box-shadow: none;
            background: transparent;
        }
        #sidebar-wrap.collapsed .sb-logo-expanded {
            display: none !important;
        }
        #sidebar-wrap.collapsed .sb-logo-collapsed {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        /* User footer when collapsed */
        #sidebar-wrap.collapsed .sb-footer-expanded {
            display: none;
        }
        #sidebar-wrap.collapsed .sb-footer-collapsed {
            display: block !important;
        }

        /* ── Mobile: sidebar as overlay ── */
        @media (max-width: 1023px) {
            #sidebar-wrap {
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                height: 100dvh;
                z-index: 50;
                width: 272px !important;
                transform: translateX(-100%);
                transition: transform .25s cubic-bezier(.4,0,.2,1);
                box-shadow: 4px 0 24px rgba(15,23,42,0.12);
            }
            #sidebar-wrap.mobile-open {
                transform: translateX(0);
            }
            #main-sidebar {
                width: 272px !important;
                height: 100% !important;
            }
            /* Backdrop */
            #sidebar-backdrop {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.4);
                z-index: 49;
                backdrop-filter: blur(2px);
            }
            #sidebar-backdrop.visible {
                display: block;
            }
        }

        @media (min-width: 1024px) {
            #sidebar-backdrop { display: none !important; }
        }

        /* ── Nav group submenu animation ── */
        .nav-group-items {
            display: grid;
            grid-template-rows: 1fr;
            transition: grid-template-rows .2s ease;
        }
        .nav-group-items.closed {
            grid-template-rows: 0fr;
        }
        .nav-group-items > div { overflow: hidden; }

        /* ── Brand: violet overrides ── */
        .btn-primary, button.btn-primary, a.btn-primary {
            background: linear-gradient(170deg, #8b5cf6, #6d28d9) !important;
            color: #fff !important;
            box-shadow: 0 2px 8px rgba(124,58,237,0.32) !important;
        }
        .btn-primary:hover, button.btn-primary:hover, a.btn-primary:hover {
            background: linear-gradient(170deg, #a78bfa, #7c3aed) !important;
            box-shadow: 0 4px 12px rgba(124,58,237,0.40) !important;
        }

        /* ── Dark sidebar overrides ── */
        #main-sidebar { background: #1a2035; }
        #main-sidebar .sidebar-link { color: rgba(148,163,184,0.9); }
        #main-sidebar .sidebar-link:hover { background: rgba(255,255,255,0.07); color: #f8fafc; }
        #main-sidebar .sidebar-link.active {
            background: rgba(139,92,246,0.18) !important;
            color: #fff !important;
            box-shadow: inset 3px 0 0 #8b5cf6 !important;
        }
        #main-sidebar .sidebar-link.active svg { color: #a78bfa !important; }
        #main-sidebar [data-nav-group-chevron] { color: rgba(148,163,184,0.55) !important; }
        #main-sidebar [data-nav-group-chevron].rotate-180 { color: #a78bfa !important; }

        /* ── Brand utilities ── */
        .text-brand-500 { color: #8b5cf6; }
        .text-brand-600 { color: #7c3aed; }
        .text-brand-700 { color: #6d28d9; }
        .text-brand-800 { color: #5b21b6; }
        .text-brand-900 { color: #4c1d95; }
        .hover\:text-brand-700:hover { color: #6d28d9; }
        .bg-brand-50  { background-color: #f5f3ff; }
        .bg-brand-50\/70 { background-color: rgba(245,243,255,0.7); }
        .bg-brand-100 { background-color: #ede9fe; }
        .bg-brand-500 { background-color: #8b5cf6; }
        .bg-brand-600 { background-color: #7c3aed; }
        .border-brand-200 { border-color: #ddd6fe; }
        .rotate-180 { transform: rotate(180deg); }
    </style>
</head>
<body class="font-sans antialiased text-slate-900" style="background: #f1f5f9;">
@php
    $logoPath = trim((string) config('portal.logo_path'), '/');
    $logoUrl  = $logoPath !== '' ? asset($logoPath) : null;
    $user     = auth()->user();
    $userInitials = $user
        ? strtoupper((string) str($user->name)->squish()->explode(' ')->map(fn($p) => mb_substr($p,0,1))->take(2)->implode(''))
        : 'LP';
    $hasPageAside       = View::hasSection('page-aside');
    $pageAsideStorageKey = trim((string) $__env->yieldContent('page-aside-storage-key', request()->route()?->getName() ?: 'default'));
    $pageSubtitle        = trim((string) $__env->yieldContent('page-subtitle', config('portal.brand_tagline')));
    $settingsActive      = request()->routeIs('admin.settings.*') || request()->routeIs('admin.permissions.*') || request()->routeIs('admin.departments.*') || request()->routeIs('admin.data-transfer.*');
    $settingsOpen        = $settingsActive;
@endphp

<div class="min-h-screen" style="background: #f1f5f9;">
    <div class="flex min-h-screen">

        {{-- ── Sidebar ── --}}
        <div id="sidebar-wrap">
            <aside id="main-sidebar" class="h-screen sticky top-0 flex flex-col" style="border-right: 1px solid rgba(255,255,255,0.06);">

                {{-- Logo --}}
                @php
                    $iconLogoUrl = asset('brand/logo2.png');
                @endphp
                <div class="px-5 py-5" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                    <a href="{{ route('dashboard') }}" class="sb-logo-wrap group flex flex-col items-center gap-3">
                        {{-- Expanded: lotus + brand text --}}
                        <div class="sb-logo-expanded flex flex-col items-center gap-2">
                            <img src="{{ asset('brand/logo2.png') }}" alt="Logo" class="h-28 w-28 object-contain drop-shadow-lg">
                            <div class="sb-logo-text text-center space-y-0.5">
                                <span class="block text-[11px] font-bold uppercase tracking-[0.20em] text-white/90">Lider Kozmetik</span>
                                <span class="block text-[10px] font-semibold uppercase tracking-[0.18em]" style="color: rgba(167,139,250,0.8);">Portal</span>
                            </div>
                        </div>
                        {{-- Collapsed: lotus only --}}
                        <div class="sb-logo-collapsed hidden items-center justify-center">
                            <img src="{{ asset('brand/logo2.png') }}" alt="Logo" class="h-16 w-16 object-contain">
                        </div>
                    </a>
                </div>

                {{-- Nav --}}
                <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-6 space-y-6">
                    @unless($user?->isSupplier())
                        <div class="space-y-1">
                            @if($user?->hasPermission('dashboard'))
                            <a href="{{ route('dashboard') }}"
                               title="Dashboard"
                               class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                <span class="sb-label">Dashboard</span>
                            </a>
                            @endif
                            @if($user?->hasPermission('orders'))
                            <a href="{{ route('orders.index') }}"
                               title="Siparişler"
                               class="sidebar-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="sb-label">Siparişler</span>
                            </a>
                            @endif
                        </div>
                    @endunless

                    @if($user?->isSupplier())
                        <div class="space-y-1">
                            <a href="{{ route('portal.orders.index') }}"
                               title="Siparişlerim"
                               class="sidebar-link {{ request()->routeIs('portal.*') ? 'active' : '' }}">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="sb-label">Siparişlerim</span>
                            </a>
                        </div>
                    @endif

                    @php
                        $canViewSuppliers = $user?->hasPermission('suppliers');
                        $canViewUsers     = $user?->hasPermission('users');
                        $canViewSettings  = $user?->isAdmin();
                        $canViewReports   = $user?->hasPermission('reports');
                        $canViewGallery   = $user?->hasPermission('gallery');
                        $canViewLogs      = $user?->hasPermission('logs');
                        $showMgmtSection  = ! $user?->isSupplier() && ($canViewSuppliers || $canViewUsers || $canViewSettings || $canViewReports || $canViewGallery || $canViewLogs);
                    @endphp

                    @if($showMgmtSection)
                        <div class="space-y-2">
                            <div class="px-3 sb-section-title">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.24em]" style="color: rgba(148,163,184,0.45);">Yönetim</p>
                            </div>
                            <div class="space-y-1">
                                @if($canViewSuppliers)
                                    <a href="{{ route('admin.suppliers.index') }}"
                                       title="Tedarikçiler"
                                       class="sidebar-link {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}">
                                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        <span class="sb-label">Tedarikçiler</span>
                                    </a>
                                @endif

                                @if($canViewUsers)
                                    <a href="{{ route('admin.users.index') }}"
                                       title="Kullanıcılar"
                                       class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                        <span class="sb-label">Kullanıcılar</span>
                                    </a>
                                @endif

                                @if($canViewSettings)
                                    {{-- Ayarlar nav group --}}
                                    <div data-nav-group="settings" data-nav-group-open="false" data-nav-group-active="{{ $settingsActive ? 'true' : 'false' }}">
                                        <button type="button"
                                                data-nav-group-toggle="settings"
                                                title="Ayarlar"
                                                class="sidebar-link w-full {{ $settingsActive ? 'active' : '' }}">
                                            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317a1 1 0 011.35-.936l1.824.73a1 1 0 00.95-.08l1.52-1.014a1 1 0 011.475.616l.497 1.99a1 1 0 00.687.719l1.945.648a1 1 0 01.572 1.473l-.92 1.69a1 1 0 000 .956l.92 1.69a1 1 0 01-.572 1.473l-1.945.648a1 1 0 00-.687.719l-.497 1.99a1 1 0 01-1.475.616l-1.52-1.014a1 1 0 00-.95-.08l-1.824.73a1 1 0 01-1.35-.936l-.13-1.864a1 1 0 00-.53-.812l-1.63-.92a1 1 0 010-1.74l1.63-.92a1 1 0 00.53-.812l.13-1.864z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span class="sb-label flex-1 text-left">Ayarlar</span>
                                            <svg data-nav-group-chevron="settings"
                                                 class="sb-chevron h-3.5 w-3.5 flex-shrink-0 transition-transform duration-200 text-slate-400"
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div class="nav-group-items closed">
                                            <div>
                                                <div class="ml-4 mt-1 mb-1 space-y-0.5 pl-3" style="border-left: 1px solid rgba(255,255,255,0.1);">
                                                    @php
                                                        $settingsTabs = [
                                                            'updates' => 'Güncellemeler',
                                                            'storage' => 'Depolama / Spaces',
                                                            'mikro'   => 'Mikro API',
                                                            'mail'    => 'Mail / Exchange',
                                                            'formats' => 'Dosya Formatları',
                                                            'portal'  => 'Portal Ayarları',
                                                            'general' => 'Genel Sistem',
                                                        ];
                                                        $activeSettingsTab = request()->query('tab', 'updates');
                                                    @endphp
                                                    @foreach($settingsTabs as $tabKey => $tabLabel)
                                                        <a href="{{ route('admin.settings.edit', ['tab' => $tabKey]) }}"
                                                           class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                                  {{ request()->routeIs('admin.settings.*') && $activeSettingsTab === $tabKey
                                                                      ? 'bg-brand-50 text-brand-700'
                                                                      : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                                                            <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full
                                                                {{ request()->routeIs('admin.settings.*') && $activeSettingsTab === $tabKey ? 'bg-brand-500' : 'bg-white/20' }}"></span>
                                                            {{ $tabLabel }}
                                                        </a>
                                                    @endforeach
                                                    <a href="{{ route('admin.permissions.index') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.permissions.*')
                                                                  ? 'bg-brand-50 text-brand-700'
                                                                  : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full
                                                            {{ request()->routeIs('admin.permissions.*') ? 'bg-brand-500' : 'bg-white/20' }}"></span>
                                                        Yetkiler
                                                    </a>
                                                    <a href="{{ route('admin.departments.index') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.departments.*')
                                                                  ? 'bg-brand-50 text-brand-700'
                                                                  : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full
                                                            {{ request()->routeIs('admin.departments.*') ? 'bg-brand-500' : 'bg-white/20' }}"></span>
                                                        Departmanlar
                                                    </a>
                                                    <a href="{{ route('admin.data-transfer.index') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.data-transfer.*')
                                                                  ? 'bg-brand-50 text-brand-700'
                                                                  : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full
                                                            {{ request()->routeIs('admin.data-transfer.*') ? 'bg-brand-500' : 'bg-slate-300' }}"></span>
                                                        Veri Aktarımı
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if($canViewReports)
                                    {{-- Raporlar nav group --}}
                                    @php $reportsActive = request()->routeIs('admin.reports.*'); @endphp
                                    <div data-nav-group="reports" data-nav-group-open="false" data-nav-group-active="{{ $reportsActive ? 'true' : 'false' }}">
                                        <button type="button"
                                                data-nav-group-toggle="reports"
                                                title="Raporlar"
                                                class="sidebar-link w-full {{ $reportsActive ? 'active' : '' }}">
                                            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                            <span class="sb-label flex-1 text-left">Raporlar</span>
                                            <svg data-nav-group-chevron="reports"
                                                 class="sb-chevron h-3.5 w-3.5 flex-shrink-0 transition-transform duration-200 text-slate-400"
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div class="nav-group-items closed">
                                            <div>
                                                <div class="ml-4 mt-1 mb-1 space-y-0.5 pl-3" style="border-left: 1px solid rgba(255,255,255,0.1);">
                                                    <a href="{{ route('admin.reports.index') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.reports.index') ? 'bg-white/10 text-brand-400 font-semibold' : 'text-white/50 hover:text-white/80 hover:bg-white/[0.05]' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('admin.reports.index') ? 'bg-brand-500' : 'bg-white/20' }}"></span>
                                                        Rapor Merkezi
                                                    </a>
                                                    <a href="{{ route('admin.reports.lead-time') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.reports.lead-time') ? 'bg-white/10 text-brand-400 font-semibold' : 'text-white/50 hover:text-white/80 hover:bg-white/[0.05]' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('admin.reports.lead-time') ? 'bg-brand-500' : 'bg-white/20' }}"></span>
                                                        Tedarik Süreci
                                                    </a>
                                                    <a href="{{ route('admin.reports.pending') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.reports.pending') ? 'bg-white/10 text-brand-400 font-semibold' : 'text-white/50 hover:text-white/80 hover:bg-white/[0.05]' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('admin.reports.pending') ? 'bg-brand-500' : 'bg-white/20' }}"></span>
                                                        Bekleyen İşler & Eskime
                                                    </a>
                                                    <a href="{{ route('admin.reports.performance') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.reports.performance') ? 'bg-white/10 text-brand-400 font-semibold' : 'text-white/50 hover:text-white/80 hover:bg-white/[0.05]' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('admin.reports.performance') ? 'bg-brand-500' : 'bg-white/20' }}"></span>
                                                        Tedarikçi Performansı
                                                    </a>
                                                    <a href="{{ route('admin.reports.category') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.reports.category') ? 'bg-white/10 text-brand-400 font-semibold' : 'text-white/50 hover:text-white/80 hover:bg-white/[0.05]' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('admin.reports.category') ? 'bg-brand-500' : 'bg-white/20' }}"></span>
                                                        Kategori & İçerik Analizi
                                                    </a>
                                                    <a href="{{ route('admin.reports.stock-code') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.reports.stock-code') ? 'bg-white/10 text-brand-400 font-semibold' : 'text-white/50 hover:text-white/80 hover:bg-white/[0.05]' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('admin.reports.stock-code') ? 'bg-brand-500' : 'bg-white/20' }}"></span>
                                                        Stok Kodu Kullanımı
                                                    </a>
                                                    <a href="{{ route('admin.reports.timeline') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.reports.timeline') ? 'bg-white/10 text-brand-400 font-semibold' : 'text-white/50 hover:text-white/80 hover:bg-white/[0.05]' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('admin.reports.timeline') ? 'bg-brand-500' : 'bg-white/20' }}"></span>
                                                        Aktivite Akışı
                                                    </a>
                                                    <a href="{{ route('admin.reports.factory.index') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.reports.factory.*') ? 'bg-white/10 text-brand-400 font-semibold' : 'text-white/50 hover:text-white/80 hover:bg-white/[0.05]' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('admin.reports.factory.*') ? 'bg-brand-500' : 'bg-white/20' }}"></span>
                                                        Özel Raporlar
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if($canViewGallery)
                                    {{-- Artwork Galerisi nav group --}}
                                    @php $galleryActive = request()->routeIs('admin.artwork-gallery.*'); @endphp
                                    <div data-nav-group="gallery" data-nav-group-open="false" data-nav-group-active="{{ $galleryActive ? 'true' : 'false' }}">
                                        <button type="button" data-nav-group-toggle="gallery" title="Artwork Galerisi"
                                                class="sidebar-link w-full {{ $galleryActive ? 'active' : '' }}">
                                            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-10h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span class="sb-label flex-1 text-left">Artwork Galerisi</span>
                                            <svg data-nav-group-chevron="gallery"
                                                 class="sb-chevron h-3.5 w-3.5 flex-shrink-0 transition-transform duration-200 text-slate-400"
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div class="nav-group-items closed">
                                            <div>
                                                <div class="ml-4 mt-1 mb-1 space-y-0.5 pl-3" style="border-left: 1px solid rgba(255,255,255,0.1);">
                                                    <a href="{{ route('admin.artwork-gallery.index') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.artwork-gallery.index') || (request()->routeIs('admin.artwork-gallery.*') && !request()->routeIs('admin.artwork-gallery.manage'))
                                                                  ? 'bg-white/10 text-brand-400 font-semibold' : 'text-white/50 hover:text-white/80 hover:bg-white/[0.05]' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('admin.artwork-gallery.index') ? 'bg-brand-500' : 'bg-white/20' }}"></span>
                                                        Galeri
                                                    </a>
                                                    @if($user?->hasPermission('gallery', 'manage'))
                                                    <a href="{{ route('admin.artwork-gallery.manage') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.artwork-gallery.manage')
                                                                  ? 'bg-white/10 text-brand-400 font-semibold' : 'text-white/50 hover:text-white/80 hover:bg-white/[0.05]' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('admin.artwork-gallery.manage') ? 'bg-brand-500' : 'bg-white/20' }}"></span>
                                                        Yönetim
                                                    </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if($canViewLogs)
                                    <a href="{{ route('admin.logs.index') }}"
                                       title="Sistem Logları"
                                       class="sidebar-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        <span class="sb-label">Sistem Logları</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Profil linki --}}
                    <div class="space-y-1">
                        <a href="{{ route('profile.edit') }}"
                           title="Profilim"
                           class="sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="sb-label">Profilim</span>
                        </a>
                    </div>
                </nav>

                {{-- User footer --}}
                <div class="p-3 sb-footer-expanded" style="border-top: 1px solid rgba(255,255,255,0.06);">
                    <div class="rounded-2xl p-3" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('profile.edit') }}" class="flex-shrink-0" title="Profilim">
                                @if($user?->profile_photo_path)
                                    <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}"
                                         class="h-10 w-10 rounded-xl object-cover">
                                @else
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl text-sm font-semibold text-white" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);">
                                        {{ $userInitials }}
                                    </div>
                                @endif
                            </a>
                            <div class="sb-user-info min-w-0 flex-1">
                                <a href="{{ route('profile.edit') }}" class="block truncate text-sm font-semibold text-white/90 hover:text-white">{{ $user?->name }}</a>
                                <p class="text-xs text-white/45">{{ $user?->role?->label() }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="sb-logout flex-shrink-0">
                                @csrf
                                <button type="submit"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl transition"
                                        style="color: rgba(148,163,184,0.7);"
                                        onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='#fff'"
                                        onmouseout="this.style.background='transparent';this.style.color='rgba(148,163,184,0.7)'"
                                        title="Çıkış">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                {{-- Collapsed footer: just avatar + logout --}}
                <div class="sb-footer-collapsed hidden p-2" style="border-top: 1px solid rgba(255,255,255,0.06);">
                    <div class="flex flex-col items-center gap-2 py-1">
                        <a href="{{ route('profile.edit') }}" title="{{ $user?->name }}">
                            @if($user?->profile_photo_path)
                                <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}"
                                     class="h-9 w-9 rounded-xl object-cover">
                            @else
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl text-sm font-semibold text-white" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);">
                                    {{ $userInitials }}
                                </div>
                            @endif
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg transition"
                                    style="color: rgba(148,163,184,0.6);"
                                    title="Çıkış">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>
        </div>

        {{-- ── Mobile sidebar backdrop ── --}}
        <div id="sidebar-backdrop"></div>

        {{-- ── Main content ── --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="border-b border-slate-200/70 bg-white px-4 py-3 sm:px-6 lg:px-8" style="box-shadow: 0 1px 0 rgba(15,23,42,0.04), 0 2px 8px rgba(15,23,42,0.03);">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="flex items-start gap-4">
                        {{-- Hamburger toggle --}}
                        <button type="button"
                                id="sidebar-toggle"
                                class="mt-0.5 inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                                title="Menüyü aç/kapat">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        <div class="space-y-1">
                            <h1 class="text-xl font-semibold tracking-tight text-slate-950 sm:text-2xl">@yield('page-title')</h1>
                            @if($pageSubtitle !== '')
                                <p class="max-w-3xl text-sm leading-6 text-slate-500">{{ $pageSubtitle }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        {{-- Global search trigger --}}
                        @if(auth()->user()?->role?->value !== 'supplier')
                        <button type="button"
                                id="search-trigger-btn"
                                onclick="window.dispatchEvent(new CustomEvent('search-open'))"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-400 transition hover:border-violet-300 hover:bg-violet-50 hover:text-violet-600 sm:min-w-[200px] xl:min-w-[260px]"
                                title="Ara (Alt+S)">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                            </svg>
                            <span class="flex-1 text-left hidden sm:block">Ara…</span>
                            <kbd class="hidden rounded border border-slate-200 bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium sm:inline">Alt S</kbd>
                        </button>
                        @endif

                        {{-- Notification bell --}}
                        @if(auth()->user()?->role?->value !== 'supplier')
                            @include('partials.notification-bell')
                        @endif

                        {{-- Theme selector --}}
                        @include('partials.theme-switcher')

                        {{-- Mobile logout (always visible on small screens) --}}
                        <form method="POST" action="{{ route('logout') }}" class="lg:hidden">
                            @csrf
                            <button type="submit"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                                    title="Çıkış Yap">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </button>
                        </form>

                        @if($hasPageAside)
                            <button type="button" class="btn btn-secondary"
                                    data-aside-toggle
                                    data-aside-storage-key="{{ $pageAsideStorageKey }}"
                                    aria-controls="page-context-aside">
                                Yardımcı Panel
                            </button>
                        @endif
                        @yield('header-actions')
                    </div>
                </div>
            </header>

            @if(session('success'))
                <x-ui.alert variant="success" class="mx-4 mt-4 sm:mx-6 lg:mx-8">
                    <div class="flex items-center gap-3">
                        <svg class="h-4 w-4 flex-shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </x-ui.alert>
            @endif

            @if(session('warning'))
                <x-ui.alert variant="warning" class="mx-4 mt-4 sm:mx-6 lg:mx-8">
                    <p class="font-medium">{{ session('warning') }}</p>
                </x-ui.alert>
            @endif

            @if(session('error') || $errors->any())
                <x-ui.alert variant="danger" class="mx-4 mt-4 sm:mx-6 lg:mx-8">
                    <div>
                        @if(session('error'))
                            <p class="font-medium">{{ session('error') }}</p>
                        @endif
                        @if($errors->any())
                            <ul class="{{ session('error') ? 'mt-2' : '' }} list-disc list-inside space-y-0.5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </x-ui.alert>
            @endif

            <main class="flex-1 px-4 py-5 sm:px-6 lg:px-8">
                @if($hasPageAside)
                    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                        <div class="min-w-0">@yield('content')</div>
                        <aside id="page-context-aside" class="page-context-aside space-y-4"
                               data-page-aside
                               data-aside-storage-key="{{ $pageAsideStorageKey }}"
                               data-default-open="desktop">
                            @yield('page-aside')
                        </aside>
                    </div>
                @else
                    @yield('content')
                @endif
            </main>
        </div>
    </div>
</div>

<script>
(function () {
    var SIDEBAR_KEY   = 'lider-portal:sidebar';
    var NAV_GROUP_KEY = 'lider-portal:nav-group:';

    var wrap     = document.getElementById('sidebar-wrap');
    var toggle   = document.getElementById('sidebar-toggle');
    var backdrop = document.getElementById('sidebar-backdrop');

    function isMobile() { return window.innerWidth < 1024; }

    /* ── Desktop: sidebar collapse (icon strip) ── */
    function setSidebar(state) {
        if (state === 'collapsed') {
            wrap.classList.add('collapsed');
        } else {
            wrap.classList.remove('collapsed');
        }
        localStorage.setItem(SIDEBAR_KEY, state);
    }

    /* ── Mobile: sidebar overlay ── */
    function setMobileSidebar(open) {
        if (open) {
            wrap.classList.add('mobile-open');
            if (backdrop) backdrop.classList.add('visible');
        } else {
            wrap.classList.remove('mobile-open');
            if (backdrop) backdrop.classList.remove('visible');
        }
    }

    if (wrap && toggle) {
        if (!isMobile()) {
            var stored = localStorage.getItem(SIDEBAR_KEY) || 'open';
            setSidebar(stored);
        }

        toggle.addEventListener('click', function () {
            if (isMobile()) {
                setMobileSidebar(!wrap.classList.contains('mobile-open'));
            } else {
                setSidebar(wrap.classList.contains('collapsed') ? 'open' : 'collapsed');
            }
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            setMobileSidebar(false);
        });
    }

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            setMobileSidebar(false);
            var stored = localStorage.getItem(SIDEBAR_KEY) || 'open';
            setSidebar(stored);
        }
    });

    /* ── Nav group toggle (Ayarlar, Raporlar, Galeri vb.) ── */
    function openNavGroup(items, chevron, groupName) {
        items.classList.remove('closed');
        if (chevron) { chevron.classList.add('rotate-180','text-brand-500'); chevron.classList.remove('text-slate-400'); }
        localStorage.setItem(NAV_GROUP_KEY + groupName, 'open');
    }
    function closeNavGroup(items, chevron, groupName) {
        items.classList.add('closed');
        if (chevron) { chevron.classList.remove('rotate-180','text-brand-500'); chevron.classList.add('text-slate-400'); }
        localStorage.setItem(NAV_GROUP_KEY + groupName, 'closed');
    }

    document.querySelectorAll('[data-nav-group-toggle]').forEach(function (btn) {
        var groupName  = btn.dataset.navGroupToggle;
        var group      = document.querySelector('[data-nav-group="' + groupName + '"]');
        var items      = group ? group.querySelector('.nav-group-items') : null;
        var chevron    = group ? group.querySelector('[data-nav-group-chevron]') : null;
        if (!group || !items) return;

        /* Route-based initial state: active group → always open; others → closed */
        var isActive = group.dataset.navGroupActive === 'true';
        if (isActive) {
            openNavGroup(items, chevron, groupName);
        } else {
            closeNavGroup(items, chevron, groupName);
        }

        btn.addEventListener('click', function () {
            if (wrap && wrap.classList.contains('collapsed')) return;

            var isClosed = items.classList.contains('closed');
            if (isClosed) {
                /* Close all other groups first */
                document.querySelectorAll('[data-nav-group]').forEach(function (otherGroup) {
                    if (otherGroup === group) return;
                    var otherItems   = otherGroup.querySelector('.nav-group-items');
                    var otherChevron = otherGroup.querySelector('[data-nav-group-chevron]');
                    var otherName    = otherGroup.dataset.navGroup;
                    if (otherItems) closeNavGroup(otherItems, otherChevron, otherName);
                });
                openNavGroup(items, chevron, groupName);
            } else {
                closeNavGroup(items, chevron, groupName);
            }
        });
    });

    /* ── Page aside (existing logic) ── */
    var asidePrefix = 'lider-portal:aside:';
    function resolveAsideDefault(aside) {
        var mode = aside.dataset.defaultOpen || 'desktop';
        if (mode === 'always') return 'open';
        return window.innerWidth >= 1280 ? 'open' : 'closed';
    }
    function applyAsideState(aside, state) {
        aside.dataset.asideState = state;
        var id = aside.id;
        if (id) {
            document.querySelectorAll('[data-aside-toggle][aria-controls="' + id + '"]').forEach(function(b){
                b.setAttribute('aria-expanded', state === 'open' ? 'true' : 'false');
                b.dataset.asideState = state;
            });
        }
    }
    var pageAside = document.querySelector('[data-page-aside]');
    if (pageAside) {
        var asideKey    = pageAside.dataset.asideStorageKey ? asidePrefix + pageAside.dataset.asideStorageKey : null;
        var asideStored = asideKey ? localStorage.getItem(asideKey) : null;
        var asideInitial = (asideStored === 'open' || asideStored === 'closed') ? asideStored : resolveAsideDefault(pageAside);
        applyAsideState(pageAside, asideInitial);
        document.querySelectorAll('[data-aside-toggle]').forEach(function(t){
            t.addEventListener('click', function(){
                var cur  = pageAside.dataset.asideState === 'open' ? 'open' : 'closed';
                var next = cur === 'open' ? 'closed' : 'open';
                applyAsideState(pageAside, next);
                if (asideKey) localStorage.setItem(asideKey, next);
            });
        });
    }

    /* ── Dialog helpers ── */
    document.addEventListener('click', function(e){
        var open = e.target.closest('[data-dialog-open]');
        if (open) { var d = document.getElementById(open.dataset.dialogOpen); if (d && d.showModal) d.showModal(); }
        var close = e.target.closest('[data-dialog-close]');
        if (close) { var d2 = close.closest('dialog'); if (d2 && d2.close) d2.close(); }
    });
    document.addEventListener('click', function(e){
        if (!(e.target instanceof HTMLDialogElement)) return;
        var r = e.target.getBoundingClientRect();
        if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) e.target.close();
    });
})();
</script>

@stack('scripts')
@stack('modals')

@include('partials.search-modal')
</body>
</html>
