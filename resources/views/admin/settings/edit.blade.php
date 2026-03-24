@extends('layouts.app')
@section('title', 'Ayarlar')
@section('page-title', 'Sistem Ayarları')

@section('content')
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
                <p class="text-sm text-slate-500 mt-1">Gerçek endpoint detayları gelmeden canlı sevk doğrulaması yapılmaz. Bu ekran bağlantı bilgilerini güvenli biçimde hazır tutar.</p>
            </div>

            <div><label class="label">Base URL</label><input class="input" type="url" name="mikro[base_url]" value="{{ $mikro['base_url'] }}"></div>
            <div><label class="label">API Key</label><input class="input" type="password" name="mikro[api_key]" value="{{ $mikro['api_key'] }}"></div>
            <div><label class="label">Sevk Endpoint Yolu</label><input class="input" type="text" name="mikro[shipment_endpoint]" value="{{ $mikro['shipment_endpoint'] }}" placeholder="/api/dispatch-status"></div>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Senkron Aralığı (dk)</label>
                    <input class="input" type="number" min="5" max="1440" name="mikro[sync_interval_minutes]" value="{{ $mikro['sync_interval_minutes'] }}">
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="hidden" name="mikro[use_direct_db]" value="0">
                        <input type="checkbox" name="mikro[use_direct_db]" value="1" class="rounded border-slate-300 text-brand-600" {{ !empty($mikro['use_direct_db']) ? 'checked' : '' }}>
                        Doğrudan DB bağlantısı kullanılacak
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
