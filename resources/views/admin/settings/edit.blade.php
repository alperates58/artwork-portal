@extends('layouts.app')
@section('title', 'Ayarlar')
@section('page-title', 'Sistem Ayarlari')
@section('page-subtitle', 'Guncelleme, entegrasyon ve altyapi ayarlarini daha net bir alt navigasyon ile yonetin.')

@php
    $tabs = [
        'updates' => [
            'label' => 'Guncellemeler',
            'description' => 'Surum durumu, release notlari ve kontrollu update hazirligi.',
            'eyebrow' => 'Surum ve yayin',
        ],
        'storage' => [
            'label' => 'Depolama / Spaces',
            'description' => 'Aktif disk secimi ve runtime depolama baglantisi.',
            'eyebrow' => 'Dosya depolama',
        ],
        'mikro' => [
            'label' => 'Mikro API',
            'description' => 'ERP baglantisi, zamanlama ve guvenli endpoint ayarlari.',
            'eyebrow' => 'ERP entegrasyonu',
        ],
        'mail' => [
            'label' => 'Mail / Exchange',
            'description' => 'Mail sunucusu ve yeni siparis bildirim davranislari.',
            'eyebrow' => 'Bildirim altyapisi',
        ],
        'formats' => [
            'label' => 'Dosya Formatları',
            'description' => 'İzin verilen dosya uzantıları ve format tanımlarını yönetin.',
            'eyebrow' => 'Yükleme kuralları',
        ],
        'general' => [
            'label' => 'Genel Sistem',
            'description' => 'Read-only uygulama ortami ve calisma zamani ozeti.',
            'eyebrow' => 'Sistem ozeti',
        ],
    ];
    $statusVariant = match($updateStatus['last_status']) {
        'success' => 'success',
        'failed' => 'danger',
        default => 'warning',
    };
    $currentRelease = $updateStatus['current_release'] ?? null;
    $remoteRelease = $updateStatus['latest_remote_release'] ?? null;
    $pendingPreparation = $updateStatus['pending_preparation'] ?? null;
    $canPrepare = $updateStatus['update_available'] === true && filled(data_get($remoteRelease, 'version'));
    $displayTimezone = config('app.timezone', 'Europe/Istanbul');
    $selectedEncryption = old('mail_server.encryption', $mailServer['encryption'] ?? 'none');
    if ($selectedEncryption === null || $selectedEncryption === '') {
        $selectedEncryption = 'none';
    }

    $sectionHighlights = [
        'updates' => [
            'title' => 'Kontrollu surum akisi',
            'summary' => 'Web tarafinda sadece gorunurluk ve hazirlik aksiyonlari sunulur; deploy yine kontrollu CLI akisinda tamamlanir.',
            'points' => [
                'GitHub kontrolu, release notlari ve update gecmisi ayni yerde gorunur.',
                'Hazirlik aksiyonu oncesi hedef release detaylari net sekilde incelenebilir.',
                'Rollback butonu yok; guvenli release disiplini korunur.',
            ],
            'meta' => [
                ['label' => 'Kurulu surum', 'value' => $updateStatus['current_version'] ?: 'Bilinmiyor'],
                ['label' => 'Hedef release', 'value' => data_get($remoteRelease, 'version') ?: 'Bekleniyor'],
            ],
        ],
        'storage' => [
            'title' => 'Depolama baglam notu',
            'summary' => 'Bu bolum local disk ve Spaces gecisini merkezi ve okunur hale getirir; bootstrap `.env` bilgileri yine baslangic noktasi olarak kalir.',
            'points' => [
                'Secret alanlar yalniz degistirilirse yazilir.',
                'Aktif disk secimi mevcut storage mimarisini degistirmez.',
                'Production ve local davranisi ayni ayar anahtarlari uzerinden surer.',
            ],
            'meta' => [
                ['label' => 'Aktif disk', 'value' => $spaces['disk'] ?? 'local'],
                ['label' => 'Bucket', 'value' => $spaces['bucket'] ?? 'Tanimli degil'],
            ],
        ],
        'mikro' => [
            'title' => 'ERP baglanti notu',
            'summary' => 'Mikro erisimi backend tarafinda kalir; bu panel yalniz runtime-safe entegrasyon alanlarini yonetir.',
            'points' => [
                'Kayitli secret alanlar plaintext olarak tekrar gosterilmez.',
                'Zamanlama ve timeout ayarlari mevcut queue tabanli sync akisiyla uyumludur.',
                'Supplier bazli sync ve mevcut integration davranisi korunur.',
            ],
            'meta' => [
                ['label' => 'Durum', 'value' => !empty($mikro['enabled']) ? 'Etkin' : 'Pasif'],
                ['label' => 'Senkron araligi', 'value' => ($mikro['sync_interval_minutes'] ?? 60) . ' dk'],
            ],
        ],
        'mail' => [
            'title' => 'Mail operasyon rehberi',
            'summary' => 'Baglanti testi, test mail ve bildirim ayarlari ayni operasyon yuzeyinde toplandi; kuyruk mantigi korunur.',
            'points' => [
                'Baglanti testi kimlik dogrulama ve SMTP/Exchange erisimini kontrol eder.',
                'Test mail mevcut bildirim hattiyla uyumlu sekilde kuyruga yazilir.',
                'Bos secret input mevcut kayitli degeri korur.',
            ],
            'meta' => [
                ['label' => 'Mailer', 'value' => $generalSystem['mail_mailer'] ?? 'smtp'],
                ['label' => 'Test alicisi', 'value' => $mailNotifications['test_recipient'] ?? 'Tanimsiz'],
            ],
        ],
        'formats' => [
            'title' => 'Dosya format kuralları',
            'summary' => 'Sisteme yüklenebilecek dosya uzantıları burada tanımlanır; her format için kısa açıklama ve tip grubu belirlenir.',
            'points' => [
                'Mevcut formatları silebilir veya düzenleyebilirsiniz.',
                'Yeni satır ekleyerek özel uzantı tanımlayabilirsiniz.',
                'Grup seçimi galeri filtrelerini (PDF, Görsel, Tasarım) etkiler.',
            ],
            'meta' => [
                ['label' => 'Tanımlı format', 'value' => count($fileFormats) . ' adet'],
                ['label' => 'Grup seçenekleri', 'value' => 'PDF, Görsel, Tasarım, Diğer'],
            ],
        ],
        'general' => [
            'title' => 'Read-only sistem ozeti',
            'summary' => 'Bu alan uygulama davranisini degistirmez; mevcut environment, queue ve storage secimini hizli kontrol icin gosterir.',
            'points' => [
                'Bootstrap env detaylari bu ekranda duzenlenmez.',
                'Surum, cache ve session durumu tek yerde gorulur.',
                'Operasyon oncesi kisa sistem kontrolu icin kullanilabilir.',
            ],
            'meta' => [
                ['label' => 'Environment', 'value' => $generalSystem['app_env']],
                ['label' => 'Queue', 'value' => $generalSystem['queue_connection']],
            ],
        ],
    ];

    $activeSection = $tabs[$activeTab];
    $activeAside = $sectionHighlights[$activeTab];
