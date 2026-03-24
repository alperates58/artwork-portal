<!DOCTYPE html>
<html lang="tr">
<head>
    @include('partials.ui-head', ['title' => 'Kurulum Sihirbazı'])
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-brand-50 min-h-screen font-sans antialiased">
@php
    $logoPath = public_path(config('portal.logo_path'));
    $logoUrl = file_exists($logoPath) ? asset(config('portal.logo_path')) : null;
@endphp

<div class="min-h-screen flex flex-col items-center justify-center p-4 py-12">
    <div class="flex items-center gap-3 mb-8">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ config('portal.brand_name') }}" class="h-10 w-auto object-contain">
        @else
            <div class="w-10 h-10 bg-brand-600 rounded-xl"></div>
        @endif
        <div>
            <p class="font-semibold text-slate-900 text-sm leading-none">{{ config('portal.brand_name') }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Kurulum Sihirbazı</p>
        </div>
    </div>

    @isset($step)
    <div class="w-full max-w-lg mb-8">
        <div class="flex items-center">
            @foreach($steps as $num => $info)
                <div class="flex flex-col items-center">
                    <div class="step-dot {{ $num < $step ? 'done' : ($num == $step ? 'active' : 'pending') }}">
                        @if($num < $step)
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            {{ $num }}
                        @endif
                    </div>
                    <p class="text-xs mt-1.5 font-medium {{ $num == $step ? 'text-brand-600' : 'text-slate-400' }} whitespace-nowrap">
                        {{ $info['label'] }}
                    </p>
                </div>
                @if(! $loop->last)
                    <div class="step-line mb-4 {{ $num < $step ? 'done' : 'pending' }}"></div>
                @endif
            @endforeach
        </div>
    </div>
    @endisset

    <div class="w-full max-w-lg">
        <div class="card rounded-2xl">
            @yield('content')
        </div>
    </div>

    <p class="text-xs text-slate-400 mt-6">{{ config('portal.brand_name') }} — {{ config('portal.brand_tagline') }}</p>
</div>

@stack('scripts')
</body>
</html>
