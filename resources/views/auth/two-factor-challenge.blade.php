<!DOCTYPE html>
<html lang="tr">
<head>
    @include('partials.ui-head', ['title' => '2 Adımlı Doğrulama'])
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-950 font-sans antialiased">
@php
    $logoAsset = 'brand/logo2.png';
    $logoPath = public_path($logoAsset);
    $logoUrl = file_exists($logoPath) ? asset($logoAsset) : null;
@endphp

<div class="relative isolate min-h-screen overflow-hidden">
    <div class="absolute inset-x-0 top-0 h-72 bg-gradient-to-b from-brand-700/30 via-brand-600/10 to-transparent"></div>
    <div class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-brand-500/20 blur-3xl"></div>
    <div class="absolute right-0 top-1/3 h-80 w-80 rounded-full bg-indigo-400/10 blur-3xl"></div>

    <div class="relative mx-auto flex min-h-screen w-full max-w-7xl items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
        <div class="w-full max-w-xl overflow-hidden rounded-[32px] border border-white/10 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.28)]">
            <section class="border-b border-slate-200 bg-slate-900 px-8 py-8 text-white">
                <div class="flex items-center gap-4">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ config('portal.brand_name') }}" class="h-14 w-auto object-contain">
                    @else
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-sm font-semibold">
                            LP
                        </div>
                    @endif

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-300">Güvenli Giriş</p>
                        <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ config('portal.brand_name') }}</h1>
                    </div>
                </div>
            </section>

            <section class="bg-white px-6 py-8 sm:px-8">
                <span class="inline-flex items-center rounded-full border border-brand-100 bg-brand-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">
                    2 Adımlı Doğrulama
                </span>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-slate-900">Girişinizi doğrulayın</h2>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    <strong>{{ $email_masked }}</strong> adresine gönderilen 6 haneli kodu girin. Kod {{ $expires_at->format('d.m.Y H:i') }} tarihine kadar geçerlidir.
                </p>

                @if(session('status'))
                    <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if($errors->has('code'))
                    <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first('code') }}
                    </div>
                @endif

                <div class="mt-8 rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_18px_40px_rgba(15,23,42,0.08)] sm:p-7">
                    <form method="POST" action="{{ route('login.two-factor.verify') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label class="label" for="code">Doğrulama Kodu</label>
                            <input
                                type="text"
                                id="code"
                                name="code"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                maxlength="6"
                                required
                                autofocus
                                class="input text-center text-2xl tracking-[0.35em]"
                                placeholder="000000"
                            >
                            <p class="mt-2 text-xs text-slate-500">Kod gelmediyse spam klasörünü kontrol edin.</p>
                        </div>

                        <button type="submit" class="btn btn-primary w-full rounded-2xl py-3 text-base shadow-[0_14px_28px_rgba(124,58,237,0.28)]">
                            Doğrulamayı Tamamla
                        </button>
                    </form>

                    <form method="POST" action="{{ route('login.two-factor.resend') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="btn btn-secondary w-full justify-center rounded-2xl py-3">
                            Kodu Yeniden Gönder
                        </button>
                    </form>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                    Başka bir hesapla giriş yapmak isterseniz <a href="{{ route('login') }}" class="font-medium text-brand-700 hover:text-brand-800">giriş ekranına dönün</a>.
                </div>
            </section>
        </div>
    </div>
</div>

</body>
</html>