@endphp

@section('content')
<div class="space-y-6">
    <div>
        <div class="min-w-0 space-y-6">
            <section class="card overflow-hidden border-slate-200/90 bg-white/95 shadow-[0_20px_40px_rgba(15,23,42,0.05)]">
                <div class="border-b border-slate-200/80 px-6 py-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">{{ $activeSection['eyebrow'] }}</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-950">{{ $activeSection['label'] }}</h2>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">{{ $activeSection['description'] }}</p>
                        </div>
                        @if($activeTab === 'updates')
                            <x-ui.badge :variant="$statusVariant">
                                {{ match($updateStatus['last_status']) {
                                    'success' => 'Son durum: Basarili',
                                    'failed' => 'Son durum: Hatali',
                                    default => 'Son durum: Kayit yok',
                                } }}
                            </x-ui.badge>
                        @endif
                    </div>
                </div>

                <div class="p-6">
                    <div class="{{ $activeTab === 'updates' ? '' : 'hidden' }}">
                        @php $localCommit = $updateStatus['current_commit']; @endphp
                        <div class="space-y-5">

                            {{-- Status bar --}}
                            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                                <div class="flex flex-wrap gap-5 text-sm">
                                    <div>
                                        <span class="text-xs font-medium uppercase tracking-wide text-slate-400">Sunucu commit</span>
                                        <p class="mt-0.5 font-mono font-semibold text-slate-800">{{ $localCommit ?: '—' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs font-medium uppercase tracking-wide text-slate-400">Branch</span>
                                        <p class="mt-0.5 font-mono font-semibold text-slate-800">{{ $updateStatus['current_branch'] ?: 'main' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs font-medium uppercase tracking-wide text-slate-400">Son güncelleme</span>
                                        <p class="mt-0.5 text-slate-700">
                                            {{ $updateStatus['last_run_at']
                                                ? \Illuminate\Support\Carbon::parse($updateStatus['last_run_at'])->timezone($displayTimezone)->format('d.m.Y H:i')
                                                : 'Kayıt yok' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" id="load-commits-btn" class="btn btn-secondary">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Commit Geçmişini Yükle
                                    </button>
                                    <button type="button" class="btn btn-primary" data-dialog-open="deploy-dialog"
                                        style="background:linear-gradient(180deg,#059669,#047857);box-shadow:0 10px 22px rgba(5,150,105,.2);">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        GitHub'dan Güncelle
                                    </button>
                                </div>
                            </div>

                            {{-- Commit list --}}
                            <div class="card">
                                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                                    <h3 class="text-sm font-semibold text-slate-700">GitHub Commit Geçmişi</h3>
                                    <span id="commits-branch-badge" class="hidden rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-mono text-slate-600"></span>
                                </div>

                                <div id="commits-loading" class="hidden px-5 py-10 text-center">
                                    <div class="mx-auto h-7 w-7 animate-spin rounded-full border-4 border-slate-200 border-t-brand-500"></div>
                                    <p class="mt-3 text-xs text-slate-400">GitHub'dan yükleniyor…</p>
                                </div>

                                <div id="commits-error" class="hidden px-5 py-6 text-center text-sm text-red-600"></div>

                                <div id="commits-empty" class="px-5 py-10 text-center text-sm text-slate-400">
                                    <svg class="mx-auto mb-3 h-8 w-8 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 6h18M3 14h12M3 18h8"/>
                                    </svg>
                                    "Commit Geçmişini Yükle" butonuna basarak GitHub'daki son commit'leri görüntüleyin.
                                </div>

                                <ol id="commits-list" class="hidden divide-y divide-slate-100"></ol>
                            </div>

                        </div>
                    </div>

                    <div class="{{ $activeTab === 'storage' ? '' : 'hidden' }}">
                        <form method="POST" action="{{ route('admin.settings.update', ['tab' => 'storage']) }}" class="space-y-5">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="settings_section" value="storage">
                            <input type="hidden" name="tab" value="storage">
                            <div class="rounded-3xl border border-slate-200 p-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">DigitalOcean Spaces</h3>
                                    <p class="mt-1 text-sm text-slate-500">Bootstrap icin `.env` kullanilmaya devam eder. Buradaki alanlar calisma zamaninda uzerine yazabilir.</p>
                                </div>
                                <div class="mt-5 space-y-5">
                                    <div>
                                        <label class="label">Aktif Disk</label>
                                        <select name="spaces[disk]" class="input" id="disk-select"
                                                onchange="document.getElementById('spaces-fields').style.display = this.value === 'spaces' ? '' : 'none'">
                                            <option value="local" {{ old('spaces.disk', $spaces['disk'] ?? 'local') === 'local' ? 'selected' : '' }}>Local</option>
                                            <option value="spaces" {{ old('spaces.disk', $spaces['disk'] ?? 'local') === 'spaces' ? 'selected' : '' }}>Spaces</option>
                                        </select>
                                    </div>
                                    <div id="spaces-fields" class="space-y-5" style="{{ ($spaces['disk'] ?? 'local') === 'local' ? 'display:none' : '' }}">
                                        <div><label class="label">Access Key</label><input class="input" type="text" name="spaces[key]" value="{{ old('spaces.key', $spaces['key'] ?? '') }}"></div>
                                        <div><label class="label">Secret Key</label><input class="input" type="password" name="spaces[secret]" value="{{ old('spaces.secret', $spaces['secret'] ?? '') }}"></div>
                                        <div><label class="label">Endpoint</label><input class="input" type="url" name="spaces[endpoint]" value="{{ old('spaces.endpoint', $spaces['endpoint'] ?? '') }}"></div>
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div><label class="label">Region</label><input class="input" type="text" name="spaces[region]" value="{{ old('spaces.region', $spaces['region'] ?? '') }}"></div>
                                            <div><label class="label">Bucket</label><input class="input" type="text" name="spaces[bucket]" value="{{ old('spaces.bucket', $spaces['bucket'] ?? '') }}"></div>
                                        </div>
                                        <div><label class="label">CDN / URL</label><input class="input" type="url" name="spaces[url]" value="{{ old('spaces.url', $spaces['url'] ?? '') }}"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end"><button type="submit" class="btn btn-primary">Depolama Ayarlarini Kaydet</button></div>
                        </form>
                    </div>

                    <div class="{{ $activeTab === 'mikro' ? '' : 'hidden' }}">
                        <form method="POST" action="{{ route('admin.settings.update', ['tab' => 'mikro']) }}" class="space-y-5">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="settings_section" value="mikro">
                            <input type="hidden" name="tab" value="mikro">
                            <div class="rounded-3xl border border-slate-200 p-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Mikro API</h3>
                                    <p class="mt-1 text-sm text-slate-500">Mikro erisimi yalniz backend tarafinda kullanilir. Gizli alanlar tekrar ekrana basilmaz.</p>
                                </div>
                                <div class="mt-5 space-y-5">
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" name="mikro[enabled]" value="0">
                                        <input type="checkbox" name="mikro[enabled]" value="1" class="rounded border-slate-300 text-brand-600" {{ old('mikro.enabled', !empty($mikro['enabled']) ? '1' : '0') === '1' ? 'checked' : '' }}>
                                        <label class="text-sm text-slate-700">Mikro entegrasyonu etkin</label>
                                    </div>
                                    <div><label class="label">Base URL</label><input class="input" type="url" name="mikro[base_url]" value="{{ old('mikro.base_url', $mikro['base_url'] ?? '') }}"></div>
                                    <div><label class="label">API Key</label><input class="input" type="password" name="mikro[api_key]" value="" placeholder="{{ !empty($mikro['has_api_key']) ? 'Kayitli anahtar var, degistirmek icin yeniden girin' : 'Opsiyonel' }}"></div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div><label class="label">Kullanici Adi</label><input class="input" type="text" name="mikro[username]" value="" placeholder="{{ !empty($mikro['has_username']) ? 'Kayitli kullanici var, degistirmek icin yeniden girin' : 'Opsiyonel' }}"></div>
                                        <div><label class="label">Sifre</label><input class="input" type="password" name="mikro[password]" value="" placeholder="{{ !empty($mikro['has_password']) ? 'Kayitli sifre var, degistirmek icin yeniden girin' : 'Opsiyonel' }}"></div>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div><label class="label">Sirket Kodu</label><input class="input" type="text" name="mikro[company_code]" value="{{ old('mikro.company_code', $mikro['company_code'] ?? '') }}"></div>
                                        <div><label class="label">Calisma Yili</label><input class="input" type="text" name="mikro[work_year]" value="{{ old('mikro.work_year', $mikro['work_year'] ?? '') }}"></div>
                                    </div>
                                    <div><label class="label">Sevk Endpoint Yolu</label><input class="input" type="text" name="mikro[shipment_endpoint]" value="{{ old('mikro.shipment_endpoint', $mikro['shipment_endpoint'] ?? '') }}" placeholder="/api/dispatch-status"></div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div><label class="label">Senkron Araligi (dk)</label><input class="input" type="number" min="5" max="1440" name="mikro[sync_interval_minutes]" value="{{ old('mikro.sync_interval_minutes', $mikro['sync_interval_minutes'] ?? 60) }}"></div>
                                        <div><label class="label">HTTP Timeout (sn)</label><input class="input" type="number" min="1" max="300" name="mikro[timeout]" value="{{ old('mikro.timeout', $mikro['timeout'] ?? 30) }}"></div>
                                    </div>
                                    <div class="flex items-end">
                                        <label class="flex items-center gap-2 text-sm text-slate-700">
                                            <input type="hidden" name="mikro[verify_ssl]" value="0">
                                            <input type="checkbox" name="mikro[verify_ssl]" value="1" class="rounded border-slate-300 text-brand-600" {{ old('mikro.verify_ssl', !empty($mikro['verify_ssl']) ? '1' : '0') === '1' ? 'checked' : '' }}>
                                            SSL dogrulamasi aktif
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end"><button type="submit" class="btn btn-primary">Mikro Ayarlarini Kaydet</button></div>
                        </form>
                    </div>

                    <div class="{{ $activeTab === 'mail' ? '' : 'hidden' }}">
                        <div class="space-y-6">
                            <form method="POST" action="{{ route('admin.settings.update', ['tab' => 'mail']) }}" class="space-y-6">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="settings_section" value="mail">
                                <input type="hidden" name="tab" value="mail">
                                <div class="rounded-3xl border border-slate-200 p-6 space-y-6">
                                    <div>
                                        <h3 class="text-lg font-semibold text-slate-900">Mail Sunucusu</h3>
                                        <p class="mt-1 text-sm text-slate-500">Yalniz runtime-safe mail sunucusu alanlari burada yonetilir. Ilgisiz altyapi env degerleri bu passtte tasinmaz.</p>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div><label class="label">MAIL_HOST</label><input class="input" type="text" name="mail_server[host]" value="{{ old('mail_server.host', $mailServer['host'] ?? '') }}"></div>
                                        <div><label class="label">MAIL_PORT</label><input class="input" type="number" min="1" max="65535" name="mail_server[port]" value="{{ old('mail_server.port', $mailServer['port'] ?? 587) }}"></div>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div><label class="label">MAIL_USERNAME</label><input class="input" type="text" name="mail_server[username]" value="" placeholder="{{ !empty($mailServer['has_username']) ? 'Kayitli kullanici var, degistirmek icin yeniden girin' : 'Opsiyonel' }}"></div>
                                        <div><label class="label">MAIL_PASSWORD</label><input class="input" type="password" name="mail_server[password]" value="" placeholder="{{ !empty($mailServer['has_password']) ? 'Kayitli sifre var, bos birakilirsa korunur' : 'Opsiyonel' }}"></div>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-3">
                                        <div>
                                            <label class="label">MAIL_ENCRYPTION</label>
                                            <select name="mail_server[encryption]" class="input">
                                                <option value="none" {{ $selectedEncryption === 'none' ? 'selected' : '' }}>Yok</option>
                                                <option value="tls" {{ $selectedEncryption === 'tls' ? 'selected' : '' }}>TLS</option>
                                                <option value="ssl" {{ $selectedEncryption === 'ssl' ? 'selected' : '' }}>SSL</option>
                                            </select>
                                        </div>
                                        <div><label class="label">MAIL_FROM_ADDRESS</label><input class="input" type="email" name="mail_server[from_address]" value="{{ old('mail_server.from_address', $mailServer['from_address'] ?? '') }}"></div>
                                        <div><label class="label">MAIL_FROM_NAME</label><input class="input" type="text" name="mail_server[from_name]" value="{{ old('mail_server.from_name', $mailServer['from_name'] ?? '') }}"></div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 p-4">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900">Mail Sunucusu Aksiyonlari</p>
                                                <p class="mt-1 text-xs text-slate-500">Kayit ettikten sonra baglanti testi ile SMTP/Exchange baglantisini dogrulayabilirsiniz.</p>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <button type="submit" class="btn btn-primary">Mail Ayarlarini Kaydet</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="border-t border-slate-200 pt-6">
                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                            <div>
                                                <h3 class="text-lg font-semibold text-slate-900">Mail Bildirimleri</h3>
                                                <p class="mt-1 text-sm text-slate-500">Yeni siparis bildirim davranisi mevcut sistemle uyumlu sekilde burada yonetilir.</p>
                                            </div>
                                            <div class="rounded-2xl bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                                Otomatik yeni siparis bildirimi yalniz Mikro ile ilk kez gelen siparislerde calisir.
                                            </div>
                                        </div>
                                        <div class="mt-5 flex items-center gap-2">
                                            <input type="hidden" name="mail_notifications[enabled]" value="0">
                                            <input type="checkbox" name="mail_notifications[enabled]" value="1" class="rounded border-slate-300 text-brand-600" {{ old('mail_notifications.enabled', !empty($mailNotifications['enabled']) ? '1' : '0') === '1' ? 'checked' : '' }}>
                                            <label class="text-sm text-slate-700">Yeni siparis mail bildirimleri etkin</label>
                                        </div>
                                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="label">Grafik Departmani Alicilar</label>
                                                <textarea name="mail_notifications[graphics_to]" class="input min-h-24">{{ old('mail_notifications.graphics_to', $mailNotifications['graphics_to'] ?? '') }}</textarea>
                                                <p class="mt-1 text-xs text-slate-500">Virgul veya bosluk ile birden fazla e-posta girilebilir.</p>
                                            </div>
                                            <div>
                                                <label class="label">Yeni Siparis Konu Sablonu</label>
                                                <input class="input" type="text" name="mail_notifications[new_order_subject]" value="{{ old('mail_notifications.new_order_subject', $mailNotifications['new_order_subject'] ?? 'Yeni siparis geldi: {order_no}') }}">
                                                <p class="mt-1 text-xs text-slate-500">Desteklenen alanlar: {order_no}, {supplier}, {order_date}, {line_count}</p>
                                            </div>
                                        </div>
                                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                                            <div><label class="label">CC Listesi</label><textarea name="mail_notifications[graphics_cc]" class="input min-h-20">{{ old('mail_notifications.graphics_cc', $mailNotifications['graphics_cc'] ?? '') }}</textarea></div>
                                            <div><label class="label">BCC Listesi</label><textarea name="mail_notifications[graphics_bcc]" class="input min-h-20">{{ old('mail_notifications.graphics_bcc', $mailNotifications['graphics_bcc'] ?? '') }}</textarea></div>
                                        </div>
                                        <div class="mt-4 grid gap-4 md:grid-cols-3">
                                            <div><label class="label">Override From Name</label><input class="input" type="text" name="mail_notifications[override_from_name]" value="{{ old('mail_notifications.override_from_name', $mailNotifications['override_from_name'] ?? '') }}" placeholder="Bos birakilirsa fallback kullanilir"></div>
                                            <div><label class="label">Override From Address</label><input class="input" type="email" name="mail_notifications[override_from_address]" value="{{ old('mail_notifications.override_from_address', $mailNotifications['override_from_address'] ?? '') }}" placeholder="portal@sirketiniz.com"></div>
                                            <div><label class="label">Kayitli Test Alicisi</label><input class="input" type="email" name="mail_notifications[test_recipient]" value="{{ old('mail_notifications.test_recipient', $mailNotifications['test_recipient'] ?? '') }}" placeholder="grafik@sirketiniz.com"></div>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('admin.settings.mail-connection-test', ['tab' => 'mail']) }}" class="rounded-3xl border border-slate-200 p-6">
                                @csrf
                                <input type="hidden" name="tab" value="mail">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Baglantiyi Test Et</p>
                                        <p class="mt-1 text-xs text-slate-500">Kayitli mail sunucusu ayarlari ile baglanti ve kimlik dogrulama testi yapar. Mail gondermez.</p>
                                    </div>
                                    <button type="submit" class="btn btn-secondary">Baglantiyi Test Et</button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('admin.settings.mail-test', ['tab' => 'mail']) }}" class="rounded-3xl border border-slate-200 p-6 space-y-4">
                                @csrf
                                <input type="hidden" name="tab" value="mail">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Test Mail Gonder</p>
                                        <p class="mt-1 text-xs text-slate-500">Kayitli test alicisi varsa o kullanilir. Yoksa asagidaki adrese tek seferlik test gonderebilirsiniz.</p>
                                    </div>
                                    <button type="submit" class="btn btn-secondary">Test Mail Gonder</button>
                                </div>
                                <div>
                                    <label class="label">Tek Seferlik Test Alicisi</label>
                                    <input class="input" type="email" name="test_mail_recipient" value="{{ old('test_mail_recipient', $mailNotifications['test_recipient'] ?? '') }}" placeholder="test@sirketiniz.com">
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- ═══ FORMATS TAB ═══ --}}
                    <div class="{{ $activeTab === 'formats' ? '' : 'hidden' }}">
                        <form method="POST" action="{{ route('admin.settings.update', ['tab' => 'formats']) }}" id="formats-form">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="settings_section" value="formats">
                            <input type="hidden" name="tab" value="formats">

                            {{-- GRUP YÖNETİMİ --}}
                            <div class="rounded-3xl border border-slate-200 p-6 space-y-4 mb-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-base font-semibold text-slate-900">Dosya Grupları</h3>
                                        <p class="mt-1 text-sm text-slate-500">Galeri filtreleri ve format seçeneklerinde kullanılan gruplar.</p>
                                    </div>
                                    <button type="button" id="add-group-row"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100 transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Yeni Grup Ekle
                                    </button>
                                </div>
                                <div class="grid grid-cols-[1fr_1fr_40px] gap-2 px-1">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Anahtar (key)</p>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Görünen Ad</p>
                                    <p></p>
                                </div>
                                <div id="group-rows" class="space-y-2">
                                    @foreach($fileGroups as $gi => $grp)
                                        <div class="group-row grid grid-cols-[1fr_1fr_40px] gap-2 items-center rounded-2xl border border-slate-100 bg-slate-50/60 px-3 py-2.5">
                                            <input type="text" name="formats[groups][{{ $gi }}][key]"
                                                   value="{{ $grp['key'] }}"
                                                   placeholder="image"
                                                   class="input input-sm font-mono w-full"/>
                                            <input type="text" name="formats[groups][{{ $gi }}][label]"
                                                   value="{{ $grp['label'] }}"
                                                   placeholder="Görseller"
                                                   class="input input-sm w-full"/>
                                            <div class="flex justify-center">
                                                <button type="button" class="remove-group-row rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors" title="Sil">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="text-[11px] text-slate-400">Anahtar küçük harf, boşluksuz olmalı (örn: <code>image</code>, <code>design</code>). Galeri tab'ları ve format grup seçenekleri buradan beslenir.</p>
                            </div>

                            {{-- FORMAT LİSTESİ --}}
                            <div class="rounded-3xl border border-slate-200 p-6 space-y-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-base font-semibold text-slate-900">İzin Verilen Dosya Formatları</h3>
                                        <p class="mt-1 text-sm text-slate-500">Sisteme yüklenebilecek uzantıları ve tanımlarını yönetin.</p>
                                    </div>
                                    <button type="button" id="add-format-row"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-medium text-brand-700 hover:bg-brand-100 transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Yeni Format Ekle
                                    </button>
                                </div>

                                {{-- Header --}}
                                <div class="grid grid-cols-[80px_1fr_160px_40px] gap-3 px-1">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Uzantı</p>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tanım</p>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Grup</p>
                                    <p></p>
                                </div>

                                {{-- Format satırları --}}
                                <div id="format-rows" class="space-y-2">
                                    @foreach($fileFormats as $i => $fmt)
                                        @php
                                            $groupColors = [
                                                'pdf'    => 'bg-red-50 text-red-700 border-red-200',
                                                'image'  => 'bg-sky-50 text-sky-700 border-sky-200',
                                                'design' => 'bg-orange-50 text-orange-700 border-orange-200',
                                                'other'  => 'bg-slate-100 text-slate-600 border-slate-200',
                                            ];
                                            $badge = $groupColors[$fmt['group']] ?? $groupColors['other'];
                                        @endphp
                                        <div class="format-row grid grid-cols-[80px_1fr_160px_40px] gap-3 items-center rounded-2xl border border-slate-100 bg-slate-50/60 px-3 py-2.5">
                                            <div>
                                                <input type="text"
                                                       name="formats[list][{{ $i }}][ext]"
                                                       value="{{ $fmt['ext'] }}"
                                                       placeholder="PDF"
                                                       maxlength="10"
                                                       class="input input-sm font-mono uppercase w-full text-center"/>
                                            </div>
                                            <div>
                                                <input type="text"
                                                       name="formats[list][{{ $i }}][label]"
                                                       value="{{ $fmt['label'] }}"
                                                       placeholder="Format açıklaması"
                                                       class="input input-sm w-full"/>
                                            </div>
                                            <div>
                                                <select name="formats[list][{{ $i }}][group]" class="input input-sm w-full">
                                                    @foreach($fileGroups as $grp)
                                                        <option value="{{ $grp['key'] }}" {{ ($fmt['group'] ?? '') === $grp['key'] ? 'selected' : '' }}>{{ $grp['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="flex justify-center">
                                                <button type="button" class="remove-format-row rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors" title="Sil">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="flex justify-end pt-2 border-t border-slate-100">
                                    <button type="submit" class="btn btn-primary px-8">Formatları Kaydet</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="{{ $activeTab === 'general' ? '' : 'hidden' }}">
                        <div class="grid gap-6 2xl:grid-cols-2">
                            <div class="rounded-3xl border border-slate-200 p-6 space-y-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Genel Sistem Bilgileri</h3>
                                    <p class="mt-1 text-sm text-slate-500">Bu sekme read-only bilgi sunar. Altyapi ve bootstrap env degerleri bu passtte admin paneline tasinmamistir.</p>
                                </div>
                                <dl class="grid gap-4 md:grid-cols-2">
                                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Uygulama Adi</dt><dd class="mt-1 text-sm text-slate-900">{{ $generalSystem['app_name'] }}</dd></div>
                                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Surum</dt><dd class="mt-1 font-mono text-sm text-slate-900">{{ $generalSystem['app_version'] }}</dd></div>
                                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Environment</dt><dd class="mt-1 text-sm text-slate-900">{{ $generalSystem['app_env'] }}</dd></div>
                                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Timezone</dt><dd class="mt-1 text-sm text-slate-900">{{ $generalSystem['app_timezone'] }}</dd></div>
                                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Queue</dt><dd class="mt-1 text-sm text-slate-900">{{ $generalSystem['queue_connection'] }}</dd></div>
                                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Cache</dt><dd class="mt-1 text-sm text-slate-900">{{ $generalSystem['cache_store'] }}</dd></div>
                                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Session</dt><dd class="mt-1 text-sm text-slate-900">{{ $generalSystem['session_driver'] }}</dd></div>
                                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Aktif Disk</dt><dd class="mt-1 text-sm text-slate-900">{{ $generalSystem['filesystem_disk'] }}</dd></div>
                                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Varsayilan Mailer</dt><dd class="mt-1 text-sm text-slate-900">{{ $generalSystem['mail_mailer'] }}</dd></div>
                                </dl>
                            </div>
                            <div class="rounded-3xl border border-slate-200 p-6 space-y-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Kapsam Notu</h3>
                                    <p class="mt-1 text-sm text-slate-500">Bu pass yalniz admin icin uygun ve runtime-safe ayarlari panel yuzeyine tasir.</p>
                                </div>
                                <ul class="list-inside list-disc space-y-2 text-sm text-slate-700">
                                    <li>MAIL_MAILER, MAIL_URL, MAIL_SCHEME gibi bootstrap detaylari burada yonetilmez.</li>
                                    <li>Mail disi altyapi env degerleri admin ayarlarina tasinmamistir.</li>
                                    <li>Mevcut update, Spaces, Mikro ve mail notification persistence anahtarlari korunur.</li>
                                    <li>Secret alanlar plaintext olarak tekrar render edilmez.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@push('scripts')
<script>
/* ── Commit geçmişi ── */
(function () {
    var loadBtn    = document.getElementById('load-commits-btn');
    var loadingEl  = document.getElementById('commits-loading');
    var errorEl    = document.getElementById('commits-error');
    var emptyEl    = document.getElementById('commits-empty');
    var listEl     = document.getElementById('commits-list');
    var branchBadge = document.getElementById('commits-branch-badge');
    var localCommit = '{{ $localCommit }}';

    if (!loadBtn) return;

    loadBtn.addEventListener('click', function () {
        loadBtn.disabled = true;
        emptyEl.classList.add('hidden');
        errorEl.classList.add('hidden');
        listEl.classList.add('hidden');
        loadingEl.classList.remove('hidden');

        fetch('{{ route('admin.settings.commits') }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            loadingEl.classList.add('hidden');
            loadBtn.disabled = false;

            if (data.error) {
                errorEl.textContent = data.error;
                errorEl.classList.remove('hidden');
                return;
            }

            var commits = data.commits || [];
            var branch  = data.branch  || 'main';

            branchBadge.textContent = branch;
            branchBadge.classList.remove('hidden');

            // Find index of local commit → everything before = new, at = current, after = applied
            var localIdx = commits.findIndex(function (c) { return c.sha === localCommit; });

            listEl.innerHTML = '';
            commits.forEach(function (c, i) {
                var isNew     = localIdx === -1 || i < localIdx;
                var isCurrent = i === localIdx;
                var date      = c.date ? new Date(c.date) : null;
                var dateStr   = date ? date.toLocaleDateString('tr-TR', { day:'2-digit', month:'short', year:'numeric' }) : '—';
                var timeStr   = date ? date.toLocaleTimeString('tr-TR', { hour:'2-digit', minute:'2-digit' }) : '';

                var dotColor  = isCurrent ? 'bg-emerald-500 ring-4 ring-emerald-100'
                              : isNew     ? 'bg-blue-500 ring-4 ring-blue-100'
                              : 'bg-slate-300';
                var shaColor  = isCurrent ? 'text-emerald-700 bg-emerald-50 border-emerald-200'
                              : isNew     ? 'text-blue-700 bg-blue-50 border-blue-200'
                              : 'text-slate-500 bg-slate-50 border-slate-200';
                var badge     = isCurrent ? '<span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Sunucuda kurulu</span>'
                              : isNew     ? '<span class="ml-2 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700">Yeni</span>'
                              : '';

                var li = document.createElement('li');
                li.className = 'flex items-start gap-4 px-5 py-3.5';
                li.innerHTML =
                    '<div class="mt-1.5 flex-shrink-0"><span class="inline-block h-2.5 w-2.5 rounded-full ' + dotColor + '"></span></div>' +
                    '<div class="min-w-0 flex-1">' +
                        '<p class="flex flex-wrap items-center gap-1 text-sm font-medium text-slate-800">' +
                            escHtml(c.message) + badge +
                        '</p>' +
                        '<p class="mt-0.5 flex flex-wrap items-center gap-3 text-xs text-slate-400">' +
                            '<span>' + escHtml(c.author) + '</span>' +
                            '<span>' + dateStr + ' ' + timeStr + '</span>' +
                        '</p>' +
                    '</div>' +
                    '<div class="flex-shrink-0">' +
                        (c.url
                            ? '<a href="' + c.url + '" target="_blank" class="rounded-lg border px-2 py-0.5 font-mono text-[11px] ' + shaColor + ' hover:opacity-80">' + escHtml(c.sha) + '</a>'
                            : '<span class="rounded-lg border px-2 py-0.5 font-mono text-[11px] ' + shaColor + '">' + escHtml(c.sha) + '</span>') +
                    '</div>';
                listEl.appendChild(li);
            });

            listEl.classList.remove('hidden');
        })
        .catch(function (err) {
            loadingEl.classList.add('hidden');
            loadBtn.disabled = false;
            errorEl.textContent = 'İstek başarısız: ' + err.message;
            errorEl.classList.remove('hidden');
        });
    });

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
<script>
(function () {
    /* ── Grup satırları ── */
    const groupContainer = document.getElementById('group-rows');
    const addGroupBtn    = document.getElementById('add-group-row');

    function reindexRows(container, rowClass) {
        container.querySelectorAll('.' + rowClass).forEach(function (row, i) {
            row.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(/\[\d+\]/, '[' + i + ']');
            });
        });
    }

    function currentGroupOptions() {
        if (!groupContainer) return '<option value="other">Diğer</option>';
        return Array.from(groupContainer.querySelectorAll('.group-row')).map(function (row) {
            const keyIn   = row.querySelector('input[name*="[key]"]');
            const labelIn = row.querySelector('input[name*="[label]"]');
            const k = (keyIn?.value || '').trim();
            const l = (labelIn?.value || k || 'Diğer').trim();
            if (!k) return '';
            return `<option value="${k}">${l}</option>`;
        }).join('');
    }

    if (addGroupBtn && groupContainer) {
        addGroupBtn.addEventListener('click', function () {
            const idx = groupContainer.querySelectorAll('.group-row').length;
            const row = document.createElement('div');
            row.className = 'group-row grid grid-cols-[1fr_1fr_40px] gap-2 items-center rounded-2xl border border-emerald-100 bg-emerald-50/40 px-3 py-2.5';
            row.innerHTML = `
                <input type="text" name="formats[groups][${idx}][key]" placeholder="my_group" class="input input-sm font-mono w-full"/>
                <input type="text" name="formats[groups][${idx}][label]" placeholder="Grup Adı" class="input input-sm w-full"/>
                <div class="flex justify-center">
                    <button type="button" class="remove-group-row rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors" title="Sil">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>`;
            groupContainer.appendChild(row);
            row.querySelector('input').focus();
        });

        groupContainer.addEventListener('click', function (e) {
            const btn = e.target.closest('.remove-group-row');
            if (!btn) return;
            btn.closest('.group-row').remove();
            reindexRows(groupContainer, 'group-row');
        });
    }

    /* ── Format satırları ── */
    const container = document.getElementById('format-rows');
    const addBtn    = document.getElementById('add-format-row');
    if (!container || !addBtn) return;

    addBtn.addEventListener('click', function () {
        const idx  = container.querySelectorAll('.format-row').length;
        const row  = document.createElement('div');
        row.className = 'format-row grid grid-cols-[80px_1fr_160px_40px] gap-3 items-center rounded-2xl border border-brand-100 bg-brand-50/40 px-3 py-2.5';
        row.innerHTML = `
            <div><input type="text" name="formats[list][${idx}][ext]" placeholder="EXT" maxlength="10" class="input input-sm font-mono uppercase w-full text-center"/></div>
            <div><input type="text" name="formats[list][${idx}][label]" placeholder="Format açıklaması" class="input input-sm w-full"/></div>
            <div><select name="formats[list][${idx}][group]" class="input input-sm w-full">${currentGroupOptions()}</select></div>
            <div class="flex justify-center">
                <button type="button" class="remove-format-row rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors" title="Sil">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>`;
        container.appendChild(row);
        row.querySelector('input').focus();
    });

    container.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-format-row');
        if (!btn) return;
        btn.closest('.format-row').remove();
        reindexRows(container, 'format-row');
    });
})();
</script>
@endpush

