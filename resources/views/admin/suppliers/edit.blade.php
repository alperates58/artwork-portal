@extends('layouts.app')
@section('title', 'Tedarikçi Düzenle')
@section('page-title', $supplier->name . ' — Düzenle')
@section('header-actions')
    <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">← Listeye Dön</a>
@endsection
@section('content')
<div class="max-w-xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}" class="space-y-4">
            @csrf @method('PATCH')
            @include('admin.suppliers._form')
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Güncelle</button>
                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">İptal</a>
            </div>
        </form>
    </div>
</div>
@endsection
