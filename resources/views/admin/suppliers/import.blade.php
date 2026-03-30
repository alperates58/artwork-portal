@extends('layouts.app')
@section('title', 'Toplu Tedarikçi İçe Aktar')
@section('page-title', 'Toplu Tedarikçi İçe Aktar')

@section('header-actions')
    <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">← Listeye Dön</a>
@endsection

@section('content')
<div class="max-w-2xl space-y-6">

    {{-- Şablon indirme --}}
    <div class="card p-5 flex items-start gap-4">
        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
            <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-medium text-slate-800 text-sm">Excel Şablonu</p>
            <p class="text-xs text-slate-500 mt-0.5">
                Zorunlu sütunlar: <span class="font-medium text-slate-700">Tedarikçi Kodu</span>,
                <span class="font-medium text-slate-700">Tedarikçi Adı</span>.
                Opsiyonel: Email, Telefon, Adres, Notlar, Aktif.
            </p>
        </div>
        <a href="{{ route('admin.suppliers.import.template') }}"
           class="btn btn-secondary flex-shrink-0 text-xs">
            Şablonu İndir
        </a>
    </div>

    {{-- Hata mesajı --}}
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- Import sonucu --}}
    @if(session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="card p-5 space-y-4">
            <h3 class="font-semibold text-slate-800">İçe Aktarma Sonucu</h3>

            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-lg bg-green-50 border border-green-100 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-green-700">{{ $result['imported'] }}</p>
                    <p class="text-xs text-green-600 mt-0.5">Eklendi</p>
                </div>
                <div class="rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-amber-700">{{ $result['skipped'] }}</p>
                    <p class="text-xs text-amber-600 mt-0.5">Atlandı (mevcut)</p>
                </div>
                <div class="rounded-lg {{ $result['error_count'] > 0 ? 'bg-red-50 border-red-100' : 'bg-slate-50 border-slate-100' }} border px-4 py-3 text-center">
                    <p class="text-2xl font-bold {{ $result['error_count'] > 0 ? 'text-red-700' : 'text-slate-400' }}">{{ $result['error_count'] }}</p>
                    <p class="text-xs {{ $result['error_count'] > 0 ? 'text-red-600' : 'text-slate-400' }} mt-0.5">Hata</p>
                </div>
            </div>

            @if(count($result['errors']) > 0)
                <div>
                    <p class="text-xs font-medium text-slate-600 mb-2">Satır Bazlı Hata Raporu</p>
                    <div class="rounded-lg border border-red-100 overflow-hidden">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="bg-red-50 border-b border-red-100">
                                    <th class="text-left px-3 py-2 font-medium text-red-700 w-14">Satır</th>
                                    <th class="text-left px-3 py-2 font-medium text-red-700 w-32">Kod</th>
                                    <th class="text-left px-3 py-2 font-medium text-red-700">Hata</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-red-50">
                                @foreach($result['errors'] as $err)
                                    <tr class="bg-white hover:bg-red-50/50">
                                        <td class="px-3 py-2 text-slate-500">{{ $err['row'] }}</td>
                                        <td class="px-3 py-2 font-mono text-slate-700">{{ $err['code'] }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ $err['message'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if($result['imported'] > 0)
                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-primary text-sm">
                    Tedarikçi Listesine Git →
                </a>
            @endif
        </div>
    @endif

    {{-- Upload formu --}}
    <div class="card p-6">
        <h3 class="font-semibold text-slate-800 mb-4">Excel Dosyası Yükle</h3>

        <form method="POST" action="{{ route('admin.suppliers.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Excel Dosyası <span class="text-red-500">*</span>
                </label>
                <input type="file" name="file" accept=".xlsx,.xls"
                    class="block w-full text-sm text-slate-600
                           file:mr-4 file:py-2 file:px-4
                           file:rounded-lg file:border-0
                           file:text-sm file:font-medium
                           file:bg-brand-50 file:text-brand-700
                           hover:file:bg-brand-100
                           border border-slate-200 rounded-lg
                           @error('file') border-red-400 @enderror">
                @error('file')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-slate-400">Maksimum 5 MB · .xlsx veya .xls</p>
            </div>

            <div class="bg-slate-50 rounded-lg p-3 text-xs text-slate-600 space-y-1">
                <p class="font-medium text-slate-700">Kurallar</p>
                <ul class="list-disc list-inside space-y-0.5 text-slate-500">
                    <li>Tedarikçi Kodu ve Tedarikçi Adı zorunludur.</li>
                    <li>Mevcut kodlar atlanır, hata listesinde gösterilir.</li>
                    <li>Dosya içinde tekrar eden kodlar reddedilir.</li>
                    <li>Boş satırlar otomatik atlanır.</li>
                    <li>Aktif sütunu boş bırakılırsa tedarikçi aktif olarak eklenir.</li>
                </ul>
            </div>

            <button type="submit" class="btn btn-primary">
                İçe Aktarmayı Başlat
            </button>
        </form>
    </div>

</div>
@endsection
