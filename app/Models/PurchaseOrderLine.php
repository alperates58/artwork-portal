<?php

namespace App\Models;

use App\Enums\ArtworkStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'line_no',
        'product_code',
        'description',
        'quantity',
        'shipped_quantity',
        'unit',
        'artwork_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'artwork_status' => ArtworkStatus::class,
            'quantity'       => 'integer',
            'shipped_quantity' => 'integer',
        ];
    }

    // ─── Relations ───────────────────────────────────────────────

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function artwork(): HasOne
    {
        return $this->hasOne(Artwork::class, 'order_line_id');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function hasActiveArtwork(): bool
    {
        return $this->artwork?->activeRevision !== null;
    }

    public function getActiveRevisionAttribute(): ?ArtworkRevision
    {
        return $this->artwork?->activeRevision;
    }
}
