<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportQueryService
{
    public function run(array $dimensions, array $metrics, array $filters = []): array
    {
        if (empty($dimensions) || empty($metrics)) {
            return ['labels' => [], 'datasets' => [], 'table' => [], 'columns' => [], 'row_count' => 0];
        }

        $query = DB::table('purchase_order_lines')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->whereNull('suppliers.deleted_at');

        // Apply filters
        if (!empty($filters['supplier_id'])) {
            $query->where('purchase_orders.supplier_id', $filters['supplier_id']);
        }
        if (!empty($filters['order_status'])) {
            $query->where('purchase_orders.status', $filters['order_status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('purchase_orders.order_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('purchase_orders.order_date', '<=', $filters['date_to']);
        }

        // Revision count join
        if (in_array('revision_count', $metrics)) {
            $query->leftJoin('artworks', 'artworks.order_line_id', '=', 'purchase_order_lines.id')
                  ->leftJoin('artwork_revisions', 'artwork_revisions.artwork_id', '=', 'artworks.id');
        }

        // Avg days join via subquery
        if (in_array('avg_days_to_upload', $metrics)) {
            $subquery = DB::table('artwork_revisions')
                ->join('artworks as aw2', 'aw2.id', '=', 'artwork_revisions.artwork_id')
                ->select('aw2.order_line_id', DB::raw('MIN(artwork_revisions.created_at) as min_uploaded_at'))
                ->where('artwork_revisions.is_active', true)
                ->groupBy('aw2.order_line_id');

            $query->leftJoinSub($subquery, 'first_rev', 'first_rev.order_line_id', '=', 'purchase_order_lines.id');
        }

        // Build SELECT + GROUP BY from dimensions
        $selects  = [];
        $groupBys = [];

        foreach ($dimensions as $dim) {
            switch ($dim) {
                case 'supplier':
                    $selects[]  = 'suppliers.name as dim_supplier';
                    $groupBys[] = 'suppliers.id';
                    $groupBys[] = 'suppliers.name';
                    break;
                case 'month':
                    $selects[]  = 'YEAR(purchase_orders.order_date) as dim_year';
                    $selects[]  = 'MONTH(purchase_orders.order_date) as dim_month';
                    $groupBys[] = DB::raw('YEAR(purchase_orders.order_date)');
                    $groupBys[] = DB::raw('MONTH(purchase_orders.order_date)');
                    break;
                case 'year':
                    $selects[]  = 'YEAR(purchase_orders.order_date) as dim_year_val';
                    $groupBys[] = DB::raw('YEAR(purchase_orders.order_date)');
                    break;
                case 'quarter':
                    $selects[]  = 'YEAR(purchase_orders.order_date) as dim_q_year';
                    $selects[]  = 'QUARTER(purchase_orders.order_date) as dim_quarter';
                    $groupBys[] = DB::raw('YEAR(purchase_orders.order_date)');
                    $groupBys[] = DB::raw('QUARTER(purchase_orders.order_date)');
                    break;
                case 'order_status':
                    $selects[]  = 'purchase_orders.status as dim_order_status';
                    $groupBys[] = 'purchase_orders.status';
                    break;
                case 'artwork_status':
                    $selects[]  = 'purchase_order_lines.artwork_status as dim_artwork_status';
                    $groupBys[] = 'purchase_order_lines.artwork_status';
                    break;
                case 'product_code':
                    $selects[]  = 'purchase_order_lines.product_code as dim_product_code';
                    $groupBys[] = 'purchase_order_lines.product_code';
                    break;
                case 'order_no':
                    $selects[]  = 'purchase_orders.order_no as dim_order_no';
                    $groupBys[] = 'purchase_orders.order_no';
                    break;
            }
        }

        // Metric aggregates
        foreach ($metrics as $metric) {
            switch ($metric) {
                case 'order_count':
                    $selects[] = 'COUNT(DISTINCT purchase_orders.id) as metric_order_count';
                    break;
                case 'line_count':
                    $selects[] = 'COUNT(purchase_order_lines.id) as metric_line_count';
                    break;
                case 'pending_artwork':
                    $selects[] = "SUM(CASE WHEN purchase_order_lines.artwork_status = 'pending' THEN 1 ELSE 0 END) as metric_pending_artwork";
                    break;
                case 'uploaded_artwork':
                    $selects[] = "SUM(CASE WHEN purchase_order_lines.artwork_status IN ('uploaded','approved') THEN 1 ELSE 0 END) as metric_uploaded_artwork";
                    break;
                case 'revision_count':
                    $selects[] = 'COUNT(DISTINCT artwork_revisions.id) as metric_revision_count';
                    break;
                case 'avg_days_to_upload':
                    $selects[] = 'ROUND(AVG(DATEDIFF(first_rev.min_uploaded_at, purchase_orders.order_date)), 1) as metric_avg_days_to_upload';
                    break;
            }
        }

        foreach ($selects as $sel) {
            $query->selectRaw($sel);
        }
        foreach ($groupBys as $gb) {
            $query->groupByRaw($gb instanceof \Illuminate\Database\Query\Expression ? (string) $gb : $gb);
        }

        if (!empty($groupBys)) {
            $first = $groupBys[0];
            $query->orderByRaw($first instanceof \Illuminate\Database\Query\Expression ? (string) $first : $first);
        }

        $rows = $query->limit(500)->get();

        return $this->format($rows, $dimensions, $metrics);
    }

    private function format($rows, array $dimensions, array $metrics): array
    {
        $dimLabels = [
            'supplier'       => 'Tedarikçi',
            'month'          => 'Ay',
            'year'           => 'Yıl',
            'quarter'        => 'Çeyrek',
            'order_status'   => 'Sipariş Durumu',
            'artwork_status' => 'Artwork Durumu',
            'product_code'   => 'Stok Kodu',
            'order_no'       => 'Sipariş No',
        ];
        $metricLabels = [
            'order_count'        => 'Sipariş Sayısı',
            'line_count'         => 'Satır Sayısı',
            'pending_artwork'    => 'Bekleyen Artwork',
            'uploaded_artwork'   => 'Yüklenen Artwork',
            'revision_count'     => 'Revizyon Sayısı',
            'avg_days_to_upload' => 'Ort. Yükleme (Gün)',
        ];
        $orderStatusLabels  = ['draft' => 'Taslak', 'active' => 'Aktif', 'completed' => 'Tamamlandı', 'cancelled' => 'İptal'];
        $artworkStatusLabels = ['pending' => 'Bekliyor', 'uploaded' => 'Yüklendi', 'revision' => 'Revizyon', 'approved' => 'Onaylı'];

        $palette = [
            'rgba(37,99,235,0.7)', 'rgba(16,185,129,0.7)', 'rgba(245,158,11,0.7)',
            'rgba(239,68,68,0.7)', 'rgba(139,92,246,0.7)', 'rgba(20,184,166,0.7)',
        ];

        $labels      = [];
        $tableRows   = [];
        $metricData  = array_fill_keys($metrics, []);

        foreach ($rows as $row) {
            $row = (array) $row;

            $parts = [];
            foreach ($dimensions as $dim) {
                $parts[] = match ($dim) {
                    'supplier'       => $row['dim_supplier'] ?? '—',
                    'month'          => str_pad((string)($row['dim_month'] ?? 0), 2, '0', STR_PAD_LEFT) . '/' . ($row['dim_year'] ?? ''),
                    'year'           => (string) ($row['dim_year_val'] ?? ''),
                    'quarter'        => 'Q' . ($row['dim_quarter'] ?? '') . ' ' . ($row['dim_q_year'] ?? ''),
                    'order_status'   => $orderStatusLabels[$row['dim_order_status'] ?? ''] ?? ($row['dim_order_status'] ?? '—'),
                    'artwork_status' => $artworkStatusLabels[$row['dim_artwork_status'] ?? ''] ?? ($row['dim_artwork_status'] ?? '—'),
                    'product_code'   => $row['dim_product_code'] ?? '—',
                    'order_no'       => $row['dim_order_no'] ?? '—',
                    default          => '—',
                };
            }

            $label    = implode(' · ', $parts) ?: 'Tümü';
            $labels[] = $label;

            $tr = ['label' => $label];
            foreach ($metrics as $metric) {
                $val = $row["metric_{$metric}"] ?? null;
                $metricData[$metric][] = $val !== null ? (float) $val : 0;
                $tr[$metric] = $val !== null ? $val : '—';
            }
            $tableRows[] = $tr;
        }

        $datasets = [];
        foreach ($metrics as $i => $metric) {
            $color = $palette[$i % count($palette)];
            $datasets[] = [
                'label'           => $metricLabels[$metric] ?? $metric,
                'data'            => $metricData[$metric],
                'backgroundColor' => $color,
                'borderColor'     => str_replace('0.7', '1', $color),
                'borderWidth'     => 2,
                'borderRadius'    => 4,
                'fill'            => false,
                'tension'         => 0.35,
            ];
        }

        $columns = [];
        foreach ($dimensions as $d) {
            $columns[] = $dimLabels[$d] ?? $d;
        }
        foreach ($metrics as $m) {
            $columns[] = $metricLabels[$m] ?? $m;
        }

        return [
            'labels'    => $labels,
            'datasets'  => $datasets,
            'table'     => $tableRows,
            'columns'   => $columns,
            'row_count' => count($rows),
        ];
    }
}
