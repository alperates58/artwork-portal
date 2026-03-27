@extends('layouts.app')
@section('title', 'Yeni Departman')
@section('page-title', 'Yeni Departman')
@section('page-subtitle', 'Departman adını girin ve ekran bazında yetkiler tanımlayın.')

@section('header-actions')
    <a href="{{ route('admin.departments.index') }}" class="btn btn-secondary">← Geri</a>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.departments.store') }}" class="space-y-6">
    @csrf

    <div class="card p-6">
        <div class="max-w-sm">
            <label class="label" for="dept_name">Departman Adı *</label>
            <input id="dept_name" name="name" type="text" class="input @error('name') error @enderror"
                   value="{{ old('name') }}" required maxlength="100" placeholder="ör. Depo Birimi, Kalite Kontrol">
            @error('name')<p class="err">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="space-y-4">
        @foreach($screens as $screenKey => $screen)
            <div class="card overflow-hidden border-slate-200/90">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ $screen['label'] }}</h3>
                </div>
                <div class="flex flex-wrap gap-3 px-5 py-4">
                    @foreach($screen['actions'] as $actionKey => $actionLabel)
                        @php $checked = old("permissions.{$screenKey}.{$actionKey}") === '1'; @endphp
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

    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary px-8">Departmanı Oluştur</button>
    </div>
</form>
@endsection
