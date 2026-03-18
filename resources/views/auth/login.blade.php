<!DOCTYPE html>
<html lang="tr">
<head>
    @include('partials.ui-head', ['title' => 'Giriş'])
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-brand-50 font-sans antialiased min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">

    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-4 shadow-sm"
             style="background:linear-gradient(180deg,var(--brand-600),var(--brand-700))">
            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1 class="text-xl font-semibold text-slate-900">Artwork Portal</h1>
        <p class="text-sm text-slate-500 mt-1">Tedarikçi Artwork Yönetim Sistemi</p>
    </div>

    {{-- Form card --}}
    <div class="card p-8">
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <label class="label" for="email">E-posta</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    autofocus
                    required
                    class="input @error('email') error @enderror"
                    placeholder="kullanici@sirket.com"
                >
                @error('email')
                    <p class="err">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label" for="password">Şifre</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                    class="input @error('password') error @enderror"
                    placeholder="••••••••"
                >
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600">
                    <span class="text-sm text-slate-600">Beni hatırla</span>
                </label>
                <a href="{{ route('password.request') }}"
                   class="text-sm text-brand-600 hover:text-brand-700 font-medium">
                    Şifremi unuttum
                </a>
            </div>

            <button type="submit"
                    class="btn btn-primary w-full">
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
