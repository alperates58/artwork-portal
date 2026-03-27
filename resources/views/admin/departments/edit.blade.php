@extends('layouts.app')
@section('title', $department->name . ' — Departman Düzenle')
@section('page-title', $department->name)
@section('page-subtitle', 'Departman adı ve yetkilerini düzenleyin.')

@section('header-actions')
    <a href="{{ route('admin.departments.index') }}" class="btn btn-secondary">← Departmanlar</a>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.departments.update', $department) }}" class="space-y-6">
    @csrf
    @method('PATCH')

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="card p-6">
        <div class="max-w-sm">
            <label class="label" for="dept_name">Departman Adı *</label>
            <input id="dept_name" name="name" type="text" class="input @error('name') error @enderror"
                   value="{{ old('name', $department->name) }}" required maxlength="100">
            @error('name')<p class="err">{{ $message }}</p>@enderror
        </div>
    </div>

    @php $effectivePermissions = $department->permissions ?? []; @endphp

    <div class="space-y-4">
        @foreach($screens as $screenKey => $screen)
            @php $screenPerms = $effectivePermissions[$screenKey] ?? []; @endphp
            <div class="card overflow-hidden border-slate-200/90">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ $screen['label'] }}</h3>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        {{ collect($screen['actions'])->keys()->filter(fn($a) => ($screenPerms[$a] ?? false))->count() }}
                        / {{ count($screen['actions']) }} aktif
                    </span>
                </div>
                <div class="flex flex-wrap gap-3 px-5 py-4">
                    @foreach($screen['actions'] as $actionKey => $actionLabel)
                        @php $checked = old("permissions.{$screenKey}.{$actionKey}", ($screenPerms[$actionKey] ?? false)) === true || old("permissions.{$screenKey}.{$actionKey}") === '1'; @endphp
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border px-4 py-2.5 text-sm transition select-none
                                      {{ $checked ? 'border-brand-200 bg-brand-50 text-brand-800' : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300 hover:bg-white' }}">
                            <input type="checkbox"
                                   name="permissions[{{ $screenKey }}][{{ $actionKey }}]"
                                   value="1"
                                   {{ $checked ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 focus:ring-offset-0">
                            {{ $actionLabel }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Users in this department --}}
    @if($department->users->isNotEmpty())
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-900">Bu Departmandaki Kullanıcılar ({{ $department->users->count() }})</h3>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($department->users as $deptUser)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-900">{{ $deptUser->name }}</p>
                        <p class="text-xs text-slate-400">{{ $deptUser->email }} · {{ $deptUser->role->label() }}</p>
                    </div>
                    <a href="{{ route('admin.users.edit', $deptUser) }}" class="text-xs text-brand-600 hover:underline">Düzenle</a>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary px-8">Değişiklikleri Kaydet</button>
    </div>
</form>
@endsection
