@extends('setup.layout')

@section('content')
<div class="p-8">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Admin Kullanıcı</h1>
        <p class="text-sm text-slate-500 mt-1">Sisteme ilk girişi yapacak yönetici hesabını oluşturun.</p>
    </div>

    <form method="POST" action="{{ route('setup.save.admin') }}" class="space-y-5" id="finalForm">
        @csrf

        <div>
            <label class="label" for="admin_name">Ad Soyad</label>
            <input type="text" id="admin_name" name="admin_name"
                   value="{{ old('admin_name') }}"
                   class="input {{ $errors->has('admin_name') ? 'error' : '' }}"
                   placeholder="Ahmet Yılmaz"
                   autofocus>
            @error('admin_name') <p class="err">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="admin_email">E-posta</label>
            <input type="email" id="admin_email" name="admin_email"
                   value="{{ old('admin_email') }}"
                   class="input {{ $errors->has('admin_email') ? 'error' : '' }}"
                   placeholder="admin@sirketiniz.com">
            @error('admin_email') <p class="err">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="admin_password">Şifre</label>
            <input type="password" id="admin_password" name="admin_password"
                   class="input {{ $errors->has('admin_password') ? 'error' : '' }}"
                   placeholder="En az 8 karakter">
            @error('admin_password') <p class="err">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="admin_password_confirmation">Şifre Tekrar</label>
            <input type="password" id="admin_password_confirmation" name="admin_password_confirmation"
                   class="input"
                   placeholder="••••••••">
        </div>

        {{-- Migration seçeneği --}}
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="run_migrations" value="1"
                       class="mt-0.5 rounded border-slate-300 text-blue-600"
                       id="runMigrations" checked>
                <div>
                    <p class="text-sm font-medium text-slate-900">Tabloları otomatik oluştur</p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <code class="bg-slate-100 px-1 py-0.5 rounded text-xs">php artisan migrate</code> çalıştırılır.
                        Veritabanı boşsa işaretli bırakın.
                    </p>
                </div>
            </label>
        </div>

        {{-- Özet --}}
        <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-xl text-xs text-emerald-700 space-y-1">
            <p class="font-semibold text-emerald-800 mb-2">Kurulum özeti</p>
            @if(session('setup.site'))
                <p>✓ Site: <strong>{{ session('setup.site.app_name') }}</strong> — {{ session('setup.site.app_url') }}</p>
            @endif
            @if(session('setup.database'))
                <p>✓ Veritabanı: <strong>{{ session('setup.database.db_database') }}</strong> @ {{ session('setup.database.db_host') }}</p>
            @endif
            @if(session('setup.spaces'))
                <p>✓ Spaces: <strong>{{ session('setup.spaces.spaces_bucket') }}</strong> ({{ session('setup.spaces.spaces_region') }})</p>
            @endif
        </div>

        <div class="pt-2 flex justify-between">
            <a href="{{ route('setup.step', 3) }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Geri
            </a>
            <button type="submit" class="btn-primary bg-emerald-600 hover:bg-emerald-700" id="finalBtn">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="finalIcon">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span id="finalLabel">Kurulumu Tamamla</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('finalForm').addEventListener('submit', function() {
    const btn = document.getElementById('finalBtn');
    document.getElementById('finalLabel').textContent = 'Kuruluyor...';
    document.getElementById('finalIcon').innerHTML = `
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>`;
    btn.disabled = true;
});
</script>
@endpush
