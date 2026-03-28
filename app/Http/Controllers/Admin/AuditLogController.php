<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    // ── Kategori → action eşlemeleri ─────────────────────────────────
    public const CATEGORIES = [
        'session'  => ['user.login', 'user.logout'],
        'artwork'  => ['artwork.upload', 'artwork.view', 'artwork.viewed', 'artwork.download', 'artwork.delete', 'artwork.approved', 'artwork.rejected', 'artwork.revision.activate'],
        'gallery'  => ['artwork.gallery.create', 'artwork.gallery.reuse', 'artwork.gallery.update', 'artwork.gallery.delete'],
        'order'    => ['order.view', 'order.create', 'order.update', 'order.delete', 'order_line.view', 'portal.order.view'],
        'mail'     => ['mail.notification.sent', 'mail.notification.failed', 'mail.notification.skipped', 'mail.notification.queued', 'mail.notification.queue_failed', 'mail.notification.test.sent', 'mail.notification.test.queued'],
        'erp'      => ['erp.sync', 'mikro.test.success', 'mikro.test.failed'],
    ];

    public const CATEGORY_LABELS = [
        'session' => 'Oturum',
        'artwork' => 'Artwork',
        'gallery' => 'Galeri',
        'order'   => 'Sipariş',
        'mail'    => 'Mail',
        'erp'     => 'ERP',
    ];

    public const ACTION_LABELS = [
        'user.login'                          => 'Giriş yapıldı',
        'user.logout'                         => 'Çıkış yapıldı',
        'artwork.upload'                      => 'Artwork yüklendi',
        'artwork.view'                        => 'Artwork görüntülendi',
        'artwork.viewed'                      => 'Artwork incelendi',
        'artwork.download'                    => 'Artwork indirildi',
        'artwork.delete'                      => 'Artwork silindi',
        'artwork.approved'                    => 'Artwork onaylandı',
        'artwork.rejected'                    => 'Artwork revize istendi',
        'artwork.revision.activate'           => 'Revizyon aktifleştirildi',
        'artwork.gallery.create'              => 'Galeriye eklendi',
        'artwork.gallery.reuse'               => 'Galeriden kullanıldı',
        'artwork.gallery.update'              => 'Galeri güncellendi',
        'artwork.gallery.delete'              => 'Galeride silindi',
        'order.view'                          => 'Sipariş görüntülendi',
        'order.create'                        => 'Sipariş oluşturuldu',
        'order.update'                        => 'Sipariş güncellendi',
        'order.delete'                        => 'Sipariş silindi',
        'order_line.view'                     => 'Sipariş satırı görüntülendi',
        'portal.order.view'                   => 'Portal sipariş görüntülendi',
        'mail.notification.sent'              => 'Mail gönderildi',
        'mail.notification.failed'            => 'Mail gönderilemedi',
        'mail.notification.skipped'           => 'Mail atlandı',
        'mail.notification.queued'            => 'Mail kuyruğa alındı',
        'mail.notification.queue_failed'      => 'Mail kuyruğu başarısız',
        'mail.notification.test.sent'         => 'Test maili gönderildi',
        'mail.notification.test.queued'       => 'Test maili kuyruğa alındı',
        'erp.sync'                            => 'ERP senkronizasyonu',
        'mikro.test.success'                  => 'Mikro bağlantı başarılı',
        'mikro.test.failed'                   => 'Mikro bağlantı başarısız',
    ];

    public function index(Request $request): View
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('logs', 'view'),
            403
        );

        // Aktif filtreler
        $selectedCategory = $request->input('category');
        $selectedAction   = $request->input('action');
        $selectedUserId   = $request->input('user_id');
        $dateFrom         = $request->input('date_from');
        $dateTo           = $request->input('date_to');

        // Kategori seçiliyse o kategorinin action'larını derle
        $categoryActions = $selectedCategory ? (self::CATEGORIES[$selectedCategory] ?? []) : [];

        $logs = AuditLog::query()
            ->select(['id', 'user_id', 'action', 'model_type', 'model_id', 'payload', 'ip_address', 'created_at'])
            ->with('user:id,name,role')
            ->when($selectedUserId,   fn ($q) => $q->where('user_id', $selectedUserId))
            ->when($selectedAction,   fn ($q) => $q->where('action', $selectedAction))
            ->when($categoryActions,  fn ($q) => $q->whereIn('action', $categoryActions))
            ->when($dateFrom, fn ($q) => $q->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay()))
            ->when($dateTo,   fn ($q) => $q->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay()))
            ->orderByDesc('created_at')
            ->simplePaginate(50)
            ->withQueryString();

        // Kullanıcı listesi (combobox için)
        $users = User::orderBy('name')->get(['id', 'name', 'role']);

        // Seçili kullanıcı varsa özet istatistiklerini hesapla
        $selectedUser   = $selectedUserId ? $users->firstWhere('id', $selectedUserId) : null;
        $userStats      = null;
        if ($selectedUser) {
            $userStats = AuditLog::where('user_id', $selectedUserId)
                ->selectRaw('action, COUNT(*) as cnt')
                ->when($dateFrom, fn ($q) => $q->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay()))
                ->when($dateTo,   fn ($q) => $q->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay()))
                ->groupBy('action')
                ->orderByDesc('cnt')
                ->pluck('cnt', 'action');
        }

        // Genel kategori sayıları (aktif filtreler olmadan — toplam)
        $categoryCounts = collect(self::CATEGORIES)->map(function ($actions) {
            return AuditLog::whereIn('action', $actions)->count();
        });

        return view('admin.logs.index', compact(
            'logs',
            'users',
            'selectedUser',
            'userStats',
            'categoryCounts',
            'selectedCategory',
            'selectedAction',
            'selectedUserId',
            'dateFrom',
            'dateTo',
        ));
    }
}
