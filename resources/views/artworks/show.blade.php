@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold">Artwork Revizyonu</h1>
        <p class="text-sm text-slate-600">
            <span class="font-mono">{{ $revision->artwork->orderLine->product_code }}</span> · Rev.{{ $revision->revision_no }} · {{ $revision->original_filename }}
        </p>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <dl class="grid gap-4 md:grid-cols-2">
            <div>
                <dt class="text-sm text-slate-500">Stok Kodu</dt>
                <dd class="font-mono font-medium">{{ $revision->artwork->orderLine->product_code }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Revizyon</dt>
                <dd class="font-medium">Rev.{{ $revision->revision_no }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Sipariş</dt>
                <dd class="font-medium">{{ $revision->artwork->orderLine->purchaseOrder->order_no }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Tedarikçi</dt>
                <dd class="font-medium">{{ $revision->artwork->orderLine->purchaseOrder->supplier->name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Yükleyen</dt>
                <dd class="font-medium">{{ $revision->uploadedBy?->name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Dosya Boyutu</dt>
                <dd class="font-medium">{{ $revision->file_size_formatted }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Önizleme PNG</dt>
                <dd class="font-medium">{{ $revision->has_preview ? ($revision->preview_original_filename ?: 'Var') : 'Yok' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Orijinal Dosya</dt>
                <dd class="font-medium">{{ $revision->original_filename }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-sm text-slate-500">Notlar</dt>
                <dd class="font-medium">{{ $revision->notes ?: 'Not yok.' }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
