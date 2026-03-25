<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page-title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['padding' => 'p-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['padding' => 'p-5']); ?>
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Artwork Bekliyor</p>
        <p class="text-2xl font-semibold text-slate-900"><?php echo e(number_format($metrics['pending_artwork'])); ?></p>
        <p class="text-xs text-amber-600 mt-1">Yuklenmemis satir</p>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $attributes = $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $component = $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['padding' => 'p-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['padding' => 'p-5']); ?>
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Artwork Yuklendi</p>
        <p class="text-2xl font-semibold text-slate-900"><?php echo e(number_format($metrics['uploaded_artwork'])); ?></p>
        <p class="text-xs text-emerald-600 mt-1">Aktif revizyonu olan</p>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $attributes = $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $component = $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['padding' => 'p-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['padding' => 'p-5']); ?>
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Onay Bekliyor</p>
        <p class="text-2xl font-semibold text-slate-900"><?php echo e(number_format($metrics['pending_approval'] ?? 0)); ?></p>
        <p class="text-xs text-blue-600 mt-1">Tedarikci onayi beklenen</p>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $attributes = $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $component = $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['padding' => 'p-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['padding' => 'p-5']); ?>
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Aktif Siparisler</p>
        <p class="text-2xl font-semibold text-slate-900"><?php echo e(number_format($metrics['active_orders'])); ?></p>
        <p class="text-xs text-slate-400 mt-1">Toplam aktif PO</p>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $attributes = $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $component = $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
            <h2 class="text-sm font-semibold text-slate-900">Son Yuklenen Artwork</h2>
            <a href="<?php echo e(route('orders.index')); ?>" class="text-xs text-brand-700 hover:underline">Tumu</a>
        </div>
        <div class="divide-y divide-slate-100">
            <?php $__empty_1 = true; $__currentLoopData = $panels['recent_revisions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $revision): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="px-5 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <span class="text-xs font-bold text-slate-500"><?php echo e($revision['extension']); ?></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate"><?php echo e($revision['filename']); ?></p>
                        <p class="text-xs text-slate-500"><?php echo e($revision['order_no']); ?> · Rev.<?php echo e($revision['revision_no']); ?></p>
                    </div>
                    <p class="text-xs text-slate-400 flex-shrink-0"><?php echo e($revision['created_at_human']); ?></p>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="px-5 py-8 text-center text-sm text-slate-400">Henuz artwork yuklenmemis.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
            <h2 class="text-sm font-semibold text-slate-900">Son Indirmeler</h2>
            <?php if(auth()->user()->isAdmin()): ?>
                <a href="<?php echo e(route('admin.logs.index')); ?>" class="text-xs text-brand-700 hover:underline">Loglar</a>
            <?php endif; ?>
        </div>
        <div class="divide-y divide-slate-100">
            <?php $__empty_1 = true; $__currentLoopData = $panels['recent_downloads']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $download): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="px-5 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-xs font-semibold text-blue-600"><?php echo e(strtoupper(substr($download['user_name'] ?: '?', 0, 2))); ?></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-900 truncate"><?php echo e($download['user_name']); ?></p>
                        <p class="text-xs text-slate-500"><?php echo e($download['filename']); ?></p>
                    </div>
                    <p class="text-xs text-slate-400 flex-shrink-0"><?php echo e($download['created_at_human']); ?></p>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="px-5 py-8 text-center text-sm text-slate-400">Henuz indirme yok.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if(auth()->user()->isAdmin()): ?>
<div class="card p-5 mt-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-sm font-semibold text-slate-900 mb-1">Mikro ERP Senkronizasyonu</h2>
            <p class="text-xs text-slate-500">Son sync: <?php echo e(!empty($panels['last_erp_sync']) ? \Illuminate\Support\Carbon::parse($panels['last_erp_sync'])->diffForHumans() : 'Henuz calistirilmadi'); ?></p>
        </div>
        <form method="POST" action="<?php echo e(route('admin.erp.sync')); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-secondary text-xs">Sync Calistir</button>
        </form>
    </div>
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/dashboard.blade.php ENDPATH**/ ?>