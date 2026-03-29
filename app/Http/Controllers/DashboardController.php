<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Services\DashboardCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardCacheService $dashboardCache
    ) {}

    public function __invoke(): View|RedirectResponse
    {
        if (auth()->user()->isSupplier()) {
            return redirect()->route('portal.orders.index');
        }

        $metrics = $this->dashboardCache->rememberMetrics(function (): array {
            $activeLinesBase = PurchaseOrderLine::query()
                ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
                ->where('purchase_orders.status', 'active');

            $activeOrderLines = (clone $activeLinesBase)->count();
            $pendingArtwork = (clone $activeLinesBase)
                ->where('purchase_order_lines.artwork_status', 'pending')
                ->count();
            $uploadedArtwork = (clone $activeLinesBase)
                ->whereIn('purchase_order_lines.artwork_status', ['uploaded', 'revision', 'approved'])
                ->count();
            $pendingApproval = (clone $activeLinesBase)
                ->where('purchase_order_lines.artwork_status', 'revision')
                ->count();
            $approvedArtwork = (clone $activeLinesBase)
                ->where('purchase_order_lines.artwork_status', 'approved')
                ->count();

            $stalledPendingArtwork = (clone $activeLinesBase)
                ->where('purchase_order_lines.artwork_status', 'pending')
                ->whereDate('purchase_orders.order_date', '<=', now()->subDays(7)->toDateString())
                ->count();

            $blockedOrders = PurchaseOrder::query()
                ->where('status', 'active')
                ->whereDate('order_date', '<=', now()->subDays(7)->toDateString())
                ->whereHas('lines', fn ($query) => $query->where('artwork_status', 'pending'))
                ->count();

            return [
                'active_orders' => PurchaseOrder::query()->where('status', 'active')->count(),
                'active_order_lines' => $activeOrderLines,
                'pending_artwork' => $pendingArtwork,
                'uploaded_artwork' => $uploadedArtwork,
                'pending_approval' => $pendingApproval,
                'approved_artwork' => $approvedArtwork,
                'stalled_pending_artwork' => $stalledPendingArtwork,
                'blocked_orders' => $blockedOrders,
                'flow_pressure_pct' => $activeOrderLines > 0
                    ? round(($pendingArtwork / $activeOrderLines) * 100, 1)
                    : 0.0,
                'upload_completion_pct' => $activeOrderLines > 0
                    ? round(($uploadedArtwork / $activeOrderLines) * 100, 1)
                    : 0.0,
                'approval_completion_pct' => $activeOrderLines > 0
                    ? round(($approvedArtwork / $activeOrderLines) * 100, 1)
                    : 0.0,
            ];
        });

        return view('dashboard', compact('metrics'));
    }
}
