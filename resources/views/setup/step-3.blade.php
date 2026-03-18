@extends('setup.layout')

@section('content')
<div class="p-8">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">DigitalOcean Spaces</h1>
        <p class="text-sm text-slate-500 mt-1">Artwork dosyalarının depolanacağı Spaces bilgilerini girin.</p>
    </div>

    {{-- Spaces nasıl oluşturulur yardım kutusu --}}
    <div class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-700 space-y-1.5">
        <p class="font-semibold text-blue-800 mb-2">Spaces nasıl oluşturulur?</p>
        <p>1. <a href="https://cloud.digitalocean.com/spaces" target="_blank" class="underline">cloud.digitalocean.com/spaces</a> → Create a Space</p>
        <p>2. Region: <strong>Frankfurt (fra1)</strong> seç → Bucket adını gir → Create</p>
        <p>3. API → Spaces Keys → Generate New Key ile Access Key + Secret al</p>
        <p>4. Aşağıya gir, bağlantı otomatik test edilecek.</p>
    </div>

    @if($errors->has('spaces_key') && str_contains($errors->first('spaces_key'), 'kurulamadı'))
        <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
            <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-red-700">{{ $errors->first('spaces_key') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('setup.save.spaces') }}" class="space-y-5">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="label" for="spaces_key">Access Key</label>
                <input type="text" id="spaces_key" name="spaces_key"
                       value="{{ old('spaces_key') }}"
                       class="input font-mono text-xs {{ $errors->has('spaces_key') ? 'error' : '' }}"
                       placeholder="DO00XXXXXXXXXXXXXXXXXXXX"
                       autocomplete="off">
                @error('spaces_key') <p class="err">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="label" for="spaces_secret">Secret Key</label>
                <input type="password" id="spaces_secret" name="spaces_secret"
                       value="{{ old('spaces_secret') }}"
                       class="input font-mono text-xs {{ $errors->has('spaces_secret') ? 'error' : '' }}"
                       placeholder="••••••••••••••••••••••••••••••••"
                       autocomplete="off">
                @error('spaces_secret') <p class="err">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="label" for="spaces_bucket">Bucket Adı</label>
            <input type="text" id="spaces_bucket" name="spaces_bucket"
                   value="{{ old('spaces_bucket', 'artwork-portal-prod') }}"
                   class="input {{ $errors->has('spaces_bucket') ? 'error' : '' }}"
                   placeholder="artwork-portal-prod">
            @error('spaces_bucket') <p class="err">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="label" for="spaces_region">Region</label>
                <select id="spaces_region" name="spaces_region"
                        class="input {{ $errors->has('spaces_region') ? 'error' : '' }}"
                        onchange="updateEndpoint(this.value)">
                    @php
                        $regions = [
                            'fra1' => 'Frankfurt (fra1)',
                            'ams3' => 'Amsterdam (ams3)',
                            'nyc3' => 'New York (nyc3)',
                            'sgp1' => 'Singapore (sgp1)',
                            'sfo3' => 'San Francisco (sfo3)',
                        ];
                        $selectedRegion = old('spaces_region', 'fra1');
                    @endphp
                    @foreach($regions as $val => $label)
                        <option value="{{ $val }}" {{ $selectedRegion === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('spaces_region') <p class="err">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="spaces_endpoint">Endpoint</label>
                <input type="url" id="spaces_endpoint" name="spaces_endpoint"
                       value="{{ old('spaces_endpoint', 'https://fra1.digitaloceanspaces.com') }}"
                       class="input text-xs {{ $errors->has('spaces_endpoint') ? 'error' : '' }}"
                       placeholder="https://fra1.digitaloceanspaces.com">
                @error('spaces_endpoint') <p class="err">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="pt-2 flex justify-between">
            <a href="{{ route('setup.step', 2) }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Geri
            </a>
            <button type="submit" class="btn-primary" id="spacesBtn">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span id="spacesLabel">Bağlantıyı Test Et ve Devam</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function updateEndpoint(region) {
    document.getElementById('spaces_endpoint').value = `https://${region}.digitaloceanspaces.com`;
}
document.querySelector('form').addEventListener('submit', function() {
    const btn = document.getElementById('spacesBtn');
    document.getElementById('spacesLabel').textContent = 'Test ediliyor...';
    btn.disabled = true;
});
</script>
@endpush
