@extends('layouts.app')
@section('title', 'Kullanıcılar')
@section('page-title', 'Kullanıcı Yönetimi')
@section('header-actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Kullanıcı
    </a>
@endsection

@section('content')
<style>
    @media (min-width: 768px) {
        .users-desktop-table {
            display: block !important;
        }
        .users-mobile-cards {
            display: none !important;
        }
    }
    @media (max-width: 767.98px) {
        .users-desktop-table {
            display: none !important;
        }
        .users-mobile-cards {
            display: block !important;
        }
    }
</style>

<form method="GET" class="card mb-5 p-4">
    <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr)_220px_220px_auto]">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="İsim veya e-posta ara..." class="input w-full">

        <select name="role" class="input w-full">
            <option value="">Tüm roller</option>
            @foreach(App\Enums\UserRole::cases() as $role)
                <option value="{{ $role->value }}" {{ request('role') === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
            @endforeach
        </select>

        <select name="department_id" class="input w-full">
            <option value="">Tüm departmanlar</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" {{ (int) request('department_id') === $department->id ? 'selected' : '' }}>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-secondary w-full lg:w-auto">Filtrele</button>
    </div>
</form>

<div class="card overflow-hidden">
    <div class="users-desktop-table overflow-x-auto">
        <table class="w-full min-w-[860px] text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Kullanıcı</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Rol</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Departman</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Tedarikçi</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Son Giriş</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Durum</th>
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
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $user->department?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $user->supplier?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $user->last_login_at?->format('d.m.Y H:i') ?? 'Henüz giriş yapmadı' }}</td>
                        <td class="px-4 py-3">
                            @if($user->is_active)
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-gray">Pasif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-xs text-brand-700 hover:underline">Düzenle</a>
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
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-400">Kullanıcı bulunamadı.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="users-mobile-cards divide-y divide-slate-100">
        @forelse($users as $user)
            <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-medium text-slate-900">{{ $user->name }}</p>
                        <p class="text-xs text-slate-500">{{ $user->email }}</p>
                    </div>
                    @if($user->is_active)
                        <span class="badge badge-success">Aktif</span>
                    @else
                        <span class="badge badge-gray">Pasif</span>
                    @endif
                </div>

                <div class="mt-3 space-y-1 text-xs text-slate-600">
                    <p><span class="text-slate-400">Rol:</span> {{ $user->role->label() }}</p>
                    <p><span class="text-slate-400">Departman:</span> {{ $user->department?->name ?? '—' }}</p>
                    <p><span class="text-slate-400">Tedarikçi:</span> {{ $user->supplier?->name ?? '—' }}</p>
                    <p><span class="text-slate-400">Son giriş:</span> {{ $user->last_login_at?->format('d.m.Y H:i') ?? 'Henüz giriş yapmadı' }}</p>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1">
                    <a href="{{ route('admin.users.edit', $user) }}" class="text-xs text-brand-700 hover:underline">Düzenle</a>
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
            </div>
        @empty
            <div class="px-4 py-10 text-center text-slate-400">Kullanıcı bulunamadı.</div>
        @endforelse
    </div>

    @if($users->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">{{ $users->links() }}</div>
    @endif
</div>
@endsection
