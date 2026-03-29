@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
    $activeOrderLines = (int) ($metrics['active_order_lines'] ?? 0);
    $uploadedArtwork = (int) ($metrics['uploaded_artwork'] ?? 0);
    $pendingArtwork = (int) ($metrics['pending_artwork'] ?? 0);
    $pendingApproval = (int) ($metrics['pending_approval'] ?? 0);
    $stalledPending = (int) ($metrics['stalled_pending_artwork'] ?? 0);
    $blockedOrders = (int) ($metrics['blocked_orders'] ?? 0);

    $uploadCompletionPct = (float) ($metrics['upload_completion_pct'] ?? 0);
    $flowPressurePct = (float) ($metrics['flow_pressure_pct'] ?? 0);
    $approvalCompletionPct = (float) ($metrics['approval_completion_pct'] ?? 0);

    $uploadedPctForChart = max(0.0, min(100.0, $uploadCompletionPct));
    $pendingPctForChart = max(0.0, 100.0 - $uploadedPctForChart);

    $approvalQueuePct = $activeOrderLines > 0 ? round(($pendingApproval / $activeOrderLines) * 100, 1) : 0.0;
    $stalledQueuePct = $activeOrderLines > 0 ? round(($stalledPending / $activeOrderLines) * 100, 1) : 0.0;

    $alarm = match (true) {
        $flowPressurePct >= 55 || $blockedOrders >= 10 => [
            'title' => 'Kritik seviye',
            'desc' => 'Bekleyen yük kritik eşiği geçmiş durumda. İş akışında tıkanma var.',
            'class' => 'bg-red-50 text-red-700 border-red-200',
            'dot' => 'bg-red-500',
        ],
        $flowPressurePct >= 35 || $blockedOrders >= 4 => [
            'title' => 'Dikkat seviyesi',
            'desc' => 'Yük artıyor. Yakın takip edilmezse darboğaz büyüyebilir.',
            'class' => 'bg-amber-50 text-amber-700 border-amber-200',
            'dot' => 'bg-amber-500',
        ],
        default => [
            'title' => 'Kontrol altında',
            'desc' => 'Mevcut yük dengeli. Süreç normal akışta ilerliyor.',
            'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'dot' => 'bg-emerald-500',
        ],
    };
@endphp

<div class="grid grid-cols-2 gap-4 xl:grid-cols-4 mb-6">
    <x-ui.card padding="p-5">
        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Takip Edilen Sipariş</p>
        <a href="{{ route('orders.index') }}" class="text-2xl font-semibold text-slate-900 hover:text-brand-700 transition-colors">
            {{ number_format($metrics['tracked_orders'] ?? 0) }}
        </a>
        <p class="mt-1 text-xs text-slate-500">İptal dışı tüm sipariş</p>
    </x-ui.card>

    <x-ui.card padding="p-5">
        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Toplam Sipariş Satırı</p>
        <a href="{{ route('orders.index') }}" class="text-2xl font-semibold text-slate-900 hover:text-brand-700 transition-colors">
            {{ number_format($activeOrderLines) }}
        </a>
        <p class="mt-1 text-xs text-slate-500">İptal dışı siparişlerin toplam satırı</p>
    </x-ui.card>

    <x-ui.card padding="p-5">
        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Artwork Yüklenen Satır</p>
        <a href="{{ route('orders.index', ['artwork_status' => 'uploaded']) }}" class="text-2xl font-semibold text-slate-900 hover:text-brand-700 transition-colors">
            {{ number_format($uploadedArtwork) }}
        </a>
        <p class="mt-1 text-xs text-emerald-600">Tamamlanma: {{ number_format($uploadCompletionPct, 1, ',', '.') }}%</p>
    </x-ui.card>

    <x-ui.card padding="p-5">
        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Henüz Artwork Bekleyen</p>
        <a href="{{ route('orders.index', ['artwork_status' => 'pending']) }}" class="text-2xl font-semibold text-slate-900 hover:text-brand-700 transition-colors">
            {{ number_format($pendingArtwork) }}
        </a>
        <p class="mt-1 text-xs text-amber-600">Basınç: {{ number_format($flowPressurePct, 1, ',', '.') }}%</p>
    </x-ui.card>
