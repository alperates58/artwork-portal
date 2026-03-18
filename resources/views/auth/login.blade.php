<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş — {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter','sans-serif'] } } } }</script>
</head>
<body class="bg-slate-50 font-sans antialiased min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">

    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-brand-600 rounded-2xl mb-4"
             style="background:#3b5bdb">
            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1 class="text-xl font-semibold text-slate-900">Artwork Portal</h1>
        <p class="text-sm text-slate-500 mt-1">Tedarikçi Artwork Yönetim Sistemi</p>
    </div>

    {{-- Form card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="email">
                    E-posta
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    autofocus
                    required
                    class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg
                           focus:outline-none focus:ring-2 focus:border-transparent
                           @error('email') border-red-400 focus:ring-red-400 @else focus:ring-blue-500 @enderror"
                    placeholder="kullanici@sirket.com"
                >
                @error('email')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="password">
                    Şifre
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                    class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                           @error('password') border-red-400 @enderror"
                    placeholder="••••••••"
                >
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600">
                    <span class="text-sm text-slate-600">Beni hatırla</span>
                </label>
                <a href="{{ route('password.request') }}"
                   class="text-sm text-blue-600 hover:text-blue-700">
                    Şifremi unuttum
                </a>
            </div>

            <button type="submit"
                    class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium
                           rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Giriş Yap
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-slate-400 mt-6">
        Hesap açmak için yönetici ile iletişime geçin.
    </p>
</div>

</body>
</html>
