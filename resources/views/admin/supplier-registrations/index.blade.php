@extends('layouts.app')
@section('title', 'Kayıt Talepleri')
@section('page-title', 'Tedarikçi Kayıt Talepleri')

@section('header-actions')
    @if($pendingCount > 0)
        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-700">
            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
            {{ $pendingCount }} bekleyen talep
        </span>
    @endif
@endsection

@section('content')

{{-- Filters --}}
<form method="GET" class="card mb-5 p-4">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_200px_auto]">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Firma, e-posta veya kişi ara..."
               class="input w-full">
        <select name="status" class="input w-full">
            <option value="">Tüm durumlar</option>
            <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Bekleyen</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Onaylı</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Reddedildi</option>
        </select>
        <button type="submit" class="btn btn-secondary w-full md:w-auto">Filtrele</button>
    </div>
</form>

{{-- Mobile cards --}}
<div class="space-y-4 md:hidden">
    @forelse($registrations as $reg)
        @php
            [$badgeCls, $badgeLabel] = match($reg->status) {
                'approved' => ['badge-success', 'Onaylı'],
                'rejected' => ['badge-danger', 'Reddedildi'],
                default    => ['badge-warning', 'Bekleyen'],
            };
        @endphp
        <div class="card p-4 space-y-3">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="font-semibold text-slate-900">{{ $reg->company_name }}</p>
                    <p class="text-sm text-slate-500">{{ $reg->company_email }}</p>
                </div>
                <span class="badge {{ $badgeCls }} flex-shrink-0">{{ $badgeLabel }}</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="rounded-xl bg-slate-50 px-3 py-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Yetkili</p>
                    <p class="mt-0.5 text-slate-700">{{ $reg->contact_name }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-3 py-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Telefon</p>
                    <p class="mt-0.5 text-slate-700">{{ $reg->phone ?: '—' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-3 py-2 col-span-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Talep Tarihi</p>
                    <p class="mt-0.5 text-slate-700">{{ $reg->created_at->format('d.m.Y H:i') }}</p>
                </div>
            </div>
            @if($reg->isPending())
                <div class="flex flex-wrap gap-2 pt-1">
                    <button type="button"
                            onclick="openApproveModal({{ $reg->id }}, '{{ e($reg->contact_name) }}', '{{ e($reg->company_email) }}')"
                            class="btn btn-primary btn-sm">
                        Onayla
                    </button>
                    <button type="button"
                            onclick="openRejectModal({{ $reg->id }}, '{{ e($reg->contact_name) }}')"
                            class="btn btn-secondary btn-sm text-red-600">
                        Reddet
                    </button>
                </div>
            @elseif($reg->isApproved())
                <form method="POST" action="{{ route('admin.supplier-registrations.welcome-mail', $reg) }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm w-full">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Hoşgeldiniz Maili Gönder
                    </button>
                </form>
            @endif
        </div>
    @empty
        <div class="card px-4 py-10 text-center text-sm text-slate-400">Kayıt talebi bulunamadı.</div>
    @endforelse
</div>

{{-- Desktop table --}}
<div class="card hidden overflow-hidden md:block">
    <table class="w-full min-w-[860px] text-xs">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50">
                <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wide text-slate-500">Firma</th>
                <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wide text-slate-500">Yetkili</th>
                <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wide text-slate-500">Telefon</th>
                <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wide text-slate-500">Talep Tarihi</th>
                <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wide text-slate-500">Durum</th>
                <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wide text-slate-500">İnceleyen</th>
                <th class="px-4 py-2.5"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($registrations as $reg)
                @php
                    [$badgeCls, $badgeLabel] = match($reg->status) {
                        'approved' => ['badge-success', 'Onaylı'],
                        'rejected' => ['badge-danger', 'Reddedildi'],
                        default    => ['badge-warning', 'Bekleyen'],
                    };
                @endphp
                <tr class="transition-colors hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <p class="font-semibold text-slate-900">{{ $reg->company_name }}</p>
                        <p class="text-slate-400">{{ $reg->company_email }}</p>
                    </td>
                    <td class="px-4 py-3 text-slate-700">{{ $reg->contact_name }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $reg->phone ?: '—' }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $reg->created_at->format('d.m.Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <span class="badge {{ $badgeCls }}">{{ $badgeLabel }}</span>
                        @if($reg->isRejected() && $reg->rejection_reason)
                            <p class="mt-1 text-slate-400 italic max-w-[180px] truncate" title="{{ $reg->rejection_reason }}">{{ $reg->rejection_reason }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-500">
                        @if($reg->reviewedBy)
                            {{ $reg->reviewedBy->name }}<br>
                            <span class="text-slate-400">{{ $reg->reviewed_at?->format('d.m.Y') }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($reg->isPending())
                            <div class="flex items-center justify-end gap-3">
                                <button type="button"
                                        onclick="openApproveModal({{ $reg->id }}, '{{ e($reg->contact_name) }}', '{{ e($reg->company_email) }}')"
                                        class="font-medium text-brand-700 hover:underline">
                                    Onayla
                                </button>
                                <button type="button"
                                        onclick="openRejectModal({{ $reg->id }}, '{{ e($reg->contact_name) }}')"
                                        class="font-medium text-red-500 hover:underline">
                                    Reddet
                                </button>
                            </div>
                        @elseif($reg->isApproved())
                            <form method="POST" action="{{ route('admin.supplier-registrations.welcome-mail', $reg) }}">
                                @csrf
                                <button type="submit" class="font-medium text-slate-500 hover:text-brand-700 hover:underline flex items-center gap-1 justify-end">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    Hoşgeldiniz Maili
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-slate-400">Kayıt talebi bulunamadı.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($registrations->hasPages())
    <div class="mt-4">{{ $registrations->links() }}</div>
@endif

{{-- Approve Modal --}}
<div id="approve-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
        <h3 class="text-lg font-semibold text-slate-900">Kaydı Onayla</h3>
        <p id="approve-modal-desc" class="mt-1 text-sm text-slate-500"></p>

        <form id="approve-form" method="POST" class="mt-5 space-y-4">
            @csrf

            <div>
                <label class="label" for="approve-supplier">Tedarikçi <span class="text-slate-400 font-normal">(opsiyonel)</span></label>
                <select id="approve-supplier" name="supplier_id" class="input w-full">
                    <option value="">— Tedarikçi Seç —</option>
                    @foreach($suppliers as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Daha sonra kullanıcı düzenleme ekranından da atanabilir.</p>
            </div>

            <div>
                <label class="label" for="approve-password">Geçici Şifre <span class="text-red-500">*</span></label>
                <input type="password" id="approve-password" name="password"
                       class="input w-full" placeholder="En az 8 karakter" required minlength="8">
            </div>

            <div>
                <label class="label" for="approve-password-confirm">Şifre Tekrar <span class="text-red-500">*</span></label>
                <input type="password" id="approve-password-confirm" name="password_confirmation"
                       class="input w-full" placeholder="Şifreyi tekrar girin" required>
            </div>

            <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                Onaylama işlemi yapıldıktan sonra <strong>Hoşgeldiniz Maili Gönder</strong> butonu ile kullanıcıya bildirim gönderebilirsiniz.
            </div>

            <div class="flex gap-3 pt-1">
                <button type="submit" class="btn btn-primary flex-1">Onayla ve Kullanıcı Oluştur</button>
                <button type="button" onclick="closeApproveModal()" class="btn btn-secondary">İptal</button>
            </div>
        </form>
    </div>
</div>

{{-- Reject Modal --}}
<div id="reject-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
        <h3 class="text-lg font-semibold text-slate-900">Kaydı Reddet</h3>
        <p id="reject-modal-desc" class="mt-1 text-sm text-slate-500"></p>

        <form id="reject-form" method="POST" class="mt-5 space-y-4">
            @csrf

            <div>
                <label class="label" for="rejection-reason">Red Gerekçesi <span class="text-red-500">*</span></label>
                <textarea id="rejection-reason" name="rejection_reason" rows="3"
                          class="input w-full resize-none"
                          placeholder="Reddetme sebebini kısaca açıklayın..."
                          required></textarea>
            </div>

            <div class="flex gap-3 pt-1">
                <button type="submit" class="btn flex-1 bg-red-600 text-white hover:bg-red-700">Reddet</button>
                <button type="button" onclick="closeRejectModal()" class="btn btn-secondary">İptal</button>
            </div>
        </form>
    </div>
</div>

<script>
function openApproveModal(id, name, email) {
    document.getElementById('approve-modal-desc').textContent = name + ' — ' + email;
    document.getElementById('approve-form').action = '/admin/kayit-talepleri/' + id + '/onayla';
    document.getElementById('approve-password').value = '';
    document.getElementById('approve-password-confirm').value = '';
    document.getElementById('approve-modal').classList.remove('hidden');
    document.getElementById('approve-modal').classList.add('flex');
}
function closeApproveModal() {
    document.getElementById('approve-modal').classList.add('hidden');
    document.getElementById('approve-modal').classList.remove('flex');
}
function openRejectModal(id, name) {
    document.getElementById('reject-modal-desc').textContent = name + ' kaydını reddetmek istediğinize emin misiniz?';
    document.getElementById('reject-form').action = '/admin/kayit-talepleri/' + id + '/reddet';
    document.getElementById('rejection-reason').value = '';
    document.getElementById('reject-modal').classList.remove('hidden');
    document.getElementById('reject-modal').classList.add('flex');
}
function closeRejectModal() {
    document.getElementById('reject-modal').classList.add('hidden');
    document.getElementById('reject-modal').classList.remove('flex');
}
// Close modals on backdrop click
document.getElementById('approve-modal').addEventListener('click', function(e) {
    if (e.target === this) closeApproveModal();
});
document.getElementById('reject-modal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});
</script>
@endsection
