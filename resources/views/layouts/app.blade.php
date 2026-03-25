<!DOCTYPE html>
<html lang="tr">
<head>
    @include('partials.ui-head', ['title' => trim($__env->yieldContent('title', config('portal.brand_name')))])
</head>
<body class="bg-slate-50 font-sans antialiased">
@php
    $logoPath = trim((string) config('portal.logo_path'), '/');
    $logoUrl = $logoPath !== '' ? asset($logoPath) : null;
@endphp

<div class="flex h-screen overflow-hidden">
    <aside class="w-64 bg-white border-r border-slate-200 flex flex-col flex-shrink-0">
        <div class="h-16 flex items-center px-5 border-b border-slate-200">
            <div class="flex items-center gap-3">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ config('portal.brand_name') }}" class="h-8 w-auto object-contain">
                @else
                    <div class="w-8 h-8 bg-brand-600 rounded-lg"></div>
                @endif
                <div>
                    <span class="font-semibold text-slate-900 text-sm block">{{ config('portal.brand_name') }}</span>
                    <span class="text-[11px] text-slate-400">{{ config('portal.brand_tagline') }}</span>
                </div>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
            @unless(auth()->user()->isSupplier())
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>

                <a href="{{ route('orders.index') }}" class="sidebar-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Siparişler
                </a>
            @endunless

            @if(auth()->user()->isSupplier())
                <a href="{{ route('portal.orders.index') }}" class="sidebar-link {{ request()->routeIs('portal.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Siparişlerim
                </a>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->isPurchasing())
                <div class="pt-4 pb-1 px-3">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Yönetim</p>
                </div>
                <a href="{{ route('admin.suppliers.index') }}" class="sidebar-link {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Tedarikçiler
                </a>
            @endif

            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Kullanıcılar
                </a>
                <a href="{{ route('admin.settings.edit') }}" class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317a1 1 0 011.35-.936l1.824.73a1 1 0 00.95-.08l1.52-1.014a1 1 0 011.475.616l.497 1.99a1 1 0 00.687.719l1.945.648a1 1 0 01.572 1.473l-.92 1.69a1 1 0 000 .956l.92 1.69a1 1 0 01-.572 1.473l-1.945.648a1 1 0 00-.687.719l-.497 1.99a1 1 0 01-1.475.616l-1.52-1.014a1 1 0 00-.95-.08l-1.824.73a1 1 0 01-1.35-.936l-.13-1.864a1 1 0 00-.53-.812l-1.63-.92a1 1 0 010-1.74l1.63-.92a1 1 0 00.53-.812l.13-1.864z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Ayarlar
                </a>
                <a href="{{ route('admin.reports.index') }}" class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3v18m-6-6h12m4-8v14a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    Raporlar
                </a>
                <a href="{{ route('admin.logs.index') }}" class="sidebar-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Sistem Logları
                </a>
            @endif
        </nav>

        <div class="p-3 border-t border-slate-200">
            <div class="flex items-center gap-3 px-2 py-2">
                <div class="w-8 h-8 bg-brand-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-brand-700 text-xs font-semibold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-500">{{ auth()->user()->role->label() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-400 hover:text-slate-600 transition-colors" title="Çıkış">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center px-6 flex-shrink-0">
            <div class="flex-1">
                <h1 class="text-base font-semibold text-slate-900">@yield('page-title')</h1>
            </div>
            <div class="flex items-center gap-3">
                @yield('header-actions')
            </div>
        </header>

        @if(session('success'))
            <x-ui.alert variant="success" class="mx-6 mt-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 flex-shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            </x-ui.alert>
        @endif

        @if(session('warning'))
            <x-ui.alert variant="warning" class="mx-6 mt-4">
                <p class="font-medium">{{ session('warning') }}</p>
            </x-ui.alert>
        @endif

        @if(session('error') || $errors->any())
            <x-ui.alert variant="danger" class="mx-6 mt-4">
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

        <main class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
