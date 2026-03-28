<?php

use App\Http\Controllers\Admin\ArtworkGalleryController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DataTransferController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\MikroTestController;
use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Artwork\ArtworkGalleryPreviewController;
use App\Http\Controllers\Artwork\ArtworkController;
use App\Http\Controllers\Artwork\DownloadController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Order\OrderLineController;
use App\Http\Controllers\Portal\PortalOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendReset'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/search', [SearchController::class, 'search'])->name('search');
    Route::get('/bildirimler', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/bildirimler/okundu', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profil/sifre', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profil/foto', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::delete('/profil/foto', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');

    Route::middleware('role:supplier')
        ->prefix('portal')
        ->name('portal.')
        ->group(function () {
            Route::get('/siparisler', [PortalOrderController::class, 'index'])->name('orders.index');
            Route::get('/siparisler/{order}', [PortalOrderController::class, 'show'])->name('orders.show');
            Route::get('/indir/{revision}', [DownloadController::class, 'download'])->name('download');
        });

    Route::middleware('role:admin,purchasing,graphic')->group(function () {
        Route::get('/siparisler', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/siparisler/yeni', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/siparisler', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/siparisler/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('/siparisler/{order}/duzenle', [OrderController::class, 'edit'])->name('orders.edit');
        Route::patch('/siparisler/{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::delete('/siparisler/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
        Route::post('/siparisler/{order}/notlar', [OrderController::class, 'storeNote'])->name('orders.notes.store');

        Route::get('/satir/{line}', [OrderLineController::class, 'show'])->name('order-lines.show');

        Route::get('/satir/{line}/artwork/yukle', [ArtworkController::class, 'create'])->name('artworks.create');
        Route::post('/satir/{line}/artwork', [ArtworkController::class, 'store'])->name('artworks.store');
        Route::get('/revizyon/{revision}', [ArtworkController::class, 'show'])->name('artworks.show');
        Route::patch('/revizyon/{revision}/aktif', [ArtworkController::class, 'activate'])->name('artworks.activate');
        Route::delete('/revizyon/{revision}', [ArtworkController::class, 'destroy'])->name('artworks.destroy');
        Route::get('/satir/{line}/revizyonlar', [ArtworkController::class, 'revisions'])->name('artworks.revisions');
        Route::get('/indir/{revision}', [DownloadController::class, 'download'])->name('artwork.download');
        Route::get('/galeri/{artworkGallery}/onizleme', ArtworkGalleryPreviewController::class)->name('artworks.gallery.preview');
    });

    Route::middleware('role:admin,purchasing')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/tedarikciler', [SupplierController::class, 'index'])->name('suppliers.index');
            Route::get('/tedarikciler/yeni', [SupplierController::class, 'create'])->name('suppliers.create');
            Route::post('/tedarikciler', [SupplierController::class, 'store'])->name('suppliers.store');
            Route::get('/tedarikciler/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
        });

    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/kullanicilar', [UserController::class, 'index'])->name('users.index');
            Route::get('/kullanicilar/yeni', [UserController::class, 'create'])->name('users.create');
            Route::post('/kullanicilar', [UserController::class, 'store'])->name('users.store');
            Route::get('/kullanicilar/{user}/duzenle', [UserController::class, 'edit'])->name('users.edit');
            Route::patch('/kullanicilar/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('/kullanicilar/{user}/aktif-tgl', [UserController::class, 'toggleActive'])->name('users.toggle');
            Route::delete('/kullanicilar/{user}', [UserController::class, 'destroy'])->name('users.destroy');

            Route::get('/tedarikciler/{supplier}/duzenle', [SupplierController::class, 'edit'])->name('suppliers.edit');
            Route::patch('/tedarikciler/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
            Route::delete('/tedarikciler/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

            Route::get('/yetkiler', [PermissionsController::class, 'index'])->name('permissions.index');
            Route::get('/yetkiler/{user}', [PermissionsController::class, 'show'])->name('permissions.show');
            Route::put('/yetkiler/{user}', [PermissionsController::class, 'update'])->name('permissions.update');
            Route::post('/yetkiler/{user}/sifirla', [PermissionsController::class, 'reset'])->name('permissions.reset');

            Route::get('/departmanlar', [DepartmentController::class, 'index'])->name('departments.index');
            Route::get('/departmanlar/yeni', [DepartmentController::class, 'create'])->name('departments.create');
            Route::post('/departmanlar', [DepartmentController::class, 'store'])->name('departments.store');
            Route::get('/departmanlar/{department}/duzenle', [DepartmentController::class, 'edit'])->name('departments.edit');
            Route::patch('/departmanlar/{department}', [DepartmentController::class, 'update'])->name('departments.update');
            Route::delete('/departmanlar/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

            Route::get('/ayarlar', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/ayarlar', [SettingsController::class, 'update'])->name('settings.update');
            Route::post('/ayarlar/mail-connection-test', [SettingsController::class, 'testMailConnection'])->name('settings.mail-connection-test');
            Route::post('/ayarlar/mail-test', [SettingsController::class, 'sendTestMail'])->name('settings.mail-test');
            Route::post('/ayarlar/update-check', [SettingsController::class, 'checkUpdates'])->name('settings.update-check');
            Route::post('/ayarlar/update-prepare', [SettingsController::class, 'prepareUpdate'])->name('settings.update-prepare');
            Route::post('/ayarlar/deploy', [SettingsController::class, 'deploy'])->name('settings.deploy');
            Route::post('/ayarlar/apply-only', [SettingsController::class, 'applyOnly'])->name('settings.apply-only');
            Route::get('/integrations/mikro/test', MikroTestController::class)->name('integrations.mikro.test');

            Route::get('/veri-aktarimi', [DataTransferController::class, 'index'])->name('data-transfer.index');
            Route::get('/veri-aktarimi/export', [DataTransferController::class, 'export'])->name('data-transfer.export');
            Route::post('/veri-aktarimi/import', [DataTransferController::class, 'import'])->name('data-transfer.import');
            Route::delete('/veri-aktarimi/imported', [DataTransferController::class, 'destroyImported'])->name('data-transfer.destroy-imported');
        });

    // Reports, Gallery, Logs — accessible to all internal roles; fine-grained access enforced in controllers via hasPermission()
    Route::middleware('role:admin,purchasing,graphic')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/raporlar', [ReportController::class, 'index'])->name('reports.index');
            Route::get('/raporlar/lead-time', [ReportController::class, 'leadTime'])->name('reports.lead-time');
            Route::get('/raporlar/bekleyen', [ReportController::class, 'pending'])->name('reports.pending');
            Route::get('/raporlar/kategori', [ReportController::class, 'category'])->name('reports.category');
            Route::get('/raporlar/stok-kodu', [ReportController::class, 'stockCode'])->name('reports.stock-code');
            Route::get('/raporlar/aktivite', [ReportController::class, 'timeline'])->name('reports.timeline');
            Route::get('/loglar', [AuditLogController::class, 'index'])->name('logs.index');
            Route::get('/artwork-galerisi', [ArtworkGalleryController::class, 'index'])->name('artwork-gallery.index');
            Route::get('/artwork-galerisi/yonetim', [ArtworkGalleryController::class, 'manage'])->name('artwork-gallery.manage');
            Route::post('/artwork-galerisi/kategoriler', [ArtworkGalleryController::class, 'storeCategory'])->name('artwork-gallery.categories.store');
            Route::delete('/artwork-galerisi/kategoriler/{category}', [ArtworkGalleryController::class, 'destroyCategory'])->name('artwork-gallery.categories.destroy');
            Route::post('/artwork-galerisi/etiketler', [ArtworkGalleryController::class, 'storeTag'])->name('artwork-gallery.tags.store');
            Route::delete('/artwork-galerisi/etiketler/{tag}', [ArtworkGalleryController::class, 'destroyTag'])->name('artwork-gallery.tags.destroy');
            Route::get('/artwork-galerisi/{artworkGallery}/duzenle', [ArtworkGalleryController::class, 'edit'])->name('artwork-gallery.edit');
            Route::patch('/artwork-galerisi/{artworkGallery}', [ArtworkGalleryController::class, 'update'])->name('artwork-gallery.update');
            Route::delete('/artwork-galerisi/{artworkGallery}', [ArtworkGalleryController::class, 'destroy'])->name('artwork-gallery.destroy');
        });

    Route::post('/admin/erp/sync', [\App\Http\Controllers\Admin\ErpSyncController::class, 'sync'])
        ->middleware('role:admin')
        ->name('admin.erp.sync');
    Route::post('/admin/tedarikciler/{supplier}/mikro-sync', [\App\Http\Controllers\Admin\ErpSyncController::class, 'syncSupplier'])
        ->middleware('role:admin')
        ->name('admin.suppliers.sync');

    Route::middleware(['auth', 'active', 'role:supplier'])->group(function () {
        Route::post('/revizyon/{revision}/gordum', [\App\Http\Controllers\Faz2\ApprovalController::class, 'confirmSeen'])->name('approval.seen');
        Route::post('/revizyon/{revision}/onayla', [\App\Http\Controllers\Faz2\ApprovalController::class, 'approve'])->name('approval.approve');
    });
});
