<?php

namespace App\Http\Controllers;

use App\Models\ArtworkRevision;
use App\Models\AuditLog;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View|\Illuminate\Http\RedirectResponse
    {
        if (auth()->user()->isSupplier()) {
            return redirect()->route('portal.orders.index');
        }

        $metrics = Cache::remember('dashboard.metrics', 300, function () {
            return [
                'pending_artwork'  => PurchaseOrderLine::where('artwork_status', 'pending')->count(),
                'uploaded_artwork' => PurchaseOrderLine::where('artwork_status', 'uploaded')->count(),
                'pending_approval' => PurchaseOrderLine::where('artwork_status', 'revision')->count(),
                'active_orders'    => PurchaseOrder::where('status', 'active')->count(),
                'total_revisions'  => ArtworkRevision::count(),
            ];
        });

        $recentRevisions = ArtworkRevision::with([
            'uploadedBy',
            'artwork.orderLine.purchaseOrder.supplier',
        ])->orderByDesc('created_at')->limit(8)->get();

        $recentDownloads = AuditLog::with('user')
            ->where('action', 'artwork.download')
            ->orderByDesc('created_at')
            ->limit(8)->get();

        $lastErpSync = AuditLog::where('action', 'erp.sync')
            ->orderByDesc('created_at')->value('created_at');

        return view('dashboard', compact(
            'metrics', 'recentRevisions', 'recentDownloads', 'lastErpSync'
        ));
    }
}
