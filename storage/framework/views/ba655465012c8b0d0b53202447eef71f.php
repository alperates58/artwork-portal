
<?php $__env->startSection('title', 'Siparişlerim'); ?>
<?php $__env->startSection('page-title', 'Siparişlerim'); ?>

<?php $__env->startSection('content'); ?>


<div class="card p-4 mb-5 flex items-center gap-3 bg-gradient-to-br from-brand-50 to-white">
    <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
         style="background:linear-gradient(180deg,var(--brand-600),var(--brand-700))">
        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
        </svg>
    </div>
    <div>
        <p class="text-sm font-semibold text-slate-900">
            <?php echo e(auth()->user()->supplier->name ?? auth()->user()->mappedSuppliers->first()?->name ?? 'Tedarikçi Portalı'); ?>

        </p>
        <p class="text-xs text-slate-600">Güncel artwork dosyalarına bu sayfadan erişebilirsiniz.</p>
    </div>
</div>


<form method="GET" class="flex flex-wrap gap-3 mb-5">
    <?php if (isset($component)) { $__componentOriginal65bd7e7dbd93cec773ad6501ce127e46 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.input','data' => ['type' => 'text','name' => 'search','value' => ''.e(request('search')).'','placeholder' => 'Sipariş no ara...','class' => 'w-52']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'text','name' => 'search','value' => ''.e(request('search')).'','placeholder' => 'Sipariş no ara...','class' => 'w-52']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46)): ?>
<?php $attributes = $__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46; ?>
<?php unset($__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal65bd7e7dbd93cec773ad6501ce127e46)): ?>
<?php $component = $__componentOriginal65bd7e7dbd93cec773ad6501ce127e46; ?>
<?php unset($__componentOriginal65bd7e7dbd93cec773ad6501ce127e46); ?>
<?php endif; ?>
    <select name="status" class="input w-44">
        <option value="">Tüm durumlar</option>
        <option value="active"    <?php echo e(request('status') === 'active'    ? 'selected' : ''); ?>>Aktif</option>
        <option value="completed" <?php echo e(request('status') === 'completed' ? 'selected' : ''); ?>>Tamamlandı</option>
    </select>
    <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','type' => 'submit']); ?>Filtrele <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
</form>


<div class="space-y-3">
    <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="card hover:shadow-sm transition-shadow">
            <div class="p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono font-semibold text-slate-900"><?php echo e($order->order_no); ?></span>
                            <?php $cls = $order->status === 'active' ? 'badge-success' : 'badge-gray'; ?>
                            <?php if (isset($component)) { $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.badge','data' => ['variant' => str_replace('badge-', '', $cls)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(str_replace('badge-', '', $cls))]); ?><?php echo e($order->status_label); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $attributes = $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $component = $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-500">
                            <?php echo e($order->order_date->format('d.m.Y')); ?>

                            <?php if($order->due_date): ?> · Son: <?php echo e($order->due_date->format('d.m.Y')); ?> <?php endif; ?>
                            · <?php echo e($order->lines->count()); ?> ürün satırı
                        </p>
                    </div>
                    <a href="<?php echo e(route('portal.orders.show', $order)); ?>" class="btn btn-primary text-xs py-2">
                        Artwork'leri Gör →
                    </a>
                </div>

                
                <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-2">
                    <?php $__currentLoopData = $order->lines->take(6); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="bg-slate-50 rounded-lg px-3 py-2">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-medium text-slate-700 truncate"><?php echo e($line->product_code); ?></p>
                                <?php if($line->hasActiveArtwork()): ?>
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span>
                                <?php else: ?>
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">
                                <?php echo e($line->hasActiveArtwork() ? 'Hazır' : 'Bekliyor'); ?>

                            </p>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php if($order->lines->count() > 6): ?>
                        <div class="bg-slate-50 rounded-lg px-3 py-2 flex items-center justify-center">
                            <p class="text-xs text-slate-400">+<?php echo e($order->lines->count() - 6); ?> daha</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="card p-12 text-center">
            <svg class="w-10 h-10 text-slate-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm text-slate-400">Henüz sipariş kaydı bulunmuyor.</p>
        </div>
    <?php endif; ?>
</div>

<?php if($orders->hasPages()): ?>
    <div class="mt-4"><?php echo e($orders->links()); ?></div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/portal/orders/index.blade.php ENDPATH**/ ?>