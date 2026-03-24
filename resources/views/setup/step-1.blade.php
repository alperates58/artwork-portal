@extends('setup.layout')

@section('content')
<div class="p-8">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Site Ayarları</h1>
        <p class="text-sm text-slate-500 mt-1">Uygulamanın temel bilgilerini girin.</p>
    </div>

    <form method="POST" action="{{ route('setup.save.site') }}" class="space-y-5">
        @csrf

        <div>
            <label class="label" for="app_name">Uygulama Adı</label>
            <input type="text" id="app_name" name="app_name"
                   value="{{ old('app_name', 'Lider Portal') }}"
                   class="input {{ $errors->has('app_name') ? 'error' : '' }}"
                   placeholder="Lider Portal">
            @error('app_name') <p class="err">{{ $message }}</p> @enderror
            <p class="hint">Tarayıcı sekmesinde ve e-postalarda görünür.</p>
        </div>

        <div>
            <label class="label" for="app_url">Site URL</label>
            <input type="url" id="app_url" name="app_url"
                   value="{{ old('app_url', 'https://') }}"
                   class="input {{ $errors->has('app_url') ? 'error' : '' }}"
                   placeholder="https://portal.sirketiniz.com">
            @error('app_url') <p class="err">{{ $message }}</p> @enderror
            <p class="hint">Sonunda / olmadan girin. Presigned URL'ler için kullanılır.</p>
        </div>

        <div>
            <label class="label" for="app_timezone">Saat Dilimi</label>
            <select id="app_timezone" name="app_timezone"
                    class="input {{ $errors->has('app_timezone') ? 'error' : '' }}">
                @php
                    $timezones = [
                        'Europe/Istanbul' => 'Türkiye (Europe/Istanbul)',
                        'UTC' => 'UTC',
                        'Europe/London' => 'Londra (Europe/London)',
                        'Europe/Berlin' => 'Berlin (Europe/Berlin)',
                        'America/New_York' => 'New York (America/New_York)',
                    ];
                    $selected = old('app_timezone', 'Europe/Istanbul');
                @endphp
                @foreach($timezones as $value => $label)
                    <option value="{{ $value }}" {{ $selected === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('app_timezone') <p class="err">{{ $message }}</p> @enderror
        </div>

        <div class="pt-2 flex justify-end">
            <button type="submit" class="btn btn-primary">
                Devam
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </form>
</div>
@endsection