@push('modals')
{{-- Deploy Dialog --}}
<dialog id="deploy-dialog" class="update-modal w-full max-w-xl rounded-3xl border border-slate-200 p-0 shadow-2xl">
    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
        <h3 class="text-sm font-semibold text-slate-800">GitHub'dan Güncelle</h3>
        <button type="button" data-dialog-close class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div id="deploy-idle" class="px-6 py-5 space-y-4">
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold mb-1">Bu işlem sırayla şunları yapacak:</p>
            <ol class="list-decimal list-inside space-y-1 text-amber-800">
                <li><code class="font-mono text-xs">git pull origin main</code> — GitHub'dan son kodu çeker</li>
                <li><code class="font-mono text-xs">config:clear</code> + <code class="font-mono text-xs">cache:clear</code> — eski önbelleği temizler</li>
                <li><code class="font-mono text-xs">portal:update</code> — migration, storage:link, optimize, queue:restart</li>
            </ol>
        </div>
        <p class="text-sm text-slate-600">Sunucu üzerinde doğrudan çalışır. Devam etmek istiyor musunuz?</p>
        <div class="flex justify-end gap-3 pt-1">
            <button type="button" data-dialog-close class="btn btn-secondary">İptal</button>
            <button type="button" id="deploy-confirm-btn"
                class="btn btn-primary"
                style="background: linear-gradient(180deg,#059669,#047857); box-shadow: 0 10px 22px rgba(5,150,105,.2);">
                Evet, Güncelle
            </button>
        </div>
    </div>

    <div id="deploy-running" class="hidden px-6 py-8 text-center">
        <div class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-emerald-500"></div>
        <p class="text-sm font-medium text-slate-700">Güncelleme çalışıyor…</p>
        <p class="mt-1 text-xs text-slate-400">Bu işlem birkaç saniye sürebilir. Sayfayı kapatmayın.</p>
    </div>

    <div id="deploy-result" class="hidden px-6 py-5 space-y-4">
        <div id="deploy-result-badge" class="rounded-2xl px-4 py-3 text-sm font-semibold"></div>
        <div id="deploy-steps" class="space-y-3 max-h-80 overflow-y-auto"></div>
        <div id="deploy-result-actions" class="flex flex-wrap justify-end gap-3 pt-1">
            <button type="button" onclick="location.reload()" class="btn btn-secondary">Sayfayı Yenile</button>
        </div>
    </div>
