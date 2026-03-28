@extends('layouts.app')
@section('title', 'Sistem Logları')
@section('page-title', 'Sistem Logları')

@section('content')
<form method="GET" class="flex flex-wrap gap-3 mb-5">
    <select name="action" class="input w-full sm:w-52">
        <option value="">Tüm işlemler</option>
        <option value="user.login" {{ request('action') === 'user.login' ? 'selected' : '' }}>Giriş</option>
        <option value="artwork.download" {{ request('action') === 'artwork.download' ? 'selected' : '' }}>İndirme</option>
        <option value="artwork.upload" {{ request('action') === 'artwork.upload' ? 'selected' : '' }}>Yükleme</option>
        <option value="artwork.view" {{ request('action') === 'artwork.view' ? 'selected' : '' }}>Görüntüleme</option>
        <option value="portal.order.view" {{ request('action') === 'portal.order.view' ? 'selected' : '' }}>Portal sipariş görüntüleme</option>
    </select>
    <input type="date" name="date_from" value="{{ request('date_from') }}" class="input w-full sm:w-40">
    <input type="date" name="date_to" value="{{ request('date_to') }}" class="input w-full sm:w-40">
    <button type="submit" class="btn btn-secondary">Filtrele</button>
    @if(request()->hasAny(['action','date_from','date_to']))
        <a href="{{ route('admin.logs.index') }}" class="btn btn-secondary text-slate-500">Temizle</a>
    @endif
</form>

<div class="card overflow-x-auto">
    <table class="w-full min-w-[600px] text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50">
                <th class="text-left px-4 py-3 font-medium text-slate-600">Zaman</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Kullanıcı</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">İşlem</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Detay</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($logs as $log)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                    <td class="px-4 py-3">
                        <p class="text-sm text-slate-900">{{ $log->user?->name ?? '—' }}</p>
                        <p class="text-xs text-slate-400">{{ $log->user?->role?->label() ?? 'Silinmiş' }}</p>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $actionClass = match(true) {
                                str_contains($log->action, 'download') => 'badge-info',
                                str_contains($log->action, 'upload') => 'badge-success',
                                str_contains($log->action, 'login') => 'badge-gray',
                                str_contains($log->action, 'delete') => 'badge-danger',
                                default => 'badge-gray',
                            };
                        @endphp
                        <span class="badge {{ $actionClass }}">{{ $log->action }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500 max-w-xs truncate">
                        @if($log->payload)
                            {{ collect($log->payload)->map(fn($v, $k) => "$k: $v")->implode(' · ') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs font-mono text-slate-400">{{ $log->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Log kaydı bulunamadı.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($logs->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
