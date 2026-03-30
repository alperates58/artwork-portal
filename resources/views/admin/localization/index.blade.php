@extends('layouts.app')
@section('title', 'Dil Ayarları')
@section('page-title', 'Dil Ayarları')
@section('page-subtitle', 'Dilleri yönetin, çevirileri tek tek düzenleyin ve JSON ile içe/dışa aktarın.')

@section('header-actions')
    <a href="{{ route('admin.settings.edit', ['tab' => 'general']) }}" class="btn btn-secondary">Sistem Özeti</a>
@endsection

@section('content')
@php
    $query = array_filter([
        'q' => $filters['q'] ?? null,
        'group' => $filters['group'] ?? null,
        'status' => $filters['status'] ?? null,
    ], fn ($value) => filled($value));
@endphp

<div class="space-y-6">
    <div class="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
        <div class="space-y-6">
            <section class="card p-5 space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Dil Yönetimi</p>
                    <h2 class="mt-2 text-lg font-semibold text-slate-900">Yeni Dil Ekle</h2>
                    <p class="mt-1 text-sm text-slate-500">Türkçe varsayılan dil olarak kalır. Yeni dil açıldığında mevcut anahtar yapısı otomatik hazırlanır.</p>
                </div>

                <form method="POST" action="{{ route('admin.localization.languages.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="label">Dil Adı</label>
                        <input type="text" name="name" class="input" placeholder="English" required>
                    </div>
                    <div>
                        <label class="label">Dil Kodu</label>
                        <input type="text" name="code" class="input" placeholder="en" required>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Aktif olarak ekle
                    </label>
                    <button type="submit" class="btn btn-primary w-full">Dil Ekle</button>
                </form>
            </section>

            <section class="card p-5 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Aktif Diller</h2>
                        <p class="text-sm text-slate-500">Editör ekranı ve üst çubuk seçicisinde görünen diller.</p>
                    </div>
                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $languages->count() }} kayıt</span>
                </div>

                <div class="space-y-2">
                    @foreach($languages as $item)
                        <a href="{{ route('admin.localization.index', ['locale' => $item->code] + $query) }}"
                           class="flex items-start justify-between gap-3 rounded-2xl border px-4 py-3 transition {{ $language->is($item) ? 'border-brand-300 bg-brand-50/70' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $item->name }}</p>
                                <p class="text-xs text-slate-500">{{ strtoupper($item->code) }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                @if($item->is_default)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Varsayılan</span>
                                @endif
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $item->is_active ? 'bg-sky-50 text-sky-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $item->is_active ? 'Aktif' : 'Pasif' }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="card p-5 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">İçe / Dışa Aktar</h2>
                    <p class="text-sm text-slate-500">JSON formatıyla mevcut dili dışa aktarabilir veya güncel çevirileri toplu içe aktarabilirsiniz.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.localization.export', ['locale' => 'tr']) }}" class="btn btn-secondary">Türkçe Tabanı Dışa Aktar</a>
                    <a href="{{ route('admin.localization.export', ['locale' => $language->code]) }}" class="btn btn-secondary">{{ $language->name }} Dışa Aktar</a>
                </div>

                @if($language->code !== 'tr')
                    <form method="POST" action="{{ route('admin.localization.import') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div>
                            <label class="label">Hedef Dil Kodu</label>
                            <input type="text" name="locale" class="input" value="{{ $language->code }}" placeholder="en" required>
                        </div>
                        <div>
                            <label class="label">Dil Adı (yeni dil için)</label>
                            <input type="text" name="language_name" class="input" value="{{ $language->name }}" placeholder="English">
                        </div>
                        <div>
                            <label class="label">JSON Dosyası</label>
                            <input type="file" name="translation_file" class="input" accept=".json,.txt" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">JSON İçe Aktar</button>
                    </form>
                @endif
            </section>
        </div>

        <div class="space-y-6">
            <section class="card p-5 space-y-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Çeviri Editörü</p>
                        <h2 class="mt-2 text-lg font-semibold text-slate-900">{{ $language->name }} için alanlar</h2>
                        <p class="mt-1 text-sm text-slate-500">Solda Türkçe kaynak değer, sağda seçili dil için çevrilebilir alan bulunur.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs text-slate-400">Toplam anahtar</p>
                            <p class="mt-1 text-xl font-semibold text-slate-900">{{ $translations->total() }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs text-slate-400">Düzenlenen dil</p>
                            <p class="mt-1 text-xl font-semibold text-slate-900">{{ strtoupper($language->code) }}</p>
                        </div>
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.localization.index') }}" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_180px_180px_auto]">
                    <input type="hidden" name="locale" value="{{ $language->code }}">
                    <input type="text" name="q" value="{{ $filters['q'] }}" class="input" placeholder="Anahtar veya metin ara">
                    <select name="group" class="input">
                        <option value="">Tüm gruplar</option>
                        @foreach($groups as $group)
                            <option value="{{ $group }}" @selected(($filters['group'] ?? '') === $group)>{{ $group }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="input">
                        <option value="">Tüm durumlar</option>
                        <option value="missing" @selected(($filters['status'] ?? '') === 'missing')>Eksik çeviri</option>
                        <option value="translated" @selected(($filters['status'] ?? '') === 'translated')>Dolu çeviri</option>
                    </select>
                    <button type="submit" class="btn btn-secondary">Filtrele</button>
                </form>
            </section>

            <section class="card overflow-hidden">
                <form method="POST" action="{{ route('admin.localization.translations.update', ['locale' => $language->code]) }}">
                    @csrf
                    @method('PUT')

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Grup / Anahtar</th>
                                    <th class="px-4 py-3 font-semibold">Türkçe Kaynak</th>
                                    <th class="px-4 py-3 font-semibold">{{ $language->name }} Çevirisi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($translations as $translation)
                                    <tr class="align-top">
                                        <td class="px-4 py-4">
                                            <div class="space-y-1">
                                                <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">{{ $translation->group }}</span>
                                                <p class="font-mono text-xs text-slate-500">{{ $translation->key }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-slate-700">
                                            {{ $translation->base_value }}
                                        </td>
                                        <td class="px-4 py-4">
                                            @if($language->code === 'tr')
                                                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                                                    Türkçe kaynak dil doğrudan sistem tabanı olarak kullanılır.
                                                </div>
                                            @else
                                                <textarea name="translations[{{ $translation->key }}]" rows="2" class="input min-h-[88px]">{{ $translation->target_value }}</textarea>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-10 text-center text-slate-500">Bu filtre için çeviri kaydı bulunamadı.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col gap-4 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>{{ $translations->links() }}</div>
                        @if($language->code !== 'tr')
                            <button type="submit" class="btn btn-primary">Çevirileri Kaydet</button>
                        @endif
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>
@endsection