</dialog>

<script>
(function () {
    const CSRF   = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const DEPLOY_URL    = '{{ route('admin.settings.deploy') }}';
    const APPLY_URL     = '{{ route('admin.settings.apply-only') }}';

    const confirmBtn = document.getElementById('deploy-confirm-btn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', () => runDeploy(DEPLOY_URL));

    async function runDeploy(url) {
        const idleEl    = document.getElementById('deploy-idle');
        const runningEl = document.getElementById('deploy-running');
        const resultEl  = document.getElementById('deploy-result');
        const badgeEl   = document.getElementById('deploy-result-badge');
        const stepsEl   = document.getElementById('deploy-steps');
        const actionsEl = document.getElementById('deploy-result-actions');

        idleEl.classList.add('hidden');
        resultEl.classList.add('hidden');
        runningEl.classList.remove('hidden');

        try {
            const res  = await fetch(url, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            });
            const data = await res.json();

            runningEl.classList.add('hidden');
            resultEl.classList.remove('hidden');
            stepsEl.innerHTML  = '';
            actionsEl.innerHTML = '<button type="button" onclick="location.reload()" class="btn btn-secondary">Sayfayı Yenile</button>';

            if (data.ok) {
                badgeEl.className   = 'rounded-2xl px-4 py-3 text-sm font-semibold bg-emerald-50 border border-emerald-200 text-emerald-800';
                badgeEl.textContent = 'Güncelleme başarıyla tamamlandı.';
            } else {
                badgeEl.className   = 'rounded-2xl px-4 py-3 text-sm font-semibold bg-red-50 border border-red-200 text-red-800';
                badgeEl.textContent = 'Güncelleme sırasında bir hata oluştu.';

                // git izin hatası → Artisan Uygula butonu göster
                if (data.git_failed) {
                    const applyBtn = document.createElement('button');
                    applyBtn.type = 'button';
                    applyBtn.className = 'btn btn-primary';
                    applyBtn.style = 'background:linear-gradient(180deg,#2563eb,#1d4ed8)';
                    applyBtn.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Artisan Adımlarını Uygula';
                    applyBtn.addEventListener('click', () => runDeploy(APPLY_URL));
                    actionsEl.prepend(applyBtn);

                    const hint = document.createElement('p');
                    hint.className = 'text-xs text-slate-500';
                    hint.textContent = 'Git pull\'u sunucuda manuel çalıştırdıysanız sadece artisan adımlarını uygulayabilirsiniz.';
                    actionsEl.after(hint);
                }
            }

            (data.steps || []).forEach(function (step) {
                const div = document.createElement('div');
                div.className = 'rounded-xl border px-4 py-3 ' + (step.ok ? 'border-emerald-100 bg-emerald-50' : 'border-red-100 bg-red-50');
                div.innerHTML =
                    '<p class="flex items-center gap-2 text-xs font-semibold ' + (step.ok ? 'text-emerald-700' : 'text-red-700') + '">' +
                    (step.ok
                        ? '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>'
                        : '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>') +
                    '<code class="font-mono">' + escHtml(step.cmd) + '</code></p>' +
                    '<pre class="mt-2 whitespace-pre-wrap text-[11px] text-slate-600 leading-relaxed">' + escHtml(step.output) + '</pre>';
                stepsEl.appendChild(div);
            });
        } catch (err) {
            runningEl.classList.add('hidden');
            resultEl.classList.remove('hidden');
            badgeEl.className   = 'rounded-2xl px-4 py-3 text-sm font-semibold bg-red-50 border border-red-200 text-red-800';
            badgeEl.textContent = 'İstek başarısız oldu: ' + err.message;
        }
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
})();
</script>
@endpush

@endsection
