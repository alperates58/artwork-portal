<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (auth()->user()->isSupplier()) {
            return redirect()->route('portal.orders.index');
        }

        $metrics = (function (): array {
            $trackedOrdersBase = PurchaseOrder::query()
                ->where(fn ($query) => $query
                    ->whereNull('status')
                    ->orWhere('status', '!=', 'cancelled'));

            $trackedLinesBase = PurchaseOrderLine::query()
                ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
                ->where(fn ($query) => $query
                    ->whereNull('purchase_orders.status')
                    ->orWhere('purchase_orders.status', '!=', 'cancelled'));

            $totalOrderLines = (clone $trackedLinesBase)->count();
            $pendingArtwork = (clone $trackedLinesBase)
                ->where('purchase_order_lines.artwork_status', 'pending')
                ->count();
            $uploadedArtwork = (clone $trackedLinesBase)
                ->whereIn('purchase_order_lines.artwork_status', ['uploaded', 'revision', 'approved'])
                ->count();
            $pendingApproval = (clone $trackedLinesBase)
                ->where('purchase_order_lines.artwork_status', 'revision')
                ->count();
            $approvedArtwork = (clone $trackedLinesBase)
                ->where('purchase_order_lines.artwork_status', 'approved')
                ->count();

            $stalledPendingArtwork = (clone $trackedLinesBase)
                ->where('purchase_order_lines.artwork_status', 'pending')
                ->whereDate('purchase_orders.order_date', '<=', now()->subDays(7)->toDateString())
                ->count();

            $blockedOrders = (clone $trackedOrdersBase)
                ->whereDate('order_date', '<=', now()->subDays(7)->toDateString())
                ->whereHas('lines', fn ($query) => $query->where('artwork_status', 'pending'))
                ->count();

            return [
                'tracked_orders' => (clone $trackedOrdersBase)->count(),
                'active_orders' => PurchaseOrder::query()->where('status', 'active')->count(),
                'active_order_lines' => $totalOrderLines,
                'pending_artwork' => $pendingArtwork,
                'uploaded_artwork' => $uploadedArtwork,
                'pending_approval' => $pendingApproval,
                'approved_artwork' => $approvedArtwork,
                'stalled_pending_artwork' => $stalledPendingArtwork,
                'blocked_orders' => $blockedOrders,
                'flow_pressure_pct' => $totalOrderLines > 0
                    ? round(($pendingArtwork / $totalOrderLines) * 100, 1)
                    : 0.0,
                'upload_completion_pct' => $totalOrderLines > 0
                    ? round(($uploadedArtwork / $totalOrderLines) * 100, 1)
                    : 0.0,
                'approval_completion_pct' => $totalOrderLines > 0
                    ? round(($approvedArtwork / $totalOrderLines) * 100, 1)
                    : 0.0,
            ];
        })();

        return view('dashboard', compact('metrics'));
    }
}
