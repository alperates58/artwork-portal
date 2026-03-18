@extends('layouts.app')
@section('title', 'Yeni Kullanıcı')
@section('page-title', 'Yeni Kullanıcı')
@section('header-actions')
    <a href="{{ route('admin.users.index') }}" class="btn-secondary">← Listeye Dön</a>
@endsection
@section('content')
<div class="max-w-xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
            @csrf
            @include('admin.users._form', ['isCreate' => true])
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Oluştur</button>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">İptal</a>
            </div>
        </form>
    </div>
</div>
@endsection
