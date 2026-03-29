@extends('layouts.app')
@section('title', 'Ayarlar')
@section('page-title', 'Sistem Ayarları')
@section('page-subtitle', 'Güncelleme, entegrasyon ve altyapı ayarlarını daha net bir alt navigasyon ile yönetin.')

@php
    $tabs = [
        'updates' => [
            'label' => 'Güncellemeler',
            'description' => 'Sürüm durumu, release notları ve kontrollü update hazırlığı.',
            'eyebrow' => 'Sürüm ve yayın',
        ],
        'storage' => [
            'label' => 'Depolama / Spaces',
            'description' => 'Aktif disk seçimi ve runtime depolama bağlantısı.',
            'eyebrow' => 'Dosya depolama',
        ],
        'mikro' => [
            'label' => 'Mikro API',
            'description' => 'ERP bağlantısı, zamanlama ve güvenli endpoint ayarları.',
            'eyebrow' => 'ERP entegrasyonu',
        ],
        'mail' => [
            'label' => 'Mail / Exchange',
            'description' => 'Mail sunucusu ve yeni sipariş bildirim davranışları.',
            'eyebrow' => 'Bildirim altyapısı',
        ],
        'formats' => [
            'label' => 'Dosya Formatları',
            'description' => 'İzin verilen dosya uzantıları ve format tanımlarını yönetin.',
            'eyebrow' => 'Yükleme kuralları',
        ],
        'portal' => [
            'label' => 'Portal Ayarları',
            'description' => 'Sipariş oluşturma, upload limiti ve portal davranış parametreleri.',
            'eyebrow' => 'İşletim parametreleri',
        ],
        'general' => [
            'label' => 'Genel Sistem',
            'description' => 'Read-only uygulama ortamı ve çalışma zamanı özeti.',
            'eyebrow' => 'Sistem özeti',
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
    $portalUploadLimitMb = (int) ($portalConfig['max_upload_size_mb'] ?? config('artwork.max_file_size_mb', 1200));
    $portalUploadLimitSystemMaxMb = max(1, (int) config('artwork.max_file_size_mb', 1200));
    $portalUploadLimitSummary = number_format($portalUploadLimitMb, 0, ',', '.') . ' MB';
    if ($portalUploadLimitMb >= 1000) {
        $portalUploadLimitSummary .= ' / ' . number_format($portalUploadLimitMb / 1000, 1, ',', '.') . ' GB';
    }
    $artworkStorageDisk = old('portal.artwork_storage_disk', $artworkStorage['disk'] ?? 'local');
    $artworkStorageSummary = $artworkStorageDisk === 'spaces' ? 'Spaces' : 'Yerel';

    $sectionHighlights = [
        'updates' => [
            'title' => 'Kontrollü sürüm akışı',
            'summary' => 'Web tarafında sadece görünürlük ve hazırlık aksiyonları sunulur; deploy yine kontrollü CLI akışında tamamlanır.',
            'points' => [
                'GitHub kontrolü, release notları ve update geçmişi aynı yerde görünür.',
                'Hazırlık aksiyonu öncesi hedef release detayları net şekilde incelenebilir.',
                'Rollback butonu yok; güvenli release disiplini korunur.',
            ],
            'meta' => [
                ['label' => 'Kurulu sürüm', 'value' => $updateStatus['current_version'] ?: 'Bilinmiyor'],
                ['label' => 'Hedef release', 'value' => data_get($remoteRelease, 'version') ?: 'Bekleniyor'],
            ],
        ],
        'storage' => [
            'title' => 'Depolama bağlam notu',
            'summary' => 'Bu bölüm local disk ve Spaces geçişini merkezi ve okunur hale getirir; bootstrap `.env` bilgileri yine başlangıç noktası olarak kalır.',
            'points' => [
                'Secret alanlar yalnız değiştirilirse yazılır.',
                'Aktif disk seçimi mevcut storage mimarisini değiştirmez.',
                'Production ve local davranışı aynı ayar anahtarları üzerinden sürer.',
            ],
            'meta' => [
                ['label' => 'Aktif disk', 'value' => $spaces['disk'] ?? 'local'],
                ['label' => 'Bucket', 'value' => $spaces['bucket'] ?? 'Tanımlı değil'],
            ],
        ],
        'mikro' => [
            'title' => 'ERP bağlantı notu',
            'summary' => 'Mikro erişimi backend tarafında kalır; bu panel yalnız runtime-safe entegrasyon alanlarını yönetir.',
            'points' => [
                'Kayıtlı secret alanlar plaintext olarak tekrar gösterilmez.',
                'Zamanlama ve timeout ayarları mevcut queue tabanlı sync akışıyla uyumludur.',
                'Supplier bazlı sync ve mevcut integration davranışı korunur.',
            ],
            'meta' => [
                ['label' => 'Durum', 'value' => !empty($mikro['enabled']) ? 'Etkin' : 'Pasif'],
                ['label' => 'Senkron aralığı', 'value' => ($mikro['sync_interval_minutes'] ?? 60) . ' dk'],
            ],
        ],
        'mail' => [
            'title' => 'Mail operasyon rehberi',
            'summary' => 'Bağlantı testi, test mail ve bildirim ayarları aynı operasyon yüzeyinde toplandı; kuyruk mantığı korunur.',
            'points' => [
                'Bağlantı testi kimlik doğrulama ve SMTP/Exchange erişimini kontrol eder.',
                'Test mail mevcut bildirim hattıyla uyumlu şekilde kuyruğa yazılır.',
                'Boş secret input mevcut kayıtlı değeri korur.',
            ],
            'meta' => [
                ['label' => 'Mailer', 'value' => $generalSystem['mail_mailer'] ?? 'smtp'],
                ['label' => 'Test alıcısı', 'value' => $mailNotifications['test_recipient'] ?? 'Tanımsız'],
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
        'portal' => [
            'title' => 'Portal işletim özeti',
            'summary' => 'Sipariş açma, tedarikçi erişimi, veri aktarımı ve upload limitleri bu sekmeden merkezi olarak yönetilir.',
            'points' => [
                'Açma/kapama anahtarları portal davranışını runtime ayarları üzerinden kontrol eder.',
                'Sayfalama, revizyon ve oturum limitleri tedarikçi deneyimini doğrudan etkiler.',
                'Bakım modu ve veri aktarımı izinleri operasyon güvenliği için tek yerden yönetilir.',
            ],
            'meta' => [
                ['label' => 'Tedarikçi portalı', 'value' => ($portalConfig['supplier_portal_enabled'] ?? false) ? 'Açık' : 'Kapalı'],
                ['label' => 'Upload limiti', 'value' => $portalUploadLimitSummary],
                ['label' => 'Artwork depolama', 'value' => $artworkStorageSummary],
            ],
        ],
        'general' => [
            'title' => 'Read-only sistem özeti',
            'summary' => 'Bu alan uygulama davranışını değiştirmez; mevcut environment, queue ve storage seçimini hızlı kontrol için gösterir.',
            'points' => [
                'Bootstrap env detayları bu ekranda düzenlenmez.',
                'Sürüm, cache ve session durumu tek yerde görülür.',
                'Operasyon öncesi kısa sistem kontrolü için kullanılabilir.',
            ],
            'meta' => [
                ['label' => 'Environment', 'value' => $generalSystem['app_env']],
                ['label' => 'Queue', 'value' => $generalSystem['queue_connection']],
            ],
        ],
    ];

    $activeSection = $tabs[$activeTab] ?? $tabs['updates'];
    $activeAside = $sectionHighlights[$activeTab] ?? $sectionHighlights['updates'];
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
                                    'success' => 'Son durum: Başarılı',
                                    'failed' => 'Son durum: Hatalı',
                                    default => 'Son durum: Kayıt yok',
                                } }}
                            </x-ui.badge>
                        @endif
                    </div>
                </div>

                <div class="p-6">
                    <div class="{{ $activeTab === 'updates' ? '' : 'hidden' }}">
                        @php
                            $localCommit  = $updateStatus['current_commit'];
                            $lastRunAt    = $updateStatus['last_run_at']
                                ? \Illuminate\Support\Carbon::parse($updateStatus['last_run_at'])->timezone($displayTimezone)
                                : null;
                            $lastCheckAt  = $updateStatus['last_checked_at']
                                ? \Illuminate\Support\Carbon::parse($updateStatus['last_checked_at'])->timezone($displayTimezone)
                                : null;
                            $updateAvailable = $updateStatus['update_available'];
                        @endphp
                        <div class="space-y-5">

                            {{-- ── Info kartları grid ── --}}
                            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">

                                {{-- Kurulu commit --}}
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3.5 col-span-1">
                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Sunucu Commit</p>
                                    <p class="mt-1.5 font-mono text-base font-bold text-slate-900 truncate">{{ $localCommit ?: '—' }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-400">Aktif versiyon</p>
                                </div>

                                {{-- Branch --}}
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Branch</p>
                                    <p class="mt-1.5 font-mono text-base font-bold text-slate-900">{{ $updateStatus['current_branch'] ?: 'main' }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-400">Aktif dal</p>
                                </div>

                                {{-- Uygulama sürümü --}}
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Sürüm</p>
                                    <p class="mt-1.5 text-base font-bold text-slate-900">{{ $updateStatus['current_version'] ?: ($updateStatus['app_version'] ?: '—') }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-400">Portal sürümü</p>
                                </div>

                                {{-- Son güncelleme --}}
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Son Güncelleme</p>
                                    <p class="mt-1.5 text-sm font-bold text-slate-900">{{ $lastRunAt ? $lastRunAt->format('d.m.Y') : '—' }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-400">{{ $lastRunAt ? $lastRunAt->format('H:i') : 'Kayıt yok' }}</p>
                                </div>

                                {{-- Son kontrol --}}
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Son Kontrol</p>
                                    <p class="mt-1.5 text-sm font-bold text-slate-900">{{ $lastCheckAt ? $lastCheckAt->format('d.m.Y') : '—' }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-400">{{ $lastCheckAt ? $lastCheckAt->format('H:i') : 'Kontrol yok' }}</p>
                                </div>

                                {{-- Güncelleme durumu --}}
                                @php
                                    $upd = match(true) {
                                        $updateAvailable === true  => ['label' => 'Güncelleme Var',  'sub' => 'Yeni sürüm mevcut', 'bg' => 'bg-amber-50',   'border' => 'border-amber-200',  'dot' => 'bg-amber-500',   'text' => 'text-amber-700'],
                                        $updateAvailable === false => ['label' => 'Güncel',           'sub' => 'Sunucu en güncel',  'bg' => 'bg-emerald-50', 'border' => 'border-emerald-200','dot' => 'bg-emerald-500', 'text' => 'text-emerald-700'],
                                        default                    => ['label' => 'Bilinmiyor',       'sub' => 'Kontrol yapılmadı', 'bg' => 'bg-slate-50',   'border' => 'border-slate-200',  'dot' => 'bg-slate-400',   'text' => 'text-slate-500'],
                                    };
                                @endphp
                                <div class="rounded-2xl border {{ $upd['border'] }} {{ $upd['bg'] }} px-4 py-3.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Durum</p>
                                    <div class="mt-1.5 flex items-center gap-1.5">
                                        <span class="inline-block h-2 w-2 flex-shrink-0 rounded-full {{ $upd['dot'] }}"></span>
                                        <p class="text-sm font-bold {{ $upd['text'] }}">{{ $upd['label'] }}</p>
                                    </div>
                                    <p class="mt-0.5 text-[11px] text-slate-400">{{ $upd['sub'] }}</p>
                                </div>
                            </div>

                            {{-- ── Eylemler ── --}}
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50/60 px-5 py-3">
                                <p class="text-xs text-slate-500">
                                    GitHub'dan commit geçmişini yükleyin veya sunucuyu güncelleyin.
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" id="load-commits-btn" class="btn btn-secondary">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Commit Geçmişini Yükle
                                    </button>
                                    <button type="button" class="btn btn-primary" data-dialog-open="deploy-dialog"
                                        style="background:linear-gradient(180deg,#059669,#047857);box-shadow:0 6px 16px rgba(5,150,105,.25);">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        GitHub'dan Güncelle
                                    </button>
                                </div>
                            </div>

                            {{-- ── Commit tablosu ── --}}
                            <div class="card overflow-x-auto">
                                {{-- Tablo başlığı --}}
                                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-5 py-3">
                                    <h3 class="text-sm font-semibold text-slate-700">GitHub Commit Geçmişi</h3>
                                    <span id="commits-branch-badge" class="hidden rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-mono text-slate-600"></span>
                                </div>

                                <div id="commits-loading" class="hidden px-5 py-10 text-center">
                                    <div class="mx-auto h-7 w-7 animate-spin rounded-full border-4 border-slate-200 border-t-brand-500"></div>
                                    <p class="mt-3 text-xs text-slate-400">GitHub'dan yükleniyor…</p>
                                </div>

                                <div id="commits-error" class="hidden px-5 py-6 text-center text-sm text-red-600"></div>

                                <div id="commits-empty" class="px-5 py-12 text-center">
                                    <svg class="mx-auto mb-3 h-9 w-9 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <p class="text-sm text-slate-400">"Commit Geçmişini Yükle" butonuna basarak</p>
                                    <p class="text-xs text-slate-300 mt-0.5">GitHub'daki son commit'leri görüntüleyin.</p>
                                </div>

                                {{-- Gerçek tablo --}}
                                <table id="commits-table" class="hidden w-full text-sm" style="min-width:640px">
                                    <thead>
                                        <tr class="border-b border-slate-200 bg-slate-50 text-left">
                                            <th class="w-6 px-4 py-2"></th>
                                            <th class="px-4 py-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Commit Mesajı</th>
                                            <th class="w-32 px-4 py-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Yazar</th>
                                            <th class="w-40 px-4 py-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Tarih</th>
                                            <th class="w-20 px-4 py-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400 text-right">SHA</th>
                                        </tr>
                                    </thead>
                                    <tbody id="commits-tbody" class="divide-y divide-slate-100"></tbody>
                                    <tfoot id="commits-footer" class="hidden">
                                        <tr>
                                            <td colspan="5" class="px-4 py-3 text-center border-t border-slate-100">
                                                <button id="commits-more-btn" type="button"
                                                        class="text-xs font-medium text-brand-600 hover:text-brand-800 transition-colors">
                                                    Daha fazla göster
                                                </button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
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
                                    <p class="mt-1 text-sm text-slate-500">Bootstrap için `.env` kullanılmaya devam eder. Buradaki alanlar çalışma zamanında üzerine yazabilir.</p>
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
                            <div class="flex justify-end"><button type="submit" class="btn btn-primary">Depolama Ayarlarını Kaydet</button></div>
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
                                    <p class="mt-1 text-sm text-slate-500">Mikro erişimi yalnız backend tarafında kullanılır. Gizli alanlar tekrar ekrana basılmaz.</p>
                                </div>
                                <div class="mt-5 space-y-5">
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" name="mikro[enabled]" value="0">
                                        <input type="checkbox" name="mikro[enabled]" value="1" class="rounded border-slate-300 text-brand-600" {{ old('mikro.enabled', !empty($mikro['enabled']) ? '1' : '0') === '1' ? 'checked' : '' }}>
                                        <label class="text-sm text-slate-700">Mikro entegrasyonu etkin</label>
                                    </div>
                                    <div><label class="label">Base URL</label><input class="input" type="url" name="mikro[base_url]" value="{{ old('mikro.base_url', $mikro['base_url'] ?? '') }}"></div>
                                    <div><label class="label">API Key</label><input class="input" type="password" name="mikro[api_key]" value="" placeholder="{{ !empty($mikro['has_api_key']) ? 'Kayıtlı anahtar var, değiştirmek için yeniden girin' : 'Opsiyonel' }}"></div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div><label class="label">Kullanıcı Adı</label><input class="input" type="text" name="mikro[username]" value="" placeholder="{{ !empty($mikro['has_username']) ? 'Kayıtlı kullanıcı var, değiştirmek için yeniden girin' : 'Opsiyonel' }}"></div>
                                        <div><label class="label">Şifre</label><input class="input" type="password" name="mikro[password]" value="" placeholder="{{ !empty($mikro['has_password']) ? 'Kayıtlı şifre var, değiştirmek için yeniden girin' : 'Opsiyonel' }}"></div>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div><label class="label">Şirket Kodu</label><input class="input" type="text" name="mikro[company_code]" value="{{ old('mikro.company_code', $mikro['company_code'] ?? '') }}"></div>
                                        <div><label class="label">Çalışma Yılı</label><input class="input" type="text" name="mikro[work_year]" value="{{ old('mikro.work_year', $mikro['work_year'] ?? '') }}"></div>
                                    </div>
                                    <div><label class="label">Sevk Endpoint Yolu</label><input class="input" type="text" name="mikro[shipment_endpoint]" value="{{ old('mikro.shipment_endpoint', $mikro['shipment_endpoint'] ?? '') }}" placeholder="/api/dispatch-status"></div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div><label class="label">Senkron Aralığı (dk)</label><input class="input" type="number" min="5" max="1440" name="mikro[sync_interval_minutes]" value="{{ old('mikro.sync_interval_minutes', $mikro['sync_interval_minutes'] ?? 60) }}"></div>
                                        <div><label class="label">HTTP Timeout (sn)</label><input class="input" type="number" min="1" max="300" name="mikro[timeout]" value="{{ old('mikro.timeout', $mikro['timeout'] ?? 30) }}"></div>
                                    </div>
                                    <div class="flex items-end">
                                        <label class="flex items-center gap-2 text-sm text-slate-700">
                                            <input type="hidden" name="mikro[verify_ssl]" value="0">
                                            <input type="checkbox" name="mikro[verify_ssl]" value="1" class="rounded border-slate-300 text-brand-600" {{ old('mikro.verify_ssl', !empty($mikro['verify_ssl']) ? '1' : '0') === '1' ? 'checked' : '' }}>
                                            SSL doğrulaması aktif
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end"><button type="submit" class="btn btn-primary">Mikro Ayarlarını Kaydet</button></div>
                        </form>

                        @php
                            $mikroFieldDefinitions = $mikroViewMapping['field_definitions'];
                            $savedSamplePayload = $mikroViewMapping['sample_payload'];
                        @endphp

                        <form method="POST" action="{{ route('admin.settings.update', ['tab' => 'mikro']) }}" class="mt-6 space-y-5" id="mikro-view-mapping-form">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="settings_section" value="mikro_view_mapping">
                            <input type="hidden" name="tab" value="mikro">
                            <input type="hidden" name="mikro_view_mapping[id]" value="{{ $mikroViewMapping['id'] ?? '' }}">

                            <div class="rounded-3xl border border-slate-200 p-6">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-slate-900">Sipariş View Eşleme</h3>
                                        <p class="mt-1 text-sm text-slate-500">BT tarafının hazırladığı SQL view / endpoint kolonlarını portalın sipariş alanlarıyla eşleyin. Aktif mapping, mevcut Mikro sipariş senkronizasyonunda otomatik kullanılır.</p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
                                        <p class="font-semibold text-slate-700">Kimlik Kuralları</p>
                                        <p class="mt-1">Supplier: <span class="font-mono">supplier_code</span> → <span class="font-mono">mikro_cari_kod</span></p>
                                        <p>Sipariş: <span class="font-mono">(supplier_id, order_no)</span></p>
                                        <p>Satır: <span class="font-mono">line_no / sip_satirno</span></p>
                                    </div>
                                </div>

                                <div class="mt-6 grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="label">Mapping Adı</label>
                                        <input class="input" type="text" name="mikro_view_mapping[name]" value="{{ old('mikro_view_mapping.name', $mikroViewMapping['name'] ?? '') }}">
                                    </div>
                                    <div>
                                        <label class="label">SQL View Adı</label>
                                        <input class="input font-mono" type="text" name="mikro_view_mapping[view_name]" value="{{ old('mikro_view_mapping.view_name', $mikroViewMapping['view_name'] ?? '') }}" placeholder="vw_portal_purchase_orders">
                                    </div>
                                    <div>
                                        <label class="label">Sipariş Endpoint Yolu</label>
                                        <input class="input font-mono" type="text" name="mikro_view_mapping[endpoint_path]" value="{{ old('mikro_view_mapping.endpoint_path', $mikroViewMapping['endpoint_path'] ?? '') }}" placeholder="/api/portal-orders">
                                    </div>
                                    <div>
                                        <label class="label">Payload Modu</label>
                                        <select class="input" name="mikro_view_mapping[payload_mode]" id="mikro-payload-mode">
                                            <option value="nested_lines" {{ old('mikro_view_mapping.payload_mode', $mikroViewMapping['payload_mode'] ?? 'nested_lines') === 'nested_lines' ? 'selected' : '' }}>Sipariş + iç içe satırlar</option>
                                            <option value="flat_rows" {{ old('mikro_view_mapping.payload_mode', $mikroViewMapping['payload_mode'] ?? 'nested_lines') === 'flat_rows' ? 'selected' : '' }}>Her satır ayrı kayıt</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="label">Satır Dizi Alanı</label>
                                        <input class="input font-mono" type="text" name="mikro_view_mapping[line_array_key]" id="mikro-line-array-key" value="{{ old('mikro_view_mapping.line_array_key', $mikroViewMapping['line_array_key'] ?? 'lines') }}" placeholder="lines">
                                        <p class="mt-1 text-xs text-slate-500">İç içe satır modunda sipariş içindeki satır dizisinin adı.</p>
                                    </div>
                                    <div>
                                        <label class="label">Not</label>
                                        <input class="input" type="text" name="mikro_view_mapping[notes]" value="{{ old('mikro_view_mapping.notes', $mikroViewMapping['notes'] ?? '') }}" placeholder="BT tarafı view v2, 2026 Q1 vb.">
                                    </div>
                                </div>

                                <div class="mt-6 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                        <div>
                                            <p class="font-semibold">Örnek Veri Çek</p>
                                            <p class="mt-1 text-xs text-blue-800">Mevcut Mikro bağlantı ayarlarıyla endpoint'ten örnek veri çekilir, kaynak kolonlar okunur ve aşağıdaki seçim listeleri otomatik doldurulur.</p>
                                        </div>
                                        <button type="button" class="btn btn-secondary border-blue-200 text-blue-700 hover:bg-blue-100" id="mikro-fetch-sample-btn">
                                            Endpoint'ten Örnek Veri Çek
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-6 grid gap-5 xl:grid-cols-2">
                                    <div class="rounded-2xl border border-slate-200 p-4">
                                        <div class="mb-3 flex items-center justify-between gap-3">
                                            <div>
                                                <h4 class="text-sm font-semibold text-slate-900">Sipariş Alanları</h4>
                                                <p class="mt-1 text-xs text-slate-500">Başlık seviyesinde tekil sipariş bilgileri</p>
                                            </div>
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-medium text-slate-600" id="mikro-order-columns-badge">Kolon bekleniyor</span>
                                        </div>
                                        <div class="space-y-3">
                                            @foreach($mikroFieldDefinitions['order'] as $fieldKey => $fieldLabel)
                                                <div class="grid gap-2 md:grid-cols-[180px_1fr] md:items-center">
                                                    <label class="text-sm font-medium text-slate-700">
                                                        {{ $fieldLabel }}
                                                        @if(in_array($fieldKey, $mikroFieldDefinitions['required']['order'], true))
                                                            <span class="text-red-500">*</span>
                                                        @endif
                                                    </label>
                                                    <select
                                                        class="input mikro-column-select"
                                                        name="mikro_view_mapping[mapping][order][{{ $fieldKey }}]"
                                                        data-scope="order"
                                                        data-field="{{ $fieldKey }}"
                                                        data-current="{{ old('mikro_view_mapping.mapping.order.' . $fieldKey, $mikroViewMapping['mapping']['order'][$fieldKey] ?? '') }}"
                                                    >
                                                        <option value="">Kolon seçin</option>
                                                    </select>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="rounded-2xl border border-slate-200 p-4">
                                        <div class="mb-3 flex items-center justify-between gap-3">
                                            <div>
                                                <h4 class="text-sm font-semibold text-slate-900">Satır Alanları</h4>
                                                <p class="mt-1 text-xs text-slate-500">Sipariş satırı ve ürün detayları</p>
                                            </div>
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-medium text-slate-600" id="mikro-line-columns-badge">Kolon bekleniyor</span>
                                        </div>
                                        <div class="space-y-3">
                                            @foreach($mikroFieldDefinitions['line'] as $fieldKey => $fieldLabel)
                                                <div class="grid gap-2 md:grid-cols-[180px_1fr] md:items-center">
                                                    <label class="text-sm font-medium text-slate-700">
                                                        {{ $fieldLabel }}
                                                        @if(in_array($fieldKey, $mikroFieldDefinitions['required']['line'], true))
                                                            <span class="text-red-500">*</span>
                                                        @endif
                                                    </label>
                                                    <select
                                                        class="input mikro-column-select"
                                                        name="mikro_view_mapping[mapping][line][{{ $fieldKey }}]"
                                                        data-scope="line"
                                                        data-field="{{ $fieldKey }}"
                                                        data-current="{{ old('mikro_view_mapping.mapping.line.' . $fieldKey, $mikroViewMapping['mapping']['line'][$fieldKey] ?? '') }}"
                                                    >
                                                        <option value="">Kolon seçin</option>
                                                    </select>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 grid gap-5 xl:grid-cols-2">
                                    <div class="rounded-2xl border border-slate-200 p-4">
                                        <h4 class="text-sm font-semibold text-slate-900">Kaynak Önizleme</h4>
                                        <p class="mt-1 text-xs text-slate-500">Endpoint'ten dönen ilk kayıt ve ilk satır örneği burada görünür.</p>
                                        <pre id="mikro-sample-preview" class="mt-4 min-h-[240px] overflow-auto rounded-2xl bg-slate-950 p-4 text-[11px] leading-5 text-emerald-200">{{ $savedSamplePayload ? json_encode($savedSamplePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'Henüz örnek veri çekilmedi.' }}</pre>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 p-4">
                                        <h4 class="text-sm font-semibold text-slate-900">Normalize Edilmiş Önizleme</h4>
                                        <p class="mt-1 text-xs text-slate-500">Portalın sipariş sync servisinin göreceği hedef payload biçimi.</p>
                                        <pre id="mikro-normalized-preview" class="mt-4 min-h-[240px] overflow-auto rounded-2xl bg-slate-950 p-4 text-[11px] leading-5 text-sky-200">Henüz normalize önizleme üretilmedi.</pre>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3">
                                <button type="submit" class="btn btn-primary">View Mapping Kaydet</button>
                            </div>
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
                                        <p class="mt-1 text-sm text-slate-500">Yalnız runtime-safe mail sunucusu alanları burada yönetilir. İlgisiz altyapı env değerleri bu passta taşınmaz.</p>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div><label class="label">MAIL_HOST</label><input class="input" type="text" name="mail_server[host]" value="{{ old('mail_server.host', $mailServer['host'] ?? '') }}"></div>
                                        <div><label class="label">MAIL_PORT</label><input class="input" type="number" min="1" max="65535" name="mail_server[port]" value="{{ old('mail_server.port', $mailServer['port'] ?? 587) }}"></div>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div><label class="label">MAIL_USERNAME</label><input class="input" type="text" name="mail_server[username]" value="" placeholder="{{ !empty($mailServer['has_username']) ? 'Kayıtlı kullanıcı var, değiştirmek için yeniden girin' : 'Opsiyonel' }}"></div>
                                        <div><label class="label">MAIL_PASSWORD</label><input class="input" type="password" name="mail_server[password]" value="" placeholder="{{ !empty($mailServer['has_password']) ? 'Kayıtlı şifre var, boş bırakılırsa korunur' : 'Opsiyonel' }}"></div>
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
                                                <p class="text-sm font-semibold text-slate-900">Mail Sunucusu Aksiyonları</p>
                                                <p class="mt-1 text-xs text-slate-500">Kayıt ettikten sonra bağlantı testi ile SMTP/Exchange bağlantısını doğrulayabilirsiniz.</p>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <button type="submit" class="btn btn-primary">Mail Ayarlarını Kaydet</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="border-t border-slate-200 pt-6">
                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                            <div>
                                                <h3 class="text-lg font-semibold text-slate-900">Mail Bildirimleri</h3>
                                                <p class="mt-1 text-sm text-slate-500">Yeni sipariş bildirim davranışı mevcut sistemle uyumlu şekilde burada yönetilir.</p>
                                            </div>
                                            <div class="rounded-2xl bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                                Otomatik yeni sipariş bildirimi yalnız Mikro ile ilk kez gelen siparişlerde çalışır.
                                            </div>
                                        </div>
                                        <div class="mt-5 flex items-center gap-2">
                                            <input type="hidden" name="mail_notifications[enabled]" value="0">
                                            <input type="checkbox" name="mail_notifications[enabled]" value="1" class="rounded border-slate-300 text-brand-600" {{ old('mail_notifications.enabled', !empty($mailNotifications['enabled']) ? '1' : '0') === '1' ? 'checked' : '' }}>
                                            <label class="text-sm text-slate-700">Yeni sipariş mail bildirimleri etkin</label>
                                        </div>
                                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="label">Grafik Departmani Alicilar</label>
                                                <textarea name="mail_notifications[graphics_to]" class="input min-h-24">{{ old('mail_notifications.graphics_to', $mailNotifications['graphics_to'] ?? '') }}</textarea>
                                                <p class="mt-1 text-xs text-slate-500">Virgül veya boşluk ile birden fazla e-posta girilebilir.</p>
                                            </div>
                                            <div>
                                                <label class="label">Yeni Sipariş Konu Şablonu</label>
                                                <input class="input" type="text" name="mail_notifications[new_order_subject]" value="{{ old('mail_notifications.new_order_subject', $mailNotifications['new_order_subject'] ?? 'Yeni sipariş geldi: {order_no}') }}">
                                                <p class="mt-1 text-xs text-slate-500">Desteklenen alanlar: {order_no}, {supplier}, {order_date}, {line_count}</p>
                                            </div>
                                        </div>
                                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                                            <div><label class="label">CC Listesi</label><textarea name="mail_notifications[graphics_cc]" class="input min-h-20">{{ old('mail_notifications.graphics_cc', $mailNotifications['graphics_cc'] ?? '') }}</textarea></div>
                                            <div><label class="label">BCC Listesi</label><textarea name="mail_notifications[graphics_bcc]" class="input min-h-20">{{ old('mail_notifications.graphics_bcc', $mailNotifications['graphics_bcc'] ?? '') }}</textarea></div>
                                        </div>
                                        <div class="mt-4 grid gap-4 md:grid-cols-3">
                                            <div><label class="label">Override From Name</label><input class="input" type="text" name="mail_notifications[override_from_name]" value="{{ old('mail_notifications.override_from_name', $mailNotifications['override_from_name'] ?? '') }}" placeholder="Boş bırakılırsa fallback kullanılır"></div>
                                            <div><label class="label">Override From Address</label><input class="input" type="email" name="mail_notifications[override_from_address]" value="{{ old('mail_notifications.override_from_address', $mailNotifications['override_from_address'] ?? '') }}" placeholder="portal@sirketiniz.com"></div>
                                            <div><label class="label">Kayıtlı Test Alıcısı</label><input class="input" type="email" name="mail_notifications[test_recipient]" value="{{ old('mail_notifications.test_recipient', $mailNotifications['test_recipient'] ?? '') }}" placeholder="grafik@sirketiniz.com"></div>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('admin.settings.mail-connection-test', ['tab' => 'mail']) }}" class="rounded-3xl border border-slate-200 p-6">
                                @csrf
                                <input type="hidden" name="tab" value="mail">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Bağlantıyı Test Et</p>
                                        <p class="mt-1 text-xs text-slate-500">Kayıtlı mail sunucusu ayarları ile bağlantı ve kimlik doğrulama testi yapar. Mail göndermez.</p>
                                    </div>
                                    <button type="submit" class="btn btn-secondary">Bağlantıyı Test Et</button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('admin.settings.mail-test', ['tab' => 'mail']) }}" class="rounded-3xl border border-slate-200 p-6 space-y-4">
                                @csrf
                                <input type="hidden" name="tab" value="mail">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Test Mail Gönder</p>
                                        <p class="mt-1 text-xs text-slate-500">Kayıtlı test alıcısı varsa o kullanılır. Yoksa aşağıdaki adrese tek seferlik test gönderebilirsiniz.</p>
                                    </div>
                                    <button type="submit" class="btn btn-secondary">Test Mail Gönder</button>
                                </div>
                                <div>
                                    <label class="label">Tek Seferlik Test Alıcısı</label>
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

                    {{-- ══════════════════════ PORTAL AYARLARI ══════════════════════ --}}
                    <div class="{{ $activeTab === 'portal' ? '' : 'hidden' }}">
                        <form method="POST" action="{{ route('admin.settings.update') }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="settings_section" value="portal">
                            <input type="hidden" name="tab" value="portal">
                            <div class="space-y-6">

                                {{-- Section title --}}
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Portal İşletim Parametreleri</h3>
                                    <p class="mt-1 text-sm text-slate-500">Bu ayarlar portaldaki işlemleri ve kısıtlamaları kontrol eder.</p>
                                </div>

                                {{-- Toggle switches --}}
                                <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100 overflow-hidden">
                                    @php
                                        $toggles = [
                                            ['key' => 'order_creation_enabled', 'label' => 'Sipariş Oluşturma', 'desc' => 'Admin panelinden yeni sipariş oluşturulabilir. Kapalıysa sipariş oluşturma formu devre dışı kalır.', 'warn' => false],
                                            ['key' => 'supplier_portal_enabled', 'label' => 'Tedarikçi Portalı', 'desc' => 'Tedarikçilerin kendi portallarine erişim izni. Kapalıysa tedarikçi girişi engellenir.', 'warn' => true],
                                            ['key' => 'maintenance_mode', 'label' => 'Bakım Modu', 'desc' => 'Aktif edildiğinde admin dışındaki kullanıcılar bakım sayfasına yönlendirilir.', 'warn' => true],
                                            ['key' => 'allow_manual_artwork', 'label' => 'Manuel Artwork Tamamlama', 'desc' => 'Satın alma ekibinin "Manuel Gönderildi" olarak işaretleyebilmesi.', 'warn' => false],
                                            ['key' => 'data_transfer_allowed', 'label' => 'Veri Aktarımı', 'desc' => 'Local ↔ Sunucu veri aktarım özelliğinin kullanılmasına izin verir.', 'warn' => false],
                                            ['key' => 'require_2fa_for_admin', 'label' => '2FA Admin Zorunluluğu', 'desc' => 'Admin hesapları için iki faktörlü doğrulama zorunlu olur (geliştirme planında).', 'warn' => false],
                                        ];
                                    @endphp
                                @foreach($toggles as $t)
                                        @php $toggleChecked = (bool) ($portalConfig[$t['key']] ?? false); @endphp
                                        <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 px-5 py-4 hover:bg-slate-50/60" data-portal-toggle-row>
                                            <button type="button" class="block cursor-pointer text-left" data-portal-toggle-label>
                                                <p class="text-sm font-semibold text-slate-800 flex items-center gap-2">
                                                    {{ $t['label'] }}
                                                    @if($t['warn'])
                                                        <span class="rounded-full bg-amber-100 text-amber-700 px-1.5 py-0.5 text-[10px] font-semibold">Dikkat</span>
                                                    @endif
                                                </p>
                                                <p class="text-xs text-slate-500 mt-0.5">{{ $t['desc'] }}</p>
                                            </button>
                                            <div class="flex items-center justify-end">
                                                <input type="hidden" name="portal[{{ $t['key'] }}]" value="0">
                                                <input type="checkbox" name="portal[{{ $t['key'] }}]" value="1" id="portal_{{ $t['key'] }}"
                                                       @checked($toggleChecked)
                                                       class="sr-only"
                                                       tabindex="-1"
                                                       aria-hidden="true"
                                                       data-portal-toggle-input>
                                                <button type="button"
                                                        class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:ring-offset-2 {{ $toggleChecked ? 'bg-brand-500' : 'bg-slate-200' }}"
                                                        aria-pressed="{{ $toggleChecked ? 'true' : 'false' }}"
                                                        aria-label="{{ $t['label'] }}"
                                                        data-portal-toggle-button>
                                                    <span aria-hidden="true"
                                                          class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200"
                                                          style="transform: translateX({{ $toggleChecked ? '1.25rem' : '0' }});"
                                                          data-portal-toggle-thumb></span>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="rounded-2xl border border-slate-200 p-5">
                                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <h4 class="text-sm font-semibold text-slate-700">Artwork Depolama</h4>
                                            <p class="mt-1 text-xs leading-5 text-slate-500">Aktif disk seçimi yeni yüklenen artwork, galeri ve veri aktarımı medyalarının nereye yazılacağını belirler. Mevcut dosyalar otomatik taşınmaz.</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">
                                            Aktif: {{ $artworkStorageSummary }}
                                        </span>
                                    </div>

                                    @if($artworkStorage['spaces_ready'] ?? false)
                                        <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_240px] lg:items-start">
                                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 px-4 py-3 text-xs leading-5 text-emerald-900">
                                                Spaces bağlantısı tamamlandığı için portal üzerinden disk seçimi yapılabilir. <span class="font-semibold">Spaces</span> seçildiğinde sistemde bundan sonra oluşacak artwork ve galeri dosyaları `spaces` diskine kaydedilir.
                                            </div>
                                            <div>
                                                <label class="label" for="portal_artwork_storage_disk">Aktif Depolama</label>
                                                <select id="portal_artwork_storage_disk" name="portal[artwork_storage_disk]" class="input">
                                                    <option value="local" {{ $artworkStorageDisk === 'local' ? 'selected' : '' }}>Yerel Disk</option>
                                                    <option value="spaces" {{ $artworkStorageDisk === 'spaces' ? 'selected' : '' }}>DigitalOcean Spaces</option>
                                                </select>
                                                <p class="mt-1 text-[11px] text-slate-400">Bu seçim `Storage / Spaces` sekmesindeki aktif disk ayarıyla aynı anahtarı kullanır.</p>
                                            </div>
                                        </div>
                                    @else
                                        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900">
                                            Storage / Spaces sekmesindeki <span class="font-semibold">Access Key, Secret Key, Endpoint, Region ve Bucket</span> alanları tamamlanınca bu bölümde Spaces seçeneği açılır.
                                        </div>
                                    @endif
                                </div>

                                {{-- Numeric settings --}}
                                <div class="rounded-2xl border border-slate-200 p-5">
                                    <h4 class="text-sm font-semibold text-slate-700 mb-4">Limit & Kota Ayarları</h4>
                                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                        <div>
                                            <label class="label" for="max_upload_size_mb">Maks. Upload Boyutu (MB)</label>
                                            <input type="number" id="max_upload_size_mb" name="portal[max_upload_size_mb]"
                                                   value="{{ old('portal.max_upload_size_mb', $portalConfig['max_upload_size_mb']) }}"
                                                   min="1" max="{{ $portalUploadLimitSystemMaxMb }}" class="input">
                                            <p class="text-[11px] text-slate-400 mt-1">Her artwork yüklemesi için maksimum dosya boyutu. Sistem üst sınırı {{ number_format($portalUploadLimitSystemMaxMb, 0, ',', '.') }} MB (yaklaşık {{ number_format($portalUploadLimitSystemMaxMb / 1000, 1, ',', '.') }} GB).</p>
                                        </div>
                                        <div>
                                            <label class="label" for="max_revision_count">Maks. Revizyon Sayısı</label>
                                            <input type="number" id="max_revision_count" name="portal[max_revision_count]"
                                                   value="{{ old('portal.max_revision_count', $portalConfig['max_revision_count']) }}"
                                                   min="1" max="100" class="input">
                                            <p class="text-[11px] text-slate-400 mt-1">Bir sipariş satırı için izin verilen maksimum revizyon sayısı.</p>
                                        </div>
                                        <div>
                                            <label class="label" for="session_timeout_minutes">Oturum Zaman Aşımı (dk)</label>
                                            <input type="number" id="session_timeout_minutes" name="portal[session_timeout_minutes]"
                                                   value="{{ old('portal.session_timeout_minutes', $portalConfig['session_timeout_minutes']) }}"
                                                   min="15" max="10080" class="input">
                                            <p class="text-[11px] text-slate-400 mt-1">Hareketsiz kullanıcı için oturum süresi. Min: 15 dk.</p>
                                        </div>
                                        <div>
                                            <label class="label" for="order_deadline_warning_days">Sipariş Uyarı Süresi (gün)</label>
                                            <input type="number" id="order_deadline_warning_days" name="portal[order_deadline_warning_days]"
                                                   value="{{ old('portal.order_deadline_warning_days', $portalConfig['order_deadline_warning_days']) }}"
                                                   min="1" max="60" class="input">
                                            <p class="text-[11px] text-slate-400 mt-1">Son teslim tarihi yaklaşan siparişler için uyarı eşiği.</p>
                                        </div>
                                        <div>
                                            <label class="label" for="max_orders_per_page">Sayfa Başı Sipariş</label>
                                            <input type="number" id="max_orders_per_page" name="portal[max_orders_per_page]"
                                                   value="{{ old('portal.max_orders_per_page', $portalConfig['max_orders_per_page']) }}"
                                                   min="5" max="200" class="input">
                                            <p class="text-[11px] text-slate-400 mt-1">Sipariş listesi sayfalama limiti.</p>
                                        </div>
                                        <div>
                                            <label class="label" for="audit_log_retention_days">Log Saklama Süresi (gün)</label>
                                            <input type="number" id="audit_log_retention_days" name="portal[audit_log_retention_days]"
                                                   value="{{ old('portal.audit_log_retention_days', $portalConfig['audit_log_retention_days']) }}"
                                                   min="30" max="3650" class="input">
                                            <p class="text-[11px] text-slate-400 mt-1">Audit ve aktivite loglarının tutulacağı süre.</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Current values summary --}}
                                <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 mb-3">Aktif Durum Özeti</p>
                                    <div class="flex flex-wrap gap-2 text-xs">
                                        <span class="rounded-full px-3 py-1 {{ $portalConfig['order_creation_enabled'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                            Sipariş: {{ $portalConfig['order_creation_enabled'] ? 'Açık' : 'Kapalı' }}
                                        </span>
                                        <span class="rounded-full px-3 py-1 {{ $portalConfig['supplier_portal_enabled'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                            Tedarikçi Portali: {{ $portalConfig['supplier_portal_enabled'] ? 'Açık' : 'Kapalı' }}
                                        </span>
                                        <span class="rounded-full px-3 py-1 {{ $portalConfig['maintenance_mode'] ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                            Bakım: {{ $portalConfig['maintenance_mode'] ? 'Aktif' : 'Pasif' }}
                                        </span>
                                        <span class="rounded-full px-3 py-1 bg-slate-100 text-slate-600">
                                            Upload: {{ $portalUploadLimitSummary }}
                                        </span>
                                        <span class="rounded-full px-3 py-1 bg-slate-100 text-slate-600">
                                            Depolama: {{ $artworkStorageSummary }}
                                        </span>
                                        <span class="rounded-full px-3 py-1 bg-slate-100 text-slate-600">
                                            Revizyon: maks. {{ $portalConfig['max_revision_count'] }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex justify-end pt-2 border-t border-slate-100">
                                    <button type="submit" class="btn btn-primary px-8">Portal Ayarlarını Kaydet</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="{{ $activeTab === 'general' ? '' : 'hidden' }}">
                        <div class="grid gap-6 2xl:grid-cols-2">
                            <div class="rounded-3xl border border-slate-200 p-6 space-y-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Genel Sistem Bilgileri</h3>
                                    <p class="mt-1 text-sm text-slate-500">Bu sekme read-only bilgi sunar. Altyapı ve bootstrap env değerleri bu passta admin paneline taşınmamıştır.</p>
                                </div>
                                <dl class="grid gap-4 md:grid-cols-2">
                                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Uygulama Adi</dt><dd class="mt-1 text-sm text-slate-900">{{ $generalSystem['app_name'] }}</dd></div>
                                    <div><dt class="text-xs uppercase tracking-wide text-slate-400">Sürüm</dt><dd class="mt-1 font-mono text-sm text-slate-900">{{ $generalSystem['app_version'] }}</dd></div>
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
                                    <p class="mt-1 text-sm text-slate-500">Bu pass yalnız admin için uygun ve runtime-safe ayarları panel yüzeyine taşır.</p>
                                </div>
                                <ul class="list-inside list-disc space-y-2 text-sm text-slate-700">
                                    <li>MAIL_MAILER, MAIL_URL, MAIL_SCHEME gibi bootstrap detayları burada yönetilmez.</li>
                                    <li>Mail dışı altyapı env değerleri admin ayarlarına taşınmamıştır.</li>
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
    var loadBtn     = document.getElementById('load-commits-btn');
    var loadingEl   = document.getElementById('commits-loading');
    var errorEl     = document.getElementById('commits-error');
    var emptyEl     = document.getElementById('commits-empty');
    var tableEl     = document.getElementById('commits-table');
    var tbodyEl     = document.getElementById('commits-tbody');
    var footerEl    = document.getElementById('commits-footer');
    var moreBtn     = document.getElementById('commits-more-btn');
    var branchBadge = document.getElementById('commits-branch-badge');
    var localCommit = '{{ $localCommit }}';

    if (!loadBtn) return;

    loadBtn.addEventListener('click', function () {
        loadBtn.disabled = true;
        emptyEl.classList.add('hidden');
        errorEl.classList.add('hidden');
        tableEl.classList.add('hidden');
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

            if (commits.length === 0) {
                emptyEl.classList.remove('hidden');
                return;
            }

            var localIdx    = commits.findIndex(function (c) { return c.sha === localCommit; });
            var PAGE_SIZE   = 30;
            var visibleCount = PAGE_SIZE;

            function buildRow(c, i) {
                var isNew     = localIdx === -1 || i < localIdx;
                var isCurrent = i === localIdx;
                var date      = c.date ? new Date(c.date) : null;
                var dateStr   = date
                    ? date.toLocaleDateString('tr-TR', { day:'2-digit', month:'short', year:'numeric' })
                      + ' · ' + date.toLocaleTimeString('tr-TR', { hour:'2-digit', minute:'2-digit' })
                    : '—';

                var dotCls  = isCurrent ? 'background:#10b981;box-shadow:0 0 0 3px #d1fae5'
                            : isNew     ? 'background:#8b5cf6;box-shadow:0 0 0 3px #ede9fe'
                            : 'background:#cbd5e1';
                var rowBg   = isCurrent ? 'background:#f0fdf9' : isNew ? 'background:#faf5ff' : '';
                var msgCls  = isCurrent ? 'color:#065f46' : isNew ? 'color:#4c1d95' : 'color:#1e293b';
                var badge   = isCurrent
                    ? '<span style="display:inline-flex;align-items:center;background:#d1fae5;color:#065f46;border-radius:9999px;padding:1px 7px;font-size:10px;font-weight:600;white-space:nowrap;flex-shrink:0">✓ Kurulu</span>'
                    : isNew
                    ? '<span style="display:inline-flex;align-items:center;background:#ede9fe;color:#4c1d95;border-radius:9999px;padding:1px 7px;font-size:10px;font-weight:600;white-space:nowrap;flex-shrink:0">↑ Yeni</span>'
                    : '';

                var shaStyle = isCurrent
                    ? 'color:#065f46;background:#d1fae5;border:1px solid #6ee7b7'
                    : isNew
                    ? 'color:#4c1d95;background:#ede9fe;border:1px solid #c4b5fd'
                    : 'color:#94a3b8;background:#f8fafc;border:1px solid #e2e8f0';
                var shaEl = c.url
                    ? '<a href="' + c.url + '" target="_blank" style="' + shaStyle + ';border-radius:6px;padding:2px 6px;font-family:monospace;font-size:10px;text-decoration:none;display:inline-block">' + escHtml(c.sha) + '</a>'
                    : '<span style="' + shaStyle + ';border-radius:6px;padding:2px 6px;font-family:monospace;font-size:10px;display:inline-block">' + escHtml(c.sha) + '</span>';

                var tr = document.createElement('tr');
                if (rowBg) tr.style.cssText = rowBg;
                tr.innerHTML =
                    '<td style="width:24px;padding:6px 16px;vertical-align:middle">' +
                        '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;flex-shrink:0;' + dotCls + '"></span>' +
                    '</td>' +
                    '<td style="padding:6px 16px;vertical-align:middle;max-width:0">' +
                        '<div style="display:flex;align-items:center;gap:6px;min-width:0">' +
                            '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;font-weight:500;' + msgCls + '">' + escHtml(c.message) + '</span>' +
                            badge +
                        '</div>' +
                    '</td>' +
                    '<td style="width:130px;padding:6px 16px;vertical-align:middle">' +
                        '<span style="font-size:11px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block">' + escHtml(c.author) + '</span>' +
                    '</td>' +
                    '<td style="width:160px;padding:6px 16px;vertical-align:middle">' +
                        '<span style="font-size:11px;color:#475569;white-space:nowrap">' + dateStr + '</span>' +
                    '</td>' +
                    '<td style="width:80px;padding:6px 16px;vertical-align:middle;text-align:right">' +
                        shaEl +
                    '</td>';
                return tr;
            }

            function renderRows() {
                tbodyEl.innerHTML = '';
                commits.slice(0, visibleCount).forEach(function (c, i) {
                    tbodyEl.appendChild(buildRow(c, i));
                });

                var remaining = commits.length - visibleCount;
                if (remaining > 0) {
                    moreBtn.textContent = 'Daha fazla göster (' + remaining + ' kayıt daha)';
                    footerEl.classList.remove('hidden');
                } else {
                    footerEl.classList.add('hidden');
                }
            }

            moreBtn.addEventListener('click', function () {
                visibleCount += PAGE_SIZE;
                renderRows();
            });

            renderRows();
            tableEl.classList.remove('hidden');
        })
        .catch(function (err) {
            loadingEl.classList.add('hidden');
            loadBtn.disabled = false;
            errorEl.textContent = 'Yükleme başarısız: ' + err.message;
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
    const form = document.getElementById('mikro-view-mapping-form');
    if (!form) return;

    const sampleBtn = document.getElementById('mikro-fetch-sample-btn');
    const samplePreview = document.getElementById('mikro-sample-preview');
    const normalizedPreview = document.getElementById('mikro-normalized-preview');
    const orderBadge = document.getElementById('mikro-order-columns-badge');
    const lineBadge = document.getElementById('mikro-line-columns-badge');
    const payloadMode = document.getElementById('mikro-payload-mode');
    const lineArrayKey = document.getElementById('mikro-line-array-key');
    const endpoint = '{{ route('admin.settings.mikro-view-sample') }}';

    function currentValue(select) {
        return select.dataset.current || select.value || '';
    }

    function populateSelects(scope, columns) {
        form.querySelectorAll(`.mikro-column-select[data-scope="${scope}"]`).forEach((select) => {
            const selected = currentValue(select);
            const opts = ['<option value="">Kolon seçin</option>'];
            const items = Array.isArray(columns) ? columns : [];

            items.forEach((column) => {
                const isSelected = selected === column ? ' selected' : '';
                opts.push(`<option value="${escapeHtml(column)}"${isSelected}>${escapeHtml(column)}</option>`);
            });

            if (selected && !items.includes(selected)) {
                opts.push(`<option value="${escapeHtml(selected)}" selected>${escapeHtml(selected)} (kayıtlı)</option>`);
            }

            select.innerHTML = opts.join('');
        });
    }

    function updateBadges(orderColumns, lineColumns) {
        orderBadge.textContent = Array.isArray(orderColumns) && orderColumns.length
            ? orderColumns.length + ' kolon bulundu'
            : 'Kolon bekleniyor';
        lineBadge.textContent = Array.isArray(lineColumns) && lineColumns.length
            ? lineColumns.length + ' kolon bulundu'
            : 'Kolon bekleniyor';
    }

    function toggleLineArrayKey() {
        lineArrayKey.readOnly = payloadMode.value === 'flat_rows';
        lineArrayKey.classList.toggle('bg-slate-50', payloadMode.value === 'flat_rows');
    }

    function formPayload() {
        const data = Object.fromEntries(new FormData(form).entries());
        const payload = {
            mikro_view_mapping: {
                id: data['mikro_view_mapping[id]'] || null,
                name: data['mikro_view_mapping[name]'] || '',
                view_name: data['mikro_view_mapping[view_name]'] || '',
                endpoint_path: data['mikro_view_mapping[endpoint_path]'] || '',
                payload_mode: data['mikro_view_mapping[payload_mode]'] || 'nested_lines',
                line_array_key: data['mikro_view_mapping[line_array_key]'] || 'lines',
                notes: data['mikro_view_mapping[notes]'] || '',
                mapping: { order: {}, line: {} },
            },
        };

        form.querySelectorAll('.mikro-column-select').forEach((select) => {
            const scope = select.dataset.scope;
            const field = select.dataset.field;
            payload.mikro_view_mapping.mapping[scope][field] = select.value;
        });

        return payload;
    }

    async function fetchSample() {
        sampleBtn.disabled = true;
        sampleBtn.textContent = 'Örnek veri çekiliyor…';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(formPayload()),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Örnek veri alınamadı.');
            }

            populateSelects('order', data.columns?.order || []);
            populateSelects('line', data.columns?.line || []);
            updateBadges(data.columns?.order || [], data.columns?.line || []);
            samplePreview.textContent = JSON.stringify(data.sample_payload, null, 2);
            normalizedPreview.textContent = JSON.stringify(data.normalized_preview, null, 2);
        } catch (error) {
            samplePreview.textContent = error.message;
            normalizedPreview.textContent = 'Önizleme üretilemedi.';
        } finally {
            sampleBtn.disabled = false;
            sampleBtn.textContent = 'Endpoint\'ten Örnek Veri Çek';
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    populateSelects('order', []);
    populateSelects('line', []);
    updateBadges([], []);
    toggleLineArrayKey();

    sampleBtn.addEventListener('click', fetchSample);
    payloadMode.addEventListener('change', toggleLineArrayKey);
})();
</script>
<script>
(function () {
    const toggleRows = document.querySelectorAll('[data-portal-toggle-row]');

    if (toggleRows.length) {
        const debugEnabled = new URLSearchParams(window.location.search).get('toggle_debug') === '1';
        let debugPanel = null;

        function debugToggle(message) {
            if (!debugEnabled) {
                return;
            }

            if (!debugPanel) {
                debugPanel = document.createElement('div');
                debugPanel.className = 'fixed bottom-4 right-4 z-[200] max-w-sm rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs font-medium text-amber-900 shadow-xl';
                document.body.appendChild(debugPanel);
            }

            debugPanel.textContent = message;
            window.console?.info?.('[portal-toggle-debug]', message);
        }

        function syncPortalToggle(row, reason) {
            const input = row.querySelector('[data-portal-toggle-input]');
            const button = row.querySelector('[data-portal-toggle-button]');
            const thumb = row.querySelector('[data-portal-toggle-thumb]');

            if (!input || !button || !thumb) {
                return;
            }

            const checked = input.checked;

            button.setAttribute('aria-pressed', checked ? 'true' : 'false');
            button.classList.toggle('bg-brand-500', checked);
            button.classList.toggle('bg-slate-200', !checked);
            thumb.style.transform = checked ? 'translateX(1.25rem)' : 'translateX(0)';

            debugToggle(`${input.name} => ${checked ? '1' : '0'} (${reason})`);
        }

        function togglePortalInput(row, reason) {
            const input = row.querySelector('[data-portal-toggle-input]');

            if (!input) {
                return;
            }

            input.checked = !input.checked;
            syncPortalToggle(row, reason);
        }

        toggleRows.forEach((row) => {
            syncPortalToggle(row, 'init');

            row.querySelector('[data-portal-toggle-button]')?.addEventListener('click', function (event) {
                event.preventDefault();
                togglePortalInput(row, 'button');
            });

            row.querySelector('[data-portal-toggle-label]')?.addEventListener('click', function (event) {
                event.preventDefault();
                togglePortalInput(row, 'label');
            });
        });
    }

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
                <li><code class="font-mono text-xs">npm ci && npm run build</code> — frontend assetlerini (Vite/Tailwind) yeniden derler</li>
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
