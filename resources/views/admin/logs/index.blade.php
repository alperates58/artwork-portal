@extends('layouts.app')
@section('title', 'Sistem Logları')
@section('page-title', 'Sistem Logları')
@section('page-subtitle', 'Kullanıcı aktiviteleri ve sistem olayları')

@php
use App\Http\Controllers\Admin\AuditLogController;

$categoryMeta = [
    'session' => ['label' => 'Oturum',  'color' => 'slate',   'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
    'artwork' => ['label' => 'Artwork', 'color' => 'violet',  'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
    'gallery' => ['label' => 'Galeri',  'color' => 'blue',    'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
    'order'   => ['label' => 'Sipariş', 'color' => 'amber',   'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
    'mail'    => ['label' => 'Mail',    'color' => 'emerald', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
    'erp'     => ['label' => 'ERP',     'color' => 'rose',    'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
];

$actionColorMap = [
    'user.login'                     => ['bg' => 'bg-slate-100',    'text' => 'text-slate-700'],
    'user.logout'                    => ['bg' => 'bg-slate-100',    'text' => 'text-slate-500'],
    'artwork.upload'                 => ['bg' => 'bg-violet-100',   'text' => 'text-violet-700'],
    'artwork.download'               => ['bg' => 'bg-blue-100',     'text' => 'text-blue-700'],
    'artwork.approved'               => ['bg' => 'bg-emerald-100',  'text' => 'text-emerald-700'],
    'artwork.rejected'               => ['bg' => 'bg-orange-100',   'text' => 'text-orange-700'],
    'artwork.delete'                 => ['bg' => 'bg-red-100',      'text' => 'text-red-700'],
    'artwork.gallery.create'         => ['bg' => 'bg-blue-100',     'text' => 'text-blue-700'],
    'artwork.gallery.delete'         => ['bg' => 'bg-red-100',      'text' => 'text-red-700'],
    'order.create'                   => ['bg' => 'bg-amber-100',    'text' => 'text-amber-700'],
    'order.delete'                   => ['bg' => 'bg-red-100',      'text' => 'text-red-700'],
    'mail.notification.failed'       => ['bg' => 'bg-red-100',      'text' => 'text-red-700'],
    'mail.notification.queue_failed' => ['bg' => 'bg-red-100',      'text' => 'text-red-700'],
    'mikro.test.failed'              => ['bg' => 'bg-red-100',      'text' => 'text-red-700'],
    'mikro.test.success'             => ['bg' => 'bg-emerald-100',  'text' => 'text-emerald-700'],
];

// Bir action'ın hangi kategoride olduğunu bul
$actionCategory = [];
foreach (AuditLogController::CATEGORIES as $cat => $actions) {
    foreach ($actions as $a) { $actionCategory[$a] = $cat; }
}
@endphp

@section('content')
<div class="space-y-5" x-data="logsPage()">

    {{-- ── Kategori özet kartları ─────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
        @foreach($categoryMeta as $catKey => $meta)
        @php $count = $categoryCounts[$catKey] ?? 0; @endphp
        <a href="{{ route('admin.logs.index', array_merge(request()->except('category','action','page'), ['category' => $catKey])) }}"
           class="card px-4 py-3 flex flex-col gap-1 hover:shadow-md transition-shadow
                  {{ $selectedCategory === $catKey ? 'ring-2 ring-brand-500' : '' }}">
            <div class="flex items-center justify-between">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-{{ $meta['color'] }}-100">
                    <svg class="h-4 w-4 text-{{ $meta['color'] }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $meta['icon'] }}"/>
                    </svg>
                </span>
                @if($selectedCategory === $catKey)
                    <span class="text-[10px] font-semibold text-brand-600 bg-brand-50 rounded-full px-2 py-0.5">Aktif</span>
                @endif
            </div>
            <p class="text-xs font-medium text-slate-500 mt-1">{{ $meta['label'] }}</p>
            <p class="text-lg font-bold text-slate-900 leading-none">{{ number_format($count) }}</p>
        </a>
        @endforeach
    </div>

    {{-- ── Seçili kullanıcı özet kartı ─────────────────────────────────── --}}
    @if($selectedUser && $userStats)
    <div class="card p-5">
        <div class="flex flex-wrap items-start gap-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white"
                     style="background: linear-gradient(135deg,#8b5cf6,#6d28d9)">
                    {{ strtoupper(mb_substr($selectedUser->name, 0, 2)) }}
                </div>
                <div>
                    <p class="font-semibold text-slate-900">{{ $selectedUser->name }}</p>
                    <p class="text-xs text-slate-500">{{ $selectedUser->role?->label() }} · {{ $userStats->sum() }} işlem</p>
                </div>
            </div>
            <a href="{{ route('admin.logs.index', array_merge(request()->except('user_id','page'), [])) }}"
               class="text-xs text-slate-400 hover:text-slate-700 flex-shrink-0">Kullanıcı filtresini kaldır ✕</a>
        </div>

        {{-- Kullanıcı aktivite özeti --}}
        @if($userStats->count())
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2">
            @foreach($userStats->take(8) as $action => $cnt)
            @php
                $label = AuditLogController::ACTION_LABELS[$action] ?? $action;
                $cat   = $actionCategory[$action] ?? 'session';
                $meta2 = $categoryMeta[$cat] ?? $categoryMeta['session'];
            @endphp
            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 flex items-center gap-2">
                <span class="inline-flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-md bg-{{ $meta2['color'] }}-100">
                    <svg class="h-3.5 w-3.5 text-{{ $meta2['color'] }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $meta2['icon'] }}"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[11px] text-slate-500 truncate">{{ $label }}</p>
                    <p class="text-sm font-bold text-slate-900 leading-none">{{ $cnt }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    {{-- ── Filtreler ───────────────────────────────────────────────────────── --}}
    <form method="GET" class="card p-4">
        <div class="flex flex-wrap gap-3 items-end">

            {{-- Kullanıcı combobox --}}
            <div class="w-full sm:w-56">
                <label class="label">Kullanıcı</label>
                <select name="user_id" class="input" onchange="this.form.submit()">
                    <option value="">Tüm kullanıcılar</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ $selectedUserId == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->role?->label() }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Kategori --}}
            <div class="w-full sm:w-44">
                <label class="label">Kategori</label>
                <select name="category" class="input" x-model="selectedCat" @change="selectedAction = ''">
                    <option value="">Tüm kategoriler</option>
                    @foreach(AuditLogController::CATEGORY_LABELS as $key => $label)
                        <option value="{{ $key }}" {{ $selectedCategory === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- İşlem (kategoriye göre filtreli) --}}
            <div class="w-full sm:w-56">
                <label class="label">İşlem Türü</label>
                <select name="action" class="input">
                    <option value="">Tüm işlemler</option>
                    @foreach(AuditLogController::CATEGORIES as $catKey => $actions)
                        @php $catLabel = AuditLogController::CATEGORY_LABELS[$catKey]; @endphp
                        <optgroup label="{{ $catLabel }}">
                            @foreach($actions as $a)
                                <option value="{{ $a }}"
                                        {{ $selectedAction === $a ? 'selected' : '' }}>
                                    {{ AuditLogController::ACTION_LABELS[$a] ?? $a }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            {{-- Tarih aralığı --}}
            <div class="w-full sm:w-36">
                <label class="label">Başlangıç</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="input">
            </div>
            <div class="w-full sm:w-36">
                <label class="label">Bitiş</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="input">
            </div>

            <div class="flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Filtrele</button>
                @if($selectedUserId || $selectedCategory || $selectedAction || $dateFrom || $dateTo)
                    <a href="{{ route('admin.logs.index') }}" class="btn btn-secondary">Temizle</a>
                @endif
            </div>
        </div>
    </form>

    {{-- ── Görünüm sekme seçici ─────────────────────────────────────────────── --}}
    <div class="flex gap-2 border-b border-slate-200 -mb-px">
        <button @click="activeTab = 'table'"
                :class="activeTab === 'table' ? 'border-brand-500 text-brand-700 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"/>
            </svg>
            Log Tablosu
        </button>
        <button @click="activeTab = 'timeline'"
                :class="activeTab === 'timeline' ? 'border-brand-500 text-brand-700 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Zaman Çizelgesi
        </button>
    </div>

    {{-- ── Zaman çizelgesi paneli ────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'timeline'" x-cloak class="space-y-4">

        {{-- Arama kutusu --}}
        <div class="card p-4">
            <div class="flex flex-wrap gap-3 items-end">

                {{-- Arama türü --}}
                <div class="w-full sm:w-44">
                    <label class="label">Arama Türü</label>
                    <select x-model="tlSearchType" @change="tlSearchValue = ''; tlResults = []; tlMeta = null" class="input">
                        <option value="order_no">Sipariş No</option>
                        <option value="stock_code">Stok Kodu</option>
                        <option value="supplier_id">Tedarikçi</option>
                    </select>
                </div>

                {{-- Sipariş No --}}
                <div x-show="tlSearchType === 'order_no'" class="w-full sm:w-64">
                    <label class="label">Sipariş No</label>
                    <input list="tl-order-nos" type="text" x-model="tlSearchValue"
                           @input.debounce.400ms="fetchTimeline()"
                           placeholder="Sipariş numarası girin…" class="input">
                    <datalist id="tl-order-nos">
                        @foreach($orderNumbers as $no)
                            <option value="{{ $no }}">
                        @endforeach
                    </datalist>
                </div>

                {{-- Stok Kodu --}}
                <div x-show="tlSearchType === 'stock_code'" class="w-full sm:w-64">
                    <label class="label">Stok Kodu</label>
                    <input list="tl-stock-codes" type="text" x-model="tlSearchValue"
                           @input.debounce.400ms="fetchTimeline()"
                           placeholder="Stok kodu girin…" class="input">
                    <datalist id="tl-stock-codes">
                        @foreach($stockCodes as $code)
                            <option value="{{ $code }}">
                        @endforeach
                    </datalist>
                </div>

                {{-- Tedarikçi --}}
                <div x-show="tlSearchType === 'supplier_id'" class="w-full sm:w-64">
                    <label class="label">Tedarikçi</label>
                    <select x-model="tlSearchValue" @change="fetchTimeline()" class="input">
                        <option value="">Tedarikçi seçin…</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}{{ $s->code ? ' (' . $s->code . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Yükleniyor / temizle --}}
                <div class="flex items-center gap-2">
                    <template x-if="tlLoading">
                        <svg class="h-5 w-5 animate-spin text-brand-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </template>
                    <template x-if="tlResults.length > 0">
                        <button @click="tlSearchValue=''; tlResults=[]; tlMeta=null" class="btn btn-secondary text-xs">Temizle</button>
                    </template>
                </div>
            </div>

            {{-- Meta bilgi bandı --}}
            <template x-if="tlMeta">
                <div class="mt-3 flex flex-wrap items-center gap-3 rounded-xl bg-slate-50 px-4 py-2.5 border border-slate-200">
                    <span class="text-xs text-slate-500">
                        <span x-text="tlMeta.type" class="font-semibold text-slate-700"></span>:
                        <span x-text="tlMeta.value" class="font-mono text-brand-700"></span>
                    </span>
                    <template x-if="tlMeta.order_count">
                        <span class="text-xs text-slate-500">·
                            <span x-text="tlMeta.order_count" class="font-bold text-slate-700"></span> sipariş
                        </span>
                    </template>
                    <span class="ml-auto text-xs font-semibold text-slate-700">
                        <span x-text="tlResults.length"></span> log kaydı
                    </span>
                </div>
            </template>
        </div>

        {{-- Boş durum --}}
        <template x-if="!tlLoading && tlResults.length === 0 && tlSearchValue !== ''">
            <div class="card py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="mt-3 text-sm text-slate-400">Bu arama için log kaydı bulunamadı.</p>
            </div>
        </template>

        {{-- Başlangıç ipucu --}}
        <template x-if="!tlLoading && tlSearchValue === ''">
            <div class="card py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="mt-3 text-sm text-slate-400">Sipariş No, Stok Kodu veya Tedarikçi seçerek<br>aktivite geçmişini görüntüleyin.</p>
            </div>
        </template>

        {{-- Zaman çizelgesi --}}
        <template x-if="tlResults.length > 0">
            <div class="space-y-0">
                <template x-for="(group, gi) in tlGrouped" :key="gi">
                    <div>
                        {{-- Gün başlığı --}}
                        <div class="sticky top-0 z-10 flex items-center gap-3 py-2 bg-slate-50/95 backdrop-blur-sm">
                            <span class="text-xs font-semibold text-slate-500 whitespace-nowrap" x-text="group.day"></span>
                            <div class="flex-1 h-px bg-slate-200"></div>
                            <span class="text-[11px] text-slate-400 whitespace-nowrap" x-text="group.items.length + ' kayıt'"></span>
                        </div>

                        {{-- Olaylar --}}
                        <div class="relative ml-3 border-l-2 border-slate-200 pl-6 pb-2 space-y-3">
                            <template x-for="(log, li) in group.items" :key="log.id">
                                <div class="relative">
                                    {{-- Zaman çizelgesi noktası --}}
                                    <span class="absolute -left-[25px] top-3 h-3 w-3 rounded-full border-2 border-white shadow-sm"
                                          :class="{
                                            'bg-emerald-500': log.color === 'emerald',
                                            'bg-red-500':     log.color === 'red',
                                            'bg-violet-500':  log.color === 'violet',
                                            'bg-blue-500':    log.color === 'blue',
                                            'bg-amber-500':   log.color === 'amber',
                                            'bg-orange-500':  log.color === 'orange',
                                            'bg-rose-500':    log.color === 'rose',
                                            'bg-slate-400':   !['emerald','red','violet','blue','amber','orange','rose'].includes(log.color),
                                          }"></span>

                                    {{-- Kart --}}
                                    <div class="rounded-xl border border-slate-100 bg-white px-4 py-3 shadow-sm hover:shadow-md transition-shadow">
                                        <div class="flex flex-wrap items-start gap-2">

                                            {{-- Kullanıcı avatarı --}}
                                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-[11px] font-bold text-white"
                                                 style="background: linear-gradient(135deg,#8b5cf6,#6d28d9)"
                                                 x-text="log.user_initials"></div>

                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    {{-- İşlem badge --}}
                                                    <span class="inline-flex items-center rounded-lg px-2.5 py-0.5 text-[11px] font-semibold"
                                                          :class="{
                                                            'bg-emerald-100 text-emerald-700': log.color === 'emerald',
                                                            'bg-red-100 text-red-700':         log.color === 'red',
                                                            'bg-violet-100 text-violet-700':   log.color === 'violet',
                                                            'bg-blue-100 text-blue-700':       log.color === 'blue',
                                                            'bg-amber-100 text-amber-700':     log.color === 'amber',
                                                            'bg-orange-100 text-orange-700':   log.color === 'orange',
                                                            'bg-rose-100 text-rose-700':       log.color === 'rose',
                                                            'bg-slate-100 text-slate-600':     !['emerald','red','violet','blue','amber','orange','rose'].includes(log.color),
                                                          }"
                                                          x-text="log.action_label"></span>
                                                    {{-- Kullanıcı adı --}}
                                                    <span class="text-xs font-medium text-slate-700" x-text="log.user_name"></span>
                                                    <span class="text-[11px] text-slate-400" x-text="log.user_role"></span>
                                                </div>

                                                {{-- Detaylar --}}
                                                <template x-if="log.details.length > 0">
                                                    <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-0.5">
                                                        <template x-for="d in log.details" :key="d.key">
                                                            <span class="text-xs text-slate-500">
                                                                <span class="font-medium text-slate-600" x-text="d.key + ':'"></span>
                                                                <span x-text="d.value"></span>
                                                            </span>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>

                                            {{-- Saat + IP --}}
                                            <div class="flex-shrink-0 text-right">
                                                <p class="text-xs font-mono font-semibold text-slate-700" x-text="log.time"></p>
                                                <p class="text-[10px] font-mono text-slate-400" x-text="log.ip || '—'"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>

    </div>

    {{-- ── Log tablosu ─────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'table'" class="card overflow-x-auto">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">
                @if($selectedCategory)
                    {{ AuditLogController::CATEGORY_LABELS[$selectedCategory] ?? '' }} Logları
                @elseif($selectedUser)
                    {{ $selectedUser->name }} — Aktiviteler
                @else
                    Tüm Loglar
                @endif
            </h3>
            <span class="text-xs text-slate-400">Sayfa başına 50 kayıt</span>
        </div>

        <table class="w-full text-sm" style="min-width:680px">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-left">
                    <th class="px-4 py-3 font-medium text-slate-600 whitespace-nowrap">Zaman</th>
                    <th class="px-4 py-3 font-medium text-slate-600 whitespace-nowrap">Kullanıcı</th>
                    <th class="px-4 py-3 font-medium text-slate-600 whitespace-nowrap">Kategori</th>
                    <th class="px-4 py-3 font-medium text-slate-600 whitespace-nowrap">İşlem</th>
                    <th class="px-4 py-3 font-medium text-slate-600">Detay</th>
                    <th class="px-4 py-3 font-medium text-slate-600 whitespace-nowrap">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($logs as $log)
                @php
                    $cat       = $actionCategory[$log->action] ?? null;
                    $catMeta   = $cat ? $categoryMeta[$cat] : null;
                    $actionLbl = AuditLogController::ACTION_LABELS[$log->action] ?? $log->action;
                    $badgeCls  = $actionColorMap[$log->action] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-600'];

                    // Payload'ı insanca formatla
                    $payloadParts = [];
                    if ($log->payload) {
                        $labelMap = [
                            'order_no'        => 'Sipariş',
                            'order_id'        => 'Sipariş ID',
                            'filename'        => 'Dosya',
                            'file'            => 'Dosya',
                            'product_code'    => 'Ürün',
                            'description'     => 'Açıklama',
                            'supplier'        => 'Tedarikçi',
                            'supplier_name'   => 'Tedarikçi',
                            'line_no'         => 'Satır',
                            'status'          => 'Durum',
                            'revision'        => 'Revizyon',
                            'note'            => 'Not',
                            'subject'         => 'Konu',
                            'to'              => 'Alıcı',
                            'error'           => 'Hata',
                            'result'          => 'Sonuç',
                        ];
                        foreach ($log->payload as $k => $v) {
                            if (is_scalar($v) && $v !== '' && $v !== null) {
                                $k2 = $labelMap[$k] ?? $k;
                                $payloadParts[] = "<span class='font-medium text-slate-600'>$k2:</span> " . e($v);
                            }
                        }
                    }
                @endphp
                <tr class="hover:bg-slate-50/70 transition-colors">
                    {{-- Zaman --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <p class="text-xs font-medium text-slate-700">{{ $log->created_at->format('d.m.Y') }}</p>
                        <p class="text-[11px] text-slate-400">{{ $log->created_at->format('H:i:s') }}</p>
                    </td>

                    {{-- Kullanıcı --}}
                    <td class="px-4 py-3">
                        @if($log->user)
                            <a href="{{ route('admin.logs.index', array_merge(request()->except('user_id','page'), ['user_id' => $log->user_id])) }}"
                               class="group flex items-center gap-2">
                                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg text-[11px] font-bold text-white"
                                     style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
                                    {{ strtoupper(mb_substr($log->user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-900 group-hover:text-brand-700 leading-tight">{{ $log->user->name }}</p>
                                    <p class="text-[10px] text-slate-400 leading-tight">{{ $log->user->role?->label() }}</p>
                                </div>
                            </a>
                        @else
                            <span class="text-xs text-slate-400 italic">Silinmiş kullanıcı</span>
                        @endif
                    </td>

                    {{-- Kategori badge --}}
                    <td class="px-4 py-3">
                        @if($catMeta)
                        <a href="{{ route('admin.logs.index', array_merge(request()->except('category','action','page'), ['category' => $cat])) }}"
                           class="inline-flex items-center gap-1.5 rounded-lg bg-{{ $catMeta['color'] }}-50 px-2.5 py-1 hover:bg-{{ $catMeta['color'] }}-100 transition-colors">
                            <svg class="h-3 w-3 text-{{ $catMeta['color'] }}-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $catMeta['icon'] }}"/>
                            </svg>
                            <span class="text-[11px] font-semibold text-{{ $catMeta['color'] }}-700">{{ $catMeta['label'] }}</span>
                        </a>
                        @endif
                    </td>

                    {{-- İşlem --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-[11px] font-semibold {{ $badgeCls['bg'] }} {{ $badgeCls['text'] }}">
                            {{ $actionLbl }}
                        </span>
                    </td>

                    {{-- Detay --}}
                    <td class="px-4 py-3 text-xs text-slate-500 max-w-xs">
                        @if($payloadParts)
                            <div class="flex flex-wrap gap-x-3 gap-y-0.5">
                                @foreach($payloadParts as $part)
                                    <span class="whitespace-nowrap">{!! $part !!}</span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>

                    {{-- IP --}}
                    <td class="px-4 py-3 text-[11px] font-mono text-slate-400 whitespace-nowrap">
                        {{ $log->ip_address ?? '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center">
                        <p class="text-slate-400">Log kaydı bulunamadı.</p>
                        @if($selectedCategory || $selectedAction || $selectedUserId || $dateFrom || $dateTo)
                            <a href="{{ route('admin.logs.index') }}" class="mt-2 inline-block text-xs text-brand-600 hover:underline">Filtreleri temizle</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">{{ $logs->links() }}</div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
function logsPage() {
    return {
        selectedCat: '{{ $selectedCategory }}',
        selectedAction: '{{ $selectedAction }}',

        // Tab
        activeTab: 'table',

        // Timeline
        tlSearchType:  'order_no',
        tlSearchValue: '',
        tlLoading:     false,
        tlResults:     [],
        tlMeta:        null,

        get tlGrouped() {
            const groups = {};
            for (const log of this.tlResults) {
                if (!groups[log.day_group]) groups[log.day_group] = { day: log.day_group, items: [] };
                groups[log.day_group].items.push(log);
            }
            return Object.values(groups);
        },

        async fetchTimeline() {
            const val = this.tlSearchValue.trim();
            if (!val) { this.tlResults = []; this.tlMeta = null; return; }

            this.tlLoading = true;
            try {
                const params = new URLSearchParams({ search_type: this.tlSearchType, search_value: val });
                const res = await fetch('{{ route('admin.logs.timeline') }}?' + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                this.tlResults = data.logs  ?? [];
                this.tlMeta    = data.meta  ?? null;
            } catch (e) {
                console.error('Timeline fetch error:', e);
                this.tlResults = [];
                this.tlMeta    = null;
            } finally {
                this.tlLoading = false;
            }
        },
    };
}
</script>
@endpush
