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

    {{-- ── Log tablosu ─────────────────────────────────────────────────────── --}}
    <div class="card overflow-x-auto">
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
    };
}
</script>
@endpush
