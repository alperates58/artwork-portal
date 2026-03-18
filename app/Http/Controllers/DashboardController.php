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
    public function __invoke(): View
    {
        // Tedarikçiyi portal'a yönlendir
        if (auth()->user()->isSupplier()) {
            return redirect()->route('portal.orders.index');
        }

        // Metrikler — 5 dakika cache
        $metrics = Cache::remember('dashboard.metrics', 300, function () {
            return [
                'pending_artwork'    => PurchaseOrderLine::where('artwork_status', 'pending')->count(),
                'uploaded_artwork'   => PurchaseOrderLine::where('artwork_status', 'uploaded')->count(),
                'active_orders'      => PurchaseOrder::where('status', 'active')->count(),
                'total_revisions'    => ArtworkRevision::count(),
            ];
        });

        // Son yüklenen dosyalar (cache'siz — gerçek zamanlı)
        $recentRevisions = ArtworkRevision::with([
            'uploadedBy',
            'artwork.orderLine.purchaseOrder.supplier',
        ])
        ->orderByDesc('created_at')
        ->limit(8)
        ->get();

        // Son indirmeler
        $recentDownloads = AuditLog::with('user')
            ->where('action', 'artwork.download')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('dashboard', compact('metrics', 'recentRevisions', 'recentDownloads'));
    }
}
