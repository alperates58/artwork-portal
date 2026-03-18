<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Services\ArtworkUploadService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalOrderController extends Controller
{
    public function __construct(
        private AuditLogService $audit,
        private ArtworkUploadService $artworkUploadService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user()->loadMissing('supplierMappings');

        $orders = PurchaseOrder::query()
            ->whereIn('supplier_id', $user->accessibleSupplierIds())
            ->with([
                'supplier',
                'lines.artwork.activeRevision',
            ])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->search($request->search))
            ->orderByDesc('order_date')
            ->paginate(20)
            ->withQueryString();

        return view('portal.orders.index', compact('orders'));
    }

    public function show(PurchaseOrder $order): View
    {
        $user = auth()->user()->loadMissing('supplierMappings');

        abort_unless($user->canAccessOrder($order), 403);

        $order->load([
            'supplier',
            'lines.artwork.activeRevision.uploadedBy',
        ]);

        foreach ($order->lines as $line) {
            if ($line->activeRevision) {
                $this->artworkUploadService->logView($line->activeRevision, $user);
            }
        }

        $this->audit->log('portal.order.view', $order, [
            'order_no' => $order->order_no,
        ]);

        return view('portal.orders.show', compact('order'));
    }
}
