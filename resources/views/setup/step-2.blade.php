@extends('setup.layout')

@section('content')
<div class="p-8">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Veritabanı Bağlantısı</h1>
        <p class="text-sm text-slate-500 mt-1">MySQL veritabanı bilgilerini girin. Bağlantı test edilecek. İsterseniz veritabanı yoksa otomatik oluşturabilirsiniz.</p>
    </div>

    @if($errors->has('db_host') && str_contains($errors->first('db_host'), 'kurulamadı'))
        <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
            <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-red-700">{{ $errors->first('db_host') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('setup.save.database') }}" class="space-y-5">
        @csrf

        <div class="grid grid-cols-3 gap-4">
            <div class="col-span-2">
                <label class="label" for="db_host">Host</label>
                <input type="text" id="db_host" name="db_host"
                       value="{{ old('db_host', 'mysql') }}"
                       class="input {{ $errors->has('db_host') ? 'error' : '' }}"
                       placeholder="mysql veya 127.0.0.1">
                @error('db_host') <p class="err">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="db_port">Port</label>
                <input type="number" id="db_port" name="db_port"
                       value="{{ old('db_port', '3306') }}"
                       class="input {{ $errors->has('db_port') ? 'error' : '' }}">
                @error('db_port') <p class="err">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="label" for="db_database">Veritabanı Adı</label>
            <input type="text" id="db_database" name="db_database"
                   value="{{ old('db_database', 'artwork_portal') }}"
                   class="input {{ $errors->has('db_database') ? 'error' : '' }}"
                   placeholder="artwork_portal">
            @error('db_database') <p class="err">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="db_username">Kullanıcı Adı</label>
            <input type="text" id="db_username" name="db_username"
                   value="{{ old('db_username', 'portal_user') }}"
                   class="input {{ $errors->has('db_username') ? 'error' : '' }}"
                   placeholder="portal_user">
            @error('db_username') <p class="err">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="db_password">Şifre</label>
            <input type="password" id="db_password" name="db_password"
                   class="input {{ $errors->has('db_password') ? 'error' : '' }}"
                   placeholder="••••••••">
            @error('db_password') <p class="err">{{ $message }}</p> @enderror
        </div>

        {{-- Opsiyonel: Veritabanını oluştur --}}
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="create_db" value="1"
                       class="mt-0.5 rounded border-slate-300 text-blue-600"
                       id="createDb"
                       {{ old('create_db') ? 'checked' : '' }}>
                <div>
                    <p class="text-sm font-medium text-slate-900">Veritabanı yoksa oluştur</p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Bu seçenek, root (veya yetkili) kullanıcı ile <code class="bg-slate-100 px-1 py-0.5 rounded text-xs">CREATE DATABASE</code> çalıştırır.
                        Docker test ortamında genelde gereklidir.
                    </p>
                </div>
            </label>

            <div id="rootFields" class="grid grid-cols-2 gap-4 {{ old('create_db') ? '' : 'hidden' }}">
                <div>
                    <label class="label" for="db_root_username">Root Kullanıcı</label>
                    <input type="text" id="db_root_username" name="db_root_username"
                           value="{{ old('db_root_username', 'root') }}"
                           class="input {{ $errors->has('db_root_username') ? 'error' : '' }}"
                           placeholder="root">
                    @error('db_root_username') <p class="err">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="db_root_password">Root Şifre</label>
                    <input type="password" id="db_root_password" name="db_root_password"
                           class="input {{ $errors->has('db_root_password') ? 'error' : '' }}"
                           placeholder="••••••••">
                    @error('db_root_password') <p class="err">{{ $message }}</p> @enderror
                    <p class="hint">Docker varsayılanı: <code class="bg-slate-100 px-1 py-0.5 rounded text-xs">rootsecret</code></p>
                </div>
            </div>
        </div>

        <div class="pt-2 flex justify-between">
            <a href="{{ route('setup.step', 1) }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Geri
            </a>
            <button type="submit" class="btn-primary" id="testBtn">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="testIcon">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span id="testLabel">Bağlantıyı Test Et ve Devam</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('createDb')?.addEventListener('change', function() {
    document.getElementById('rootFields')?.classList.toggle('hidden', !this.checked);
});
document.querySelector('form').addEventListener('submit', function() {
    const btn = document.getElementById('testBtn');
    const label = document.getElementById('testLabel');
    btn.disabled = true;
    label.textContent = 'Test ediliyor...';
});
</script>
@endpush
