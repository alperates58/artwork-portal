<!DOCTYPE html>
<html lang="tr">
<head>
    <?php echo $__env->make('partials.ui-head', ['title' => trim($__env->yieldContent('title', config('portal.brand_name')))], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</head>
<body class="bg-slate-100 font-sans antialiased text-slate-900">
<?php
    $logoPath = trim((string) config('portal.logo_path'), '/');
    $logoUrl = $logoPath !== '' ? asset($logoPath) : null;
    $user = auth()->user();
    $userInitials = $user ? strtoupper((string) str($user->name)->squish()->explode(' ')->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('')) : 'LP';
    $hasPageAside = View::hasSection('page-aside');
    $pageAsideStorageKey = trim((string) $__env->yieldContent('page-aside-storage-key', request()->route()?->getName() ?: 'default'));
    $pageSubtitle = trim((string) $__env->yieldContent('page-subtitle', config('portal.brand_tagline')));
?>

<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(244,154,11,0.08),_transparent_28%),linear-gradient(180deg,_#f8fafc_0%,_#eef2f7_100%)]">
    <div class="flex min-h-screen">
        <aside class="hidden w-[288px] flex-shrink-0 border-r border-slate-200/80 bg-white/95 backdrop-blur lg:flex lg:flex-col">
            <div class="border-b border-slate-200/80 px-5 py-5">
                <a href="<?php echo e(route('dashboard')); ?>" class="brand-shell group flex items-center gap-4 rounded-3xl border border-slate-200/80 bg-[linear-gradient(135deg,_rgba(255,255,255,0.96),_rgba(255,247,237,0.96))] p-4 shadow-[0_16px_40px_rgba(15,23,42,0.08)] transition hover:border-brand-200">
                    <div class="brand-mark">
                        <?php if($logoUrl): ?>
                            <img src="<?php echo e($logoUrl); ?>" alt="<?php echo e(config('portal.brand_name')); ?>" class="h-12 w-auto object-contain sm:h-14">
                        <?php else: ?>
                            <div class="h-12 w-12 rounded-2xl bg-brand-600"></div>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0">
                        <span class="brand-eyebrow">Lider Kozmetik</span>
                        <span class="mt-1 block truncate text-base font-semibold text-slate-950"><?php echo e(config('portal.brand_name')); ?></span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500"><?php echo e(config('portal.brand_tagline')); ?></span>
                    </div>
                </a>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-6">
                <?php if (! ($user?->isSupplier())): ?>
                    <div class="space-y-1.5">
                        <a href="<?php echo e(route('dashboard')); ?>" class="sidebar-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span>Dashboard</span>
                        </a>

                        <a href="<?php echo e(route('orders.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('orders.*') ? 'active' : ''); ?>">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Siparisler</span>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if($user?->isSupplier()): ?>
                    <div class="space-y-1.5">
                        <a href="<?php echo e(route('portal.orders.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('portal.*') ? 'active' : ''); ?>">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Siparislerim</span>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if($user?->isAdmin() || $user?->isPurchasing()): ?>
                    <div class="space-y-2">
                        <div class="px-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Yonetim</p>
                        </div>
                        <div class="space-y-1.5">
                            <a href="<?php echo e(route('admin.suppliers.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.suppliers.*') ? 'active' : ''); ?>">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                <span>Tedarikciler</span>
                            </a>

                            <?php if($user->isAdmin()): ?>
                                <a href="<?php echo e(route('admin.users.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.users.*') ? 'active' : ''); ?>">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    <span>Kullanicilar</span>
                                </a>
                                <a href="<?php echo e(route('admin.settings.edit')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.settings.*') ? 'active' : ''); ?>">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317a1 1 0 011.35-.936l1.824.73a1 1 0 00.95-.08l1.52-1.014a1 1 0 011.475.616l.497 1.99a1 1 0 00.687.719l1.945.648a1 1 0 01.572 1.473l-.92 1.69a1 1 0 000 .956l.92 1.69a1 1 0 01-.572 1.473l-1.945.648a1 1 0 00-.687.719l-.497 1.99a1 1 0 01-1.475.616l-1.52-1.014a1 1 0 00-.95-.08l-1.824.73a1 1 0 01-1.35-.936l-.13-1.864a1 1 0 00-.53-.812l-1.63-.92a1 1 0 010-1.74l1.63-.92a1 1 0 00.53-.812l.13-1.864z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>Ayarlar</span>
                                </a>
                                <a href="<?php echo e(route('admin.reports.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.reports.*') ? 'active' : ''); ?>">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3v18m-6-6h12m4-8v14a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                                    <span>Raporlar</span>
                                </a>
                                <a href="<?php echo e(route('admin.artwork-gallery.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.artwork-gallery.*') ? 'active' : ''); ?>">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-10h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span>Artwork Galerisi</span>
                                </a>
                                <a href="<?php echo e(route('admin.logs.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.logs.*') ? 'active' : ''); ?>">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    <span>Sistem Loglari</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </nav>

            <div class="border-t border-slate-200/80 p-4">
                <div class="rounded-3xl border border-slate-200/80 bg-slate-50/80 p-3 shadow-[0_10px_25px_rgba(15,23,42,0.04)]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-brand-100 text-sm font-semibold text-brand-800 shadow-inner">
                            <?php echo e($userInitials); ?>

                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900"><?php echo e($user?->name); ?></p>
                            <p class="text-xs text-slate-500"><?php echo e($user?->role?->label()); ?></p>
                        </div>
                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-transparent text-slate-400 transition hover:border-slate-200 hover:bg-white hover:text-slate-700" title="Cikis">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="border-b border-slate-200/80 bg-white/85 px-4 py-4 backdrop-blur sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                            <span class="inline-block h-2 w-2 rounded-full bg-brand-500"></span>
                            Lider Portal
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold tracking-tight text-slate-950 sm:text-2xl"><?php echo $__env->yieldContent('page-title'); ?></h1>
                            <?php if($pageSubtitle !== ''): ?>
                                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500"><?php echo e($pageSubtitle); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <?php if($hasPageAside): ?>
                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-aside-toggle
                                data-aside-storage-key="<?php echo e($pageAsideStorageKey); ?>"
                                aria-controls="page-context-aside"
                            >
                                Yardimci Panel
                            </button>
                        <?php endif; ?>
                        <?php echo $__env->yieldContent('header-actions'); ?>
                    </div>
                </div>
            </header>

            <?php if(session('success')): ?>
                <?php if (isset($component)) { $__componentOriginal746de018ded8594083eb43be3f1332e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal746de018ded8594083eb43be3f1332e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.alert','data' => ['variant' => 'success','class' => 'mx-4 mt-4 sm:mx-6 lg:mx-8']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'success','class' => 'mx-4 mt-4 sm:mx-6 lg:mx-8']); ?>
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 flex-shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span><?php echo e(session('success')); ?></span>
                        </div>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $attributes = $__attributesOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__attributesOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $component = $__componentOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__componentOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
            <?php endif; ?>

            <?php if(session('warning')): ?>
                <?php if (isset($component)) { $__componentOriginal746de018ded8594083eb43be3f1332e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal746de018ded8594083eb43be3f1332e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.alert','data' => ['variant' => 'warning','class' => 'mx-4 mt-4 sm:mx-6 lg:mx-8']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'warning','class' => 'mx-4 mt-4 sm:mx-6 lg:mx-8']); ?>
                    <p class="font-medium"><?php echo e(session('warning')); ?></p>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $attributes = $__attributesOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__attributesOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $component = $__componentOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__componentOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
            <?php endif; ?>

            <?php if(session('error') || $errors->any()): ?>
                <?php if (isset($component)) { $__componentOriginal746de018ded8594083eb43be3f1332e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal746de018ded8594083eb43be3f1332e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.alert','data' => ['variant' => 'danger','class' => 'mx-4 mt-4 sm:mx-6 lg:mx-8']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'danger','class' => 'mx-4 mt-4 sm:mx-6 lg:mx-8']); ?>
                    <div>
                        <?php if(session('error')): ?>
                            <p class="font-medium"><?php echo e(session('error')); ?></p>
                        <?php endif; ?>
                        <?php if($errors->any()): ?>
                            <ul class="<?php echo e(session('error') ? 'mt-2' : ''); ?> list-disc list-inside space-y-0.5">
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><?php echo e($error); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $attributes = $__attributesOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__attributesOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $component = $__componentOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__componentOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
            <?php endif; ?>

            <main class="flex-1 px-4 py-5 sm:px-6 lg:px-8">
                <?php if($hasPageAside): ?>
                    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                        <div class="min-w-0">
                            <?php echo $__env->yieldContent('content'); ?>
                        </div>
                        <aside
                            id="page-context-aside"
                            class="page-context-aside space-y-4"
                            data-page-aside
                            data-aside-storage-key="<?php echo e($pageAsideStorageKey); ?>"
                            data-default-open="desktop"
                        >
                            <?php echo $__env->yieldContent('page-aside'); ?>
                        </aside>
                    </div>
                <?php else: ?>
                    <?php echo $__env->yieldContent('content'); ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/layouts/app.blade.php ENDPATH**/ ?>