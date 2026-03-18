<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'order_no',
        'status',
        'order_date',
        'due_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'due_date'   => 'date',
        ];
    }

    // ─── Relations ───────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('order_no', 'like', "%{$term}%");
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft'     => 'Taslak',
            'active'    => 'Aktif',
            'completed' => 'Tamamlandı',
            'cancelled' => 'İptal',
            default     => $this->status,
        };
    }

    public function getPendingArtworkCountAttribute(): int
    {
        return $this->lines()
            ->whereDoesntHave('artwork.revisions', fn ($q) => $q->where('is_active', true))
            ->count();
    }
}
