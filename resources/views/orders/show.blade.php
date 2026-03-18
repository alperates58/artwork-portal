@extends('layouts.app')
@section('title', 'Sipariş ' . $order->order_no)
@section('page-title', 'Sipariş Detayı')

@section('header-actions')
    <a href="{{ route('orders.index') }}" class="btn-secondary">← Listeye Dön</a>
    @can('update', $order)
        <a href="{{ route('orders.edit', $order) }}" class="btn-secondary">Düzenle</a>
    @endcan
@endsection

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Order info --}}
    <div class="lg:col-span-1 space-y-4">
        <div class="card p-5 space-y-4">
            <div>
                <p class="text-xs text-slate-500 mb-0.5">Sipariş No</p>
                <p class="font-mono font-semibold text-slate-900 text-lg">{{ $order->order_no }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500 mb-0.5">Tedarikçi</p>
                <p class="text-sm font-medium text-slate-900">{{ $order->supplier->name }}</p>
                <p class="text-xs text-slate-500">{{ $order->supplier->code }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <p class="text-xs text-slate-500 mb-0.5">Sipariş Tarihi</p>
                    <p class="text-sm text-slate-900">{{ $order->order_date->format('d.m.Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 mb-0.5">Teslim Tarihi</p>
                    <p class="text-sm text-slate-900">{{ $order->due_date?->format('d.m.Y') ?? '—' }}</p>
                </div>
            </div>
            <div>
                <p class="text-xs text-slate-500 mb-0.5">Durum</p>
                @php
                    $cls = match($order->status) {
                        'active' => 'badge-success', 'draft' => 'badge-gray',
                        'completed' => 'badge-info',  'cancelled' => 'badge-danger', default => 'badge-gray'
                    };
                @endphp
                <span class="badge {{ $cls }}">{{ $order->status_label }}</span>
            </div>
            @if($order->notes)
                <div>
                    <p class="text-xs text-slate-500 mb-0.5">Notlar</p>
                    <p class="text-sm text-slate-700">{{ $order->notes }}</p>
                </div>
            @endif
            <div>
                <p class="text-xs text-slate-500 mb-0.5">Oluşturan</p>
                <p class="text-sm text-slate-700">{{ $order->createdBy->name }}</p>
                <p class="text-xs text-slate-400">{{ $order->created_at->format('d.m.Y H:i') }}</p>
            </div>
        </div>
    </div>

    {{-- Order lines --}}
    <div class="lg:col-span-2">
        <div class="card">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">Sipariş Satırları ({{ $order->lines->count() }})</h2>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($order->lines as $line)
                    <div class="px-5 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-600">{{ $line->line_no }}</span>
                                    <span class="text-sm font-medium text-slate-900">{{ $line->product_code }}</span>
                                    @php
                                        $artCls = match($line->artwork_status?->value ?? 'pending') {
                                            'uploaded' => 'badge-success', 'revision' => 'badge-danger',
                                            'approved' => 'badge-info',   default     => 'badge-warning',
                                        };
                                    @endphp
                                    <span class="badge {{ $artCls }}">{{ $line->artwork_status?->label() ?? 'Bekliyor' }}</span>
                                </div>
                                <p class="text-sm text-slate-600">{{ $line->description }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">{{ $line->quantity }} {{ $line->unit }}</p>
                            </div>

                            {{-- Artwork actions --}}
                            <div class="flex items-center gap-2 flex-shrink-0">
                                @if($line->hasActiveArtwork())
                                    <a href="{{ route('artwork.download', $line->activeRevision) }}"
                                       class="btn-secondary text-xs py-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        İndir Rev.{{ $line->activeRevision->revision_no }}
                                    </a>
                                    <a href="{{ route('artworks.revisions', $line) }}"
                                       class="text-xs text-blue-600 hover:underline">
                                        Revizyonlar
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">Artwork yok</span>
                                @endif

                                @if(auth()->user()->canUploadArtwork())
                                    <a href="{{ route('artworks.create', $line) }}" class="btn-primary text-xs py-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                        {{ $line->hasActiveArtwork() ? 'Yeni Revizyon' : 'Yükle' }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Active revision info --}}
                        @if($line->hasActiveArtwork())
                            <div class="mt-3 flex items-center gap-3 bg-slate-50 rounded-lg px-3 py-2">
                                <div class="w-7 h-7 bg-slate-200 rounded flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-slate-600">{{ $line->activeRevision->extension }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-slate-700 truncate">{{ $line->activeRevision->original_filename }}</p>
                                    <p class="text-xs text-slate-400">
                                        Rev.{{ $line->activeRevision->revision_no }} ·
                                        {{ $line->activeRevision->file_size_formatted }} ·
                                        {{ $line->activeRevision->uploadedBy->name }} ·
                                        {{ $line->activeRevision->created_at->format('d.m.Y H:i') }}
                                    </p>
                                </div>
                                <span class="badge badge-success text-xs">Güncel</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
