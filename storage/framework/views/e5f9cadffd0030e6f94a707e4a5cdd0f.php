
<?php $__env->startSection('title', 'Kullanıcılar'); ?>
<?php $__env->startSection('page-title', 'Kullanıcı Yönetimi'); ?>
<?php $__env->startSection('header-actions'); ?>
    <a href="<?php echo e(route('admin.users.create')); ?>" class="btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Yeni Kullanıcı
    </a>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<form method="GET" class="flex gap-3 mb-5">
    <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="İsim veya e-posta ara..." class="input w-64">
    <select name="role" class="input w-44">
        <option value="">Tüm roller</option>
        <?php $__currentLoopData = App\Enums\UserRole::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($role->value); ?>" <?php echo e(request('role') === $role->value ? 'selected' : ''); ?>><?php echo e($role->label()); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button type="submit" class="btn-secondary">Filtrele</button>
</form>

<div class="card">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50">
                <th class="text-left px-4 py-3 font-medium text-slate-600">Kullanıcı</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Rol</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Tedarikçi</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Son Giriş</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Durum</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-3">
                    <p class="font-medium text-slate-900"><?php echo e($user->name); ?></p>
                    <p class="text-xs text-slate-500"><?php echo e($user->email); ?></p>
                </td>
                <td class="px-4 py-3">
                    <?php $roleCls = match($user->role->value) {
                        'admin' => 'badge-danger', 'graphic' => 'badge-info',
                        'purchasing' => 'badge-warning', default => 'badge-gray'
                    }; ?>
                    <span class="badge <?php echo e($roleCls); ?>"><?php echo e($user->role->label()); ?></span>
                </td>
                <td class="px-4 py-3 text-slate-600 text-sm"><?php echo e($user->supplier?->name ?? '—'); ?></td>
                <td class="px-4 py-3 text-slate-500 text-xs">
                    <?php echo e($user->last_login_at?->format('d.m.Y H:i') ?? 'Hiç giriş yapmadı'); ?>

                </td>
                <td class="px-4 py-3">
                    <?php if($user->is_active): ?>
                        <span class="badge badge-success">Aktif</span>
                    <?php else: ?>
                        <span class="badge badge-gray">Pasif</span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-right flex items-center justify-end gap-3">
                    <a href="<?php echo e(route('admin.users.edit', $user)); ?>" class="text-blue-600 hover:underline text-xs">Düzenle</a>
                    <?php if($user->id !== auth()->id()): ?>
                        <form method="POST" action="<?php echo e(route('admin.users.toggle', $user)); ?>">
                            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                            <button type="submit" class="text-xs <?php echo e($user->is_active ? 'text-red-500' : 'text-emerald-600'); ?> hover:underline">
                                <?php echo e($user->is_active ? 'Pasife Al' : 'Aktif Et'); ?>

                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Kullanıcı bulunamadı.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if($users->hasPages()): ?>
        <div class="px-4 py-3 border-t border-slate-100"><?php echo e($users->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/users/index.blade.php ENDPATH**/ ?>