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
        'manual_artwork_completed_at',
        'manual_artwork_completed_by',
        'manual_artwork_note',
    ];

    protected function casts(): array
    {
        return [
            'artwork_status' => ArtworkStatus::class,
            'quantity'       => 'integer',
            'shipped_quantity' => 'integer',
            'manual_artwork_completed_at' => 'datetime',
        ];
    }

    // ─── Relations ───────────────────────────────────────────────

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function manualArtworkCompletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manual_artwork_completed_by');
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

    public function getIsManualArtworkCompletedAttribute(): bool
    {
        return $this->manual_artwork_completed_at !== null;
    }

    public function hasArtworkStageCompleted(): bool
    {
        return $this->hasActiveArtwork() || $this->is_manual_artwork_completed;
    }

    public function getActiveRevisionAttribute(): ?ArtworkRevision
    {
        return $this->artwork?->activeRevision;
    }
}
