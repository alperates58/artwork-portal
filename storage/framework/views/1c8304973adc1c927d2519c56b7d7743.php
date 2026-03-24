<?php $__env->startSection('title', 'Tedarikçiler'); ?>
<?php $__env->startSection('page-title', 'Tedarikçi Yönetimi'); ?>

<?php $__env->startSection('header-actions'); ?>
    <a href="<?php echo e(route('admin.suppliers.create')); ?>" class="btn btn-primary shadow-sm">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Yeni Tedarikçi
    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<form method="GET" class="flex flex-wrap gap-3 mb-5">
    <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="İsim veya kod ara..." class="input w-64">
    <button type="submit" class="btn btn-secondary">Ara</button>
    <?php if(request('search')): ?>
        <a href="<?php echo e(route('admin.suppliers.index')); ?>" class="btn btn-secondary text-slate-500">Temizle</a>
    <?php endif; ?>
</form>

<div class="card">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50">
                <th class="text-left px-4 py-3 font-medium text-slate-600">Tedarikçi</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Kod</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">İletişim</th>
                <th class="text-center px-4 py-3 font-medium text-slate-600">Kullanıcı</th>
                <th class="text-center px-4 py-3 font-medium text-slate-600">Sipariş</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Durum</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php $__empty_1 = true; $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supplier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-3">
                    <p class="font-medium text-slate-900"><?php echo e($supplier->name); ?></p>
                </td>
                <td class="px-4 py-3 font-mono text-slate-600 text-xs"><?php echo e($supplier->code); ?></td>
                <td class="px-4 py-3 text-slate-500 text-xs">
                    <?php echo e($supplier->email ?? '—'); ?><br><?php echo e($supplier->phone ?? ''); ?>

                </td>
                <td class="px-4 py-3 text-center text-slate-700"><?php echo e($supplier->users_count); ?></td>
                <td class="px-4 py-3 text-center text-slate-700"><?php echo e($supplier->purchase_orders_count); ?></td>
                <td class="px-4 py-3">
                    <?php if($supplier->is_active): ?>
                        <span class="badge badge-success">Aktif</span>
                    <?php else: ?>
                        <span class="badge badge-gray">Pasif</span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <a href="<?php echo e(route('admin.suppliers.show', $supplier)); ?>" class="text-slate-600 hover:underline text-xs">Detay</a>
                        <?php if(auth()->user()->isAdmin()): ?>
                            <a href="<?php echo e(route('admin.suppliers.edit', $supplier)); ?>" class="text-brand-700 hover:underline text-xs font-medium">Düzenle</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Tedarikçi bulunamadı.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if($suppliers->hasPages()): ?>
        <div class="px-4 py-3 border-t border-slate-100"><?php echo e($suppliers->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/suppliers/index.blade.php ENDPATH**/ ?>