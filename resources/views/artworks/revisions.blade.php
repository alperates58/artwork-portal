@extends('layouts.app')
@section('title', 'Revizyon Geçmişi')
@section('page-title', 'Revizyon Geçmişi')
@section('header-actions')
    <a href="{{ route('orders.show', $line->purchaseOrder) }}" class="btn-secondary">← Siparişe Dön</a>
    @if(auth()->user()->canUploadArtwork())
        <a href="{{ route('artworks.create', $line) }}" class="btn-primary">Yeni Revizyon</a>
    @endif
@endsection

@section('content')
<div class="max-w-3xl">
    {{-- Context --}}
    <div class="card p-4 mb-5">
        <div class="flex items-center gap-3">
            <div>
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="text-xs font-mono bg-slate-100 px-1.5 py-0.5 rounded">{{ $line->line_no }}</span>
                    <span class="text-sm font-semibold text-slate-900">{{ $line->product_code }}</span>
                </div>
                <p class="text-xs text-slate-500">
                    {{ $line->purchaseOrder->order_no }} · {{ $line->purchaseOrder->supplier->name }}
                </p>
            </div>
        </div>
    </div>

    @if($line->artwork && $line->artwork->revisions->isNotEmpty())
        <div class="card">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">
                    {{ $line->artwork->revisions->count() }} Revizyon
                </h2>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach($line->artwork->revisions as $rev)
                    <div class="px-5 py-4 flex items-start gap-4">
                        <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-bold text-slate-600">{{ $rev->extension }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="text-sm font-semibold text-slate-900">Rev.{{ $rev->revision_no }}</span>
                                @if($rev->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-gray">Arşiv</span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-700 truncate">{{ $rev->original_filename }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ $rev->file_size_formatted }} ·
                                @if(!auth()->user()->isSupplier())
                                    <a href="{{ route('profile.edit') }}" class="hover:text-violet-600 hover:underline">{{ $rev->uploadedBy->name }}</a>
                                @else
                                    {{ $rev->uploadedBy->name }}
                                @endif
                                · {{ $rev->created_at->format('d.m.Y H:i') }}
                            </p>
                            @if($rev->notes)
                                <p class="text-xs text-slate-500 mt-1 italic">{{ $rev->notes }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <a href="{{ route('artwork.download', $rev) }}" class="btn btn-secondary text-xs py-1.5">İndir</a>
                            @if(!$rev->is_active && auth()->user()->canUploadArtwork())
                                <form method="POST" action="{{ route('artworks.activate', $rev) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-secondary text-xs py-1.5 text-emerald-600">
                                        Aktif Yap
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('artworks.destroy', $rev) }}"
                                      onsubmit="return confirm('Rev.{{ $rev->revision_no }} silinsin mi? Bu işlem geri alınamaz.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-secondary text-xs py-1.5 text-red-600 hover:border-red-300 hover:bg-red-50">
                                        Sil
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="card p-10 text-center text-slate-400">
            Bu satır için henüz artwork yüklenmemiş.
        </div>
    @endif
</div>
@endsection
