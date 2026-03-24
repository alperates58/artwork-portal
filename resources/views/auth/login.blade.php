<!DOCTYPE html>
<html lang="tr">
<head>
    @include('partials.ui-head', ['title' => 'Giriş'])
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-brand-50 font-sans antialiased min-h-screen flex items-center justify-center p-4">
@php
    $logoPath = public_path(config('portal.logo_path'));
    $logoUrl = file_exists($logoPath) ? asset(config('portal.logo_path')) : null;
@endphp

<div class="w-full max-w-sm">
    <div class="text-center mb-8">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ config('portal.brand_name') }}" class="h-16 w-auto mx-auto mb-4 object-contain">
        @else
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-4 shadow-sm"
                 style="background:linear-gradient(180deg,var(--brand-600),var(--brand-700))"></div>
        @endif
        <h1 class="text-xl font-semibold text-slate-900">{{ config('portal.brand_name') }}</h1>
        <p class="text-sm text-slate-500 mt-1">{{ config('portal.brand_tagline') }}</p>
    </div>

    <div class="card p-8">
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <label class="label" for="email">E-posta</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus required class="input @error('email') error @enderror" placeholder="kullanici@sirket.com">
                @error('email')
                    <p class="err">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label" for="password">Şifre</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required class="input @error('password') error @enderror" placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600">
                    <span class="text-sm text-slate-600">Beni hatırla</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium">
                    Şifremi unuttum
                </a>
            </div>

            <button type="submit" class="btn btn-primary w-full">
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
