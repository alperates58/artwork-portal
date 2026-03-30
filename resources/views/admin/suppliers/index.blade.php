@extends('layouts.app')
@section('title', 'Tedarikçiler')
@section('page-title', 'Tedarikçi Yönetimi')

@section('header-actions')
    @if(auth()->user()->hasPermission('suppliers', 'create'))
        <a href="{{ route('admin.suppliers.import.form') }}" class="btn btn-secondary shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Toplu İçe Aktar
        </a>
        <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Yeni Tedarikçi
        </a>
    @endif
@endsection

@section('content')
<form method="GET" class="flex flex-wrap gap-3 mb-5">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="İsim veya kod ara..." class="input w-full sm:w-64">
    <button type="submit" class="btn btn-secondary">Ara</button>
    @if(request('search'))
        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary text-slate-500">Temizle</a>
    @endif
</form>

<div class="card overflow-x-auto">
    <table class="w-full min-w-[560px] text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50">
                <th class="text-left px-4 py-3 font-medium text-slate-600">Tedarikçi</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Kod</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">İletişim</th>
                <th class="text-center px-4 py-3 font-medium text-slate-600">Kullanıcı</th>
                <th class="text-center px-4 py-3 font-medium text-slate-600">Sipariş</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Durum</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($suppliers as $supplier)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-3">
                    <p class="font-medium text-slate-900">{{ $supplier->name }}</p>
                </td>
                <td class="px-4 py-3 font-mono text-slate-600 text-xs">{{ $supplier->code }}</td>
                <td class="px-4 py-3 text-slate-500 text-xs">
                    {{ $supplier->email ?? '—' }}<br>{{ $supplier->phone ?? '' }}
                </td>
                <td class="px-4 py-3 text-center text-slate-700">{{ $supplier->users_count }}</td>
                <td class="px-4 py-3 text-center text-slate-700">{{ $supplier->purchase_orders_count }}</td>
                <td class="px-4 py-3">
                    @if($supplier->is_active)
                        <span class="badge badge-success">Aktif</span>
                    @else
                        <span class="badge badge-gray">Pasif</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.suppliers.show', $supplier) }}" class="text-slate-600 hover:underline text-xs">Detay</a>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="text-brand-700 hover:underline text-xs font-medium">Düzenle</a>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Tedarikçi bulunamadı.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($suppliers->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $suppliers->links() }}</div>
    @endif
</div>
@endsection
