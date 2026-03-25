@extends('layouts.app')
@section('title', 'Ayarlar')
@section('page-title', 'Sistem Ayarlari')

@php
    $tabs = [
        'updates' => 'Guncellemeler',
        'storage' => 'Depolama / Spaces',
        'mikro' => 'Mikro API',
        'mail' => 'Mail / Exchange',
        'general' => 'Genel Sistem',
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
@endphp

@section('content')
<div class="space-y-6">
    <div class="card p-3">
        <div class="flex flex-wrap gap-2">
            @foreach($tabs as $tabKey => $tabLabel)
                <a href="{{ route('admin.settings.edit', ['tab' => $tabKey]) }}" class="settings-tab-link {{ $activeTab === $tabKey ? 'active' : '' }}">
                    {{ $tabLabel }}
                </a>
            @endforeach
        </div>
    </div>
    <div class="{{ $activeTab === 'updates' ? '' : 'hidden' }}">
        <div class="card p-6 space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Uygulama Guncelleme Durumu</h2>
                    <p class="mt-1 text-sm text-slate-500">Release notlari, surum bilgileri ve guvenli admin aksiyonlari bu sekmeden yonetilir.</p>
                </div>
                <x-ui.badge :variant="$statusVariant">
                    {{ match($updateStatus['last_status']) {
                        'success' => 'Son durum: Basarili',
                        'failed' => 'Son durum: Hatali',
                        default => 'Son durum: Kayit yok',
                    } }}
                </x-ui.badge>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Kurulu Surum</p>
                    <p class="mt-2 font-mono text-sm text-slate-900">{{ $updateStatus['current_version'] ?: 'Bilinmiyor' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['current_branch'] ? 'Branch: '.$updateStatus['current_branch'] : 'Git branch bilgisi okunamadi' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['current_commit'] ? 'Commit: '.$updateStatus['current_commit'] : 'Commit bilgisi okunamadi' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Hedef Release</p>
                    <p class="mt-2 font-mono text-sm text-slate-900">{{ data_get($remoteRelease, 'version') ?: 'Henuz kontrol edilmedi' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ data_get($remoteRelease, 'title') ?: 'Release manifest bilgisi yok' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['last_checked_at'] ? \Illuminate\Support\Carbon::parse($updateStatus['last_checked_at'])->timezone($displayTimezone)->format('d.m.Y H:i') : 'Henuz GitHub kontrolu yok' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Son Bilinen Deploy</p>
                    <p class="mt-2 font-mono text-sm text-slate-900">{{ $updateStatus['last_deployed_version'] ?: 'Kayit yok' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['last_run_at'] ? \Illuminate\Support\Carbon::parse($updateStatus['last_run_at'])->timezone($displayTimezone)->format('d.m.Y H:i') : 'Henuz portal:update kaydi yok' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['last_message'] ?: 'Son run mesaji bulunmuyor.' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Update Durumu</p>
                    <p class="mt-2 text-sm text-slate-900">
                        @if($updateStatus['update_available'] === true)
                            Yeni bir surum uygulanmaya hazir gorunuyor.
                        @elseif($updateStatus['update_available'] === false)
                            Kurulu surum ile son kontrol edilen release eslesiyor.
                        @else
                            Henuz guvenilir bir update karsilastirmasi yapilmadi.
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['last_check_message'] ?: 'GitHub kontrol mesaji bulunmuyor.' }}</p>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                <div class="rounded-xl border border-slate-200 p-4 xl:col-span-2">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400">Guvenli Admin Aksiyonlari</p>
                            <p class="mt-2 text-sm text-slate-900">Web istegi icinden tehlikeli deploy komutlari calistirilmaz. Yalnizca guvenli kontrol ve hazirlik aksiyonlari sunulur.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('admin.settings.update-check', ['tab' => 'updates']) }}">
                                @csrf
                                <input type="hidden" name="tab" value="updates">
                                <button type="submit" class="btn btn-secondary">GitHub Kontrolü Yap</button>
                            </form>
                            <button type="button" class="btn btn-primary" data-dialog-open="update-confirmation" {{ $canPrepare ? '' : 'disabled' }}>
                                Guncellemeyi Incele
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Kurulu Release Ozeti</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ data_get($currentRelease, 'title') ?: 'Surum manifesti bulunamadi' }}</p>
                            <p class="mt-2 text-sm text-slate-600">{{ data_get($currentRelease, 'summary') ?: 'Mevcut surum icin aciklama yok.' }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Hazir Hedef Release</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ data_get($remoteRelease, 'title') ?: 'Henuz hedef release yok' }}</p>
                            <p class="mt-2 text-sm text-slate-600">{{ data_get($remoteRelease, 'summary') ?: 'GitHub kontrolu yapildiginda release ozeti burada gorunur.' }}</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Gelen Değişiklikler</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse($remoteRelease['changed_modules'] ?? [] as $module)
                                <span class="badge badge-gray">{{ $module }}</span>
                            @empty
                                <span class="text-sm text-slate-500">Modul listesi yok.</span>
                            @endforelse
                        </div>
                        <ul class="mt-3 list-inside list-disc space-y-1 text-sm text-slate-700">
                            @forelse($remoteRelease['change_summary'] ?? [] as $item)
                                <li>{{ $item }}</li>
                            @empty
                                <li>Detayli release notu bulunmuyor.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-amber-700">Rollback Notu</p>
                        <p class="mt-2 text-sm text-amber-900">Rollback ancak release dizini, image tag veya snapshot stratejisi ile guvenli hale gelir. Bu panel rollback butonu sunmaz.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Bekleyen Hazirlik</p>
                        @if($pendingPreparation)
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $pendingPreparation['from_version'] ?: '?' }} -> {{ $pendingPreparation['to_version'] ?: '?' }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $pendingPreparation['release_title'] ?: 'Release basligi yok' }}</p>
                        @else
                            <p class="mt-2 text-sm text-slate-600">Bekleyen admin hazirligi yok.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-400">Update Geçmişi</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500">
                                <th class="pb-2 pr-4">Tip</th>
                                <th class="pb-2 pr-4">Durum</th>
                                <th class="pb-2 pr-4">Surum Gecisi</th>
                                <th class="pb-2">Release</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($updateStatus['history'] as $event)
                                <tr class="align-top">
                                    <td class="py-3 pr-4">{{ match($event['type']) { 'run' => 'Update Run', 'prepare' => 'Hazirlik', default => 'GitHub Check' } }}</td>
                                    <td class="py-3 pr-4">{{ $event['status'] }}</td>
                                    <td class="py-3 pr-4 font-mono text-xs">{{ $event['from_version'] ?: ($event['local_version'] ?: '-') }} -> {{ $event['to_version'] ?: ($event['remote_version'] ?: '-') }}</td>
                                    <td class="py-3 text-sm text-slate-900">{{ $event['release_title'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-sm text-slate-500">Henuz update gecmisi kaydi yok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <dialog id="update-confirmation" class="update-modal w-full max-w-3xl rounded-2xl border border-slate-200 p-0 shadow-2xl">
            <form method="dialog" class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Update Onayi</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $updateStatus['current_version'] ?: '?' }} -> {{ data_get($remoteRelease, 'version') ?: '?' }}</h3>
                </div>
                <button type="button" class="text-slate-400 hover:text-slate-600" data-dialog-close>x</button>
            </form>
            <div class="space-y-4 px-6 py-5">
                <p class="text-sm font-semibold text-slate-900">{{ data_get($remoteRelease, 'summary') ?: 'Release ozeti bulunamadi.' }}</p>
                <ul class="list-inside list-disc space-y-1 text-sm text-slate-700">
                    @forelse($remoteRelease['change_summary'] ?? [] as $item)
                        <li>{{ $item }}</li>
                    @empty
                        <li>Detayli madde yok.</li>
                    @endforelse
                </ul>
            </div>
            <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-6 py-4">
                <button type="button" class="btn btn-secondary" data-dialog-close>Iptal</button>
                <form method="POST" action="{{ route('admin.settings.update-prepare', ['tab' => 'updates']) }}">
                    @csrf
                    <input type="hidden" name="tab" value="updates">
                    <button type="submit" class="btn btn-primary">Hazirligi Onayla</button>
                </form>
            </div>
        </dialog>
    </div>
    <div class="{{ $activeTab === 'storage' ? '' : 'hidden' }}">
        <form method="POST" action="{{ route('admin.settings.update', ['tab' => 'storage']) }}" class="card p-6 space-y-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="settings_section" value="storage">
            <input type="hidden" name="tab" value="storage">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">DigitalOcean Spaces</h2>
                <p class="mt-1 text-sm text-slate-500">Bootstrap icin `.env` kullanilmaya devam eder. Buradaki alanlar calisma zamaninda uzerine yazabilir.</p>
            </div>
            <div>
                <label class="label">Aktif Disk</label>
                <select name="spaces[disk]" class="input">
                    <option value="local" {{ old('spaces.disk', $spaces['disk'] ?? 'local') === 'local' ? 'selected' : '' }}>Local</option>
                    <option value="spaces" {{ old('spaces.disk', $spaces['disk'] ?? 'local') === 'spaces' ? 'selected' : '' }}>Spaces</option>
                </select>
            </div>
            <div><label class="label">Access Key</label><input class="input" type="text" name="spaces[key]" value="{{ old('spaces.key', $spaces['key'] ?? '') }}"></div>
            <div><label class="label">Secret Key</label><input class="input" type="password" name="spaces[secret]" value="{{ old('spaces.secret', $spaces['secret'] ?? '') }}"></div>
            <div><label class="label">Endpoint</label><input class="input" type="url" name="spaces[endpoint]" value="{{ old('spaces.endpoint', $spaces['endpoint'] ?? '') }}"></div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="label">Region</label><input class="input" type="text" name="spaces[region]" value="{{ old('spaces.region', $spaces['region'] ?? '') }}"></div>
                <div><label class="label">Bucket</label><input class="input" type="text" name="spaces[bucket]" value="{{ old('spaces.bucket', $spaces['bucket'] ?? '') }}"></div>
            </div>
            <div><label class="label">CDN / URL</label><input class="input" type="url" name="spaces[url]" value="{{ old('spaces.url', $spaces['url'] ?? '') }}"></div>
            <div class="flex justify-end"><button type="submit" class="btn btn-primary">Depolama Ayarlarini Kaydet</button></div>
        </form>
    </div>

    <div class="{{ $activeTab === 'mikro' ? '' : 'hidden' }}">
        <form method="POST" action="{{ route('admin.settings.update', ['tab' => 'mikro']) }}" class="card p-6 space-y-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="settings_section" value="mikro">
            <input type="hidden" name="tab" value="mikro">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Mikro API</h2>
                <p class="mt-1 text-sm text-slate-500">Mikro erisimi yalniz backend tarafinda kullanilir. Gizli alanlar tekrar ekrana basilmaz.</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="mikro[enabled]" value="0">
                <input type="checkbox" name="mikro[enabled]" value="1" class="rounded border-slate-300 text-brand-600" {{ old('mikro.enabled', !empty($mikro['enabled']) ? '1' : '0') === '1' ? 'checked' : '' }}>
                <label class="text-sm text-slate-700">Mikro entegrasyonu etkin</label>
            </div>
            <div><label class="label">Base URL</label><input class="input" type="url" name="mikro[base_url]" value="{{ old('mikro.base_url', $mikro['base_url'] ?? '') }}"></div>
            <div><label class="label">API Key</label><input class="input" type="password" name="mikro[api_key]" value="" placeholder="{{ !empty($mikro['has_api_key']) ? 'Kayıtlı anahtar var, değiştirmek için yeniden girin' : 'Opsiyonel' }}"></div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="label">Kullanici Adi</label><input class="input" type="text" name="mikro[username]" value="" placeholder="{{ !empty($mikro['has_username']) ? 'Kayıtlı kullanıcı var, değiştirmek için yeniden girin' : 'Opsiyonel' }}"></div>
                <div><label class="label">Sifre</label><input class="input" type="password" name="mikro[password]" value="" placeholder="{{ !empty($mikro['has_password']) ? 'Kayıtlı şifre var, değiştirmek için yeniden girin' : 'Opsiyonel' }}"></div>
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
            <div class="flex justify-end"><button type="submit" class="btn btn-primary">Mikro Ayarlarini Kaydet</button></div>
        </form>
    </div>
    <div class="{{ $activeTab === 'mail' ? '' : 'hidden' }}">
        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.settings.update', ['tab' => 'mail']) }}" class="card p-6 space-y-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="settings_section" value="mail">
                <input type="hidden" name="tab" value="mail">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Mail Sunucusu</h2>
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
                <div class="rounded-xl border border-slate-200 p-4">
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
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Mail Bildirimleri</h2>
                            <p class="mt-1 text-sm text-slate-500">Yeni siparis bildirim davranisi mevcut sistemle uyumlu sekilde burada yonetilir.</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
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
            </form>

            <form method="POST" action="{{ route('admin.settings.mail-connection-test', ['tab' => 'mail']) }}" class="card p-6 space-y-4">
                @csrf
                <input type="hidden" name="tab" value="mail">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Baglantiyi Test Et</p>
                        <p class="mt-1 text-xs text-slate-500">Kayitli mail sunucusu ayarlari ile baglanti ve kimlik dogrulama testi yapar. Mail gondermez.</p>
                    </div>
                    <button type="submit" class="btn btn-secondary">Baglantiyi Test Et</button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.settings.mail-test', ['tab' => 'mail']) }}" class="card p-6 space-y-4">
                @csrf
                <input type="hidden" name="tab" value="mail">
                <div class="flex items-start justify-between gap-4">
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

    <div class="{{ $activeTab === 'general' ? '' : 'hidden' }}">
        <div class="grid gap-6 xl:grid-cols-2">
            <div class="card p-6 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Genel Sistem Bilgileri</h2>
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
            <div class="card p-6 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Kapsam Notu</h2>
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
@endsection
