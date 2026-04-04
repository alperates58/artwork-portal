<?php

namespace App\Services;

use App\Models\ArtworkDownloadLog;
use App\Models\ArtworkRevision;
use App\Models\ArtworkViewLog;
use App\Models\AuditLog;
use App\Models\PurchaseOrderLine;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TraceabilityReportService
{
    public function search(?string $search = null, ?int $supplierId = null, int $limit = 25): array
    {
        $term = trim((string) $search);
        $like = $term !== '' ? '%' . addcslashes($term, '\\%_') . '%' : null;

        $lines = PurchaseOrderLine::query()
            ->select([
                'purchase_order_lines.id',
                'purchase_order_lines.purchase_order_id',
                'purchase_order_lines.line_no',
                'purchase_order_lines.product_code',
                'purchase_order_lines.description',
                'purchase_order_lines.quantity',
                'purchase_order_lines.shipped_quantity',
                'purchase_order_lines.artwork_status',
                'purchase_order_lines.manual_artwork_completed_at',
                'purchase_order_lines.manual_artwork_completed_by',
                'purchase_order_lines.manual_artwork_note',
            ])
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
            ->when($supplierId, fn (Builder $query) => $query->where('purchase_orders.supplier_id', $supplierId))
            ->when($term === '', function (Builder $query): void {
                $query->where(function (Builder $subQuery): void {
                    $subQuery->whereHas('artwork.revisions')
                        ->orWhereNotNull('purchase_order_lines.manual_artwork_completed_at')
                        ->orWhere('purchase_order_lines.shipped_quantity', '>', 0);
                });
            })
            ->when($like !== null, function (Builder $query) use ($like): void {
                $query->where(function (Builder $subQuery) use ($like): void {
                    $subQuery->where('purchase_order_lines.product_code', 'like', $like)
                        ->orWhere('purchase_order_lines.description', 'like', $like)
                        ->orWhereHas('artwork.revisions.galleryItem', function (Builder $galleryQuery) use ($like): void {
                            $galleryQuery->where('stock_code', 'like', $like)
                                ->orWhere('name', 'like', $like)
                                ->orWhereHas('stockCard', function (Builder $stockCardQuery) use ($like): void {
                                    $stockCardQuery->where('stock_code', 'like', $like)
                                        ->orWhere('stock_name', 'like', $like);
                                });
                        });
                });
            })
            ->with([
                'purchaseOrder:id,supplier_id,order_no,order_date,shipment_status,shipment_reference,shipment_synced_at,created_at',
                'purchaseOrder.supplier:id,name',
                'manualArtworkCompletedBy:id,name',
                'artwork:id,order_line_id,active_revision_id',
                'artwork.activeRevision:id,artwork_id,artwork_gallery_id,revision_no,created_at,approved_at,approval_status,is_active,uploaded_by',
                'artwork.activeRevision.uploadedBy:id,name',
                'artwork.activeRevision.galleryItem:id,stock_card_id,stock_code,name,revision_no',
                'artwork.activeRevision.galleryItem.stockCard:id,stock_code,stock_name',
                'artwork.revisions' => function ($revisionQuery): void {
                    $revisionQuery
                        ->select([
                            'id',
                            'artwork_id',
                            'artwork_gallery_id',
                            'revision_no',
                            'created_at',
                            'approved_at',
                            'approval_status',
                            'is_active',
                            'uploaded_by',
                        ])
                        ->with([
                            'uploadedBy:id,name',
                            'galleryItem:id,stock_card_id,stock_code,name,revision_no',
                            'galleryItem.stockCard:id,stock_code,stock_name',
                            'approvals' => function ($approvalQuery): void {
                                $approvalQuery
                                    ->select([
                                        'id',
                                        'artwork_revision_id',
                                        'user_id',
                                        'supplier_id',
                                        'status',
                                        'notes',
                                        'actioned_at',
                                    ])
                                    ->with([
                                        'user:id,name',
                                        'supplier:id,name',
                                    ])
                                    ->orderBy('actioned_at');
                            },
                        ])
                        ->orderBy('revision_no');
                },
            ])
            ->orderByDesc('purchase_orders.order_date')
            ->orderByDesc('purchase_order_lines.id')
            ->limit($limit)
            ->get();

        $lineIds = $lines->pluck('id')->values();
        $revisionIds = $lines
            ->flatMap(fn (PurchaseOrderLine $line) => $line->artwork?->revisions?->pluck('id') ?? collect())
            ->unique()
            ->values();

        $lineLogs = $lineIds->isEmpty()
            ? collect()
            : AuditLog::query()
                ->select(['id', 'user_id', 'action', 'model_id', 'payload', 'created_at'])
                ->with('user:id,name')
                ->where('model_type', PurchaseOrderLine::class)
                ->whereIn('model_id', $lineIds)
                ->whereIn('action', [
                    'order_line.manual_artwork.complete',
                    'order_line.revision.complete',
                ])
                ->orderBy('created_at')
                ->get()
                ->groupBy('model_id');

        $viewLogs = $revisionIds->isEmpty()
            ? collect()
            : ArtworkViewLog::query()
                ->select(['id', 'artwork_revision_id', 'user_id', 'supplier_id', 'viewed_at'])
                ->with([
                    'user:id,name',
                    'supplier:id,name',
                ])
                ->whereIn('artwork_revision_id', $revisionIds)
                ->orderBy('viewed_at')
                ->get()
                ->groupBy('artwork_revision_id');

        $downloadLogs = $revisionIds->isEmpty()
            ? collect()
            : ArtworkDownloadLog::query()
                ->select(['id', 'artwork_revision_id', 'user_id', 'supplier_id', 'downloaded_at'])
                ->with([
                    'user:id,name',
                    'supplier:id,name',
                ])
                ->whereIn('artwork_revision_id', $revisionIds)
                ->orderBy('downloaded_at')
                ->get()
                ->groupBy('artwork_revision_id');

        $items = $lines
            ->map(fn (PurchaseOrderLine $line) => $this->mapLine(
                line: $line,
                lineLogs: $lineLogs->get($line->id, collect()),
                viewLogs: $viewLogs,
                downloadLogs: $downloadLogs,
            ))
            ->sortByDesc(fn (object $item) => $item->sort_at?->getTimestamp() ?? 0)
            ->values();

        $printedItems = $items->filter(fn (object $item) => $item->has_print_signal && $item->metrics['order_to_print_days'] !== null);

        return [
            'items' => $items,
            'latestPrinted' => $items->first(fn (object $item) => $item->has_print_signal),
            'summary' => [
                'matches' => $items->count(),
                'printed' => $items->where('has_print_signal', true)->count(),
                'active_revisions' => $items->filter(fn (object $item) => $item->active_revision_no !== null)->count(),
                'awaiting_supplier_action' => $items->filter(
                    fn (object $item) => $item->first_upload_at instanceof Carbon
                        && ! $item->first_supplier_action_at instanceof Carbon
                        && ! $item->has_print_signal
                )->count(),
                'revision_requested' => $items->filter(fn (object $item) => $item->artwork_status_value === 'revision')->count(),
                'avg_print_days' => $printedItems->isNotEmpty()
                    ? round($printedItems->avg(fn (object $item) => $item->metrics['order_to_print_days']), 1)
                    : null,
                'last_activity_at' => $items->first()?->last_activity_at,
            ],
        ];
    }

    private function mapLine(
        PurchaseOrderLine $line,
        Collection $lineLogs,
        Collection $viewLogs,
        Collection $downloadLogs,
    ): object {
        $order = $line->purchaseOrder;
        $revisions = $line->artwork?->revisions?->values() ?? collect();
        $activeRevision = $line->artwork?->activeRevision
            ?: $revisions->first(fn (ArtworkRevision $revision) => $revision->is_active);

        $startAt = $order?->order_date?->copy()?->startOfDay()
            ?: $order?->created_at?->copy();

        $supplierActions = $revisions
            ->flatMap(function (ArtworkRevision $revision): Collection {
                return $revision->approvals->map(function ($approval) use ($revision): array {
                    return [
                        'revision_no' => $revision->revision_no,
                        'status' => $approval->status,
                        'notes' => $approval->notes,
                        'supplier_name' => $approval->supplier?->name,
                        'user_name' => $approval->user?->name,
                        'at' => $approval->actioned_at,
                    ];
                });
            })
            ->filter(fn (array $action) => $action['at'] instanceof Carbon)
            ->sortBy('at')
            ->values();

        $firstUploadAt = $revisions->sortBy('created_at')->first()?->created_at;
        $lastUploadAt = $revisions->sortByDesc('created_at')->first()?->created_at;
        $firstSupplierActionAt = $supplierActions->first()['at'] ?? null;
        $approvedAction = $supplierActions
            ->filter(fn (array $action) => $action['status'] === 'approved')
            ->sortByDesc('at')
            ->first();
        $approvedAt = $approvedAction['at'] ?? $activeRevision?->approved_at;

        $printSignalAt = $this->resolvePrintSignalAt($line);
        $printRevision = $this->resolvePrintRevision(
            revisions: $revisions,
            activeRevision: $activeRevision,
            approvedRevisionNo: $approvedAction['revision_no'] ?? null,
        );

        $timeline = $this->buildTimeline(
            line: $line,
            revisions: $revisions,
            lineLogs: $lineLogs,
            viewLogs: $viewLogs,
            downloadLogs: $downloadLogs,
            startAt: $startAt,
        );

        $lastActivityAt = $timeline->first()['at'] ?? $startAt;

        return (object) [
            'line_id' => $line->id,
            'order_id' => $order?->id,
            'order_no' => $order?->order_no,
            'line_no' => $line->line_no,
            'supplier_name' => $order?->supplier?->name ?? '—',
            'product_code' => $this->resolveStockCode($line, $revisions),
            'stock_name' => $this->resolveStockName($line, $revisions),
            'description' => $line->description,
            'artwork_status_value' => $line->artwork_status?->value ?? (string) $line->artwork_status,
            'artwork_status_label' => $line->artwork_status?->label() ?? (string) $line->artwork_status,
            'active_revision_no' => $activeRevision?->revision_no,
            'print_revision_no' => $printRevision?->revision_no,
            'print_reference' => $this->resolvePrintReference($line),
            'has_print_signal' => $this->hasPrintSignal($line),
            'shipment_status_label' => $order?->shipment_status_label,
            'shipment_synced_at' => $order?->shipment_synced_at,
            'shipped_quantity' => $line->shipped_quantity,
            'first_upload_at' => $firstUploadAt,
            'first_supplier_action_at' => $firstSupplierActionAt,
            'last_activity_at' => $lastActivityAt,
            'sort_at' => $printSignalAt ?: $lastActivityAt,
            'timeline' => $timeline,
            'metrics' => [
                'order_to_first_upload_days' => $this->diffDays($startAt, $firstUploadAt),
                'first_upload_to_first_supplier_action_days' => $this->diffDays($firstUploadAt, $firstSupplierActionAt),
                'last_upload_to_approval_days' => $this->diffDays($lastUploadAt, $approvedAt),
                'approval_to_print_days' => $this->diffDays($approvedAt, $printSignalAt),
                'order_to_print_days' => $this->diffDays($startAt, $printSignalAt),
            ],
        ];
    }

    private function buildTimeline(
        PurchaseOrderLine $line,
        Collection $revisions,
        Collection $lineLogs,
        Collection $viewLogs,
        Collection $downloadLogs,
        ?Carbon $startAt,
    ): Collection {
        $order = $line->purchaseOrder;
        $events = collect();

        if ($startAt) {
            $events->push([
                'at' => $startAt,
                'icon' => 'order',
                'color' => 'slate',
                'title' => 'Sipariş açıldı',
                'sub' => ($order?->order_no ?? '—') . ' · ' . ($order?->supplier?->name ?? '—'),
                'body' => $line->description,
            ]);
        }

        foreach ($revisions as $revision) {
            if ($revision->created_at instanceof Carbon) {
                $events->push([
                    'at' => $revision->created_at,
                    'icon' => 'upload',
                    'color' => 'blue',
                    'title' => 'Revizyon #' . $revision->revision_no . ' yüklendi',
                    'sub' => $revision->uploadedBy?->name ?? 'Grafik ekibi',
                    'body' => $revision->galleryItem?->stockCard?->display_stock_name ?: $revision->galleryItem?->name,
                ]);
            }

            foreach ($revision->approvals as $approval) {
                if (! $approval->actioned_at instanceof Carbon) {
                    continue;
                }

                $events->push([
                    'at' => $approval->actioned_at,
                    'icon' => match ($approval->status) {
                        'approved' => 'check',
                        'rejected' => 'x',
                        default => 'eye',
                    },
                    'color' => match ($approval->status) {
                        'approved' => 'emerald',
                        'rejected' => 'red',
                        default => 'amber',
                    },
                    'title' => match ($approval->status) {
                        'approved' => 'Tedarikçi onayladı',
                        'rejected' => 'Revizyon talebi açıldı',
                        default => 'Tedarikçi gördü',
                    },
                    'sub' => 'Revizyon #' . $revision->revision_no . ' · ' . ($approval->supplier?->name ?? $approval->user?->name ?? 'Tedarikçi'),
                    'body' => $approval->notes,
                ]);
            }

            foreach ($viewLogs->get($revision->id, collect()) as $viewLog) {
                $events->push([
                    'at' => $viewLog->viewed_at,
                    'icon' => 'eye',
                    'color' => 'amber',
                    'title' => 'Dosya görüntülendi',
                    'sub' => 'Revizyon #' . $revision->revision_no . ' · ' . ($viewLog->supplier?->name ?? $viewLog->user?->name ?? 'Kullanıcı'),
                    'body' => null,
                ]);
            }

            foreach ($downloadLogs->get($revision->id, collect()) as $downloadLog) {
                $events->push([
                    'at' => $downloadLog->downloaded_at,
                    'icon' => 'download',
                    'color' => 'violet',
                    'title' => 'Dosya indirildi',
                    'sub' => 'Revizyon #' . $revision->revision_no . ' · ' . ($downloadLog->supplier?->name ?? $downloadLog->user?->name ?? 'Kullanıcı'),
                    'body' => null,
                ]);
            }
        }

        foreach ($lineLogs as $log) {
            $events->push([
                'at' => $log->created_at,
                'icon' => match ($log->action) {
                    'order_line.manual_artwork.complete' => 'mail',
                    default => 'check',
                },
                'color' => 'emerald',
                'title' => match ($log->action) {
                    'order_line.manual_artwork.complete' => 'Manuel gönderim tamamlandı',
                    default => 'Revizyon tekrar hazır',
                },
                'sub' => $log->user?->name ?? 'Sistem',
                'body' => $log->payload['note'] ?? $log->payload['summary'] ?? null,
            ]);
        }

        if ($this->hasPrintSignal($line) && $order?->shipment_synced_at instanceof Carbon) {
            $events->push([
                'at' => $order->shipment_synced_at,
                'icon' => 'truck',
                'color' => 'emerald',
                'title' => 'Basım / sevk sinyali alındı',
                'sub' => $this->resolvePrintReference($line),
                'body' => filled($order->shipment_reference)
                    ? 'İrsaliye / referans: ' . $order->shipment_reference
                    : null,
            ]);
        }

        $sorted = $events
            ->filter(fn (array $event) => $event['at'] instanceof Carbon)
            ->sortByDesc('at')
            ->values();

        return $sorted->map(function (array $event, int $index) use ($sorted): array {
            $next = $sorted->get($index + 1);
            $event['days_gap'] = $next
                ? round(abs($event['at']->diffInMinutes($next['at'])) / 1440, 1)
                : null;

            return $event;
        });
    }

    private function resolveStockCode(PurchaseOrderLine $line, Collection $revisions): string
    {
        $candidate = $line->product_code
            ?: $line->artwork?->activeRevision?->galleryItem?->stock_code
            ?: $revisions->first(fn (ArtworkRevision $revision) => filled($revision->galleryItem?->stock_code))?->galleryItem?->stock_code
            ?: '—';

        return (string) $candidate;
    }

    private function resolveStockName(PurchaseOrderLine $line, Collection $revisions): string
    {
        $candidates = collect([
            $line->artwork?->activeRevision?->galleryItem?->stockCard?->display_stock_name,
            $line->artwork?->activeRevision?->galleryItem?->name,
            ...$revisions->map(fn (ArtworkRevision $revision) => $revision->galleryItem?->stockCard?->display_stock_name)->all(),
            ...$revisions->map(fn (ArtworkRevision $revision) => $revision->galleryItem?->name)->all(),
            $line->description,
        ])->filter(fn ($value) => filled($value));

        return (string) ($candidates->first() ?? '—');
    }

    private function hasPrintSignal(PurchaseOrderLine $line): bool
    {
        $shipmentStatus = $line->purchaseOrder?->shipment_status;

        return (int) ($line->shipped_quantity ?? 0) > 0
            || in_array($shipmentStatus, ['dispatched', 'delivered'], true);
    }

    private function resolvePrintSignalAt(PurchaseOrderLine $line): ?Carbon
    {
        if (! $this->hasPrintSignal($line)) {
            return null;
        }

        return $line->purchaseOrder?->shipment_synced_at?->copy();
    }

    private function resolvePrintRevision(
        Collection $revisions,
        ?ArtworkRevision $activeRevision,
        ?int $approvedRevisionNo,
    ): ?ArtworkRevision {
        if ($approvedRevisionNo !== null) {
            $approvedRevision = $revisions->first(fn (ArtworkRevision $revision) => $revision->revision_no === $approvedRevisionNo);

            if ($approvedRevision instanceof ArtworkRevision) {
                return $approvedRevision;
            }
        }

        if ($activeRevision instanceof ArtworkRevision) {
            return $activeRevision;
        }

        $latestRevision = $revisions->sortByDesc('revision_no')->first();

        return $latestRevision instanceof ArtworkRevision ? $latestRevision : null;
    }

    private function resolvePrintReference(PurchaseOrderLine $line): string
    {
        $parts = [];

        if ((int) ($line->shipped_quantity ?? 0) > 0) {
            $parts[] = number_format((int) $line->shipped_quantity, 0, ',', '.') . ' adet sevk';
        }

        if (filled($line->purchaseOrder?->shipment_status_label)) {
            $parts[] = $line->purchaseOrder->shipment_status_label;
        }

        if (filled($line->purchaseOrder?->shipment_reference)) {
            $parts[] = $line->purchaseOrder->shipment_reference;
        }

        return $parts !== [] ? implode(' · ', $parts) : 'Basım sinyali henüz yok';
    }

    private function diffDays(?Carbon $from, ?Carbon $to): ?float
    {
        if (! $from instanceof Carbon || ! $to instanceof Carbon) {
            return null;
        }

        return round(abs($from->diffInMinutes($to)) / 1440, 1);
    }
}
