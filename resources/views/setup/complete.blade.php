<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurulum Tamamlandı — Artwork Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter','sans-serif'] } } } }</script>
    <style>
        @keyframes scaleIn { from { transform: scale(0.5); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @keyframes fadeUp  { from { transform: translateY(16px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .anim-scale { animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
        .anim-fade  { animation: fadeUp 0.4s ease forwards; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-emerald-50 min-h-screen font-sans antialiased flex items-center justify-center p-4">

<div class="w-full max-w-md text-center">

    {{-- Success icon --}}
    <div class="anim-scale inline-flex items-center justify-center w-20 h-20 bg-emerald-500 rounded-full mb-6">
        <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <div class="anim-fade" style="animation-delay: 0.2s; opacity: 0;">
        <h1 class="text-2xl font-semibold text-slate-900 mb-2">Kurulum Tamamlandı!</h1>
        <p class="text-slate-500 text-sm mb-8">
            Artwork Portal başarıyla yapılandırıldı.<br>
            Artık sisteme giriş yapabilirsiniz.
        </p>

        {{-- Checklist --}}
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

        {{-- Güvenlik notu --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-left mb-6">
            <p class="text-xs font-semibold text-amber-800 mb-1">Güvenlik Hatırlatması</p>
            <p class="text-xs text-amber-700">
                Kurulum sihirbazı kalıcı olarak kilitlendi.
                <code class="bg-amber-100 px-1 rounded">storage/app/.setup_complete</code>
                dosyası silinmedikçe <code>/setup</code> adresi 403 döner.
            </p>
        </div>

        <a href="{{ url('/login') }}"
           class="inline-flex items-center gap-2 px-8 py-3 bg-blue-600 hover:bg-blue-700
                  text-white font-medium rounded-xl transition-colors text-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            Giriş Yap
        </a>
    </div>
</div>

</body>
</html>
