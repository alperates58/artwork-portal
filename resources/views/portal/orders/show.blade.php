@extends('layouts.app')
@section('title', 'Sipariş ' . $order->order_no)
@section('page-title', $order->order_no)

@section('header-actions')
    <a href="{{ route('portal.orders.index') }}" class="btn-secondary">← Siparişlerime Dön</a>
@endsection

@section('content')

<div class="max-w-3xl">

    {{-- Order summary --}}
    <div class="card p-4 mb-5 flex items-center gap-4">
        <div class="flex-1">
            <p class="text-xs text-slate-500 mb-0.5">Sipariş</p>
            <p class="font-mono font-semibold text-slate-900">{{ $order->order_no }}</p>
        </div>
        <div class="text-center">
            <p class="text-xs text-slate-500 mb-0.5">Tarih</p>
            <p class="text-sm text-slate-700">{{ $order->order_date->format('d.m.Y') }}</p>
        </div>
        <div class="text-center">
            <p class="text-xs text-slate-500 mb-0.5">Toplam Satır</p>
            <p class="text-sm text-slate-700">{{ $order->lines->count() }}</p>
        </div>
        <div class="text-center">
            <p class="text-xs text-slate-500 mb-0.5">Hazır</p>
            <p class="text-sm font-semibold text-emerald-600">
                {{ $order->lines->filter->hasActiveArtwork()->count() }} / {{ $order->lines->count() }}
            </p>
        </div>
    </div>

    {{-- Artwork lines --}}
    <div class="space-y-3">
        @foreach($order->lines as $line)
            <div class="card">
                <div class="p-5">
                    {{-- Line header --}}
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="text-xs font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-600">{{ $line->line_no }}</span>
                                <span class="text-sm font-semibold text-slate-900">{{ $line->product_code }}</span>
                            </div>
                            <p class="text-sm text-slate-600">{{ $line->description }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $line->quantity }} {{ $line->unit }}</p>
                        </div>
                    </div>

                    {{-- Active artwork --}}
                    @if($line->hasActiveArtwork())
                        @php $rev = $line->activeRevision; @endphp
                        <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    {{-- File type badge --}}
                                    <div class="w-12 h-12 bg-white rounded-lg border border-emerald-200
                                                flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-slate-700">{{ $rev->extension }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $rev->original_filename }}</p>
                                        <p class="text-xs text-slate-500">
                                            Rev.{{ $rev->revision_no }} ·
                                            {{ $rev->file_size_formatted }} ·
                                            {{ $rev->created_at->format('d.m.Y') }}
                                        </p>
                                        <div class="flex items-center gap-1 mt-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span class="text-xs text-emerald-600 font-medium">Güncel & Aktif</span>
                                        </div>
                                    </div>
                                </div>

                                <a href="{{ route('portal.download', $rev) }}"
                                   class="flex-shrink-0 inline-flex items-center gap-2 px-5 py-2.5
                                          bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium
                                          rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    İndir
                                </a>
                                <form method="POST" action="{{ route('approval.seen', $rev) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium
                                                   text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                                        Gördüm
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('approval.approve', $rev) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium
                                                   text-emerald-700 bg-emerald-100 hover:bg-emerald-200 rounded-lg transition-colors">
                                        Onayladım
                                    </button>
                                </form>
                            </div>

                            @if($rev->notes)
                                <div class="mt-3 pt-3 border-t border-emerald-200">
                                    <p class="text-xs text-slate-600">
                                        <span class="font-medium">Not:</span> {{ $rev->notes }}
                                    </p>
                                </div>
                            @endif
                        </div>

                    @else
                        {{-- No artwork yet --}}
                        <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 flex items-center gap-3">
                            <svg class="w-8 h-8 text-amber-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-amber-800">Artwork henüz hazır değil</p>
                                <p class="text-xs text-amber-600">Grafik departmanı dosyayı yükledikten sonra buradan indirebilirsiniz.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
