@extends('layouts.app')
@section('title', 'Yetkiler')
@section('page-title', 'Kullanıcı Yetkileri')
@section('page-subtitle', 'Kullanıcı bazında ekran ve aksiyon erişimlerini yönetin.')

@section('content')
<div class="space-y-6">

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($users as $user)
            @php
                $isCustom = ! is_null($user->permissions);
                $screenCount = count($screens);
                $grantedCount = 0;
                foreach ($screens as $screenKey => $screen) {
                    foreach ($screen['actions'] as $actionKey => $actionLabel) {
                        if ($user->hasPermission($screenKey, $actionKey)) {
                            $grantedCount++;
                        }
                    }
                }
                $totalActions = collect($screens)->sum(fn($s) => count($s['actions']));
            @endphp
            <a href="{{ route('admin.permissions.show', $user) }}"
               class="group card border-slate-200/90 bg-white/95 p-5 transition hover:border-brand-200 hover:shadow-[0_16px_40px_rgba(244,154,11,0.08)]">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-brand-100 text-sm font-semibold text-brand-800">
                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                    </div>
                    @if($isCustom)
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-amber-700 border border-amber-200">
                            Özel
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            Varsayılan
                        </span>
                    @endif
                </div>
                <div class="mt-4">
                    <p class="text-sm font-semibold text-slate-900 group-hover:text-brand-700">{{ $user->name }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $user->role->label() }}</p>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between text-xs text-slate-500">
                        <span>Erişim</span>
                        <span class="font-semibold text-slate-700">{{ $grantedCount }} / {{ $totalActions }}</span>
                    </div>
                    <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-brand-500 transition-all"
                             style="width: {{ $totalActions > 0 ? round($grantedCount / $totalActions * 100) : 0 }}%"></div>
                    </div>
                </div>
            </a>
        @endforeach

        @if($users->isEmpty())
            <div class="col-span-4 rounded-[28px] border border-dashed border-slate-200 bg-white/60 px-6 py-12 text-center">
                <p class="text-sm text-slate-500">Yetki yönetimi yapılabilecek kullanıcı bulunamadı.</p>
                <p class="mt-1 text-xs text-slate-400">Satın Alma veya Grafik rolündeki kullanıcılar burada görünür.</p>
            </div>
        @endif
    </div>

    <div class="rounded-[28px] border border-slate-200/80 bg-white/60 p-5 sm:p-6">
        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Bilgi</p>
        <p class="mt-2 text-sm text-slate-600">
            <strong class="text-slate-800">Admin</strong> kullanıcılar tüm ekranlara tam erişime sahiptir ve bu sayfadan yönetilemez.
            <strong class="text-slate-800">Tedarikçi</strong> hesapları ise yalnızca kendi sipariş portallarına erişebilir.
        </p>
    </div>

</div>
@endsection
