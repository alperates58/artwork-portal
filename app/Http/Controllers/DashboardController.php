<?php

namespace App\Http\Controllers;

use App\Models\ArtworkRevision;
use App\Models\AuditLog;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\SupplierMikroAccount;
use App\Services\DashboardCacheService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardCacheService $dashboardCache
    ) {}

    public function __invoke(): View|\Illuminate\Http\RedirectResponse
    {
        if (auth()->user()->isSupplier()) {
            return redirect()->route('portal.orders.index');
        }

        $metrics = $this->dashboardCache->rememberMetrics(function () {
            $pendingArtwork = PurchaseOrderLine::query()
                ->where('artwork_status', 'pending')
                ->count();

            $activeOrderLines = PurchaseOrderLine::query()
                ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
                ->where('purchase_orders.status', 'active')
                ->count();

            $stalledPendingArtwork = PurchaseOrderLine::query()
                ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
                ->where('purchase_orders.status', 'active')
                ->where('purchase_order_lines.artwork_status', 'pending')
                ->whereDate('purchase_orders.order_date', '<=', now()->subDays(7)->toDateString())
                ->count();

            $blockedOrders = PurchaseOrder::query()
                ->where('status', 'active')
                ->whereDate('order_date', '<=', now()->subDays(7)->toDateString())
                ->whereHas('lines', fn ($query) => $query->where('artwork_status', 'pending'))
                ->count();

            return [
                'pending_artwork' => $pendingArtwork,
                'uploaded_artwork' => PurchaseOrderLine::where('artwork_status', 'uploaded')->count(),
                'pending_approval' => PurchaseOrderLine::where('artwork_status', 'revision')->count(),
                'active_orders' => PurchaseOrder::where('status', 'active')->count(),
                'total_revisions' => ArtworkRevision::count(),
                'active_order_lines' => $activeOrderLines,
                'flow_pressure_pct' => $activeOrderLines > 0
                    ? round(($pendingArtwork / $activeOrderLines) * 100, 1)
                    : 0.0,
                'stalled_pending_artwork' => $stalledPendingArtwork,
                'blocked_orders' => $blockedOrders,
            ];
        });

        $panels = $this->dashboardCache->rememberPanels(function () {
            return [
                'recent_revisions' => ArtworkRevision::query()
                    ->with([
                        'artwork.orderLine.purchaseOrder:id,order_no,supplier_id',
                        'artwork.orderLine.purchaseOrder.supplier:id,name',
                    ])
                    ->orderByDesc('created_at')
                    ->limit(8)
                    ->get()
                    ->map(fn (ArtworkRevision $revision) => [
                        'extension' => $revision->extension,
                        'filename' => $revision->original_filename,
                        'order_no' => $revision->artwork->orderLine->purchaseOrder->order_no,
                        'revision_no' => $revision->revision_no,
                        'created_at_human' => $revision->created_at->diffForHumans(),
                    ])
                    ->all(),
                'recent_downloads' => AuditLog::query()
                    ->with('user:id,name')
                    ->where('action', 'artwork.download')
                    ->orderByDesc('created_at')
                    ->limit(8)
                    ->get()
                    ->map(fn (AuditLog $log) => [
                        'user_name' => $log->user?->name ?? '—',
                        'filename' => $log->payload['original_filename'] ?? '—',
                        'created_at_human' => $log->created_at->diffForHumans(),
                    ])
                    ->all(),
                'last_erp_sync' => AuditLog::query()
                    ->where('action', 'erp.sync')
                    ->orderByDesc('created_at')
                    ->value('created_at'),
                'last_supplier_sync' => SupplierMikroAccount::query()
                    ->whereNotNull('last_sync_at')
                    ->orderByDesc('last_sync_at')
                    ->value('last_sync_at'),
            ];
        });

        return view('dashboard', compact('metrics', 'panels'));
    }
}
