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
    $hasSupplierRegistration = \Illuminate\Support\Facades\Route::has('supplier-registration.store');
    $supplierRegistrationUrl = $hasSupplierRegistration ? route('supplier-registration.store') : null;
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

                    @if($hasSupplierRegistration)
                        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                            <p class="font-medium text-slate-700">Henüz hesabınız yok mu?</p>
                            <p class="mt-1">Tedarikçi kaydı oluşturmak için aşağıdaki butona tıklayın. Talebiniz yönetici onayından sonra aktif hale gelecektir.</p>
                            <button type="button" id="open-register-modal"
                                    class="mt-3 w-full rounded-xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-100">
                                Tedarikçi Kaydı Oluştur
                            </button>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>

@if($hasSupplierRegistration)
    {{-- Supplier Registration Modal --}}
    <div id="register-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 px-4 py-8">
        <div class="w-full max-w-lg rounded-[28px] bg-white shadow-2xl overflow-y-auto max-h-[90vh]">
            <div class="px-6 pt-6 pb-2 flex items-center justify-between border-b border-slate-100">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Tedarikçi Kaydı</h2>
                    <p class="mt-1 text-sm text-slate-500">Talebiniz yönetici onayından sonra aktif olacaktır.</p>
                </div>
                <button type="button" id="close-register-modal"
                        class="flex h-9 w-9 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div id="register-success" class="hidden px-6 py-10 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">Talebiniz Alındı!</h3>
                <p class="mt-2 text-sm text-slate-500">Kayıt talebiniz incelemeye alındı. En kısa sürede size bilgi verilecektir.</p>
                <button type="button" id="close-register-success"
                        class="mt-6 btn btn-primary px-8">Tamam</button>
            </div>

            <form id="register-form" class="px-6 py-5 space-y-4" novalidate>
                @csrf

                {{-- Honeypot --}}
                <input type="text" name="website" id="website" value="" autocomplete="off"
                       tabindex="-1" aria-hidden="true" style="display:none!important">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label" for="reg-company-name">Firma Adı <span class="text-red-500">*</span></label>
                        <input type="text" id="reg-company-name" name="company_name"
                               class="input w-full" placeholder="Firma Adı A.Ş." required maxlength="200">
                        <p class="err hidden" id="err-company-name"></p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label" for="reg-company-email">Firma E-posta <span class="text-red-500">*</span></label>
                        <input type="email" id="reg-company-email" name="company_email"
                               class="input w-full" placeholder="info@firma.com" required maxlength="200">
                        <p class="err hidden" id="err-company-email"></p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label" for="reg-contact-name">Adı Soyadı (Yetkili) <span class="text-red-500">*</span></label>
                        <input type="text" id="reg-contact-name" name="contact_name"
                               class="input w-full" placeholder="Ad Soyad" required maxlength="200">
                        <p class="err hidden" id="err-contact-name"></p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label" for="reg-phone-local">Telefon</label>
                        <input type="hidden" id="reg-phone" name="phone" value="">
                        <div class="flex overflow-hidden rounded-2xl border border-slate-200 bg-white focus-within:border-brand-300 focus-within:ring-4 focus-within:ring-brand-100/70">
                            <div class="flex min-w-[92px] items-center justify-center border-r border-slate-200 bg-slate-50 px-4 text-sm font-semibold text-slate-600">
                                +90
                            </div>
                            <input type="tel" id="reg-phone-local"
                                   class="w-full border-0 bg-transparent px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                                   inputmode="numeric" autocomplete="tel-national"
                                   placeholder="507 123 45 67" maxlength="13" aria-label="Telefon numarası">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label" for="reg-notes">Notlar <span class="text-slate-400 font-normal">(opsiyonel)</span></label>
                        <textarea id="reg-notes" name="notes" rows="2"
                                  class="input w-full resize-none"
                                  placeholder="Ek açıklama veya bilgi..." maxlength="1000"></textarea>
                    </div>
                </div>

                <div id="register-global-error" class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                <div class="flex gap-3 pt-1 border-t border-slate-100 pb-1">
                    <button type="submit" id="register-submit"
                            class="btn btn-primary flex-1 py-3">
                        <span id="register-submit-text">Kayıt Talebi Gönder</span>
                        <svg id="register-submit-spinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </button>
                    <button type="button" id="cancel-register-modal" class="btn btn-secondary">İptal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        const modal        = document.getElementById('register-modal');
        const form         = document.getElementById('register-form');
        const successBox   = document.getElementById('register-success');
        const globalErr    = document.getElementById('register-global-error');
        const submitBtn    = document.getElementById('register-submit');
        const submitText   = document.getElementById('register-submit-text');
        const spinner      = document.getElementById('register-submit-spinner');
        const phoneInput   = document.getElementById('reg-phone');
        const phoneLocal   = document.getElementById('reg-phone-local');

        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
            form.classList.remove('hidden');
            successBox.classList.add('hidden');
            form.reset();
            clearErrors();
            syncPhoneValue();
        }
        function clearErrors() {
            document.querySelectorAll('.err').forEach(el => { el.textContent = ''; el.classList.add('hidden'); });
            globalErr.textContent = '';
            globalErr.classList.add('hidden');
        }
        function digitsOnly(value) {
            return value.replace(/\D/g, '');
        }
        function normalizeLocalPhoneDigits(value) {
            let digits = digitsOnly(value);

            if (digits.length > 10 && digits.startsWith('90')) {
                digits = digits.slice(2);
            }

            if (digits.startsWith('0')) {
                digits = digits.slice(1);
            }

            return digits.slice(0, 10);
        }
        function formatLocalPhone(value) {
            const digits = normalizeLocalPhoneDigits(value);
            const parts = [];

            if (digits.slice(0, 3)) {
                parts.push(digits.slice(0, 3));
            }
            if (digits.slice(3, 6)) {
                parts.push(digits.slice(3, 6));
            }
            if (digits.slice(6, 8)) {
                parts.push(digits.slice(6, 8));
            }
            if (digits.slice(8, 10)) {
                parts.push(digits.slice(8, 10));
            }

            return parts.join(' ');
        }
        function syncPhoneValue() {
            const digits = normalizeLocalPhoneDigits(phoneLocal.value);

            if (!digits) {
                phoneInput.value = '';
                return;
            }

            phoneInput.value = '+90 ' + formatLocalPhone(digits);
        }
        function showFieldError(field, msg) {
            const el = document.getElementById('err-' + field);
            if (el) { el.textContent = msg; el.classList.remove('hidden'); }
        }
        function setLoading(on) {
            submitBtn.disabled = on;
            submitText.classList.toggle('hidden', on);
            spinner.classList.toggle('hidden', !on);
        }

        phoneLocal.addEventListener('input', function () {
            this.value = formatLocalPhone(this.value);
            syncPhoneValue();
        });

        document.getElementById('open-register-modal').addEventListener('click', openModal);
        document.getElementById('close-register-modal').addEventListener('click', closeModal);
        document.getElementById('cancel-register-modal').addEventListener('click', closeModal);
        document.getElementById('close-register-success').addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === this) closeModal(); });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            clearErrors();
            syncPhoneValue();
            setLoading(true);

            try {
                const data = new FormData(form);
                const res  = await fetch(@json($supplierRegistrationUrl), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': data.get('_token'), 'Accept': 'application/json' },
                    body: data,
                });
                const json = await res.json();

                if (res.ok) {
                    form.classList.add('hidden');
                    successBox.classList.remove('hidden');
                } else if (res.status === 422 && json.errors) {
                    Object.entries(json.errors).forEach(([field, msgs]) => {
                        const key = field.replace(/_/g, '-');
                        showFieldError(key, msgs[0]);
                    });
                } else if (res.status === 429) {
                    globalErr.textContent = 'Çok fazla deneme yaptınız. Lütfen birkaç dakika bekleyip tekrar deneyin.';
                    globalErr.classList.remove('hidden');
                } else {
                    globalErr.textContent = json.message || 'Bir hata oluştu. Lütfen tekrar deneyin.';
                    globalErr.classList.remove('hidden');
                }
            } catch (_) {
                globalErr.textContent = 'Bağlantı hatası. Lütfen internet bağlantınızı kontrol edin.';
                globalErr.classList.remove('hidden');
            } finally {
                setLoading(false);
            }
        });
    })();
    </script>
@endif

</body>
</html>
