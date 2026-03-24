<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtworkDownloadLog;
use App\Models\ArtworkRevision;
use App\Models\ArtworkViewLog;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $summary = [
            'active_orders' => PurchaseOrder::query()->where('status', 'active')->count(),
            'pending_artwork' => PurchaseOrderLine::query()->where('artwork_status', 'pending')->count(),
            'uploaded_today' => ArtworkRevision::query()->whereDate('created_at', today())->count(),
            'recent_downloads' => ArtworkDownloadLog::query()->where('downloaded_at', '>=', now()->subDays(7))->count(),
        ];

        $orderLeadTimes = PurchaseOrder::query()
            ->select([
                'purchase_orders.id',
                'purchase_orders.order_no',
                'purchase_orders.order_date',
                'purchase_orders.status',
                'suppliers.name as supplier_name',
                DB::raw('MIN(artwork_revisions.created_at) as first_upload_at'),
            ])
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->leftJoin('purchase_order_lines', 'purchase_order_lines.purchase_order_id', '=', 'purchase_orders.id')
            ->leftJoin('artworks', 'artworks.order_line_id', '=', 'purchase_order_lines.id')
            ->leftJoin('artwork_revisions', 'artwork_revisions.artwork_id', '=', 'artworks.id')
            ->groupBy('purchase_orders.id', 'purchase_orders.order_no', 'purchase_orders.order_date', 'purchase_orders.status', 'suppliers.name')
            ->orderByDesc('purchase_orders.order_date')
            ->limit(12)
            ->get()
            ->map(function ($row) {
                $firstUploadAt = $row->first_upload_at ? Carbon::parse($row->first_upload_at) : null;

                return (object) [
                    'order_no' => $row->order_no,
                    'supplier_name' => $row->supplier_name,
                    'order_date' => Carbon::parse($row->order_date),
                    'status' => $row->status,
                    'first_upload_at' => $firstUploadAt,
                    'lead_days' => $firstUploadAt ? Carbon::parse($row->order_date)->diffInDays($firstUploadAt) : null,
                ];
            });

        $supplierActivity = Supplier::query()
            ->select(['suppliers.id', 'suppliers.name'])
            ->withCount([
                'purchaseOrders',
                'purchaseOrders as active_orders_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->orderByDesc('purchase_orders_count')
            ->limit(8)
            ->get();

        $recentTimeline = collect([
            ...ArtworkRevision::query()
                ->with('artwork.orderLine.purchaseOrder.supplier')
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (ArtworkRevision $revision) => [
                    'type' => 'upload',
                    'title' => 'Artwork yüklendi',
                    'meta' => $revision->artwork->orderLine->purchaseOrder->order_no . ' / Rev.' . $revision->revision_no,
                    'subject' => $revision->artwork->orderLine->purchaseOrder->supplier->name,
                    'at' => $revision->created_at,
                ]),
            ...ArtworkViewLog::query()
                ->with('revision.artwork.orderLine.purchaseOrder')
                ->latest('viewed_at')
                ->limit(8)
                ->get()
                ->map(fn (ArtworkViewLog $log) => [
                    'type' => 'view',
                    'title' => 'Revizyon görüntülendi',
                    'meta' => $log->revision?->artwork?->orderLine?->purchaseOrder?->order_no,
                    'subject' => $log->user?->name ?? 'Bilinmeyen kullanıcı',
                    'at' => $log->viewed_at,
                ]),
            ...ArtworkDownloadLog::query()
                ->with('revision.artwork.orderLine.purchaseOrder')
                ->latest('downloaded_at')
                ->limit(8)
                ->get()
                ->map(fn (ArtworkDownloadLog $log) => [
                    'type' => 'download',
                    'title' => 'Revizyon indirildi',
                    'meta' => $log->revision?->artwork?->orderLine?->purchaseOrder?->order_no,
                    'subject' => $log->user?->name ?? 'Bilinmeyen kullanıcı',
                    'at' => $log->downloaded_at,
                ]),
        ])->sortByDesc('at')->take(15)->values();

        return view('admin.reports.index', compact('summary', 'orderLeadTimes', 'supplierActivity', 'recentTimeline'));
    }
}
