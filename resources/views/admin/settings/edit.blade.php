@extends('layouts.app')
@section('title', 'Ayarlar')
@section('page-title', 'Sistem Ayarları')

@php
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
@endphp

@section('content')
<div class="card p-6 space-y-6 mb-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Uygulama Güncelleme Durumu</h2>
            <p class="text-sm text-slate-500 mt-1">Bu alan kurulu sürümü, release notlarını, schema etkisini, son kontrol bilgisini ve yalnız güvenli admin aksiyonlarını gösterir.</p>
        </div>
        <x-ui.badge :variant="$statusVariant">
            {{ match($updateStatus['last_status']) {
                'success' => 'Son durum: Başarılı',
                'failed' => 'Son durum: Hatalı',
                default => 'Son durum: Kayıt yok',
            } }}
        </x-ui.badge>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Kurulu Sürüm</p>
            <p class="mt-2 font-mono text-sm text-slate-900">{{ $updateStatus['current_version'] ?: 'Bilinmiyor' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['current_branch'] ? 'Branch: '.$updateStatus['current_branch'] : 'Git branch bilgisi okunamadı' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['current_commit'] ? 'Commit: '.$updateStatus['current_commit'] : 'Commit bilgisi okunamadı' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Hedef Release</p>
            <p class="mt-2 font-mono text-sm text-slate-900">{{ data_get($remoteRelease, 'version') ?: 'Henüz kontrol edilmedi' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ data_get($remoteRelease, 'title') ?: 'Release manifest bilgisi yok' }}</p>
            <p class="mt-1 text-xs text-slate-500">
                {{ $updateStatus['last_checked_at'] ? \Illuminate\Support\Carbon::parse($updateStatus['last_checked_at'])->timezone($displayTimezone)->format('d.m.Y H:i') : 'Henüz GitHub kontrolü yok' }}
            </p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Son Bilinen Deploy</p>
            <p class="mt-2 font-mono text-sm text-slate-900">{{ $updateStatus['last_deployed_version'] ?: 'Kayıt yok' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['last_run_at'] ? \Illuminate\Support\Carbon::parse($updateStatus['last_run_at'])->timezone($displayTimezone)->format('d.m.Y H:i') : 'Henüz portal:update kaydı yok' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['last_message'] ?: 'Son run mesajı bulunmuyor.' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Update Durumu</p>
            <p class="mt-2 text-sm text-slate-900">
                @if($updateStatus['update_available'] === true)
                    Yeni bir sürüm uygulanmaya hazır görünüyor.
                @elseif($updateStatus['update_available'] === false)
                    Kurulu sürüm ile son kontrol edilen release eşleşiyor.
                @else
                    Henüz güvenilir bir update karşılaştırması yapılmadı.
                @endif
            </p>
            <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['last_check_message'] ?: 'GitHub kontrol mesajı bulunmuyor.' }}</p>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 p-4 xl:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Güvenli Admin Aksiyonları</p>
                    <p class="mt-2 text-sm text-slate-900">Bu panel release notlarını ve gerekli sunucu adımlarını gösterir. Web isteği içinden `git pull`, `composer install`, `migrate --force` veya rollback çalıştırılmaz.</p>
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <form method="POST" action="{{ route('admin.settings.update-check') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary">GitHub Kontrolü Yap</button>
                    </form>
                    <button type="button" class="btn btn-primary" data-dialog-open="update-confirmation" {{ $canPrepare ? '' : 'disabled' }}>
                        Güncellemeyi İncele
                    </button>
                </div>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Kontrol Komutu</p>
                    <p class="mt-2 font-mono text-xs text-slate-900">{{ $updateStatus['check_command'] }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Deploy Komutu</p>
                    <p class="mt-2 font-mono text-xs text-slate-900">{{ $updateStatus['update_command'] }}</p>
                </div>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Kurulu Release Özeti</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ data_get($currentRelease, 'title') ?: 'Sürüm manifesti bulunamadı' }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ data_get($currentRelease, 'summary') ?: 'Mevcut sürüm için açıklama kaydı yok.' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Hazır Hedef Release</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ data_get($remoteRelease, 'title') ?: 'Henüz hedef release yok' }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ data_get($remoteRelease, 'summary') ?: 'Önce GitHub kontrolü yapıldığında release özeti burada görünür.' }}</p>
                </div>
            </div>

            <div class="mt-4">
                <p class="text-xs uppercase tracking-wide text-slate-400">Önerilen CLI Akışı</p>
                <ol class="mt-2 space-y-2 text-sm text-slate-700">
                    @foreach($updateStatus['safe_update_steps'] as $step)
                        <li class="font-mono text-xs rounded-lg bg-slate-50 px-3 py-2">{{ $step }}</li>
                    @endforeach
                </ol>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs uppercase tracking-wide text-amber-700">Rollback Notu</p>
                <p class="mt-2 text-sm text-amber-900">Rollback ancak release dizini, image tag veya snapshot stratejisi ile güvenli hale gelir. Bu panel bilinçli olarak rollback butonu sunmaz.</p>
                <p class="mt-3 text-xs text-amber-800">Veritabanı rollback, migration tipine göre otomatik ve güvenli kabul edilmez. Spaces dosyaları ve queue işleri ayrı plan gerektirir.</p>
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-400">Bekleyen Hazırlık</p>
                @if($pendingPreparation)
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $pendingPreparation['from_version'] ?: '?' }} → {{ $pendingPreparation['to_version'] ?: '?' }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $pendingPreparation['release_title'] ?: 'Release başlığı yok' }}</p>
                    <p class="mt-2 text-xs text-slate-500">Hazırlık zamanı: {{ \Illuminate\Support\Carbon::parse($pendingPreparation['created_at'])->timezone($displayTimezone)->format('d.m.Y H:i') }}</p>
                @else
                    <p class="mt-2 text-sm text-slate-600">Bekleyen admin hazırlığı yok.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Gelen Değişiklikler</p>
            @if($remoteRelease)
                <div class="mt-3 space-y-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $remoteRelease['title'] ?: 'Başlık yok' }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $remoteRelease['summary'] ?: 'Özet yok.' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Modüller</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse($remoteRelease['changed_modules'] ?? [] as $module)
                                <span class="badge badge-gray">{{ $module }}</span>
                            @empty
                                <span class="text-sm text-slate-500">Modül listesi yok.</span>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Değişiklik Maddeleri</p>
                        <ul class="mt-2 space-y-2 text-sm text-slate-700 list-disc list-inside">
                            @forelse($remoteRelease['change_summary'] ?? [] as $item)
                                <li>{{ $item }}</li>
                            @empty
                                <li>Detaylı release notu bulunmuyor.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @else
                <p class="mt-2 text-sm text-slate-600">Henüz remote release verisi yok.</p>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Schema ve Operasyon Notları</p>
            @if($remoteRelease)
                <div class="mt-3 space-y-4 text-sm">
                    <div class="flex items-center gap-2">
                        <x-ui.badge :variant="($remoteRelease['migrations_included'] ?? false) ? 'warning' : 'gray'">
                            {{ ($remoteRelease['migrations_included'] ?? false) ? 'Migration içerir' : 'Migration içermez' }}
                        </x-ui.badge>
                        @if(data_get($remoteRelease, 'release_date'))
                            <span class="text-xs text-slate-500">Release tarihi: {{ \Illuminate\Support\Carbon::parse($remoteRelease['release_date'])->timezone($displayTimezone)->format('d.m.Y') }}</span>
                        @endif
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Yeni Tablolar</p>
                        <ul class="mt-2 space-y-1 text-slate-700 list-disc list-inside">
                            @forelse(data_get($remoteRelease, 'schema_changes.new_tables', []) as $tableName)
                                <li class="font-mono text-xs">{{ $tableName }}</li>
                            @empty
                                <li>Yeni tablo yok.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Yeni Kolonlar</p>
                        <ul class="mt-2 space-y-1 text-slate-700 list-disc list-inside">
                            @forelse(data_get($remoteRelease, 'schema_changes.new_columns', []) as $columnName)
                                <li class="font-mono text-xs">{{ $columnName }}</li>
                            @empty
                                <li>Yeni kolon yok.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Uyarılar</p>
                        <ul class="mt-2 space-y-1 text-slate-700 list-disc list-inside">
                            @forelse($remoteRelease['warnings'] ?? [] as $warning)
                                <li>{{ $warning }}</li>
                            @empty
                                <li>Özel uyarı kaydı yok.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Post-update Notları</p>
                        <ul class="mt-2 space-y-1 text-slate-700 list-disc list-inside">
                            @forelse($remoteRelease['post_update_notes'] ?? [] as $note)
                                <li class="font-mono text-xs">{{ $note }}</li>
                            @empty
                                <li>Post-update notu yok.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @else
                <p class="mt-2 text-sm text-slate-600">Schema ve operasyon notları için önce GitHub kontrolü yapın.</p>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 p-4">
        <p class="text-xs uppercase tracking-wide text-slate-400">Update Geçmişi</p>
        <p class="mt-2 text-sm text-slate-900">Son kontrol, hazırlık ve run kayıtları burada tutulur. Tüm zamanlar {{ $displayTimezone }} bazında gösterilir.</p>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="pb-2 pr-4">Tip</th>
                        <th class="pb-2 pr-4">Durum</th>
                        <th class="pb-2 pr-4">Sürüm Geçişi</th>
                        <th class="pb-2 pr-4">Release</th>
                        <th class="pb-2 pr-4">Şema</th>
                        <th class="pb-2 pr-4">Zaman</th>
                        <th class="pb-2">Not</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($updateStatus['history'] as $event)
                        <tr class="align-top">
                            <td class="py-3 pr-4">
                                {{ match($event['type']) {
                                    'run' => 'Update Run',
                                    'prepare' => 'Hazırlık',
                                    default => 'GitHub Check',
                                } }}
                            </td>
                            <td class="py-3 pr-4">
                                <x-ui.badge :variant="match($event['status']) {
                                    'success', 'applied' => 'success',
                                    'failed' => 'danger',
                                    'pending' => 'warning',
                                    default => 'gray',
                                }">
                                    {{ $event['status'] }}
                                </x-ui.badge>
                            </td>
                            <td class="py-3 pr-4 font-mono text-xs text-slate-700">
                                {{ $event['from_version'] ?: ($event['local_version'] ?: '-') }} → {{ $event['to_version'] ?: ($event['remote_version'] ?: '-') }}
                            </td>
                            <td class="py-3 pr-4">
                                <p class="text-sm text-slate-900">{{ $event['release_title'] ?: '-' }}</p>
                                @if($event['release_date'])
                                    <p class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($event['release_date'])->timezone($displayTimezone)->format('d.m.Y') }}</p>
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-xs text-slate-600">
                                @if($event['migrations_included'])
                                    <p>Migration var</p>
                                    @if(!empty(data_get($event, 'schema_changes.new_tables', [])))
                                        <p>Tablo: {{ implode(', ', data_get($event, 'schema_changes.new_tables', [])) }}</p>
                                    @endif
                                    @if(!empty(data_get($event, 'schema_changes.new_columns', [])))
                                        <p>Kolon: {{ implode(', ', data_get($event, 'schema_changes.new_columns', [])) }}</p>
                                    @endif
                                @else
                                    <p>Schema değişikliği yok</p>
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-xs text-slate-500">
                                {{ $event['completed_at'] ? \Illuminate\Support\Carbon::parse($event['completed_at'])->timezone($displayTimezone)->format('d.m.Y H:i') : '-' }}
                            </td>
                            <td class="py-3 text-xs text-slate-600">
                                <p>{{ $event['message'] ?: '-' }}</p>
                                @if(!empty($event['applied_migrations']))
                                    <p class="mt-1 font-mono">{{ implode(', ', $event['applied_migrations']) }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-sm text-slate-500">Henüz update geçmişi kaydı yok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<dialog id="update-confirmation" class="update-modal w-full max-w-3xl rounded-2xl border border-slate-200 p-0 shadow-2xl">
    <form method="dialog" class="border-b border-slate-200 px-6 py-4 flex items-start justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-400">Update Onayı</p>
            <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $updateStatus['current_version'] ?: '?' }} → {{ data_get($remoteRelease, 'version') ?: '?' }}</h3>
        </div>
        <button type="button" class="text-slate-400 hover:text-slate-600" data-dialog-close>
            <span class="sr-only">Kapat</span>
            ×
        </button>
    </form>

    <div class="px-6 py-5 space-y-5">
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-400">Mevcut Sürüm</p>
                <p class="mt-2 font-mono text-sm text-slate-900">{{ $updateStatus['current_version'] ?: 'Bilinmiyor' }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ data_get($currentRelease, 'title') ?: 'Release başlığı yok' }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-400">Hedef Sürüm</p>
                <p class="mt-2 font-mono text-sm text-slate-900">{{ data_get($remoteRelease, 'version') ?: 'Bilinmiyor' }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ data_get($remoteRelease, 'title') ?: 'Release başlığı yok' }}</p>
            </div>
        </div>

        <div>
            <p class="text-sm font-semibold text-slate-900">{{ data_get($remoteRelease, 'summary') ?: 'Release özeti bulunamadı.' }}</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Değişiklikler</p>
                <ul class="mt-2 space-y-2 text-sm text-slate-700 list-disc list-inside">
                    @forelse($remoteRelease['change_summary'] ?? [] as $item)
                        <li>{{ $item }}</li>
                    @empty
                        <li>Detaylı madde yok.</li>
                    @endforelse
                </ul>
            </div>
            <div class="space-y-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Schema Etkisi</p>
                    <p class="mt-2 text-sm text-slate-700">{{ ($remoteRelease['migrations_included'] ?? false) ? 'Bu update migration/schema değişikliği içeriyor.' : 'Bu update için schema değişikliği görünmüyor.' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Uyarılar</p>
                    <ul class="mt-2 space-y-1 text-sm text-slate-700 list-disc list-inside">
                        @forelse($remoteRelease['warnings'] ?? [] as $warning)
                            <li>{{ $warning }}</li>
                        @empty
                            <li>Özel uyarı yok.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Bu onay işlemi sunucuda doğrudan deploy çalıştırmaz. Yalnızca admin niyetini ve hedef release bilgisini kaydeder. Gerçek uygulama adımı kontrollü CLI veya deploy pipeline üzerinden tamamlanmalıdır.
        </div>
    </div>

    <div class="border-t border-slate-200 px-6 py-4 flex items-center justify-between gap-3">
        <button type="button" class="btn btn-secondary" data-dialog-close>İptal</button>
        <form method="POST" action="{{ route('admin.settings.update-prepare') }}">
            @csrf
            <button type="submit" class="btn btn-primary">Hazırlığı Onayla</button>
        </form>
    </div>
</dialog>

<form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="card p-6 space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">DigitalOcean Spaces</h2>
                <p class="text-sm text-slate-500 mt-1">Bootstrap için `.env` kullanılmaya devam eder. Buradaki alanlar çalışma zamanında üzerine yazabilir.</p>
            </div>

            <div>
                <label class="label">Aktif Disk</label>
                <select name="spaces[disk]" class="input">
                    <option value="local" {{ ($spaces['disk'] ?? 'local') === 'local' ? 'selected' : '' }}>Local</option>
                    <option value="spaces" {{ ($spaces['disk'] ?? 'local') === 'spaces' ? 'selected' : '' }}>Spaces</option>
                </select>
            </div>
            <div><label class="label">Access Key</label><input class="input" type="text" name="spaces[key]" value="{{ $spaces['key'] }}"></div>
            <div><label class="label">Secret Key</label><input class="input" type="password" name="spaces[secret]" value="{{ $spaces['secret'] }}"></div>
            <div><label class="label">Endpoint</label><input class="input" type="url" name="spaces[endpoint]" value="{{ $spaces['endpoint'] }}"></div>
            <div class="grid md:grid-cols-2 gap-4">
                <div><label class="label">Region</label><input class="input" type="text" name="spaces[region]" value="{{ $spaces['region'] }}"></div>
                <div><label class="label">Bucket</label><input class="input" type="text" name="spaces[bucket]" value="{{ $spaces['bucket'] }}"></div>
            </div>
            <div><label class="label">CDN / URL</label><input class="input" type="url" name="spaces[url]" value="{{ $spaces['url'] }}"></div>
        </div>

        <div class="card p-6 space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Mikro API</h2>
                <p class="text-sm text-slate-500 mt-1">Mikro erişimi yalnızca backend tarafında kullanılır. Gizli alanlar tekrar ekrana basılmaz; değiştirmek isterseniz yeni değer girmeniz yeterlidir.</p>
            </div>

            <div class="flex items-center gap-2">
                <input type="hidden" name="mikro[enabled]" value="0">
                <input type="checkbox" name="mikro[enabled]" value="1" class="rounded border-slate-300 text-brand-600" {{ !empty($mikro['enabled']) ? 'checked' : '' }}>
                <label class="text-sm text-slate-700">Mikro entegrasyonu etkin</label>
            </div>

            <div><label class="label">Base URL</label><input class="input" type="url" name="mikro[base_url]" value="{{ $mikro['base_url'] }}"></div>
            <div><label class="label">API Key</label><input class="input" type="password" name="mikro[api_key]" value="" placeholder="{{ !empty($mikro['has_api_key']) ? 'Kayıtlı anahtar var, değiştirmek için yeniden girin' : 'Opsiyonel' }}"></div>

            <div class="grid md:grid-cols-2 gap-4">
                <div><label class="label">Kullanıcı Adı</label><input class="input" type="text" name="mikro[username]" value="" placeholder="{{ !empty($mikro['has_username']) ? 'Kayıtlı kullanıcı var, değiştirmek için yeniden girin' : 'Opsiyonel' }}"></div>
                <div><label class="label">Şifre</label><input class="input" type="password" name="mikro[password]" value="" placeholder="{{ !empty($mikro['has_password']) ? 'Kayıtlı şifre var, değiştirmek için yeniden girin' : 'Opsiyonel' }}"></div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div><label class="label">Şirket Kodu</label><input class="input" type="text" name="mikro[company_code]" value="{{ $mikro['company_code'] }}"></div>
                <div><label class="label">Çalışma Yılı</label><input class="input" type="text" name="mikro[work_year]" value="{{ $mikro['work_year'] }}"></div>
            </div>

            <div><label class="label">Sevk Endpoint Yolu</label><input class="input" type="text" name="mikro[shipment_endpoint]" value="{{ $mikro['shipment_endpoint'] }}" placeholder="/api/dispatch-status"></div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Senkron Aralığı (dk)</label>
                    <input class="input" type="number" min="5" max="1440" name="mikro[sync_interval_minutes]" value="{{ $mikro['sync_interval_minutes'] }}">
                </div>
                <div>
                    <label class="label">HTTP Timeout (sn)</label>
                    <input class="input" type="number" min="1" max="300" name="mikro[timeout]" value="{{ $mikro['timeout'] }}">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                    Tercih edilen entegrasyon kontratı ERP tarafında sabit bir SQL VIEW alias yapısıdır. Bu panel serbest SQL veya doğrudan DB sorgusu çalıştırmaz.
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="hidden" name="mikro[verify_ssl]" value="0">
                        <input type="checkbox" name="mikro[verify_ssl]" value="1" class="rounded border-slate-300 text-brand-600" {{ !empty($mikro['verify_ssl']) ? 'checked' : '' }}>
                        SSL doğrulaması aktif
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary">Ayarları Kaydet</button>
    </div>
</form>
@endsection
