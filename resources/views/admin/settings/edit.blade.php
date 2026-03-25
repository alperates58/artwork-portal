@extends('layouts.app')
@section('title', 'Ayarlar')
@section('page-title', 'Sistem Ayarlari')

@section('content')
<div class="card p-6 space-y-4 mb-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Uygulama Guncelleme Durumu</h2>
            <p class="text-sm text-slate-500 mt-1">Bu alan sadece mevcut kod surumunu, son bilinen deploy kaydini ve manuel update komutunu gosterir.</p>
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
            <p class="text-xs uppercase tracking-wide text-slate-400">Mevcut Surum</p>
            <p class="mt-2 font-mono text-sm text-slate-900">{{ $updateStatus['current_version'] ?? 'Bilinmiyor' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['current_branch'] ? 'Branch: '.$updateStatus['current_branch'] : 'Git bilgisi okunamadi' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Son Bilinen Deploy</p>
            <p class="mt-2 font-mono text-sm text-slate-900">{{ $updateStatus['last_deployed_version'] ?: 'Kayit yok' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['last_run_at'] ? \Illuminate\Support\Carbon::parse($updateStatus['last_run_at'])->format('d.m.Y H:i') : 'Henüz portal:update kaydi yok' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Komut Gorunurlugu</p>
            <p class="mt-2 font-mono text-xs text-slate-900">{{ $updateStatus['command'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Bu ekranda sadece bilgi verilir; update islemi manuel ve kontrollu yapilmalidir.</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Durum Notu</p>
            <p class="mt-2 text-sm text-slate-900">{{ $updateStatus['is_out_of_sync'] ? 'Calisan kod ile son deploy kaydi farkli.' : 'Kod ile son deploy kaydi uyumlu veya henuz deploy kaydi yok.' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $updateStatus['last_message'] ?: 'Ek bir update mesaji bulunmuyor.' }}</p>
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
