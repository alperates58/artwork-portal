@extends('layouts.app')
@section('title', 'Yeni Stok Kartı')
@section('page-title', 'Yeni Stok Kartı')

@section('header-actions')
    <a href="{{ route('admin.stock-cards.index') }}" class="btn btn-secondary">← Listeye Dön</a>
@endsection

@section('content')
<div class="max-w-xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('admin.stock-cards.store') }}" class="space-y-4">
            @csrf
            @include('admin.stock-cards._form')

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Oluştur</button>
                <a href="{{ route('admin.stock-cards.index') }}" class="btn btn-secondary">İptal</a>
            </div>
        </form>
    </div>
</div>
@endsection
