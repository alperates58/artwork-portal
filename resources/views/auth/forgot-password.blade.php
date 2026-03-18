<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Unuttum — {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 font-sans antialiased min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-sm">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-4" style="background:#3b5bdb">
            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
        </div>
        <h1 class="text-xl font-semibold text-slate-900">Şifremi Unuttum</h1>
        <p class="text-sm text-slate-500 mt-1">E-posta adresinizi girin, sıfırlama bağlantısı gönderelim.</p>
    </div>

    @if(session('status'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">E-posta</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="kullanici@sirket.com">
                @error('email')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                Sıfırlama Bağlantısı Gönder
            </button>
        </form>
    </div>

    <div class="text-center mt-6">
        <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:underline">← Giriş sayfasına dön</a>
    </div>
</div>
</body>
</html>
