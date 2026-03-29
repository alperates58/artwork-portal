@extends('layouts.app')
@section('title', 'Veri Aktarımı')
@section('page-title', 'Veri Aktarımı')
@section('page-subtitle', 'Hangi verilerin ve hangi alanların taşınacağını seçin. Aynı içerik tekrar dışa aktarılmaz.')

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

<div class="grid gap-6 xl:grid-cols-[1.35fr_.95fr]">
    <form method="POST" action="{{ route('admin.data-transfer.export') }}" class="card p-6">
        @csrf

        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Dışa Aktarım Paketi</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Her bölüm için hangi alanların gideceğini seçin. İçeriği değişmemiş kayıtlar tekrar pakete eklenmez.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-right text-xs text-slate-500">
                <p class="font-semibold text-slate-700">Son dışa aktarım</p>
                <p>{{ $last_export_at?->format('d.m.Y H:i') ?? 'Henüz aktarım yok' }}</p>
            </div>
        </div>

        <div class="mb-6 grid gap-4 md:grid-cols-2">
            <label class="flex items-start gap-3 rounded-2xl border border-brand-100 bg-brand-50/70 px-4 py-3">
                <input type="hidden" name="only_new" value="0">
                <input type="checkbox" name="only_new" value="1" class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500" checked>
                <span>
                    <span class="block text-sm font-semibold text-slate-800">Sadece yeni veya değişmiş kayıtları dahil et</span>
                    <span class="mt-1 block text-xs text-slate-500">Aynı alan setiyle daha önce gönderilmiş ve içeriği değişmemiş kayıtlar atlanır.</span>
                </span>
            </label>

            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <input type="checkbox" name="include_media" value="1" class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span>
                    <span class="block text-sm font-semibold text-slate-800">Medya dosyalarını da pakete ekle</span>
                    <span class="mt-1 block text-xs text-slate-500">Artwork galerisi ve revizyonlar için dosya içeriği Base64 olarak XML içine yazılır. Paket boyutu büyürse sistem sizi bölüm bölüm aktarım yapmanız için uyarır.</span>
                </span>
            </label>
        </div>

        <div class="space-y-4">
            @foreach($sections as $section)
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <label class="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    data-section-toggle="{{ $section['key'] }}"
                                    class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                    {{ $section['default'] ? 'checked' : '' }}
                                >
                                <span class="text-sm font-semibold text-slate-900">{{ $section['label'] }}</span>
                            </label>
                            <p class="mt-2 text-xs text-slate-500">{{ $section['description'] }}</p>
                        </div>

                        <div class="flex gap-2 text-xs">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">Toplam: {{ $section['stats']['total'] }}</span>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-amber-700">İzlenen: {{ $section['stats']['tracked'] }}</span>
                            @if($section['supports_media'])
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-blue-700">Medya destekli</span>
                            @endif
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($section['fields'] as $fieldKey => $fieldLabel)
                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    name="fields[{{ $section['key'] }}][]"
                                    value="{{ $fieldKey }}"
                                    data-section-field="{{ $section['key'] }}"
                                    class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                    {{ $section['default'] ? 'checked' : '' }}
                                >
                                <span>{{ $fieldLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
            <p class="text-xs text-slate-500">
                Siparişler dışa aktarılırken tekrar kontrolü tedarikçi + sipariş no bileşimiyle yapılır. Revizyon ve galeri kayıtlarında içerik özeti de hesaba katılır.
            </p>

            <button type="submit" class="btn btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                XML Paketini Oluştur
            </button>
        </div>
    </form>

    <div class="space-y-6">
        <div class="card p-6">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-slate-900">İçe Aktar (XML)</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Aynı içerik ikinci kez işlenmez. Sipariş kontrolü tedarikçi + sipariş no birleşimi üzerinden yapılır.
                </p>
            </div>

            <div class="mb-5 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-800">
                Medya taşıyan galeri ve revizyon kayıtlarının import edilebilmesi için XML paketi oluşturulurken “Medya dosyalarını da pakete ekle” seçeneğinin işaretlenmiş olması gerekir.
            </div>

            <form method="POST" action="{{ route('admin.data-transfer.import') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="label">XML Dosyası</label>
                    <input type="file" name="xml_file" accept=".xml,application/xml,text/xml" class="input" required>
                </div>

                <button type="submit" class="btn btn-secondary w-full justify-center border-blue-200 text-blue-700 hover:bg-blue-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/>
                    </svg>
                    İçe Aktarmayı Başlat
                </button>
            </form>
        </div>

        <div class="card p-6">
            <h2 class="mb-4 text-base font-semibold text-slate-900">İçe Aktarılan Kayıtlar</h2>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                    <div class="text-slate-500">Tedarikçiler</div>
                    <div class="mt-1 text-xl font-semibold text-slate-900">{{ $imported_count['suppliers'] }}</div>
                </div>
                <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                    <div class="text-slate-500">Kullanıcılar</div>
                    <div class="mt-1 text-xl font-semibold text-slate-900">{{ $imported_count['users'] }}</div>
                </div>
                <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                    <div class="text-slate-500">Siparişler</div>
                    <div class="mt-1 text-xl font-semibold text-slate-900">{{ $imported_count['purchase_orders'] }}</div>
                </div>
                <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                    <div class="text-slate-500">Galeri kayıtları</div>
                    <div class="mt-1 text-xl font-semibold text-slate-900">{{ $imported_count['artwork_gallery'] }}</div>
                </div>
                <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm sm:col-span-2">
                    <div class="text-slate-500">Artwork revizyonları</div>
                    <div class="mt-1 text-xl font-semibold text-slate-900">{{ $imported_count['artwork_revisions'] }}</div>
                </div>
            </div>

            @php
                $hasImported = collect($imported_count)->sum() > 0;
            @endphp

            @if($hasImported)
                <form method="POST" action="{{ route('admin.data-transfer.destroy-imported') }}" class="mt-5" onsubmit="return confirm('İçe aktarılan tüm kayıtlar silinecek. Emin misiniz?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-secondary border-red-200 text-red-600 hover:bg-red-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        İçe Aktarılanları Sil
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-section-toggle]').forEach((toggle) => {
        const section = toggle.dataset.sectionToggle;
        const syncSection = () => {
            document.querySelectorAll(`[data-section-field="${section}"]`).forEach((field) => {
                field.checked = toggle.checked;
            });
        };

        toggle.addEventListener('change', syncSection);
    });
});
</script>
@endpush
