<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomReport extends Model
{
    protected $fillable = [
        'created_by', 'name', 'description',
        'dimensions', 'metrics', 'chart_type', 'filters', 'is_shared',
    ];

    protected function casts(): array
    {
        return [
            'dimensions' => 'array',
            'metrics' => 'array',
            'filters' => 'array',
            'is_shared' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function dimensionLabels(): array
    {
        return [
            'supplier' => 'Tedarikçi',
            'month' => 'Ay',
            'year' => 'Yıl',
            'quarter' => 'Çeyrek',
            'order_status' => 'Sipariş Durumu',
            'artwork_status' => 'Artwork Durumu',
            'product_code' => 'Stok Kodu',
            'order_no' => 'Sipariş No',
        ];
    }

    public static function metricLabels(): array
    {
        return [
            'order_count' => 'Sipariş Sayısı',
            'line_count' => 'Satır Sayısı',
            'pending_artwork' => 'Bekleyen Artwork',
            'uploaded_artwork' => 'Yüklenen Artwork',
            'revision_count' => 'Revizyon Sayısı',
            'avg_days_to_upload' => 'Ort. Yükleme (Gün)',
        ];
    }
}
