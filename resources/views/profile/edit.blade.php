@extends('layouts.app')
@section('title', 'Profilim')
@section('page-title', 'Profilim')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">

    {{-- Profile Photo --}}
    <div class="card p-6">
        <h2 class="mb-4 text-base font-semibold text-slate-800">Profil Fotoğrafı</h2>
        <div class="flex items-center gap-6">
            {{-- Avatar --}}
            <div class="flex-shrink-0">
                @if($user->profile_photo_path)
                    <img
                        src="{{ $user->profile_photo_url }}"
                        alt="{{ $user->name }}"
                        class="h-20 w-20 rounded-2xl object-cover ring-2 ring-violet-200"
                    >
                @else
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl text-2xl font-bold text-white"
                         style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);">
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    </div>
                @endif
            </div>

            <div class="flex-1 space-y-3">
                <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data" class="flex items-center gap-3">
                    @csrf
                    <label class="btn btn-secondary cursor-pointer">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Fotoğraf Yükle
                        <input type="file" name="photo" accept="image/*" class="hidden" onchange="this.closest('form').submit()">
                    </label>
                    @error('photo')
                        <span class="text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </form>

                @if($user->profile_photo_path)
                    <form method="POST" action="{{ route('profile.photo.delete') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-500 hover:text-red-700 hover:underline">
                            Fotoğrafı kaldır
                        </button>
                    </form>
                @endif

                <p class="text-xs text-slate-400">JPG, PNG veya WebP. Maksimum 2 MB.</p>
            </div>
        </div>
    </div>

    {{-- Profile Info --}}
    <div class="card p-6">
        <h2 class="mb-4 text-base font-semibold text-slate-800">Kişisel Bilgiler</h2>
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="label" for="name">Ad Soyad</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    class="input @error('name') error @enderror"
                    value="{{ old('name', $user->name) }}"
                    required
                >
                @error('name')
                    <p class="err">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label" for="email">E-Posta</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    class="input @error('email') error @enderror"
                    value="{{ old('email', $user->email) }}"
                    required
                >
                @error('email')
                    <p class="err">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>

    {{-- Change Password --}}
    <div class="card p-6">
        <h2 class="mb-4 text-base font-semibold text-slate-800">Şifre Değiştir</h2>
        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="label" for="current_password">Mevcut Şifre</label>
                <input
                    id="current_password"
                    type="password"
                    name="current_password"
                    class="input @error('current_password') error @enderror"
                    autocomplete="current-password"
                >
                @error('current_password')
                    <p class="err">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label" for="password">Yeni Şifre</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="input @error('password') error @enderror"
                    autocomplete="new-password"
                >
                @error('password')
                    <p class="err">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label" for="password_confirmation">Yeni Şifre (Tekrar)</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    class="input"
                    autocomplete="new-password"
                >
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="btn btn-primary">Şifreyi Değiştir</button>
            </div>
        </form>
    </div>

    {{-- Recent Notifications --}}
    <div class="card p-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-800">Son Bildirimler</h2>
            <form method="POST" action="{{ route('notifications.read') }}">
                @csrf
                <button type="submit" class="text-xs text-violet-600 hover:underline">Tümünü okundu işaretle</button>
            </form>
        </div>

        <div
            x-data="{ items: [], loading: true }"
            x-init="
                fetch('/bildirimler', { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(d => { items = d.items ?? []; loading = false; })
                    .catch(() => loading = false)
            "
        >
            <div x-show="loading" class="py-6 text-center text-sm text-slate-400">Yükleniyor…</div>
            <div x-show="!loading && items.length === 0" class="py-6 text-center text-sm text-slate-400">Bildirim yok</div>

            <template x-if="!loading && items.length > 0">
                <ul class="divide-y divide-slate-100">
                    <template x-for="item in items" :key="item.id">
                        <li class="flex items-start gap-3 py-3" :class="{'opacity-50': item.read}">
                            <span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full"
                                  :class="item.read ? 'bg-slate-200' : 'bg-violet-500'"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-800" x-text="item.title"></p>
                                <p class="mt-0.5 text-xs text-slate-500" x-show="item.body" x-text="item.body"></p>
                                <p class="mt-1 text-[11px] text-slate-400" x-text="item.created_at"></p>
                            </div>
                            <a x-show="item.url" :href="item.url"
                               class="flex-shrink-0 text-xs text-violet-600 hover:underline">Görüntüle</a>
                        </li>
                    </template>
                </ul>
            </template>
        </div>
    </div>

</div>
@endsection
