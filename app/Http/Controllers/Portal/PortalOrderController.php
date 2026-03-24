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
        $user = $request->user()->loadMissing([
            'supplier:id,name',
            'supplierMappings',
            'mappedSuppliers:id,name',
        ]);

        $orders = PurchaseOrder::query()
            ->whereIn('supplier_id', $user->accessibleSupplierIds()->all())
            ->with([
                'supplier:id,name',
                'lines' => fn ($query) => $query->select('id', 'purchase_order_id', 'line_no', 'product_code', 'description', 'quantity', 'unit'),
                'lines.artwork:id,order_line_id',
                'lines.artwork.activeRevision:id,artwork_id',
            ])
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->when($request->search, fn ($query) => $query->search($request->search))
            ->orderByDesc('order_date')
            ->paginate(20)
            ->withQueryString();

        $supplierDisplayName = $user->supplier?->name
            ?? $user->mappedSuppliers->first()?->name
            ?? 'Lider Portal';

        return view('portal.orders.index', compact('orders', 'supplierDisplayName'));
    }

    public function show(PurchaseOrder $order): View
    {
        $user = auth()->user()->loadMissing('supplierMappings');

        abort_unless($user->canAccessOrder($order), 403);

        $order->load([
            'supplier',
            'lines.artwork.activeRevision.uploadedBy',
        ]);

        $this->artworkUploadService->logViews(
            $order->lines->pluck('activeRevision')->filter(),
            $user,
            $order->supplier_id
        );

        $this->audit->log('portal.order.view', $order, [
            'order_no' => $order->order_no,
        ]);

        return view('portal.orders.show', compact('order'));
    }
}
