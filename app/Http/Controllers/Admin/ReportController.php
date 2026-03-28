<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtworkCategory;
use App\Models\ArtworkDownloadLog;
use App\Models\ArtworkRevision;
use App\Models\ArtworkTag;
use App\Models\ArtworkViewLog;
use App\Models\OrderNote;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    private function checkAccess(): void
    {
        abort_if(
            ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('reports', 'view'),
            403
        );
    }

    public function index(): View
    {
        $this->checkAccess();

        $summary = [
            'active_orders'    => PurchaseOrder::query()->where('status', 'active')->count(),
            'pending_artwork'  => PurchaseOrderLine::query()->where('artwork_status', 'pending')->count(),
            'uploaded_today'   => ArtworkRevision::query()->whereDate('created_at', today())->count(),
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
                    'order_no'       => $row->order_no,
                    'supplier_name'  => $row->supplier_name,
                    'order_date'     => Carbon::parse($row->order_date),
                    'status'         => $row->status,
                    'first_upload_at'=> $firstUploadAt,
                    'lead_days'      => $firstUploadAt ? round(Carbon::parse($row->order_date)->floatDiffInDays($firstUploadAt), 1) : null,
                ];
            });

        $supplierActivity = Supplier::query()
            ->select(['suppliers.id', 'suppliers.name'])
            ->withCount([
                'purchaseOrders',
                'purchaseOrders as active_orders_count' => fn ($q) => $q->where('status', 'active'),
            ])
            ->orderByDesc('purchase_orders_count')
            ->limit(8)
            ->get();

        // Firma bazlı sipariş durumu dağılımı (grafik için)
        $supplierOrderChart = Supplier::query()
            ->select([
                'suppliers.name',
                DB::raw('SUM(CASE WHEN purchase_orders.status = "active" THEN 1 ELSE 0 END) as active_count'),
                DB::raw('SUM(CASE WHEN purchase_orders.status = "completed" THEN 1 ELSE 0 END) as completed_count'),
                DB::raw('SUM(CASE WHEN purchase_orders.status IN ("draft","cancelled") THEN 1 ELSE 0 END) as other_count'),
                DB::raw('COUNT(CASE WHEN purchase_order_lines.artwork_status = "pending" THEN 1 END) as pending_lines'),
                DB::raw('COUNT(CASE WHEN purchase_order_lines.artwork_status = "uploaded" THEN 1 END) as uploaded_lines'),
                DB::raw('COUNT(CASE WHEN purchase_order_lines.artwork_status = "approved" THEN 1 END) as approved_lines'),
            ])
            ->leftJoin('purchase_orders', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->leftJoin('purchase_order_lines', 'purchase_order_lines.purchase_order_id', '=', 'purchase_orders.id')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('active_count')
            ->get();

        $recentTimeline = collect([
            ...ArtworkRevision::query()
                ->with('artwork.orderLine.purchaseOrder.supplier')
                ->latest()->limit(8)->get()
                ->map(fn (ArtworkRevision $r) => [
                    'type'    => 'upload',
                    'title'   => 'Artwork yüklendi',
                    'meta'    => $r->artwork->orderLine->purchaseOrder->order_no . ' / Rev.' . $r->revision_no,
                    'subject' => $r->artwork->orderLine->purchaseOrder->supplier->name,
                    'at'      => $r->created_at,
                ]),
            ...ArtworkViewLog::query()
                ->with(['revision.artwork.orderLine.purchaseOrder', 'user:id,name'])
                ->latest('viewed_at')->limit(8)->get()
                ->map(fn (ArtworkViewLog $l) => [
                    'type'    => 'view',
                    'title'   => 'Revizyon görüntülendi',
                    'meta'    => $l->revision?->artwork?->orderLine?->purchaseOrder?->order_no,
                    'subject' => $l->user?->name ?? 'Bilinmeyen kullanıcı',
                    'at'      => $l->viewed_at,
                ]),
            ...ArtworkDownloadLog::query()
                ->with(['revision.artwork.orderLine.purchaseOrder', 'user:id,name'])
                ->latest('downloaded_at')->limit(8)->get()
                ->map(fn (ArtworkDownloadLog $l) => [
                    'type'    => 'download',
                    'title'   => 'Revizyon indirildi',
                    'meta'    => $l->revision?->artwork?->orderLine?->purchaseOrder?->order_no,
                    'subject' => $l->user?->name ?? 'Bilinmeyen kullanıcı',
                    'at'      => $l->downloaded_at,
                ]),
        ])->sortByDesc('at')->take(15)->values();

        return view('admin.reports.index', compact('summary', 'orderLeadTimes', 'supplierActivity', 'supplierOrderChart', 'recentTimeline'));
    }

    public function leadTime(): View
    {
        $this->checkAccess();

        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name']);
        $selectedSupplierId = request()->integer('supplier_id') ?: null;

        // Per-order: order_date, first_upload_at, first_download_at
        $rows = PurchaseOrder::query()
            ->select([
                'purchase_orders.id',
                'purchase_orders.order_no',
                'purchase_orders.order_date',
                'purchase_orders.status',
                'suppliers.name as supplier_name',
                DB::raw('MIN(artwork_revisions.created_at) as first_upload_at'),
                DB::raw('MIN(artwork_download_logs.downloaded_at) as first_download_at'),
            ])
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->leftJoin('purchase_order_lines', 'purchase_order_lines.purchase_order_id', '=', 'purchase_orders.id')
            ->leftJoin('artworks', 'artworks.order_line_id', '=', 'purchase_order_lines.id')
            ->leftJoin('artwork_revisions', 'artwork_revisions.artwork_id', '=', 'artworks.id')
            ->leftJoin('artwork_download_logs', 'artwork_download_logs.artwork_revision_id', '=', 'artwork_revisions.id')
            ->when($selectedSupplierId, fn ($q) => $q->where('purchase_orders.supplier_id', $selectedSupplierId))
            ->groupBy('purchase_orders.id', 'purchase_orders.order_no', 'purchase_orders.order_date', 'purchase_orders.status', 'suppliers.name')
            ->orderByDesc('purchase_orders.order_date')
            ->limit(50)
            ->get()
            ->map(function ($row) {
                $orderDate     = Carbon::parse($row->order_date);
                $uploadAt      = $row->first_upload_at  ? Carbon::parse($row->first_upload_at)  : null;
                $downloadAt    = $row->first_download_at ? Carbon::parse($row->first_download_at) : null;

                $orderToUpload   = $uploadAt   ? $orderDate->diffInHours($uploadAt)   : null;
                $uploadToDownload = ($uploadAt && $downloadAt) ? $uploadAt->diffInHours($downloadAt) : null;
                $orderToDownload = $downloadAt ? $orderDate->diffInHours($downloadAt) : null;

                return (object) [
                    'order_no'             => $row->order_no,
                    'supplier_name'        => $row->supplier_name,
                    'order_date'           => $orderDate,
                    'status'               => $row->status,
                    'upload_at'            => $uploadAt,
                    'download_at'          => $downloadAt,
                    'order_to_upload_h'    => $orderToUpload,
                    'order_to_upload_d'    => $orderToUpload !== null ? round($orderToUpload / 24, 1) : null,
                    'upload_to_download_h' => $uploadToDownload,
                    'upload_to_download_d' => $uploadToDownload !== null ? round($uploadToDownload / 24, 1) : null,
                    'total_d'              => $orderToDownload !== null ? round($orderToDownload / 24, 1) : null,
                ];
            });

        // Supplier averages for chart
        $supplierAvgs = $rows
            ->filter(fn ($r) => $r->order_to_upload_d !== null)
            ->groupBy('supplier_name')
            ->map(fn ($group) => [
                'label'           => $group->first()->supplier_name,
                'avg_to_upload'   => round($group->avg('order_to_upload_d'), 1),
                'avg_to_download' => round($group->filter(fn ($r) => $r->upload_to_download_d !== null)->avg('upload_to_download_d') ?? 0, 1),
            ])
            ->values();

        return view('admin.reports.lead-time', compact('rows', 'supplierAvgs', 'suppliers', 'selectedSupplierId'));
    }

    public function pending(): View
    {
        $this->checkAccess();

        $now = now();
        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name']);
        $selectedSupplierId = request()->integer('supplier_id') ?: null;
        $searchProduct = request('product_code', '');

        $lines = PurchaseOrderLine::query()
            ->select(['purchase_order_lines.*', 'purchase_orders.order_date', 'purchase_orders.order_no', 'suppliers.name as supplier_name'])
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->where('purchase_order_lines.artwork_status', 'pending')
            ->where('purchase_orders.status', 'active')
            ->when($selectedSupplierId, fn ($q) => $q->where('purchase_orders.supplier_id', $selectedSupplierId))
            ->when($searchProduct, fn ($q) => $q->where('purchase_order_lines.product_code', 'like', "%{$searchProduct}%"))
            ->orderBy('purchase_orders.order_date')
            ->get()
            ->map(function ($line) use ($now) {
                $orderDate   = Carbon::parse($line->order_date);
                $waitingDays = round($orderDate->floatDiffInDays($now), 1);
                $band = match(true) {
                    $waitingDays <= 3  => '0-3 gün',
                    $waitingDays <= 7  => '4-7 gün',
                    $waitingDays <= 14 => '8-14 gün',
                    $waitingDays <= 30 => '15-30 gün',
                    default            => '30+ gün',
                };
                return (object) [
                    'line_no'       => $line->line_no,
                    'product_code'  => $line->product_code,
                    'description'   => $line->description,
                    'order_no'      => $line->order_no,
                    'supplier_name' => $line->supplier_name,
                    'order_date'    => $orderDate,
                    'waiting_days'  => $waitingDays,
                    'band'          => $band,
                ];
            });

        $bandCounts = $lines->groupBy('band')->map->count();
        $bandOrder  = ['0-3 gün', '4-7 gün', '8-14 gün', '15-30 gün', '30+ gün'];
        $chartBands = collect($bandOrder)->map(fn ($b) => ['label' => $b, 'count' => $bandCounts->get($b, 0)])->values();

        $supplierCounts = $lines->groupBy('supplier_name')
            ->map(fn ($g) => ['name' => $g->first()->supplier_name, 'count' => $g->count(), 'max_wait' => $g->max('waiting_days')])
            ->sortByDesc('count')->values();

        return view('admin.reports.pending', compact('lines', 'chartBands', 'supplierCounts', 'suppliers', 'selectedSupplierId', 'searchProduct'));
    }

    public function stockCode(): View
    {
        $this->checkAccess();

        $searchCode = request('stock_code', '');

        // All gallery items that have a stock_code, with usage details
        $items = \App\Models\ArtworkGallery::query()
            ->select(['artwork_gallery.id', 'artwork_gallery.stock_code', 'artwork_gallery.name', 'artwork_gallery.created_at'])
            ->with(['usages' => fn ($q) => $q->with('supplier:id,name')->latest('used_at')])
            ->withCount('usages')
            ->when($searchCode, fn ($q) => $q->where('stock_code', 'like', "%{$searchCode}%"))
            ->whereNotNull('stock_code')
            ->where('stock_code', '!=', '')
            ->orderByDesc('usages_count')
            ->get()
            ->map(function ($item) {
                $suppliers = $item->usages->pluck('supplier.name')->filter()->unique()->values();
                return (object) [
                    'id'           => $item->id,
                    'stock_code'   => $item->stock_code,
                    'name'         => $item->name,
                    'usage_count'  => $item->usages_count,
                    'supplier_count' => $suppliers->count(),
                    'suppliers'    => $suppliers,
                    'last_used_at' => $item->usages->first()?->used_at,
                    'created_at'   => $item->created_at,
                ];
            });

        // Revision counts per stock_code (how many artwork_revisions reference each gallery item)
        $revisionCounts = \App\Models\ArtworkRevision::query()
            ->select([
                'artwork_gallery.stock_code',
                DB::raw('COUNT(artwork_revisions.id) as revision_count'),
                DB::raw('COUNT(DISTINCT purchase_orders.supplier_id) as supplier_count'),
                DB::raw('MAX(artwork_revisions.created_at) as last_revision_at'),
            ])
            ->join('artwork_gallery', 'artwork_gallery.id', '=', 'artwork_revisions.artwork_gallery_id')
            ->join('artworks', 'artworks.id', '=', 'artwork_revisions.artwork_id')
            ->join('purchase_order_lines', 'purchase_order_lines.id', '=', 'artworks.order_line_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
            ->whereNotNull('artwork_gallery.stock_code')
            ->where('artwork_gallery.stock_code', '!=', '')
            ->when($searchCode, fn ($q) => $q->where('artwork_gallery.stock_code', 'like', "%{$searchCode}%"))
            ->groupBy('artwork_gallery.stock_code')
            ->orderByDesc('revision_count')
            ->get();

        return view('admin.reports.stock-code', compact('items', 'revisionCounts', 'searchCode'));
    }

    public function category(): View
    {
        $this->checkAccess();

        $suppliers          = Supplier::query()->orderBy('name')->get(['id', 'name']);
        $selectedSupplierId = request()->integer('supplier_id') ?: null;
        $dateFrom           = request('date_from', '');
        $dateTo             = request('date_to', '');

        $categories = ArtworkCategory::query()
            ->select(['artwork_categories.*'])
            ->withCount('galleryItems')
            ->with(['galleryItems.usages' => fn ($q) => $q
                ->when($selectedSupplierId, fn ($sq) => $sq->where('supplier_id', $selectedSupplierId))
                ->when($dateFrom, fn ($sq) => $sq->whereDate('used_at', '>=', $dateFrom))
                ->when($dateTo,   fn ($sq) => $sq->whereDate('used_at', '<=', $dateTo))
            ])
            ->get()
            ->map(function ($cat) {
                $allUsages   = $cat->galleryItems->flatMap(fn ($item) => $item->usages);
                $totalUsages = $allUsages->count();
                $lastUsed    = $allUsages->max('used_at');
                return (object) [
                    'name'        => $cat->display_name,
                    'file_count'  => $cat->gallery_items_count,
                    'usage_count' => $totalUsages,
                    'last_used'   => $lastUsed ? Carbon::parse($lastUsed) : null,
                ];
            })
            ->sortByDesc('usage_count')->values();

        $tags = ArtworkTag::query()
            ->select(['artwork_tags.*'])
            ->withCount('galleryItems')
            ->with(['galleryItems.usages' => fn ($q) => $q
                ->when($selectedSupplierId, fn ($sq) => $sq->where('supplier_id', $selectedSupplierId))
                ->when($dateFrom, fn ($sq) => $sq->whereDate('used_at', '>=', $dateFrom))
                ->when($dateTo,   fn ($sq) => $sq->whereDate('used_at', '<=', $dateTo))
            ])
            ->get()
            ->map(function ($tag) {
                $totalUsages = $tag->galleryItems->sum(fn ($item) => $item->usages->count());
                return (object) [
                    'name'        => $tag->display_name,
                    'file_count'  => $tag->gallery_items_count,
                    'usage_count' => $totalUsages,
                ];
            })
            ->sortByDesc('usage_count')->values();

        // Pending days by category (through artwork gallery usages)
        $pendingByCategory = PurchaseOrderLine::query()
            ->select([
                'artwork_categories.name as category_name',
                DB::raw('COUNT(*) as pending_count'),
                DB::raw('AVG(DATEDIFF(NOW(), purchase_orders.order_date)) as avg_waiting_days'),
                DB::raw('MAX(DATEDIFF(NOW(), purchase_orders.order_date)) as max_waiting_days'),
            ])
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
            ->leftJoin('artworks', 'artworks.order_line_id', '=', 'purchase_order_lines.id')
            ->leftJoin('artwork_revisions', function ($join) {
                $join->on('artwork_revisions.artwork_id', '=', 'artworks.id')
                     ->where('artwork_revisions.is_active', true);
            })
            ->leftJoin('artwork_gallery', 'artwork_gallery.id', '=', 'artwork_revisions.artwork_gallery_id')
            ->leftJoin('artwork_categories', 'artwork_categories.id', '=', 'artwork_gallery.category_id')
            ->where('purchase_order_lines.artwork_status', 'pending')
            ->where('purchase_orders.status', 'active')
            ->when($selectedSupplierId, fn ($q) => $q->where('purchase_orders.supplier_id', $selectedSupplierId))
            ->when($dateFrom, fn ($q) => $q->whereDate('purchase_orders.order_date', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->whereDate('purchase_orders.order_date', '<=', $dateTo))
            ->groupBy('artwork_categories.name')
            ->get();

        return view('admin.reports.category', compact('categories', 'tags', 'pendingByCategory', 'suppliers', 'selectedSupplierId', 'dateFrom', 'dateTo'));
    }

    public function timeline(Request $request): View
    {
        $this->checkAccess();

        $suppliers         = Supplier::orderBy('name')->get(['id', 'name']);
        $selectedSupplier  = $request->input('supplier_id');
        $dateFrom          = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo            = $request->input('date_to', now()->format('Y-m-d'));
        $types             = $request->input('types', ['order', 'artwork', 'note']);
        if (is_string($types)) {
            $types = [$types];
        }

        $from = Carbon::parse($dateFrom)->startOfDay();
        $to   = Carbon::parse($dateTo)->endOfDay();

        $timeline = collect();

        // Orders created
        if (in_array('order', $types)) {
            $orders = PurchaseOrder::query()
                ->with(['supplier:id,name', 'createdBy:id,name'])
                ->when($selectedSupplier, fn ($q) => $q->where('supplier_id', $selectedSupplier))
                ->whereBetween('created_at', [$from, $to])
                ->get(['id', 'order_no', 'supplier_id', 'created_by', 'created_at']);

            foreach ($orders as $order) {
                $timeline->push([
                    'at'      => $order->created_at,
                    'type'    => 'order',
                    'icon'    => 'plus',
                    'color'   => 'violet',
                    'title'   => 'Sipariş oluşturuldu',
                    'sub'     => $order->order_no . ' · ' . ($order->supplier?->name ?? '—'),
                    'user'    => $order->createdBy?->name ?? '—',
                    'link'    => route('orders.show', $order->id),
                ]);
            }
        }

        // Artwork revisions uploaded
        if (in_array('artwork', $types)) {
            $revisions = ArtworkRevision::query()
                ->with([
                    'uploadedBy:id,name',
                    'artwork.orderLine.purchaseOrder.supplier:id,name',
                    'artwork.orderLine.purchaseOrder:id,order_no,supplier_id',
                    'artwork.orderLine:id,purchase_order_id,product_code,description',
                ])
                ->when($selectedSupplier, fn ($q) => $q->whereHas(
                    'artwork.orderLine.purchaseOrder',
                    fn ($oq) => $oq->where('supplier_id', $selectedSupplier)
                ))
                ->whereBetween('artwork_revisions.created_at', [$from, $to])
                ->get();

            foreach ($revisions as $rev) {
                $order = $rev->artwork?->orderLine?->purchaseOrder;
                $line  = $rev->artwork?->orderLine;
                $timeline->push([
                    'at'    => $rev->created_at,
                    'type'  => 'artwork',
                    'icon'  => 'upload',
                    'color' => 'blue',
                    'title' => "Revizyon #{$rev->revision_no} yüklendi",
                    'sub'   => ($order?->order_no ?? '—') . ' · ' . ($line?->description ?? $line?->product_code ?? "Satır"),
                    'user'  => $rev->uploadedBy?->name ?? '—',
                    'link'  => $order ? route('orders.show', $order->id) : null,
                ]);
            }
        }

        // Order notes
        if (in_array('note', $types)) {
            $notes = OrderNote::query()
                ->with([
                    'user:id,name',
                    'order:id,order_no,supplier_id',
                    'order.supplier:id,name',
                ])
                ->when($selectedSupplier, fn ($q) => $q->whereHas(
                    'order',
                    fn ($oq) => $oq->where('supplier_id', $selectedSupplier)
                ))
                ->whereBetween('created_at', [$from, $to])
                ->get();

            foreach ($notes as $note) {
                $timeline->push([
                    'at'    => $note->created_at,
                    'type'  => 'note',
                    'icon'  => 'note',
                    'color' => 'amber',
                    'title' => 'Not eklendi',
                    'sub'   => $note->order?->order_no . ' · ' . ($note->order?->supplier?->name ?? '—'),
                    'user'  => $note->user?->name ?? '—',
                    'body'  => mb_strimwidth($note->body, 0, 160, '…'),
                    'link'  => $note->order ? route('orders.show', $note->order->id) : null,
                ]);
            }
        }

        $timeline = $timeline->sortByDesc('at')->values();

        // Chart: events per day for the date range
        $chartDays = [];
        $current = $from->copy();
        while ($current->lte($to)) {
            $dayKey = $current->format('Y-m-d');
            $chartDays[$dayKey] = [
                'label'   => $current->format('d.m'),
                'order'   => 0,
                'artwork' => 0,
                'note'    => 0,
            ];
            $current->addDay();
        }
        foreach ($timeline as $event) {
            $key = $event['at']->format('Y-m-d');
            if (isset($chartDays[$key])) {
                $chartDays[$key][$event['type']]++;
            }
        }
        $chartDays = array_values($chartDays);

        $stats = [
            'total'   => $timeline->count(),
            'order'   => $timeline->where('type', 'order')->count(),
            'artwork' => $timeline->where('type', 'artwork')->count(),
            'note'    => $timeline->where('type', 'note')->count(),
        ];

        return view('admin.reports.timeline', compact(
            'timeline', 'stats', 'chartDays',
            'suppliers', 'selectedSupplier', 'dateFrom', 'dateTo', 'types'
        ));
    }
}
