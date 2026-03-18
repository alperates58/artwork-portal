<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırla — {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 font-sans antialiased min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-sm">
    <div class="text-center mb-8">
        <h1 class="text-xl font-semibold text-slate-900">Yeni Şifre Belirle</h1>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">E-posta</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required
                       class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Yeni Şifre</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('password')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Şifre Tekrar</label>
                <input type="password" name="password_confirmation" required
                       class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit"
                    class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                Şifremi Güncelle
            </button>
        </form>
    </div>
</div>
</body>
</html>
