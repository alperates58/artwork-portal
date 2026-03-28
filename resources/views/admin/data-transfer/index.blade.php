@extends('layouts.app')
@section('title', 'Veri Aktarımı')
@section('page-title', 'Veri Aktarımı')
@section('page-subtitle', 'Demo içerik yükleyin veya mevcut veriyi XML olarak dışa aktarın.')

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <svg class="h-4 w-4 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <ul class="space-y-0.5">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-6 lg:grid-cols-2">

    {{-- Export --}}
    <div class="card p-6">
        <div class="mb-4 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Dışa Aktar (XML)</h2>
                <p class="text-xs text-slate-500">Mevcut verileri bilgisayarınıza indirin</p>
            </div>
        </div>

        <p class="mb-5 text-sm text-slate-600">
            Sistemdeki tüm tedarikçiler, admin olmayan kullanıcılar ve siparişler (satır detaylarıyla birlikte) bir XML dosyasına aktarılır.
            Bu dosyayı başka bir kuruluma import edebilirsiniz.
        </p>

        <div class="mb-5 rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500 space-y-1">
            <p class="font-semibold text-slate-700 mb-1.5">Dışa aktarılacak veriler:</p>
            <p>• Tedarikçiler (kod, iletişim, durum, notlar, oluşturma tarihi)</p>
            <p>• Tedarikçi–kullanıcı bağlantıları (başlık, yetkiler)</p>
            <p>• Admin olmayan tüm kullanıcılar (rol, departman, iletişim)</p>
            <p>• Siparişler ve satır detayları (oluşturma tarihi dahil)</p>
        </div>

        <a href="{{ route('admin.data-transfer.export') }}"
           class="btn btn-primary w-full justify-center">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            XML Olarak İndir
        </a>
    </div>

    {{-- Import --}}
    <div class="card p-6">
        <div class="mb-4 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-slate-800">İçe Aktar (XML)</h2>
                <p class="text-xs text-slate-500">Daha önce dışa aktarılan XML dosyasını yükleyin</p>
            </div>
        </div>

        <p class="mb-5 text-sm text-slate-600">
            Dışa aktarılan XML dosyasını seçin. Zaten mevcut olan kayıtlar (aynı kod/e-posta/sipariş no) atlanır.
            İçe aktarılan kullanıcılara varsayılan şifre: <code class="rounded bg-slate-100 px-1 py-0.5 text-xs font-mono">Import@{{ now()->year }}</code>
        </p>

        <form method="POST" action="{{ route('admin.data-transfer.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="label">XML Dosyası</label>
                <input type="file"
                       name="xml_file"
                       accept=".xml,application/xml,text/xml"
                       class="input @error('xml_file') error @endif"
                       required>
                @error('xml_file')
                    <p class="err">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn btn-secondary w-full justify-center border-blue-200 text-blue-700 hover:bg-blue-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/>
                </svg>
                İçe Aktarmayı Başlat
            </button>
        </form>
    </div>

</div>

{{-- Imported data management --}}
@php
    $hasImported = ($importedCount['suppliers'] + $importedCount['users'] + $importedCount['purchase_orders']) > 0;
@endphp

@if($hasImported)
<div class="mt-6 card p-6 border-red-100">
    <div class="mb-4 flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-red-50 text-red-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </div>
        <div>
            <h2 class="text-sm font-semibold text-slate-800">İçe Aktarılan Verileri Sil</h2>
            <p class="text-xs text-slate-500">Yalnızca bu kuruluma import edilmiş kayıtlar silinir</p>
        </div>
    </div>

    <div class="mb-5 flex flex-wrap gap-3">
        <div class="flex items-center gap-2 rounded-xl bg-red-50 px-3 py-2 text-sm">
            <span class="font-semibold text-red-700">{{ $importedCount['suppliers'] }}</span>
            <span class="text-slate-600">tedarikçi</span>
        </div>
        <div class="flex items-center gap-2 rounded-xl bg-red-50 px-3 py-2 text-sm">
            <span class="font-semibold text-red-700">{{ $importedCount['users'] }}</span>
            <span class="text-slate-600">kullanıcı</span>
        </div>
        <div class="flex items-center gap-2 rounded-xl bg-red-50 px-3 py-2 text-sm">
            <span class="font-semibold text-red-700">{{ $importedCount['purchase_orders'] }}</span>
            <span class="text-slate-600">sipariş</span>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.data-transfer.destroy-imported') }}"
          onsubmit="return confirm('İçe aktarılan tüm veriler kalıcı olarak silinecek. Emin misiniz?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-secondary border-red-200 text-red-600 hover:bg-red-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            İçe Aktarılanları Sil
        </button>
    </form>
</div>
@endif

@endsection
