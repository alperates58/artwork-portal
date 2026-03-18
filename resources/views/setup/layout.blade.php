<!DOCTYPE html>
<html lang="tr">
<head>
    @include('partials.ui-head', ['title' => 'Kurulum Sihirbazı'])
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-brand-50 min-h-screen font-sans antialiased">

<div class="min-h-screen flex flex-col items-center justify-center p-4 py-12">

    {{-- Logo --}}
    <div class="flex items-center gap-3 mb-8">
        <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <div>
            <p class="font-semibold text-slate-900 text-sm leading-none">Artwork Portal</p>
            <p class="text-xs text-slate-400 mt-0.5">Kurulum Sihirbazı</p>
        </div>
    </div>

    {{-- Step progress bar --}}
    @isset($step)
    <div class="w-full max-w-lg mb-8">
        <div class="flex items-center">
            @foreach($steps as $num => $info)
                {{-- Dot --}}
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
                    <p class="text-xs mt-1.5 font-medium {{ $num == $step ? 'text-blue-600' : 'text-slate-400' }} whitespace-nowrap">
                        {{ $info['label'] }}
                    </p>
                </div>
                {{-- Line between dots --}}
                @if(! $loop->last)
                    <div class="step-line mb-4 {{ $num < $step ? 'done' : 'pending' }}"></div>
                @endif
            @endforeach
        </div>
    </div>
    @endisset

    {{-- Card --}}
    <div class="w-full max-w-lg">
        <div class="card rounded-2xl">
            @yield('content')
        </div>
    </div>

    {{-- Footer --}}
    <p class="text-xs text-slate-400 mt-6">Artwork Portal — Tedarikçi Artwork Yönetim Sistemi</p>

</div>

@stack('scripts')
</body>
</html>
