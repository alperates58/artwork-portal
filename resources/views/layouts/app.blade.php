<!DOCTYPE html>
<html lang="tr">
<head>
    @include('partials.ui-head', ['title' => trim($__env->yieldContent('title', config('portal.brand_name')))])
    <style>
        /* ── Sidebar transitions ── */
        #sidebar-wrap {
            width: 288px;
            flex-shrink: 0;
            transition: width .25s cubic-bezier(.4,0,.2,1);
        }
        #main-sidebar {
            width: 288px;
            transition: width .25s cubic-bezier(.4,0,.2,1);
            overflow: hidden;
        }

        /* ── Collapsed: icon strip (64px) ── */
        #sidebar-wrap.collapsed,
        #sidebar-wrap.collapsed #main-sidebar {
            width: 64px;
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

        /* Logo: center image when collapsed */
        #sidebar-wrap.collapsed .sb-logo-wrap {
            flex-direction: column;
            align-items: center;
            padding: 0.75rem 0.5rem;
        }
        #sidebar-wrap.collapsed .sb-logo-img {
            height: 2.5rem;
        }

        /* User footer when collapsed */
        #sidebar-wrap.collapsed .sb-footer-inner {
            justify-content: center;
            gap: 0;
        }
        #sidebar-wrap.collapsed .sb-logout {
            display: none;
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
    </style>
</head>
<body class="bg-slate-100 font-sans antialiased text-slate-900">
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
    $settingsOpen        = request()->routeIs('admin.settings.*') || request()->routeIs('admin.permissions.*');
@endphp

