@extends('layouts.app')
@section('title', $user->name . ' — Düzenle')
@section('page-title', $user->name . ' — Düzenle')
@section('header-actions')
    <a href="{{ route('admin.users.index') }}" class="btn-secondary">← Listeye Dön</a>
@endsection
@section('content')
<div class="max-w-xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
            @csrf @method('PATCH')
            @include('admin.users._form', ['isCreate' => false])
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Güncelle</button>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">İptal</a>
            </div>
        </form>
    </div>
</div>
@endsection
