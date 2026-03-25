@extends('layouts.app')
@section('title', 'Ayarlar')
@section('page-title', 'Sistem Ayarlari')

@section('content')
<div class="card p-6 space-y-4 mb-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Uygulama Guncelleme Durumu</h2>
            <p class="text-sm text-slate-500 mt-1">Bu alan kurulu surumu, GitHub kontrol sonucunu, son update kaydini ve sadece guvenli admin aksiyonlarini gosterir.</p>
        </div>
        @php
            $statusVariant = match($updateStatus['last_status']) {
                'success' => 'success',
                'failed' => 'danger',
                default => 'warning',
            };
        @endphp
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
            <p class="text-xs uppercase tracking-wide text-slate-400">Kurulu Kod</p>
            <p class="mt-2 font-mono text-sm text-slate-900">{{ $updateStatus['current_commit'] ?: ($updateStatus['current_version'] ?? 'Bilinmiyor') }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['current_branch'] ? 'Branch: '.$updateStatus['current_branch'] : 'Git bilgisi okunamadi' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['app_version'] ? 'APP_VERSION: '.$updateStatus['app_version'] : 'APP_VERSION tanimli degil' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">GitHub Son Durum</p>
            <p class="mt-2 font-mono text-sm text-slate-900">{{ $updateStatus['latest_remote_commit'] ?: 'Henüz kontrol edilmedi' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['latest_remote_branch'] ? 'Remote branch: '.$updateStatus['latest_remote_branch'] : 'Varsayilan branch ayari kullanilacak' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['last_checked_at'] ? \Illuminate\Support\Carbon::parse($updateStatus['last_checked_at'])->format('d.m.Y H:i') : 'Henüz GitHub kontrolu yok' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Son Bilinen Deploy</p>
            <p class="mt-2 font-mono text-sm text-slate-900">{{ $updateStatus['last_deployed_version'] ?: 'Kayit yok' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['last_run_at'] ? \Illuminate\Support\Carbon::parse($updateStatus['last_run_at'])->format('d.m.Y H:i') : 'Henüz portal:update kaydi yok' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['last_message'] ?: 'Son run mesaji bulunmuyor.' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Update Durumu</p>
            <p class="mt-2 text-sm text-slate-900">
                @if($updateStatus['update_available'] === true)
                    GitHub uzerinde daha yeni bir surum var.
                @elseif($updateStatus['update_available'] === false)
                    Kurulu commit, son kontrol edilen remote commit ile ayni.
                @else
                    Henüz guvenilir bir update karsilastirmasi yapilmadi.
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
                    <p class="mt-2 text-sm text-slate-900">Bu panel sadece backend uzerinden GitHub kontrolu yapar. Kod cekme, composer, migration ve servis restart adimlari web isteginden calistirilmaz.</p>
                </div>
                <form method="POST" action="{{ route('admin.settings.update-check') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary">GitHub Kontrolu Yap</button>
                </form>
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

            <div class="mt-4">
                <p class="text-xs uppercase tracking-wide text-slate-400">Onerilen CLI Akisi</p>
                <ol class="mt-2 space-y-2 text-sm text-slate-700">
                    @foreach($updateStatus['safe_update_steps'] as $step)
                        <li class="font-mono text-xs rounded-lg bg-slate-50 px-3 py-2">{{ $step }}</li>
                    @endforeach
                </ol>
            </div>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-xs uppercase tracking-wide text-amber-700">Rollback Notu</p>
            <p class="mt-2 text-sm text-amber-900">Kod rollback ancak release dizini, snapshot veya image-tag stratejisi ile guvenli hale gelir. Bu panel simdilik rollback butonu sunmaz.</p>
            <p class="mt-3 text-xs text-amber-800">DB rollback, migration turune gore otomatik ve guvenli kabul edilmez. Spaces dosyalari ve queue isleri de ayri plan gerektirir.</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 p-4">
        <p class="text-xs uppercase tracking-wide text-slate-400">Update Gecmisi</p>
        <p class="mt-2 text-sm text-slate-900">Son kontrol ve run kayitlari burada tutulur.</p>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="pb-2 pr-4">Tip</th>
                        <th class="pb-2 pr-4">Durum</th>
                        <th class="pb-2 pr-4">Branch</th>
                        <th class="pb-2 pr-4">Local</th>
                        <th class="pb-2 pr-4">Remote</th>
                        <th class="pb-2 pr-4">Zaman</th>
                        <th class="pb-2">Mesaj</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($updateStatus['history'] as $event)
                        <tr class="align-top">
                            <td class="py-2 pr-4">{{ $event['type'] === 'run' ? 'Update Run' : 'GitHub Check' }}</td>
                            <td class="py-2 pr-4">{{ $event['status'] }}</td>
                            <td class="py-2 pr-4 font-mono text-xs">{{ $event['branch'] ?: '-' }}</td>
                            <td class="py-2 pr-4 font-mono text-xs">{{ $event['local_commit'] ?: ($event['local_version'] ?: '-') }}</td>
                            <td class="py-2 pr-4 font-mono text-xs">{{ $event['remote_commit'] ?: ($event['remote_version'] ?: '-') }}</td>
                            <td class="py-2 pr-4 text-xs text-slate-500">{{ $event['completed_at'] ? \Illuminate\Support\Carbon::parse($event['completed_at'])->format('d.m.Y H:i') : '-' }}</td>
                            <td class="py-2 text-xs text-slate-600">{{ $event['message'] ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-sm text-slate-500">Henüz update gecmisi kaydi yok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="card p-6 space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">DigitalOcean Spaces</h2>
                <p class="text-sm text-slate-500 mt-1">Bootstrap icin `.env` kullanilmaya devam eder. Buradaki alanlar calisma zamaninda uzerine yazabilir.</p>
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
                <p class="text-sm text-slate-500 mt-1">Mikro erisimi yalnizca backend tarafinda kullanilir. Gizli alanlar tekrar ekrana basilmaz; degistirmek isterseniz yeni deger girmeniz yeterlidir.</p>
            </div>

            <div class="flex items-center gap-2">
                <input type="hidden" name="mikro[enabled]" value="0">
                <input type="checkbox" name="mikro[enabled]" value="1" class="rounded border-slate-300 text-brand-600" {{ !empty($mikro['enabled']) ? 'checked' : '' }}>
                <label class="text-sm text-slate-700">Mikro entegrasyonu etkin</label>
            </div>

            <div><label class="label">Base URL</label><input class="input" type="url" name="mikro[base_url]" value="{{ $mikro['base_url'] }}"></div>
            <div><label class="label">API Key</label><input class="input" type="password" name="mikro[api_key]" value="" placeholder="{{ !empty($mikro['has_api_key']) ? 'Kayitli anahtar var, degistirmek icin yeniden girin' : 'Opsiyonel' }}"></div>

            <div class="grid md:grid-cols-2 gap-4">
                <div><label class="label">Kullanici Adi</label><input class="input" type="text" name="mikro[username]" value="" placeholder="{{ !empty($mikro['has_username']) ? 'Kayitli kullanici var, degistirmek icin yeniden girin' : 'Opsiyonel' }}"></div>
                <div><label class="label">Sifre</label><input class="input" type="password" name="mikro[password]" value="" placeholder="{{ !empty($mikro['has_password']) ? 'Kayitli sifre var, degistirmek icin yeniden girin' : 'Opsiyonel' }}"></div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div><label class="label">Sirket Kodu</label><input class="input" type="text" name="mikro[company_code]" value="{{ $mikro['company_code'] }}"></div>
                <div><label class="label">Calisma Yili</label><input class="input" type="text" name="mikro[work_year]" value="{{ $mikro['work_year'] }}"></div>
            </div>

            <div><label class="label">Sevk Endpoint Yolu</label><input class="input" type="text" name="mikro[shipment_endpoint]" value="{{ $mikro['shipment_endpoint'] }}" placeholder="/api/dispatch-status"></div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Senkron Araligi (dk)</label>
                    <input class="input" type="number" min="5" max="1440" name="mikro[sync_interval_minutes]" value="{{ $mikro['sync_interval_minutes'] }}">
                </div>
                <div>
                    <label class="label">HTTP Timeout (sn)</label>
                    <input class="input" type="number" min="1" max="300" name="mikro[timeout]" value="{{ $mikro['timeout'] }}">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="hidden" name="mikro[use_direct_db]" value="0">
                        <input type="checkbox" name="mikro[use_direct_db]" value="1" class="rounded border-slate-300 text-brand-600" {{ !empty($mikro['use_direct_db']) ? 'checked' : '' }}>
                        Dogrudan DB baglantisi kullanilacak
                    </label>
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="hidden" name="mikro[verify_ssl]" value="0">
                        <input type="checkbox" name="mikro[verify_ssl]" value="1" class="rounded border-slate-300 text-brand-600" {{ !empty($mikro['verify_ssl']) ? 'checked' : '' }}>
                        SSL dogrulamasi aktif
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary">Ayarlari Kaydet</button>
    </div>
</form>
@endsection