<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(244,154,11,0.08),_transparent_28%),linear-gradient(180deg,_#f8fafc_0%,_#eef2f7_100%)]">
    <div class="flex min-h-screen">

        {{-- ── Sidebar ── --}}
        <div id="sidebar-wrap">
            <aside id="main-sidebar" class="h-screen sticky top-0 flex flex-col border-r border-slate-200/80 bg-white/95 backdrop-blur">

                {{-- Logo --}}
                <div class="border-b border-slate-200/80 px-4 py-4">
                    <a href="{{ route('dashboard') }}"
                       class="sb-logo-wrap group flex flex-col items-center gap-2 rounded-3xl border border-slate-200/80 bg-[linear-gradient(135deg,_rgba(255,255,255,0.96),_rgba(255,247,237,0.96))] px-4 py-4 shadow-[0_16px_40px_rgba(15,23,42,0.08)] transition hover:border-brand-200">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ config('portal.brand_name') }}" class="sb-logo-img h-16 w-auto object-contain">
                        @else
                            <div class="sb-logo-img h-16 w-16 rounded-2xl bg-brand-600"></div>
                        @endif
                        <div class="sb-logo-text text-center">
                            <span class="brand-eyebrow block">Lider Kozmetik</span>
                            <span class="block text-sm font-semibold text-slate-950">{{ config('portal.brand_name') }}</span>
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
                                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Yönetim</p>
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
                                    <div data-nav-group="settings" data-nav-group-open="{{ $settingsOpen ? 'true' : 'false' }}">
                                        <button type="button"
                                                data-nav-group-toggle="settings"
                                                title="Ayarlar"
                                                class="sidebar-link w-full {{ $settingsOpen ? 'active' : '' }}">
                                            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317a1 1 0 011.35-.936l1.824.73a1 1 0 00.95-.08l1.52-1.014a1 1 0 011.475.616l.497 1.99a1 1 0 00.687.719l1.945.648a1 1 0 01.572 1.473l-.92 1.69a1 1 0 000 .956l.92 1.69a1 1 0 01-.572 1.473l-1.945.648a1 1 0 00-.687.719l-.497 1.99a1 1 0 01-1.475.616l-1.52-1.014a1 1 0 00-.95-.08l-1.824.73a1 1 0 01-1.35-.936l-.13-1.864a1 1 0 00-.53-.812l-1.63-.92a1 1 0 010-1.74l1.63-.92a1 1 0 00.53-.812l.13-1.864z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span class="sb-label flex-1 text-left">Ayarlar</span>
                                            <svg data-nav-group-chevron="settings"
                                                 class="sb-chevron h-3.5 w-3.5 flex-shrink-0 transition-transform duration-200 {{ $settingsOpen ? 'rotate-180 text-brand-500' : 'text-slate-400' }}"
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div class="nav-group-items {{ $settingsOpen ? '' : 'closed' }}">
                                            <div>
                                                <div class="ml-4 mt-1 mb-1 space-y-0.5 border-l border-slate-200 pl-3">
                                                    @php
                                                        $settingsTabs = [
                                                            'updates' => 'Güncellemeler',
                                                            'storage' => 'Depolama / Spaces',
                                                            'mikro'   => 'Mikro API',
                                                            'mail'    => 'Mail / Exchange',
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
                                                                {{ request()->routeIs('admin.settings.*') && $activeSettingsTab === $tabKey ? 'bg-brand-500' : 'bg-slate-300' }}"></span>
                                                            {{ $tabLabel }}
                                                        </a>
                                                    @endforeach
                                                    <a href="{{ route('admin.permissions.index') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.permissions.*')
                                                                  ? 'bg-brand-50 text-brand-700'
                                                                  : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full
                                                            {{ request()->routeIs('admin.permissions.*') ? 'bg-brand-500' : 'bg-slate-300' }}"></span>
                                                        Yetkiler
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if($canViewReports)
                                    <a href="{{ route('admin.reports.index') }}"
                                       title="Raporlar"
                                       class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                        <span class="sb-label">Raporlar</span>
                                    </a>
                                @endif

                                @if($canViewGallery)
                                    {{-- Artwork Galerisi nav group --}}
                                    @php $galleryOpen = request()->routeIs('admin.artwork-gallery.*'); @endphp
                                    <div data-nav-group="gallery" data-nav-group-open="{{ $galleryOpen ? 'true' : 'false' }}">
                                        <button type="button" data-nav-group-toggle="gallery" title="Artwork Galerisi"
                                                class="sidebar-link w-full {{ $galleryOpen ? 'active' : '' }}">
                                            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-10h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span class="sb-label flex-1 text-left">Artwork Galerisi</span>
                                            <svg data-nav-group-chevron="gallery"
                                                 class="sb-chevron h-3.5 w-3.5 flex-shrink-0 transition-transform duration-200 {{ $galleryOpen ? 'rotate-180 text-brand-500' : 'text-slate-400' }}"
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div class="nav-group-items {{ $galleryOpen ? '' : 'closed' }}">
                                            <div>
                                                <div class="ml-4 mt-1 mb-1 space-y-0.5 border-l border-slate-200 pl-3">
                                                    <a href="{{ route('admin.artwork-gallery.index') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.artwork-gallery.index') || (request()->routeIs('admin.artwork-gallery.*') && !request()->routeIs('admin.artwork-gallery.manage'))
                                                                  ? 'bg-brand-50 text-brand-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('admin.artwork-gallery.index') ? 'bg-brand-500' : 'bg-slate-300' }}"></span>
                                                        Galeri
                                                    </a>
                                                    @if($user?->hasPermission('gallery', 'manage'))
                                                    <a href="{{ route('admin.artwork-gallery.manage') }}"
                                                       class="flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium transition
                                                              {{ request()->routeIs('admin.artwork-gallery.manage')
                                                                  ? 'bg-brand-50 text-brand-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                                                        <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('admin.artwork-gallery.manage') ? 'bg-brand-500' : 'bg-slate-300' }}"></span>
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
                </nav>

                {{-- User footer --}}
                <div class="border-t border-slate-200/80 p-3">
                    <div class="rounded-3xl border border-slate-200/80 bg-slate-50/80 p-3 shadow-[0_10px_25px_rgba(15,23,42,0.04)]">
                        <div class="sb-footer-inner flex items-center gap-3">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-brand-100 text-sm font-semibold text-brand-800 shadow-inner">
                                {{ $userInitials }}
                            </div>
                            <div class="sb-user-info min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $user?->name }}</p>
                                <p class="text-xs text-slate-500">{{ $user?->role?->label() }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="sb-logout">
                                @csrf
                                <button type="submit"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-transparent text-slate-400 transition hover:border-slate-200 hover:bg-white hover:text-slate-700"
                                        title="Çıkış">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        {{-- ── Main content ── --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="border-b border-slate-200/80 bg-white/85 px-4 py-4 backdrop-blur sm:px-6 lg:px-8">
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

    /* ── Sidebar collapse (icon strip) ── */
    var wrap   = document.getElementById('sidebar-wrap');
    var toggle = document.getElementById('sidebar-toggle');

    function setSidebar(state) {
        if (state === 'collapsed') {
            wrap.classList.add('collapsed');
        } else {
            wrap.classList.remove('collapsed');
        }
        localStorage.setItem(SIDEBAR_KEY, state);
    }

    if (wrap && toggle) {
        var stored = localStorage.getItem(SIDEBAR_KEY) || 'open';
        setSidebar(stored);

        toggle.addEventListener('click', function () {
            setSidebar(wrap.classList.contains('collapsed') ? 'open' : 'collapsed');
        });
    }

    /* ── Nav group toggle (Ayarlar vb.) ── */
    document.querySelectorAll('[data-nav-group-toggle]').forEach(function (btn) {
        var groupName = btn.dataset.navGroupToggle;
        var group     = document.querySelector('[data-nav-group="' + groupName + '"]');
        var items     = group ? group.querySelector('.nav-group-items') : null;
        var chevron   = group ? group.querySelector('[data-nav-group-chevron]') : null;
        if (!group || !items) return;

        var forcedOpen = group.dataset.navGroupOpen === 'true';

        if (!forcedOpen) {
            var stored = localStorage.getItem(NAV_GROUP_KEY + groupName);
            if (stored === 'closed') {
                items.classList.add('closed');
                if (chevron) { chevron.classList.remove('rotate-180','text-brand-500'); chevron.classList.add('text-slate-400'); }
            } else if (stored === 'open') {
                items.classList.remove('closed');
                if (chevron) { chevron.classList.add('rotate-180','text-brand-500'); chevron.classList.remove('text-slate-400'); }
            }
        }

        btn.addEventListener('click', function () {
            /* Ignore toggle when sidebar is in icon-strip mode */
            if (wrap && wrap.classList.contains('collapsed')) return;

            var isClosed = items.classList.contains('closed');
            if (isClosed) {
                items.classList.remove('closed');
                if (chevron) { chevron.classList.add('rotate-180','text-brand-500'); chevron.classList.remove('text-slate-400'); }
                localStorage.setItem(NAV_GROUP_KEY + groupName, 'open');
            } else {
                items.classList.add('closed');
                if (chevron) { chevron.classList.remove('rotate-180','text-brand-500'); chevron.classList.add('text-slate-400'); }
                localStorage.setItem(NAV_GROUP_KEY + groupName, 'closed');
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
</body>
</html>
