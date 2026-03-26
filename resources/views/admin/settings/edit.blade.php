@extends('layouts.app')
@section('title', 'Ayarlar')
@section('page-title', 'Sistem Ayarlari')
@section('page-subtitle', 'Guncelleme, entegrasyon ve altyapi ayarlarini daha net bir alt navigasyon ile yonetin.')
@section('page-aside-storage-key', 'admin-settings-aside')

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

@section('page-aside')
    <div class="card overflow-hidden border-slate-200/90 bg-white/95 shadow-[0_20px_40px_rgba(15,23,42,0.06)]">
        <div class="border-b border-slate-200/80 px-5 py-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">{{ $activeSection['eyebrow'] }}</p>
            <h2 class="mt-2 text-base font-semibold text-slate-950">{{ $activeAside['title'] }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">{{ $activeAside['summary'] }}</p>
        </div>
        <div class="space-y-5 px-5 py-5">
            <div class="space-y-3">
                @foreach($activeAside['meta'] as $item)
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ $item['label'] }}</p>
                        <p class="mt-1 text-sm font-medium text-slate-900">{{ $item['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Bu bolumde dikkat</p>
                <ul class="mt-3 space-y-3 text-sm leading-6 text-slate-600">
                    @foreach($activeAside['points'] as $point)
                        <li class="flex gap-3">
                            <span class="mt-2 inline-block h-2 w-2 flex-shrink-0 rounded-full bg-brand-500"></span>
                            <span>{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="card border-slate-200/90 bg-white/90 p-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Hizli gecis</p>
                <h3 class="mt-1 text-sm font-semibold text-slate-900">Ayar bolumleri</h3>
            </div>
            <x-ui.badge :variant="$statusVariant">
                {{ $activeSection['label'] }}
            </x-ui.badge>
        </div>
        <div class="mt-4 space-y-2">
            @foreach($tabs as $tabKey => $tab)
                <a href="{{ route('admin.settings.edit', ['tab' => $tabKey]) }}" class="settings-mini-link {{ $activeTab === $tabKey ? 'active' : '' }}">
                    <span class="font-medium">{{ $tab['label'] }}</span>
                    <span class="text-xs text-slate-500">{{ $tab['eyebrow'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <section class="rounded-[28px] border border-slate-200/80 bg-white/95 p-5 shadow-[0_20px_40px_rgba(15,23,42,0.05)] sm:p-6">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-3xl">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Admin ayarlari</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Ayarlar menusu artik daha net bir alt yapida</h2>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    "Ayarlar" ana menude tek noktada kalir. Alt kategoriler ise bu sayfada daha okunur bir yapiyla ayrilir; mevcut save, validation ve deep-link davranisi korunur.
                </p>
            </div>
            <div class="grid gap-3 sm:grid-cols-3 xl:w-[420px]">
                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Aktif bolum</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $activeSection['label'] }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Deep-link</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">?tab={{ $activeTab }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Sag panel</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">Ac / kapat</p>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[280px_minmax(0,1fr)]">
        <aside class="space-y-4">
            <div class="card overflow-hidden border-slate-200/90 bg-white/95 shadow-[0_20px_40px_rgba(15,23,42,0.05)]">
                <div class="border-b border-slate-200/80 px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Bolumler</p>
                    <h2 class="mt-1 text-base font-semibold text-slate-900">Ayarlar alt navigasyonu</h2>
                </div>
                <div class="space-y-2 p-3">
                    @foreach($tabs as $tabKey => $tab)
                        <a href="{{ route('admin.settings.edit', ['tab' => $tabKey]) }}" class="settings-nav-link {{ $activeTab === $tabKey ? 'active' : '' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] {{ $activeTab === $tabKey ? 'text-brand-700' : 'text-slate-400' }}">{{ $tab['eyebrow'] }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $tab['label'] }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $tab['description'] }}</p>
                                </div>
                                <span class="settings-nav-dot {{ $activeTab === $tabKey ? 'active' : '' }}"></span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </aside>

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
                        <div class="space-y-6">
                            <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <p class="text-xs uppercase tracking-wide text-slate-400">Kurulu Surum</p>
                                    <p class="mt-2 font-mono text-sm text-slate-900">{{ $updateStatus['current_version'] ?: 'Bilinmiyor' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['current_branch'] ? 'Branch: '.$updateStatus['current_branch'] : 'Git branch bilgisi okunamadi' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['current_commit'] ? 'Commit: '.$updateStatus['current_commit'] : 'Commit bilgisi okunamadi' }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <p class="text-xs uppercase tracking-wide text-slate-400">Hedef Release</p>
                                    <p class="mt-2 font-mono text-sm text-slate-900">{{ data_get($remoteRelease, 'version') ?: 'Henuz kontrol edilmedi' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ data_get($remoteRelease, 'title') ?: 'Release manifest bilgisi yok' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['last_checked_at'] ? \Illuminate\Support\Carbon::parse($updateStatus['last_checked_at'])->timezone($displayTimezone)->format('d.m.Y H:i') : 'Henuz GitHub kontrolu yok' }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <p class="text-xs uppercase tracking-wide text-slate-400">Son Bilinen Deploy</p>
                                    <p class="mt-2 font-mono text-sm text-slate-900">{{ $updateStatus['last_deployed_version'] ?: 'Kayit yok' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['last_run_at'] ? \Illuminate\Support\Carbon::parse($updateStatus['last_run_at'])->timezone($displayTimezone)->format('d.m.Y H:i') : 'Henuz portal:update kaydi yok' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['last_message'] ?: 'Son run mesaji bulunmuyor.' }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 p-4">
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

                            <div class="grid gap-4 2xl:grid-cols-[minmax(0,1.6fr)_minmax(280px,0.8fr)]">
                                <div class="rounded-3xl border border-slate-200 p-5">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div>
                                            <p class="text-xs uppercase tracking-wide text-slate-400">Guvenli Admin Aksiyonlari</p>
                                            <p class="mt-2 text-sm text-slate-900">Web istegi icinden tehlikeli deploy komutlari calistirilmaz. Yalnizca guvenli kontrol ve hazirlik aksiyonlari sunulur.</p>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <form method="POST" action="{{ route('admin.settings.update-check', ['tab' => 'updates']) }}">
                                                @csrf
                                                <input type="hidden" name="tab" value="updates">
                                                <button type="submit" class="btn btn-secondary">GitHub Kontrolu Yap</button>
                                            </form>
                                            <button type="button" class="btn btn-primary" data-dialog-open="update-confirmation" {{ $canPrepare ? '' : 'disabled' }}>
                                                Guncellemeyi Incele
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                                        <div class="rounded-2xl bg-slate-50 p-4">
                                            <p class="text-xs uppercase tracking-wide text-slate-400">Kurulu Release Ozeti</p>
                                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ data_get($currentRelease, 'title') ?: 'Surum manifesti bulunamadi' }}</p>
                                            <p class="mt-2 text-sm text-slate-600">{{ data_get($currentRelease, 'summary') ?: 'Mevcut surum icin aciklama yok.' }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-slate-50 p-4">
                                            <p class="text-xs uppercase tracking-wide text-slate-400">Hazir Hedef Release</p>
                                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ data_get($remoteRelease, 'title') ?: 'Henuz hedef release yok' }}</p>
                                            <p class="mt-2 text-sm text-slate-600">{{ data_get($remoteRelease, 'summary') ?: 'GitHub kontrolu yapildiginda release ozeti burada gorunur.' }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-5">
                                        <p class="text-xs uppercase tracking-wide text-slate-400">Gelen Degisiklikler</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @forelse($remoteRelease['changed_modules'] ?? [] as $module)
                                                <span class="badge badge-gray">{{ $module }}</span>
                                            @empty
                                                <span class="text-sm text-slate-500">Modul listesi yok.</span>
                                            @endforelse
                                        </div>
                                        <ul class="mt-4 list-inside list-disc space-y-1 text-sm text-slate-700">
                                            @forelse($remoteRelease['change_summary'] ?? [] as $item)
                                                <li>{{ $item }}</li>
                                            @empty
                                                <li>Detayli release notu bulunmuyor.</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="rounded-3xl border border-amber-200 bg-amber-50 p-4">
                                        <p class="text-xs uppercase tracking-wide text-amber-700">Rollback Notu</p>
                                        <p class="mt-2 text-sm text-amber-900">Rollback ancak release dizini, image tag veya snapshot stratejisi ile guvenli hale gelir. Bu panel rollback butonu sunmaz.</p>
                                    </div>
                                    <div class="rounded-3xl border border-slate-200 p-4">
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

                            <div class="rounded-3xl border border-slate-200 p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-400">Update Gecmisi</p>
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

                        <dialog id="update-confirmation" class="update-modal w-full max-w-3xl rounded-3xl border border-slate-200 p-0 shadow-2xl">
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
@endsection
