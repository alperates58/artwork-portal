@extends('setup.layout')

@section('content')
<div class="p-8">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">DigitalOcean Spaces</h1>
        <p class="text-sm text-slate-500 mt-1">İsterseniz şimdi Spaces yapılandırın, isterseniz yerel Windows kurulumunda bu adımı atlayıp sonra tamamlayın.</p>
    </div>

    <div class="mb-6 p-4 bg-amber-50 border border-amber-100 rounded-xl text-sm text-amber-800 space-y-2">
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="enable_spaces_toggle" value="1" id="enableSpaces" class="mt-0.5 rounded border-slate-300 text-brand-600" {{ old('enable_spaces', '1') ? 'checked' : '' }}>
            <div>
                <p class="font-medium text-amber-900">Spaces yapılandırmasını şimdi yap</p>
                <p class="text-xs text-amber-700 mt-0.5">Yerel Windows kurulumunda bu seçeneği kapatırsanız kurulum yerel disk ile tamamlanır. Spaces bilgilerini daha sonra ortam değişkenlerinden ekleyebilirsiniz.</p>
            </div>
        </label>
    </div>

    <div id="spacesHelp" class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-700 space-y-1.5">
        <p class="font-semibold text-blue-800 mb-2">Spaces nasıl oluşturulur?</p>
        <p>1. <a href="https://cloud.digitalocean.com/spaces" target="_blank" class="underline">cloud.digitalocean.com/spaces</a> → Create a Space</p>
        <p>2. Region: <strong>Frankfurt (fra1)</strong> seç → bucket adını gir → Create</p>
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
        <input type="hidden" name="enable_spaces" id="enableSpacesInput" value="{{ old('enable_spaces', '1') ? '1' : '0' }}">

        <div id="spacesFields" class="space-y-5">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="label" for="spaces_key">Access Key</label>
                    <input type="text" id="spaces_key" name="spaces_key" value="{{ old('spaces_key') }}" class="input font-mono text-xs {{ $errors->has('spaces_key') ? 'error' : '' }}" placeholder="DO00XXXXXXXXXXXXXXXXXXXX" autocomplete="off">
                    @error('spaces_key') <p class="err">{{ $message }}</p> @enderror
                </div>
                <div class="col-span-2">
                    <label class="label" for="spaces_secret">Secret Key</label>
                    <input type="password" id="spaces_secret" name="spaces_secret" value="{{ old('spaces_secret') }}" class="input font-mono text-xs {{ $errors->has('spaces_secret') ? 'error' : '' }}" placeholder="••••••••••••••••••••••••••••••••" autocomplete="off">
                    @error('spaces_secret') <p class="err">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="label" for="spaces_bucket">Bucket Adı</label>
                <input type="text" id="spaces_bucket" name="spaces_bucket" value="{{ old('spaces_bucket', 'lider-portal-prod') }}" class="input {{ $errors->has('spaces_bucket') ? 'error' : '' }}" placeholder="lider-portal-prod">
                @error('spaces_bucket') <p class="err">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label" for="spaces_region">Region</label>
                    <select id="spaces_region" name="spaces_region" class="input {{ $errors->has('spaces_region') ? 'error' : '' }}" onchange="updateEndpoint(this.value)">
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
                            <option value="{{ $val }}" {{ $selectedRegion === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('spaces_region') <p class="err">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="spaces_endpoint">Endpoint</label>
                    <input type="url" id="spaces_endpoint" name="spaces_endpoint" value="{{ old('spaces_endpoint', 'https://fra1.digitaloceanspaces.com') }}" class="input text-xs {{ $errors->has('spaces_endpoint') ? 'error' : '' }}" placeholder="https://fra1.digitaloceanspaces.com">
                    @error('spaces_endpoint') <p class="err">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="pt-2 flex justify-between">
            <a href="{{ route('setup.step', 2) }}" class="btn btn-secondary">Geri</a>
            <button type="submit" class="btn btn-primary" id="spacesBtn">
                <span id="spacesLabel">{{ old('enable_spaces', '1') ? 'Bağlantıyı Test Et ve Devam' : 'Spaces Olmadan Devam Et' }}</span>
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

function syncSpacesMode() {
    const enabled = document.getElementById('enableSpaces')?.checked ?? false;
    const fields = document.getElementById('spacesFields');
    const help = document.getElementById('spacesHelp');
    const input = document.getElementById('enableSpacesInput');
    const label = document.getElementById('spacesLabel');

    input.value = enabled ? '1' : '0';
    fields?.classList.toggle('hidden', !enabled);
    help?.classList.toggle('hidden', !enabled);
    label.textContent = enabled ? 'Bağlantıyı Test Et ve Devam' : 'Spaces Olmadan Devam Et';
}

document.getElementById('enableSpaces')?.addEventListener('change', syncSpacesMode);
syncSpacesMode();

document.querySelector('form').addEventListener('submit', function() {
    const btn = document.getElementById('spacesBtn');
    const enabled = document.getElementById('enableSpaces')?.checked ?? false;
    document.getElementById('spacesLabel').textContent = enabled ? 'Test ediliyor...' : 'Kaydediliyor...';
    btn.disabled = true;
});
</script>
@endpush
