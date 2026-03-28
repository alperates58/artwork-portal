@extends('layouts.app')
@section('title', 'Profilim')
@section('page-title', 'Profilim')

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- LEFT: Profile card --}}
        <div class="space-y-5">

            {{-- Avatar + name card --}}
            <div class="card overflow-hidden">
                {{-- Cover gradient --}}
                <div class="h-20 w-full" style="background: linear-gradient(135deg, var(--brand-600, #7c3aed), var(--brand-500, #8b5cf6));"></div>
                <div class="px-5 pb-5">
                    {{-- Avatar --}}
                    <div class="-mt-10 mb-3 flex items-end justify-between">
                        <div class="relative">
                            @if($user->profile_photo_path)
                                <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}"
                                     class="h-20 w-20 rounded-2xl object-cover ring-4 ring-white shadow-lg">
                            @else
                                <div class="flex h-20 w-20 items-center justify-center rounded-2xl text-2xl font-bold text-white ring-4 ring-white shadow-lg"
                                     style="background: linear-gradient(135deg, var(--brand-500, #8b5cf6), var(--brand-700, #6d28d9));">
                                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                            {{-- Upload photo overlay --}}
                            <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data" id="photo-form">
                                @csrf
                                <label class="absolute -bottom-1 -right-1 flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border-2 border-white bg-violet-600 text-white shadow transition hover:bg-violet-700"
                                       title="Fotoğraf Değiştir">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <input type="file" name="photo" accept="image/*" class="hidden" onchange="document.getElementById('photo-form').submit()">
                                </label>
                            </form>
                        </div>
                        @if($user->profile_photo_path)
                            <form method="POST" action="{{ route('profile.photo.delete') }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-slate-400 hover:text-red-500" title="Fotoğrafı kaldır">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </div>

                    <h2 class="text-lg font-semibold text-slate-900">{{ $user->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $user->role?->label() }}</p>
                    @if($user->department)
                        <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-violet-50 px-2.5 py-0.5 text-xs font-medium text-violet-700">
                            {{ $user->department->name }}
                        </span>
                    @endif

                    @if($user->bio)
                        <p class="mt-3 text-sm text-slate-600 leading-relaxed">{{ $user->bio }}</p>
                    @endif

                    {{-- Contact info --}}
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <svg class="h-4 w-4 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span class="truncate">{{ $user->email }}</span>
                        </div>
                        @if($user->contact_email && $user->contact_email !== $user->email)
                            <div class="flex items-center gap-2 text-sm text-slate-600">
                                <svg class="h-4 w-4 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <a href="mailto:{{ $user->contact_email }}" class="truncate hover:text-violet-600 hover:underline">{{ $user->contact_email }}</a>
                            </div>
                        @endif
                        @if($user->phone)
                            <div class="flex items-center gap-2 text-sm text-slate-600">
                                <svg class="h-4 w-4 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <a href="tel:{{ $user->phone }}" class="hover:text-violet-600 hover:underline">{{ $user->phone }}</a>
                            </div>
                        @endif
                        @if($user->linkedin_url)
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="h-4 w-4 flex-shrink-0 text-[#0077b5]" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                                <a href="{{ $user->linkedin_url }}" target="_blank" rel="noopener" class="text-[#0077b5] hover:underline truncate">LinkedIn</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Recent Notifications --}}
            <div class="card p-5"
                 x-data="{ items: [], loading: true }"
                 x-init="
                    fetch('/bildirimler', { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(d => { items = d.items ?? []; loading = false; })
                        .catch(() => loading = false)
                 ">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-800">Son Bildirimler</h3>
                    <form method="POST" action="{{ route('notifications.read') }}">
                        @csrf
                        <button type="submit" class="text-xs text-violet-600 hover:underline">Tümünü oku</button>
                    </form>
                </div>
                <div x-show="loading" class="py-4 text-center text-sm text-slate-400">Yükleniyor…</div>
                <div x-show="!loading && items.length === 0" class="py-4 text-center text-sm text-slate-400">Bildirim yok</div>
                <template x-if="!loading && items.length > 0">
                    <ul class="space-y-2">
                        <template x-for="item in items.slice(0, 5)" :key="item.id">
                            <li class="flex items-start gap-2.5">
                                <span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full transition"
                                      :class="item.read ? 'bg-slate-200' : 'bg-violet-500'"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-slate-700 leading-snug" x-text="item.title"></p>
                                    <p class="text-[11px] text-slate-400 mt-0.5" x-text="item.created_at"></p>
                                </div>
                                <a x-show="item.url" :href="item.url"
                                   class="flex-shrink-0 text-[11px] text-violet-600 hover:underline">→</a>
                            </li>
                        </template>
                    </ul>
                </template>
            </div>
        </div>

        {{-- RIGHT: Edit forms --}}
        <div class="space-y-5 lg:col-span-2">

            {{-- Personal Info --}}
            <div class="card p-6">
                <h2 class="mb-5 text-base font-semibold text-slate-800 flex items-center gap-2">
                    <svg class="h-5 w-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Kişisel Bilgiler
                </h2>
                <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                    @csrf @method('PATCH')

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="name">Ad Soyad</label>
                            <input id="name" type="text" name="name"
                                   class="input @error('name') error @enderror"
                                   value="{{ old('name', $user->name) }}" required>
                            @error('name')<p class="err">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label" for="email">E-Posta (Giriş)</label>
                            <input id="email" type="email" name="email"
                                   class="input @error('email') error @enderror"
                                   value="{{ old('email', $user->email) }}" required>
                            @error('email')<p class="err">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label" for="phone">Telefon</label>
                            <input id="phone" type="tel" name="phone"
                                   class="input @error('phone') error @enderror"
                                   value="{{ old('phone', $user->phone) }}"
                                   placeholder="+90 555 000 00 00">
                            @error('phone')<p class="err">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label" for="contact_email">İletişim E-Postası</label>
                            <input id="contact_email" type="email" name="contact_email"
                                   class="input @error('contact_email') error @enderror"
                                   value="{{ old('contact_email', $user->contact_email) }}"
                                   placeholder="iletisim@ornek.com">
                            @error('contact_email')<p class="err">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="label" for="linkedin_url">LinkedIn Profil URL</label>
                        <input id="linkedin_url" type="url" name="linkedin_url"
                               class="input @error('linkedin_url') error @enderror"
                               value="{{ old('linkedin_url', $user->linkedin_url) }}"
                               placeholder="https://linkedin.com/in/kullanici-adi">
                        @error('linkedin_url')<p class="err">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="label" for="bio">Hakkımda <span class="font-normal text-slate-400">(isteğe bağlı)</span></label>
                        <textarea id="bio" name="bio" rows="3"
                                  class="input resize-none @error('bio') error @enderror"
                                  placeholder="Kısa bir tanıtım yazısı…">{{ old('bio', $user->bio) }}</textarea>
                        @error('bio')<p class="err">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>

            {{-- Change Password --}}
            <div class="card p-6">
                <h2 class="mb-5 text-base font-semibold text-slate-800 flex items-center gap-2">
                    <svg class="h-5 w-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Şifre Değiştir
                </h2>
                <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                    @csrf @method('PATCH')

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="label" for="current_password">Mevcut Şifre</label>
                            <input id="current_password" type="password" name="current_password"
                                   class="input @error('current_password') error @enderror"
                                   autocomplete="current-password">
                            @error('current_password')<p class="err">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label" for="password">Yeni Şifre</label>
                            <input id="password" type="password" name="password"
                                   class="input @error('password') error @enderror"
                                   autocomplete="new-password">
                            @error('password')<p class="err">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label" for="password_confirmation">Tekrar</label>
                            <input id="password_confirmation" type="password" name="password_confirmation"
                                   class="input" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="submit" class="btn btn-primary">Şifreyi Değiştir</button>
                    </div>
                </form>
            </div>

            {{-- Account info (readonly) --}}
            <div class="card p-6">
                <h2 class="mb-4 text-base font-semibold text-slate-800 flex items-center gap-2">
                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Hesap Bilgileri
                </h2>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-slate-400">Rol</dt>
                        <dd class="font-medium text-slate-800">{{ $user->role?->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Departman</dt>
                        <dd class="font-medium text-slate-800">{{ $user->department?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Kayıt Tarihi</dt>
                        <dd class="font-medium text-slate-800">{{ $user->created_at->format('d.m.Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Son Giriş</dt>
                        <dd class="font-medium text-slate-800">{{ $user->last_login_at?->format('d.m.Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

        </div>
    </div>
</div>
@endsection
