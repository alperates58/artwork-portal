<!DOCTYPE html>
<html lang="tr">
<head>
    @include('partials.ui-head', ['title' => 'Giriş'])
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
        <div class="grid w-full max-w-5xl overflow-hidden rounded-[32px] border border-white/10 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.28)] lg:grid-cols-[1.08fr_0.92fr]">
            <section class="relative hidden overflow-hidden bg-slate-900 px-10 py-12 text-white lg:flex lg:flex-col">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(139,92,246,0.32),_transparent_48%)]"></div>
                <div class="absolute inset-x-0 bottom-0 h-56 bg-gradient-to-t from-brand-700/25 to-transparent"></div>

                <div class="relative flex items-center gap-4">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ config('portal.brand_name') }}" class="h-16 w-auto object-contain drop-shadow-[0_10px_22px_rgba(15,23,42,0.28)]">
                    @else
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 text-lg font-semibold">
                            LP
                        </div>
                    @endif

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-brand-300">Lider Kozmetik</p>
                        <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ config('portal.brand_name') }}</h1>
                    </div>
                </div>

                <div class="relative mt-16 space-y-6">
                    <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-slate-200">
                        Tedarikçi ve iş ortağı portalı
                    </span>

                    <div class="space-y-4">
                        <h2 class="max-w-md text-4xl font-semibold leading-tight">
                            Sipariş ve artwork süreçlerinizi tek yerden yönetin.
                        </h2>
                        <p class="max-w-xl text-sm leading-7 text-slate-300">
                            Lider Portal üzerinden size atanmış siparişleri görüntüleyebilir, artwork revizyonlarını takip edebilir ve bekleyen onay adımlarını hızlıca tamamlayabilirsiniz.
                        </p>
                    </div>
                </div>

                <div class="relative mt-auto grid gap-4 pt-12">
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-sm">
                        <p class="text-sm font-semibold text-white">Portalda sizi neler bekliyor?</p>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            Güncel siparişlerinizi, artwork dosyalarınızı ve onay bekleyen adımları tek akışta takip ederek süreci daha düzenli ilerletebilirsiniz.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Siparişler</p>
                            <p class="mt-2 text-sm font-semibold text-white">Size atanmış siparişleri takip edin</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Artwork</p>
                            <p class="mt-2 text-sm font-semibold text-white">Revizyon ve dosya akışını görüntüleyin</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Onaylar</p>
                            <p class="mt-2 text-sm font-semibold text-white">Bekleyen aksiyonları hızlıca tamamlayın</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-white px-6 py-8 sm:px-10 sm:py-10 lg:px-12 lg:py-12">
                <div class="mx-auto flex w-full max-w-md flex-col justify-center">
                    <div class="flex items-center gap-4 lg:hidden">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ config('portal.brand_name') }}" class="h-14 w-auto object-contain">
                        @else
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600 text-sm font-semibold text-white">
                                LP
                            </div>
                        @endif

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.26em] text-brand-600">İş Ortağı Girişi</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">{{ config('portal.brand_name') }}</p>
                        </div>
                    </div>

                    <div class="mt-8 lg:mt-0">
                        <span class="inline-flex items-center rounded-full border border-brand-100 bg-brand-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">
                            Güvenli erişim
                        </span>
                        <h2 class="mt-4 text-3xl font-semibold tracking-tight text-slate-900">Lider Portal’a giriş yapın</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Tedarikçi hesabınızla giriş yaparak sipariş, artwork ve onay ekranlarına erişin.
                        </p>
                    </div>

                    @if(session('status'))
                        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if($errors->has('email'))
                        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ $errors->first('email') }}
                        </div>
                    @endif

                    <div class="mt-8 rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_18px_40px_rgba(15,23,42,0.08)] sm:p-7">
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
                            </div>

                            <div>
                                <div class="mb-1.5 flex items-center justify-between gap-3">
                                    <label class="label mb-0" for="password">Şifre</label>
                                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-600 transition hover:text-brand-700">
                                        Şifremi unuttum
                                    </a>
                                </div>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    autocomplete="current-password"
                                    required
                                    class="input @error('password') error @enderror"
                                    placeholder="••••••••"
                                >

                                @error('password')
                                    <p class="err">{{ $message }}</p>
                                @enderror
                            </div>

                            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 transition hover:border-brand-200 hover:bg-brand-50/50">
                                <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" {{ old('remember') ? 'checked' : '' }}>
                                <span>Beni hatırla</span>
                            </label>

                            <button type="submit" class="btn btn-primary w-full rounded-2xl py-3 text-base shadow-[0_14px_28px_rgba(124,58,237,0.28)]">
                                Giriş Yap
                            </button>
                        </form>
                    </div>

                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                        Hesap açmak için sistem yöneticisi ile iletişime geçin.
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

</body>
</html>
