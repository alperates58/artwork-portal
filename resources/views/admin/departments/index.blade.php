@extends('layouts.app')
@section('title', 'Departmanlar')
@section('page-title', 'Departmanlar')
@section('page-subtitle', 'Kullanıcı gruplarını ve grup yetkilerini yönetin.')

@section('header-actions')
    <a href="{{ route('admin.departments.create') }}" class="btn btn-primary">+ Yeni Departman</a>
@endsection

@section('content')
<div class="space-y-6">

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="card overflow-x-auto">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Tüm Departmanlar</h2>
            <span class="text-xs text-slate-400">{{ $departments->count() }} departman</span>
        </div>
        <table class="w-full min-w-[480px] text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-left">
                    <th class="px-4 py-3 font-medium text-slate-600">Departman Adı</th>
                    <th class="px-4 py-3 font-medium text-slate-600 text-center">Kullanıcı Sayısı</th>
                    <th class="px-4 py-3 font-medium text-slate-600 text-center">Yetki Durumu</th>
                    <th class="px-4 py-3 font-medium text-slate-600 text-right">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($departments as $dept)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $dept->name }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold bg-blue-50 text-blue-700">
                                {{ $dept->users_count }} kullanıcı
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($dept->permissions)
                                <span class="badge badge-success">Özel yetkiler tanımlı</span>
                            @else
                                <span class="badge badge-gray">Yetki tanımlanmamış</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.departments.edit', $dept) }}" class="btn btn-secondary text-xs py-1.5 px-3">Düzenle</a>
                                <form method="POST" action="{{ route('admin.departments.destroy', $dept) }}"
                                      onsubmit="return confirm('{{ $dept->name }} departmanı silinecek. Bağlı kullanıcıların departman ataması kaldırılacak. Devam edilsin mi?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary text-xs py-1.5 px-3 text-red-600 hover:border-red-200 hover:bg-red-50">Sil</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-slate-400">
                            Henüz departman oluşturulmamış.
                            <a href="{{ route('admin.departments.create') }}" class="text-brand-600 hover:underline">İlk departmanı oluştur →</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
