@extends('layouts.app')
@section('title', 'Toplu Stok Kartı İçe Aktar')
@section('page-title', 'Toplu Stok Kartı İçe Aktar')

@section('header-actions')
    <a href="{{ route('admin.stock-cards.index') }}" class="btn btn-secondary">← Listeye Dön</a>
@endsection

@section('content')
<div class="max-w-4xl space-y-6">
    <div class="card flex items-start gap-4 p-5">
        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-amber-50">
            <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-medium text-slate-800">Excel Şablonu</p>
            <p class="mt-0.5 text-xs text-slate-500">
                Zorunlu sütunlar:
                <span class="font-medium text-slate-700">Stok Kodu</span>,
                <span class="font-medium text-slate-700">Stok Adı</span>,
                <span class="font-medium text-slate-700">Kategori</span>.
            </p>
        </div>
        <a href="{{ route('admin.stock-cards.import.template') }}" class="btn btn-secondary text-xs">Şablonu İndir</a>
    </div>

    <div class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Örnek Şablon</h3>
                <p class="mt-1 text-xs text-slate-500">Excel dosyanızda ilk satır başlık, alt satırlar veri olacak şekilde aşağıdaki yapıyı kullanabilirsiniz.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-600">3 zorunlu sütun</span>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-left">
                        <th class="px-4 py-3 font-semibold text-slate-700">Stok Kodu</th>
                        <th class="px-4 py-3 font-semibold text-slate-700">Stok Adı</th>
                        <th class="px-4 py-3 font-semibold text-slate-700">Kategori</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr class="bg-white">
                        <td class="px-4 py-3 font-mono text-slate-700">STK-1001</td>
                        <td class="px-4 py-3 text-slate-600">Lider Kutu 350 gr</td>
                        <td class="px-4 py-3 text-slate-600">Kutu</td>
                    </tr>
                    <tr class="bg-slate-50/60">
                        <td class="px-4 py-3 font-mono text-slate-700">STK-1002</td>
                        <td class="px-4 py-3 text-slate-600">Lider Etiket 90x120</td>
                        <td class="px-4 py-3 text-slate-600">Etiket</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if(session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="card space-y-4 p-5">
            <h3 class="font-semibold text-slate-800">İçe Aktarma Sonucu</h3>
            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-lg border border-green-100 bg-green-50 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-green-700">{{ $result['imported'] }}</p>
                    <p class="mt-0.5 text-xs text-green-600">Eklendi</p>
                </div>
                <div class="rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-amber-700">{{ $result['skipped'] }}</p>
                    <p class="mt-0.5 text-xs text-amber-600">Atlandı</p>
                </div>
                <div class="rounded-lg border {{ $result['error_count'] > 0 ? 'border-red-100 bg-red-50' : 'border-slate-100 bg-slate-50' }} px-4 py-3 text-center">
                    <p class="text-2xl font-bold {{ $result['error_count'] > 0 ? 'text-red-700' : 'text-slate-400' }}">{{ $result['error_count'] }}</p>
                    <p class="mt-0.5 text-xs {{ $result['error_count'] > 0 ? 'text-red-600' : 'text-slate-400' }}">Hata</p>
                </div>
            </div>

            @if(count($result['errors']) > 0)
                <div>
                    <p class="mb-2 text-xs font-medium text-slate-600">Satır Bazlı Hata Raporu</p>
                    <div class="overflow-hidden rounded-lg border border-red-100">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b border-red-100 bg-red-50">
                                    <th class="w-16 px-3 py-2 text-left font-medium text-red-700">Satır</th>
                                    <th class="w-36 px-3 py-2 text-left font-medium text-red-700">Stok Kodu</th>
                                    <th class="px-3 py-2 text-left font-medium text-red-700">Hata</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-red-50">
                                @foreach($result['errors'] as $error)
                                    <tr class="bg-white">
                                        <td class="px-3 py-2 text-slate-500">{{ $error['row'] }}</td>
                                        <td class="px-3 py-2 font-mono text-slate-700">{{ $error['code'] }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ $error['message'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="card p-6">
        <h3 class="mb-4 font-semibold text-slate-800">Excel Dosyası Yükle</h3>

        <form method="POST" action="{{ route('admin.stock-cards.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Excel Dosyası <span class="text-red-500">*</span></label>
                <input type="file" name="file" accept=".xlsx,.xls" class="block w-full rounded-lg border border-slate-200 text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100 @error('file') border-red-400 @enderror">
                @error('file')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-slate-400">Maksimum 5 MB · .xlsx veya .xls</p>
            </div>

            <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                <p class="font-medium text-slate-700">Kurallar</p>
                <ul class="mt-1 list-disc list-inside space-y-0.5 text-slate-500">
                    <li>Stok kodu, stok adı ve kategori zorunludur.</li>
                    <li>Mevcut stok kodları atlanır ve sonuçta gösterilir.</li>
                    <li>Aynı kategori tekrar girilirse mevcut kategori yeniden kullanılır.</li>
                    <li>Dosya içindeki tekrar eden stok kodları reddedilir.</li>
                </ul>
            </div>

            <button type="submit" class="btn btn-primary">İçe Aktarmayı Başlat</button>
        </form>
    </div>
</div>
@endsection
