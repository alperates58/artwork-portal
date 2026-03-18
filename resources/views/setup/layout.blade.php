<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kurulum Sihirbazı — Artwork Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } }
        }
    </script>
    <style>
        .step-dot { @apply w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold transition-all duration-300; }
        .step-dot.done    { @apply bg-emerald-500 text-white; }
        .step-dot.active  { @apply bg-blue-600 text-white ring-4 ring-blue-100; }
        .step-dot.pending { @apply bg-slate-200 text-slate-400; }
        .step-line { @apply h-0.5 flex-1 transition-all duration-500; }
        .step-line.done   { @apply bg-emerald-400; }
        .step-line.pending{ @apply bg-slate-200; }
        .input { @apply w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition; }
        .input.error { @apply border-red-400 focus:ring-red-400; }
        .label { @apply block text-sm font-medium text-slate-700 mb-1.5; }
        .hint  { @apply text-xs text-slate-400 mt-1; }
        .err   { @apply text-xs text-red-600 mt-1.5; }
        .btn-primary { @apply inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors; }
        .btn-secondary { @apply inline-flex items-center gap-2 px-5 py-2.5 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg border border-slate-300 transition-colors; }
    </style>
    @stack('styles')
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen font-sans antialiased">

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
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        @yield('content')
    </div>

    {{-- Footer --}}
    <p class="text-xs text-slate-400 mt-6">Artwork Portal — Tedarikçi Artwork Yönetim Sistemi</p>

</div>

@stack('scripts')
</body>
</html>
