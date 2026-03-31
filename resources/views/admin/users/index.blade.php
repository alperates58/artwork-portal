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
    <table class="w-full min-w-[760px] text-xs">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50">
                <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wide text-slate-500">Kullanıcı</th>
                <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wide text-slate-500">Rol</th>
                <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wide text-slate-500">Departman</th>
                <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wide text-slate-500">Tedarikçi</th>
                <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wide text-slate-500">Son Giriş</th>
                <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wide text-slate-500">Durum</th>
                <th class="px-4 py-2.5"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($users as $user)
                @php
                    $initials = collect(explode(' ', $user->name))
                        ->filter()
                        ->take(2)
                        ->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                        ->implode('');
                    $avatarColor = match($user->role->value) {
                        'admin'      => 'bg-red-100 text-red-700',
                        'graphic'    => 'bg-blue-100 text-blue-700',
                        'purchasing' => 'bg-amber-100 text-amber-700',
                        default      => 'bg-slate-100 text-slate-600',
                    };
                @endphp
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full {{ $avatarColor }} text-xs font-bold">
                                {{ $initials }}
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                                <p class="text-slate-400">{{ $user->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @php $roleCls = match($user->role->value) {'admin' => 'badge-danger', 'graphic' => 'badge-info', 'purchasing' => 'badge-warning', default => 'badge-gray'}; @endphp
                        <span class="badge {{ $roleCls }}">{{ $user->role->label() }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->department?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->supplier?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-400">
                        @if($user->last_login_at)
                            <span class="text-slate-600">{{ $user->last_login_at->format('d.m.Y') }}</span>
                            <span class="text-slate-400"> {{ $user->last_login_at->format('H:i') }}</span>
                        @else
                            <span class="text-slate-300">Henüz giriş yapmadı</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($user->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-gray">Pasif</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                            <a href="{{ route('admin.users.edit', $user) }}" class="font-medium text-brand-700 hover:underline">Düzenle</a>
                            @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="font-medium {{ $user->is_active ? 'text-amber-600' : 'text-emerald-600' }} hover:underline">
                                        {{ $user->is_active ? 'Pasife Al' : 'Aktif Et' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                      onsubmit="return confirm('{{ $user->name }} kullanıcısını silmek istediğinize emin misiniz?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="font-medium text-red-500 hover:underline">Sil</button>
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

    @if($users->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">{{ $users->links() }}</div>
    @endif
</div>
@endsection
