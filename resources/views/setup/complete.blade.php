<!DOCTYPE html>
<html lang="tr">
<head>
    @include('partials.ui-head', ['title' => 'Kurulum Tamamlandı'])
</head>
<body class="bg-gradient-to-br from-slate-50 to-emerald-50 min-h-screen font-sans antialiased flex items-center justify-center p-4">

<div class="w-full max-w-md text-center">
    <div class="inline-flex items-center justify-center w-20 h-20 bg-emerald-500 rounded-full mb-6">
        <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <h1 class="text-2xl font-semibold text-slate-900 mb-2">Kurulum Tamamlandı!</h1>
    <p class="text-slate-500 text-sm mb-8">
        Lider Portal başarıyla yapılandırıldı.<br>
        Artık sisteme giriş yapabilirsiniz.
    </p>

    <div class="bg-white rounded-2xl border border-slate-200 p-5 text-left mb-6 space-y-3">
        @php
            $checks = [
                '.env dosyası oluşturuldu',
                'Veritabanı tabloları oluşturuldu',
                'Admin kullanıcı oluşturuldu',
                'DigitalOcean Spaces bağlantısı doğrulandı',
                'Setup ekranı kilitlendi',
            ];
        @endphp
        @foreach($checks as $check)
            <div class="flex items-center gap-3">
                <div class="w-5 h-5 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <p class="text-sm text-slate-700">{{ $check }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-left mb-6">
        <p class="text-xs font-semibold text-amber-800 mb-1">Güvenlik Hatırlatması</p>
        <p class="text-xs text-amber-700">
            Kurulum sihirbazı kalıcı olarak kilitlendi.
            <code class="bg-amber-100 px-1 rounded">storage/app/.setup_complete</code>
            dosyası silinmedikçe <code>/setup</code> adresi 403 döner.
        </p>
    </div>

    <a href="{{ url('/login') }}" class="btn btn-primary">Giriş Yap</a>
</div>

</body>
</html>
