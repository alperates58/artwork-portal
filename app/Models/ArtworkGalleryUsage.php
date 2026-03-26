<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtworkGalleryUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'artwork_gallery_id',
        'purchase_order_id',
        'purchase_order_line_id',
        'supplier_id',
        'used_at',
        'usage_type',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    public function galleryItem(): BelongsTo
    {
        return $this->belongsTo(ArtworkGallery::class, 'artwork_gallery_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
