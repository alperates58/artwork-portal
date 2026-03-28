@extends('layouts.app')
@section('title', 'Kullanıcılar')
@section('page-title', 'Kullanıcı Yönetimi')
@section('header-actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Yeni Kullanıcı
    </a>
@endsection
@section('content')
<form method="GET" class="flex flex-wrap gap-3 mb-5">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="İsim veya e-posta ara..." class="input w-full sm:w-64">
    <select name="role" class="input w-full sm:w-44">
        <option value="">Tüm roller</option>
        @foreach(App\Enums\UserRole::cases() as $role)
            <option value="{{ $role->value }}" {{ request('role') === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-secondary">Filtrele</button>
</form>

<div class="card overflow-x-auto">
    <table class="w-full min-w-[560px] text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50">
                <th class="text-left px-4 py-3 font-medium text-slate-600">Kullanıcı</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Rol</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Tedarikçi</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Son Giriş</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Durum</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($users as $user)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-3">
                    <p class="font-medium text-slate-900">{{ $user->name }}</p>
                    <p class="text-xs text-slate-500">{{ $user->email }}</p>
                </td>
                <td class="px-4 py-3">
                    @php $roleCls = match($user->role->value) {'admin' => 'badge-danger', 'graphic' => 'badge-info', 'purchasing' => 'badge-warning', default => 'badge-gray'}; @endphp
                    <span class="badge {{ $roleCls }}">{{ $user->role->label() }}</span>
                </td>
                <td class="px-4 py-3 text-slate-600 text-sm">{{ $user->supplier?->name ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-500 text-xs">{{ $user->last_login_at?->format('d.m.Y H:i') ?? 'Henüz giriş yapmadı' }}</td>
                <td class="px-4 py-3">
                    @if($user->is_active)
                        <span class="badge badge-success">Aktif</span>
                    @else
                        <span class="badge badge-gray">Pasif</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-brand-700 hover:underline text-xs">Düzenle</a>
                        @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs {{ $user->is_active ? 'text-amber-600' : 'text-emerald-600' }} hover:underline">
                                    {{ $user->is_active ? 'Pasife Al' : 'Aktif Et' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                  onsubmit="return confirm('{{ $user->name }} kullanıcısını silmek istediğinize emin misiniz?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:underline">Sil</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Kullanıcı bulunamadı.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($users->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $users->links() }}</div>
    @endif
</div>
@endsection