</div>

<div class="grid grid-cols-1 gap-5 xl:grid-cols-2 mb-6">
    <x-ui.card padding="p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">İş Akışı Görünümü</p>
                <h2 class="mt-2 text-lg font-semibold text-slate-900">Toplam satıra göre yükleme ilerlemesi</h2>
                <p class="mt-1 text-xs text-slate-500">Bu grafik, aktif sipariş satırlarının ne kadarının artwork yükleme adımını geçtiğini doğrudan gösterir.</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold {{ $alarm['class'] }}">
                <span class="h-2 w-2 rounded-full {{ $alarm['dot'] }}"></span>
                {{ $alarm['title'] }}
            </span>
        </div>

        <div class="mt-5 grid grid-cols-1 items-center gap-6 sm:grid-cols-[220px_minmax(0,1fr)]">
            <div class="mx-auto">
                <div class="relative h-44 w-44 rounded-full" style="background: conic-gradient(#10b981 0% {{ $uploadedPctForChart }}%, #f59e0b {{ $uploadedPctForChart }}% 100%);">
                    <div class="absolute inset-4 rounded-full border border-slate-100 bg-white flex flex-col items-center justify-center text-center">
                        <p class="text-[11px] text-slate-400">Yükleme</p>
                        <p class="text-2xl font-semibold text-slate-900">{{ number_format($uploadCompletionPct, 1, ',', '.') }}%</p>
                        <p class="text-[11px] text-slate-400">tamamlandı</p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-400">Yüklenen satır</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-700">{{ number_format($uploadedArtwork) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-400">Bekleyen satır</p>
                    <p class="mt-1 text-lg font-semibold text-amber-700">{{ number_format($pendingArtwork) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-400">Toplam sipariş satırı</p>
                    <p class="mt-1 text-lg font-semibold text-slate-800">{{ number_format($activeOrderLines) }}</p>
                </div>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card padding="p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Alarm Grafiği</p>
        <h2 class="mt-2 text-lg font-semibold text-slate-900">İşler nerede sıkışıyor?</h2>
        <p class="mt-1 text-xs text-slate-500">{{ $alarm['desc'] }}</p>

        <div class="mt-5 space-y-4">
            <div>
                <div class="mb-1 flex items-center justify-between text-xs">
                    <span class="text-slate-500">Bekleyen oranı</span>
                    <span class="font-semibold text-slate-700">{{ number_format($flowPressurePct, 1, ',', '.') }}%</span>
                </div>
                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full bg-amber-500" style="width: {{ max(0, min(100, $flowPressurePct)) }}%"></div>
                </div>
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between text-xs">
                    <span class="text-slate-500">Onay kuyruğu oranı</span>
                    <span class="font-semibold text-slate-700">{{ number_format($approvalQueuePct, 1, ',', '.') }}%</span>
                </div>
                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full bg-indigo-500" style="width: {{ max(0, min(100, $approvalQueuePct)) }}%"></div>
                </div>
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between text-xs">
                    <span class="text-slate-500">7+ gün bekleyen oranı</span>
                    <span class="font-semibold text-slate-700">{{ number_format($stalledQueuePct, 1, ',', '.') }}%</span>
                </div>
                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full bg-red-500" style="width: {{ max(0, min(100, $stalledQueuePct)) }}%"></div>
                </div>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3 text-xs">
            <div class="rounded-xl bg-slate-50 px-3 py-2">
                <p class="text-slate-400">Darboğaz sipariş</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($blockedOrders) }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-3 py-2">
                <p class="text-slate-400">7+ gün bekleyen satır</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($stalledPending) }}</p>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('orders.index', ['status' => 'active', 'artwork_status' => 'pending']) }}" class="btn btn-secondary text-xs">Bekleyen siparişleri aç</a>
            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('reports', 'view'))
                <a href="{{ route('admin.reports.pending') }}" class="btn btn-secondary text-xs">Bekleyen artwork raporu</a>
            @endif
        </div>
    </x-ui.card>
</div>
@endsection
