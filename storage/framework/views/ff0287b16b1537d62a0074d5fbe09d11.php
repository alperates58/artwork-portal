<!DOCTYPE html>
<html lang="tr">
<head>
    <?php echo $__env->make('partials.ui-head', ['title' => 'Giriş'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-brand-50 font-sans antialiased min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">

    
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-4 shadow-sm"
             style="background:linear-gradient(180deg,var(--brand-600),var(--brand-700))">
            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1 class="text-xl font-semibold text-slate-900">Artwork Portal</h1>
        <p class="text-sm text-slate-500 mt-1">Tedarikçi Artwork Yönetim Sistemi</p>
    </div>

    
    <div class="card p-8">
        <form method="POST" action="<?php echo e(route('login')); ?>" class="space-y-5">
            <?php echo csrf_field(); ?>

            <div>
                <label class="label" for="email">E-posta</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo e(old('email')); ?>"
                    autocomplete="email"
                    autofocus
                    required
                    class="input <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    placeholder="kullanici@sirket.com"
                >
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="err"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div>
                <label class="label" for="password">Şifre</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                    class="input <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    placeholder="••••••••"
                >
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600">
                    <span class="text-sm text-slate-600">Beni hatırla</span>
                </label>
                <a href="<?php echo e(route('password.request')); ?>"
                   class="text-sm text-brand-600 hover:text-brand-700 font-medium">
                    Şifremi unuttum
                </a>
            </div>

            <button type="submit"
                    class="btn btn-primary w-full">
                Giriş Yap
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-slate-400 mt-6">
        Hesap açmak için yönetici ile iletişime geçin.
    </p>
</div>

</body>
</html>
<?php /**PATH /var/www/html/resources/views/auth/login.blade.php ENDPATH**/ ?>