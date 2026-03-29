@extends('layouts.app')
@section('title', 'Yetkiler')
@section('page-title', 'Kullanıcı Yetkileri')
@section('page-subtitle', 'Kullanıcı ve departman bazında ekran erişimlerini yönetin.')

@section('header-actions')
    <a href="{{ route('admin.departments.index') }}" class="btn btn-secondary">Departmanlar</a>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">+ Kullanıcı Ekle</a>
@endsection

@section('content')
@php
    $totalActions = collect($screens)->sum(fn($s) => count($s['actions']));
    $grouped = $users->groupBy(fn($u) => $u->department?->name ?? '__unassigned__');
    $depIds  = $departments->pluck('id', 'name');

    // Active department filter from query
    $filterDept = request('dept');
@endphp
<div class="space-y-6">

    {{-- Stats bar --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="card p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 mb-1">Toplam Kullanıcı</p>
            <p class="text-2xl font-bold text-slate-900">{{ $users->count() }}</p>
        </div>
        <div class="card p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 mb-1">Departmanlar</p>
            <p class="text-2xl font-bold text-slate-900">{{ $departments->count() }}</p>
        </div>
        <div class="card p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 mb-1">Özel Yetki</p>
            <p class="text-2xl font-bold text-amber-600">{{ $users->whereNotNull('permissions')->count() }}</p>
        </div>
        <div class="card p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 mb-1">Departmansız</p>
            <p class="text-2xl font-bold text-red-500">{{ $users->whereNull('department_id')->count() }}</p>
        </div>
    </div>

    {{-- Department filter tabs --}}
    <div class="flex flex-wrap gap-2 items-center">
        <a href="{{ route('admin.permissions.index') }}"
           class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold transition
                  {{ !$filterDept ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:border-brand-300 hover:text-brand-700' }}">
            Tümü
            <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ !$filterDept ? 'bg-white/25' : 'bg-slate-100' }}">{{ $users->count() }}</span>
        </a>
        @foreach($departments as $dept)
            @php $deptCount = $grouped[$dept->name]?->count() ?? 0; @endphp
            @if($deptCount > 0)
                <a href="{{ route('admin.permissions.index', ['dept' => $dept->name]) }}"
                   class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold transition
                          {{ $filterDept === $dept->name ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:border-brand-300 hover:text-brand-700' }}">
                    {{ $dept->name }}
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ $filterDept === $dept->name ? 'bg-white/25' : 'bg-slate-100' }}">{{ $deptCount }}</span>
                </a>
            @endif
        @endforeach
        @if(isset($grouped['__unassigned__']))
            <a href="{{ route('admin.permissions.index', ['dept' => '__unassigned__']) }}"
               class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold transition
                      {{ $filterDept === '__unassigned__' ? 'bg-red-500 text-white shadow-sm' : 'bg-white text-red-500 border border-red-200 hover:bg-red-50' }}">
                Departmansız
                <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ $filterDept === '__unassigned__' ? 'bg-white/25' : 'bg-red-50' }}">{{ $grouped['__unassigned__']->count() }}</span>
            </a>
        @endif
    </div>

    {{-- User cards --}}
    @php
        $displayUsers = $filterDept
            ? ($grouped[$filterDept] ?? collect())
            : $users;
        $displayGrouped = $filterDept
            ? collect([$filterDept => $displayUsers])
            : $grouped;
    @endphp

    @if($displayUsers->isEmpty())
        <div class="card px-6 py-12 text-center">
            <p class="text-sm text-slate-500">Bu filtrede kullanıcı bulunamadı.</p>
        </div>
    @else
        @foreach($displayGrouped as $deptName => $deptUsers)
            @php
                $dept = $deptName === '__unassigned__' ? null : $departments->firstWhere('name', $deptName);
                $headerLabel = $deptName === '__unassigned__' ? 'Departmansız Kullanıcılar' : $deptName;
            @endphp
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <h3 class="text-xs font-bold uppercase tracking-widest {{ $deptName === '__unassigned__' ? 'text-red-500' : 'text-slate-500' }}">{{ $headerLabel }}</h3>
                    <div class="flex-1 border-t border-slate-200"></div>
                    @if($dept)
                        <a href="{{ route('admin.departments.edit', $dept) }}"
                           class="text-[11px] text-brand-600 hover:underline">Departman yetkilerini düzenle →</a>
                    @endif
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    @foreach($deptUsers->sortBy('name') as $user)
                        @php
                            $isCustom = !is_null($user->permissions);
                            $grantedCount = 0;
                            foreach ($screens as $sk => $sc) {
                                foreach ($sc['actions'] as $ak => $al) {
                                    if ($user->hasPermission($sk, $ak)) $grantedCount++;
                                }
                            }
                            $pct = $totalActions > 0 ? round($grantedCount / $totalActions * 100) : 0;
                            $barColor = $pct >= 70 ? 'bg-emerald-500' : ($pct >= 35 ? 'bg-brand-500' : 'bg-amber-400');
                        @endphp
                        <a href="{{ route('admin.permissions.show', $user) }}"
                           class="group card border-slate-200/80 bg-white p-4 transition hover:border-brand-300 hover:shadow-md flex flex-col gap-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-100 to-brand-200 text-sm font-bold text-brand-800">
                                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-900 group-hover:text-brand-700 truncate">{{ $user->name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $user->role->label() }}</p>
                                    </div>
                                </div>
                                @if($isCustom)
                                    <span class="flex-shrink-0 rounded-full bg-amber-50 border border-amber-200 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Özel</span>
                                @elseif($user->department?->permissions)
                                    <span class="flex-shrink-0 rounded-full bg-blue-50 border border-blue-200 px-2 py-0.5 text-[10px] font-semibold text-blue-600">Dept.</span>
                                @else
                                    <span class="flex-shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">Varsayılan</span>
                                @endif
                            </div>

                            {{-- Active status indicator --}}
                            @if(!$user->is_active)
                                <div class="rounded-lg bg-red-50 border border-red-100 px-2.5 py-1 text-[11px] text-red-600 font-medium">Pasif hesap</div>
                            @endif

                            {{-- Permission bar --}}
                            <div>
                                <div class="flex items-center justify-between text-[11px] text-slate-500 mb-1">
                                    <span>Erişim izinleri</span>
                                    <span class="font-semibold text-slate-700">{{ $grantedCount }}/{{ $totalActions }}</span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full {{ $barColor }} transition-all" style="width:{{ $pct }}%"></div>
                                </div>
                            </div>

                            {{-- Screen badges --}}
                            <div class="flex flex-wrap gap-1">
                                @foreach($screens as $sk => $sc)
                                    @php $hasAny = collect($sc['actions'])->keys()->contains(fn($ak) => $user->hasPermission($sk, $ak)); @endphp
                                    <span class="rounded px-1.5 py-0.5 text-[10px] font-medium {{ $hasAny ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-400 line-through' }}">
                                        {{ $sc['label'] }}
                                    </span>
                                @endforeach
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif

    {{-- Info box --}}
    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-5">
        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 mb-2">Yetki Öncelik Sırası</p>
        <div class="flex flex-wrap gap-3 text-xs text-slate-600">
            <span class="flex items-center gap-1.5">
                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-[10px] font-bold text-amber-700">1</span>
                Kullanıcıya özel yetkiler (en yüksek öncelik)
            </span>
            <span class="text-slate-300">→</span>
            <span class="flex items-center gap-1.5">
                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-[10px] font-bold text-blue-700">2</span>
                Departman yetkileri
            </span>
            <span class="text-slate-300">→</span>
            <span class="flex items-center gap-1.5">
                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-600">3</span>
                Rol varsayılanları (en düşük öncelik)
            </span>
        </div>
        <p class="mt-3 text-xs text-slate-500">
            <strong class="text-slate-700">Admin</strong> tam erişime sahip, bu sayfadan yönetilemez. &nbsp;|&nbsp;
            <strong class="text-slate-700">Tedarikçi</strong> hesapları yalnızca kendi sipariş portalına erişebilir.
        </p>
    </div>

</div>
@endsection
