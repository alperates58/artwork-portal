@extends('layouts.app')
@section('title', $user->name . ' — Yetkiler')
@section('page-title', $user->name)
@section('page-subtitle', 'Ekran ve aksiyon bazında erişim yetkilerini düzenleyin.')

@section('header-actions')
    <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">
        ← Tüm Kullanıcılar
    </a>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Kullanıcı bilgi kartı --}}
    <div class="flex flex-wrap items-center gap-4 rounded-[28px] border border-slate-200/80 bg-white/95 p-5 shadow-[0_8px_24px_rgba(15,23,42,0.05)]">
        <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-brand-100 text-lg font-semibold text-brand-800">
            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
        </div>
        <div class="flex-1">
            <p class="text-base font-semibold text-slate-900">{{ $user->name }}</p>
            <p class="text-sm text-slate-500">{{ $user->email }} · {{ $user->role->label() }}</p>
        </div>
        <div class="flex items-center gap-3">
            @if($isCustom)
                <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                    Özel Yetkiler Aktif
                </span>
                <form method="POST" action="{{ route('admin.permissions.reset', $user) }}">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('Yetkiler role varsayılanına sıfırlanacak. Devam edilsin mi?')"
                            class="btn btn-secondary text-xs">
                        Varsayılana Sıfırla
                    </button>
                </form>
            @else
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">
                    Role Varsayılanı
                </span>
            @endif
        </div>
    </div>

    {{-- Yetki formu --}}
    <form method="POST" action="{{ route('admin.permissions.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="space-y-4">
            @foreach($screens as $screenKey => $screen)
                @php
                    $screenPerms = $effectivePermissions[$screenKey] ?? [];
                @endphp
                <div class="card overflow-hidden border-slate-200/90 bg-white/95">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                        <h3 class="text-sm font-semibold text-slate-900">{{ $screen['label'] }}</h3>
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            {{ collect($screen['actions'])->keys()->filter(fn($a) => ($screenPerms[$a] ?? false))->count() }}
                            / {{ count($screen['actions']) }} aktif
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-3 px-5 py-4">
                        @foreach($screen['actions'] as $actionKey => $actionLabel)
                            @php $checked = ($screenPerms[$actionKey] ?? false) === true; @endphp
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border px-4 py-2.5 text-sm transition select-none
                                          {{ $checked
                                              ? 'border-brand-200 bg-brand-50 text-brand-800'
                                              : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300 hover:bg-white' }}">
                                <input type="checkbox"
                                       name="permissions[{{ $screenKey }}][{{ $actionKey }}]"
                                       value="1"
                                       {{ $checked ? 'checked' : '' }}
                                       class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 focus:ring-offset-0">
                                {{ $actionLabel }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="btn btn-primary px-8">
                Yetkileri Kaydet
            </button>
        </div>
    </form>

</div>
@endsection
