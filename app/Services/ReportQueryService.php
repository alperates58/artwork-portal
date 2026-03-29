<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportQueryService
{
    public function run(array $dimensions, array $metrics, array $filters = []): array
    {
        if (empty($dimensions) || empty($metrics)) {
            return ['labels' => [], 'datasets' => [], 'table' => [], 'columns' => [], 'row_count' => 0];
        }

        $hasManualArtworkColumns = $this->hasManualArtworkColumns();
        $yearExpression = $this->yearExpression('purchase_orders.order_date');
        $monthExpression = $this->monthExpression('purchase_orders.order_date');
        $quarterExpression = $this->quarterExpression('purchase_orders.order_date');

        $query = DB::table('purchase_order_lines')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->whereNull('suppliers.deleted_at');

        if (! empty($filters['supplier_id'])) {
            $query->where('purchase_orders.supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['order_status'])) {
            $query->where('purchase_orders.status', $filters['order_status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('purchase_orders.order_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('purchase_orders.order_date', '<=', $filters['date_to']);
        }

        if (in_array('revision_count', $metrics, true)) {
            $query->leftJoin('artworks', 'artworks.order_line_id', '=', 'purchase_order_lines.id')
                ->leftJoin('artwork_revisions', 'artwork_revisions.artwork_id', '=', 'artworks.id');
        }

        if (in_array('avg_days_to_upload', $metrics, true)) {
            $subquery = DB::table('artwork_revisions')
                ->join('artworks as aw2', 'aw2.id', '=', 'artwork_revisions.artwork_id')
                ->select('aw2.order_line_id', DB::raw('MIN(artwork_revisions.created_at) as min_uploaded_at'))
                ->where('artwork_revisions.is_active', true)
                ->groupBy('aw2.order_line_id');

            $query->leftJoinSub($subquery, 'first_rev', 'first_rev.order_line_id', '=', 'purchase_order_lines.id');
        }

        $selects = [];
        $groupBys = [];

        foreach ($dimensions as $dimension) {
            switch ($dimension) {
                case 'supplier':
                    $selects[] = 'suppliers.name as dim_supplier';
                    $groupBys[] = 'suppliers.id';
                    $groupBys[] = 'suppliers.name';
                    break;
                case 'month':
                    $selects[] = $yearExpression . ' as dim_year';
                    $selects[] = $monthExpression . ' as dim_month';
                    $groupBys[] = $yearExpression;
                    $groupBys[] = $monthExpression;
                    break;
                case 'year':
                    $selects[] = $yearExpression . ' as dim_year_val';
                    $groupBys[] = $yearExpression;
                    break;
                case 'quarter':
                    $selects[] = $yearExpression . ' as dim_q_year';
                    $selects[] = $quarterExpression . ' as dim_quarter';
                    $groupBys[] = $yearExpression;
                    $groupBys[] = $quarterExpression;
                    break;
                case 'order_status':
                    $selects[] = 'purchase_orders.status as dim_order_status';
                    $groupBys[] = 'purchase_orders.status';
                    break;
                case 'artwork_status':
                    $selects[] = 'purchase_order_lines.artwork_status as dim_artwork_status';
                    $groupBys[] = 'purchase_order_lines.artwork_status';
                    break;
                case 'product_code':
                    $selects[] = 'purchase_order_lines.product_code as dim_product_code';
                    $groupBys[] = 'purchase_order_lines.product_code';
                    break;
                case 'order_no':
                    $selects[] = 'purchase_orders.order_no as dim_order_no';
                    $groupBys[] = 'purchase_orders.order_no';
                    break;
            }
        }

        foreach ($metrics as $metric) {
            switch ($metric) {
                case 'order_count':
                    $selects[] = 'COUNT(DISTINCT purchase_orders.id) as metric_order_count';
                    break;
                case 'line_count':
                    $selects[] = 'COUNT(DISTINCT purchase_order_lines.id) as metric_line_count';
                    break;
                case 'pending_artwork':
                    $selects[] = $hasManualArtworkColumns
                        ? "SUM(CASE WHEN purchase_order_lines.manual_artwork_completed_at IS NULL AND purchase_order_lines.artwork_status = 'pending' THEN 1 ELSE 0 END) as metric_pending_artwork"
                        : "SUM(CASE WHEN purchase_order_lines.artwork_status = 'pending' THEN 1 ELSE 0 END) as metric_pending_artwork";
                    break;
                case 'uploaded_artwork':
                    $selects[] = $hasManualArtworkColumns
                        ? "SUM(CASE WHEN purchase_order_lines.manual_artwork_completed_at IS NOT NULL OR purchase_order_lines.artwork_status IN ('uploaded','approved') THEN 1 ELSE 0 END) as metric_uploaded_artwork"
                        : "SUM(CASE WHEN purchase_order_lines.artwork_status IN ('uploaded','approved') THEN 1 ELSE 0 END) as metric_uploaded_artwork";
                    break;
                case 'manual_artwork':
                    $selects[] = $hasManualArtworkColumns
                        ? 'SUM(CASE WHEN purchase_order_lines.manual_artwork_completed_at IS NOT NULL THEN 1 ELSE 0 END) as metric_manual_artwork'
                        : '0 as metric_manual_artwork';
                    break;
                case 'revision_count':
                    $selects[] = 'COUNT(DISTINCT artwork_revisions.id) as metric_revision_count';
                    break;
                case 'avg_days_to_upload':
                    $selects[] = 'ROUND(AVG(DATEDIFF(first_rev.min_uploaded_at, purchase_orders.order_date)), 1) as metric_avg_days_to_upload';
                    break;
            }
        }

        foreach ($selects as $select) {
            $query->selectRaw($select);
        }

        foreach ($groupBys as $groupBy) {
            if (str_contains($groupBy, '(')) {
                $query->groupByRaw($groupBy);
            } else {
                $query->groupBy($groupBy);
            }
        }

        if (! empty($groupBys)) {
            $firstGroupBy = $groupBys[0];

            if (str_contains($firstGroupBy, '(')) {
                $query->orderByRaw($firstGroupBy);
            } else {
                $query->orderBy($firstGroupBy);
            }
        }

        $rows = $query->limit(500)->get();

        return $this->format($rows, $dimensions, $metrics);
    }

    private function format($rows, array $dimensions, array $metrics): array
    {
        $dimensionLabels = [
            'supplier' => 'Tedarikçi',
            'month' => 'Ay',
            'year' => 'Yıl',
            'quarter' => 'Çeyrek',
            'order_status' => 'Sipariş Durumu',
            'artwork_status' => 'Artwork Durumu',
            'product_code' => 'Stok Kodu',
            'order_no' => 'Sipariş No',
        ];

        $metricLabels = [
            'order_count' => 'Sipariş Sayısı',
            'line_count' => 'Satır Sayısı',
            'pending_artwork' => 'Bekleyen Artwork',
            'uploaded_artwork' => 'Yüklenen Artwork',
            'manual_artwork' => 'Manuel Tamamlanan Artwork',
            'revision_count' => 'Revizyon Sayısı',
            'avg_days_to_upload' => 'Ort. Yükleme (Gün)',
        ];

        $orderStatusLabels = [
            'draft' => 'Taslak',
            'active' => 'Aktif',
            'completed' => 'Tamamlandı',
            'cancelled' => 'İptal',
        ];

        $artworkStatusLabels = [
            'pending' => 'Bekliyor',
            'uploaded' => 'Yüklendi',
            'revision' => 'Revizyon',
            'approved' => 'Onaylı',
        ];

        $palette = [
            'rgba(37,99,235,0.7)',
            'rgba(16,185,129,0.7)',
            'rgba(245,158,11,0.7)',
            'rgba(239,68,68,0.7)',
            'rgba(139,92,246,0.7)',
            'rgba(20,184,166,0.7)',
        ];

        $labels = [];
        $tableRows = [];
        $metricData = array_fill_keys($metrics, []);

        foreach ($rows as $row) {
            $row = (array) $row;
            $labelParts = [];

            foreach ($dimensions as $dimension) {
                $labelParts[] = match ($dimension) {
                    'supplier' => $row['dim_supplier'] ?? '—',
                    'month' => str_pad((string) ($row['dim_month'] ?? 0), 2, '0', STR_PAD_LEFT) . '/' . ($row['dim_year'] ?? ''),
                    'year' => (string) ($row['dim_year_val'] ?? ''),
                    'quarter' => 'Q' . ($row['dim_quarter'] ?? '') . ' ' . ($row['dim_q_year'] ?? ''),
                    'order_status' => $orderStatusLabels[$row['dim_order_status'] ?? ''] ?? ($row['dim_order_status'] ?? '—'),
                    'artwork_status' => $artworkStatusLabels[$row['dim_artwork_status'] ?? ''] ?? ($row['dim_artwork_status'] ?? '—'),
                    'product_code' => $row['dim_product_code'] ?? '—',
                    'order_no' => $row['dim_order_no'] ?? '—',
                    default => '—',
                };
            }

            $label = implode(' · ', $labelParts) ?: 'Tümü';
            $labels[] = $label;

            $tableRow = ['label' => $label];

            foreach ($metrics as $metric) {
                $value = $row["metric_{$metric}"] ?? null;
                $metricData[$metric][] = $value !== null ? (float) $value : 0;
                $tableRow[$metric] = $value !== null ? $value : '—';
            }

            $tableRows[] = $tableRow;
        }

        $datasets = [];

        foreach ($metrics as $index => $metric) {
            $color = $palette[$index % count($palette)];
            $datasets[] = [
                'label' => $metricLabels[$metric] ?? $metric,
                'data' => $metricData[$metric],
                'backgroundColor' => $color,
                'borderColor' => str_replace('0.7', '1', $color),
                'borderWidth' => 2,
                'borderRadius' => 4,
                'fill' => false,
                'tension' => 0.35,
            ];
        }

        $columns = [];

        foreach ($dimensions as $dimension) {
            $columns[] = $dimensionLabels[$dimension] ?? $dimension;
        }

        foreach ($metrics as $metric) {
            $columns[] = $metricLabels[$metric] ?? $metric;
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'table' => $tableRows,
            'columns' => $columns,
            'row_count' => count($rows),
        ];
    }

    private function hasManualArtworkColumns(): bool
    {
        static $hasColumns;

        if ($hasColumns === null) {
            $hasColumns = Schema::hasColumns('purchase_order_lines', [
                'manual_artwork_completed_at',
                'manual_artwork_completed_by',
                'manual_artwork_note',
            ]);
        }

        return $hasColumns;
    }

    private function yearExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%Y', {$column}) AS INTEGER)"
            : "YEAR({$column})";
    }

    private function monthExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', {$column}) AS INTEGER)"
            : "MONTH({$column})";
    }

    private function quarterExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "CAST((((CAST(strftime('%m', {$column}) AS INTEGER) - 1) / 3) + 1) AS INTEGER)"
            : "QUARTER({$column})";
    }
}
