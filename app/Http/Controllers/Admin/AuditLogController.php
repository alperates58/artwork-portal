<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public const CATEGORIES = [
        'session' => ['user.login', 'user.logout'],
        'artwork' => ['artwork.upload', 'artwork.view', 'artwork.viewed', 'artwork.download', 'artwork.delete', 'artwork.approved', 'artwork.rejected', 'artwork.revision.activate', 'artwork.preview.started', 'artwork.preview.success', 'artwork.preview.failed'],
        'gallery' => ['artwork.gallery.create', 'artwork.gallery.reuse', 'artwork.gallery.update', 'artwork.gallery.delete', 'artwork.gallery.download', 'artwork.gallery.activate', 'artwork.gallery.deactivate'],
        'stock' => ['stock_card.create', 'stock_card.update', 'stock_card.delete', 'stock_card.import'],
        'supplier' => ['supplier.delete'],
        'order' => ['order.view', 'order.create', 'order.update', 'order.delete', 'order_line.view', 'portal.order.view', 'order_line.manual_artwork.complete'],
        'mail' => ['mail.notification.sent', 'mail.notification.failed', 'mail.notification.skipped', 'mail.notification.queued', 'mail.notification.queue_failed', 'mail.notification.test.sent', 'mail.notification.test.queued'],
        'erp' => ['erp.sync', 'mikro.test.success', 'mikro.test.failed'],
    ];

    public const CATEGORY_LABELS = [
        'session' => 'Oturum',
        'artwork' => 'Artwork',
        'gallery' => 'Galeri',
        'stock' => 'Stok',
        'supplier' => 'Tedarikçi',
        'order' => 'Sipariş',
        'mail' => 'Mail',
        'erp' => 'ERP',
    ];

    public const ACTION_LABELS = [
        'user.login' => 'Giriş yapıldı',
        'user.logout' => 'Çıkış yapıldı',
        'artwork.upload' => 'Artwork yüklendi',
        'artwork.view' => 'Artwork görüntülendi',
        'artwork.viewed' => 'Artwork incelendi',
        'artwork.download' => 'Artwork indirildi',
        'artwork.delete' => 'Artwork silindi',
        'artwork.approved' => 'Artwork onaylandı',
        'artwork.rejected' => 'Artwork revize istendi',
        'artwork.revision.activate' => 'Revizyon aktifleştirildi',
        'artwork.preview.started' => 'Artwork önizleme üretimi başladı',
        'artwork.preview.success' => 'Artwork önizleme üretildi',
        'artwork.preview.failed' => 'Artwork önizleme üretilemedi',
        'artwork.gallery.create' => 'Galeriye eklendi',
        'artwork.gallery.reuse' => 'Galeriden kullanıldı',
        'artwork.gallery.update' => 'Galeri güncellendi',
        'artwork.gallery.delete' => 'Galeride silindi',
        'artwork.gallery.download' => 'Galeriden indirildi',
        'artwork.gallery.activate' => 'Galeri kaydı aktifleştirildi',
        'artwork.gallery.deactivate' => 'Galeri kaydı pasife alındı',
        'stock_card.create' => 'Stok kartı oluşturuldu',
        'stock_card.update' => 'Stok kartı güncellendi',
        'stock_card.delete' => 'Stok kartı silindi',
        'stock_card.import' => 'Stok kartları içe aktarıldı',
        'supplier.delete' => 'Tedarikçi arşivlendi',
        'order.view' => 'Sipariş görüntülendi',
        'order.create' => 'Sipariş oluşturuldu',
        'order.update' => 'Sipariş güncellendi',
        'order.delete' => 'Sipariş silindi',
        'order_line.view' => 'Sipariş satırı görüntülendi',
        'order_line.manual_artwork.complete' => 'Sipariş satırı manuel gönderildi olarak işaretlendi',
        'portal.order.view' => 'Portal sipariş görüntülendi',
        'mail.notification.sent' => 'Mail gönderildi',
        'mail.notification.failed' => 'Mail gönderilemedi',
        'mail.notification.skipped' => 'Mail atlandı',
        'mail.notification.queued' => 'Mail kuyruğa alındı',
        'mail.notification.queue_failed' => 'Mail kuyruğu başarısız',
        'mail.notification.test.sent' => 'Test maili gönderildi',
        'mail.notification.test.queued' => 'Test maili kuyruğa alındı',
        'erp.sync' => 'ERP senkronizasyonu',
        'mikro.test.success' => 'Mikro bağlantı başarılı',
        'mikro.test.failed' => 'Mikro bağlantı başarısız',
    ];

    public const ACTION_COLORS = [
        'user.login' => 'slate',
        'user.logout' => 'slate',
        'artwork.upload' => 'violet',
        'artwork.download' => 'blue',
        'artwork.approved' => 'emerald',
        'artwork.rejected' => 'orange',
        'artwork.delete' => 'red',
        'artwork.preview.started' => 'amber',
        'artwork.preview.success' => 'emerald',
        'artwork.preview.failed' => 'red',
        'artwork.gallery.create' => 'blue',
        'artwork.gallery.delete' => 'red',
        'artwork.gallery.activate' => 'emerald',
        'artwork.gallery.deactivate' => 'amber',
        'stock_card.create' => 'emerald',
        'stock_card.update' => 'blue',
        'stock_card.delete' => 'red',
        'stock_card.import' => 'amber',
        'supplier.delete' => 'red',
        'supplier.delete' => 'Tedarikçi arşivlendi',
        'order.create' => 'amber',
        'order.update' => 'amber',
        'order.delete' => 'red',
        'order.view' => 'slate',
        'order_line.manual_artwork.complete' => 'emerald',
        'portal.order.view' => 'slate',
        'mail.notification.sent' => 'emerald',
        'mail.notification.failed' => 'red',
        'mail.notification.queue_failed' => 'red',
        'mikro.test.failed' => 'red',
        'mikro.test.success' => 'emerald',
        'erp.sync' => 'rose',
    ];

    private const PAYLOAD_LABELS = [
        'order_no' => 'Sipariş',
        'order_id' => 'Sipariş ID',
        'filename' => 'Dosya',
        'original_filename' => 'Dosya',
        'file' => 'Dosya',
        'product_code' => 'Stok Kodu',
        'description' => 'Açıklama',
        'supplier' => 'Tedarikçi',
        'supplier_name' => 'Tedarikçi',
        'supplier_id' => 'Tedarikçi ID',
        'line_no' => 'Satır',
        'status' => 'Durum',
        'revision' => 'Revizyon',
        'revision_no' => 'Revizyon No',
        'note' => 'Not',
        'notes' => 'Not',
        'subject' => 'Konu',
        'to' => 'Alıcı',
        'recipient' => 'Alıcı',
        'error' => 'Hata',
        'message' => 'Mesaj',
        'result' => 'Sonuç',
        'triggered_by' => 'Tetikleyen',
        'mode' => 'Mod',
        'strategy' => 'Strateji',
        'file_size' => 'Boyut',
        'reason' => 'Neden',
        'type' => 'Tür',
    ];

    public function index(Request $request): View
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('logs', 'view'),
            403
        );

        $selectedCategory = $request->input('category');
        $selectedAction = $request->input('action');
        $selectedUserId = $request->input('user_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $categoryActions = $selectedCategory ? (self::CATEGORIES[$selectedCategory] ?? []) : [];

        $logs = AuditLog::query()
            ->select(['id', 'user_id', 'action', 'model_type', 'model_id', 'payload', 'ip_address', 'created_at'])
            ->with('user:id,name,role')
            ->when($selectedUserId, fn ($q) => $q->where('user_id', $selectedUserId))
            ->when($selectedAction, fn ($q) => $q->where('action', $selectedAction))
            ->when($categoryActions, fn ($q) => $q->whereIn('action', $categoryActions))
            ->when($dateFrom, fn ($q) => $q->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay()))
            ->when($dateTo, fn ($q) => $q->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay()))
            ->orderByDesc('created_at')
            ->simplePaginate(50)
            ->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name', 'role']);

        $selectedUser = $selectedUserId ? $users->firstWhere('id', $selectedUserId) : null;
        $userStats = null;

        if ($selectedUser) {
            $userStats = AuditLog::where('user_id', $selectedUserId)
                ->selectRaw('action, COUNT(*) as cnt')
                ->when($dateFrom, fn ($q) => $q->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay()))
                ->when($dateTo, fn ($q) => $q->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay()))
                ->groupBy('action')
                ->orderByDesc('cnt')
                ->pluck('cnt', 'action');
        }

        $categoryCounts = collect(self::CATEGORIES)->map(
            fn ($actions) => AuditLog::whereIn('action', $actions)->count()
        );

        $orderNumbers = PurchaseOrder::orderByDesc('id')->limit(300)->pluck('order_no');
        $stockCodes = PurchaseOrderLine::distinct()->orderBy('product_code')->limit(500)->pluck('product_code')->filter();
        $suppliers = Supplier::whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code']);

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
            'orderNumbers',
            'stockCodes',
            'suppliers',
        ));
    }

    public function timeline(Request $request): JsonResponse
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('logs', 'view'),
            403
        );

        $searchType = $request->input('search_type', 'order_no');
        $searchValue = trim((string) $request->input('search_value', ''));

        if (strlen($searchValue) < 1) {
            return response()->json(['logs' => [], 'count' => 0, 'meta' => null]);
        }

        $query = AuditLog::query()
            ->select(['id', 'user_id', 'action', 'payload', 'ip_address', 'created_at'])
            ->with('user:id,name,role')
            ->orderByDesc('created_at')
            ->limit(200);

        if ($searchType === 'order_no') {
            $query->whereNotNull('payload')
                ->where('payload', 'like', '%"order_no":"' . addslashes($searchValue) . '"%');

            $meta = ['type' => 'Sipariş No', 'value' => $searchValue];
        } elseif ($searchType === 'stock_code') {
            $orderNos = PurchaseOrderLine::where('product_code', $searchValue)
                ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
                ->pluck('purchase_orders.order_no')
                ->unique()
                ->values();

            if ($orderNos->isEmpty()) {
                return response()->json(['logs' => [], 'count' => 0, 'meta' => ['type' => 'Stok Kodu', 'value' => $searchValue]]);
            }

            $patterns = $orderNos->map(fn ($no) => '"order_no":"' . addslashes($no) . '"')->all();

            $query->whereNotNull('payload')
                ->where(function ($q) use ($patterns) {
                    foreach ($patterns as $pattern) {
                        $q->orWhere('payload', 'like', "%$pattern%");
                    }
                });

            $meta = ['type' => 'Stok Kodu', 'value' => $searchValue, 'order_count' => $orderNos->count(), 'orders' => $orderNos->all()];
        } elseif ($searchType === 'supplier_id') {
            $supplier = Supplier::find($searchValue);

            if (! $supplier) {
                return response()->json(['logs' => [], 'count' => 0, 'meta' => null]);
            }

            $orderNos = PurchaseOrder::where('supplier_id', $searchValue)->pluck('order_no');

            if ($orderNos->isEmpty()) {
                return response()->json(['logs' => [], 'count' => 0, 'meta' => ['type' => 'Tedarikçi', 'value' => $supplier->name]]);
            }

            $patterns = $orderNos->map(fn ($no) => '"order_no":"' . addslashes($no) . '"')->all();

            $query->whereNotNull('payload')
                ->where(function ($q) use ($patterns) {
                    foreach ($patterns as $pattern) {
                        $q->orWhere('payload', 'like', "%$pattern%");
                    }
                });

            $meta = ['type' => 'Tedarikçi', 'value' => $supplier->name, 'order_count' => $orderNos->count()];
        } else {
            return response()->json(['logs' => [], 'count' => 0, 'meta' => null]);
        }

        $logs = $query->get()->map(function (AuditLog $log) {
            $payload = $log->payload ?? [];
            $details = [];

            foreach ($payload as $k => $v) {
                if (is_scalar($v) && $v !== '' && $v !== null) {
                    $details[] = [
                        'key' => self::PAYLOAD_LABELS[$k] ?? $k,
                        'value' => (string) $v,
                    ];
                }
            }

            return [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => self::ACTION_LABELS[$log->action] ?? $log->action,
                'color' => self::ACTION_COLORS[$log->action] ?? 'slate',
                'category' => $this->actionCategory($log->action),
                'user_name' => $log->user?->name ?? 'Silinmiş kullanıcı',
                'user_role' => $log->user?->role?->label() ?? '',
                'user_initials' => $log->user ? strtoupper(mb_substr($log->user->name, 0, 2)) : '??',
                'ip' => $log->ip_address,
                'details' => $details,
                'date' => $log->created_at->format('d.m.Y'),
                'time' => $log->created_at->format('H:i:s'),
                'datetime' => $log->created_at->toIso8601String(),
                'day_group' => $log->created_at->format('d.m.Y'),
            ];
        });

        return response()->json([
            'logs' => $logs,
            'count' => $logs->count(),
            'meta' => $meta,
        ]);
    }

    private function actionCategory(string $action): string
    {
        foreach (self::CATEGORIES as $cat => $actions) {
            if (in_array($action, $actions, true)) {
                return $cat;
            }
        }

        return 'session';
    }
}

