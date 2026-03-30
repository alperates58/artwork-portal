<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'order_no',
        'status',
        'shipment_status',
        'shipment_reference',
        'shipment_synced_at',
        'shipment_payload',
        'erp_source',
        'source_metadata',
        'order_date',
        'due_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'due_date' => 'date',
            'shipment_synced_at' => 'datetime',
            'shipment_payload' => 'array',
            'source_metadata' => 'array',
        ];
    }

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

    public function orderNotes(): HasMany
    {
        return $this->hasMany(OrderNote::class)
            ->whereNull('purchase_order_line_id')
            ->whereNull('parent_id')
            ->orderBy('created_at');
    }

    public function allOrderNotes(): HasMany
    {
        return $this->hasMany(OrderNote::class)->orderBy('created_at');
    }

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

    public function scopeWithListMetrics(Builder $query): Builder
    {
        if (! self::hasManualArtworkColumns()) {
            return $query->withCount('lines')
                ->withCount([
                    'lines as pending_artwork_lines_count' => fn (Builder $lineQuery) => $lineQuery
                        ->whereDoesntHave('artwork.activeRevision'),
                ]);
        }

        return $query->withCount('lines')
            ->withCount([
                'lines as pending_artwork_lines_count' => fn (Builder $lineQuery) => $lineQuery
                    ->whereNull('manual_artwork_completed_at')
                    ->whereDoesntHave('artwork.activeRevision'),
                'lines as manual_artwork_lines_count' => fn (Builder $lineQuery) => $lineQuery
                    ->whereNotNull('manual_artwork_completed_at'),
            ]);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Taslak',
            'active' => 'Aktif',
            'completed' => 'Tamamlandı',
            'cancelled' => 'İptal',
            default => $this->status,
        };
    }

    public function getShipmentStatusLabelAttribute(): string
    {
        return match ($this->shipment_status) {
            'pending' => 'Sevk bekleniyor',
            'dispatched' => 'Sevk edildi',
            'delivered' => 'Stoğa ulaştı',
            'not_found' => 'Mikro kaydı bulunamadı',
            default => 'Henüz doğrulanmadı',
        };
    }

    public function getPendingArtworkCountAttribute(): int
    {
        if (array_key_exists('pending_artwork_lines_count', $this->attributes)) {
            return (int) $this->attributes['pending_artwork_lines_count'];
        }

        if (! self::hasManualArtworkColumns()) {
            return $this->lines()
                ->whereDoesntHave('artwork.revisions', fn ($query) => $query->where('is_active', true))
                ->count();
        }

        return $this->lines()
            ->whereNull('manual_artwork_completed_at')
            ->whereDoesntHave('artwork.revisions', fn ($query) => $query->where('is_active', true))
            ->count();
    }

    public function getShippedLinesCountAttribute(): int
    {
        if ($this->relationLoaded('lines')) {
            return $this->lines->where('shipped_quantity', '>', 0)->count();
        }

        return $this->lines()->where('shipped_quantity', '>', 0)->count();
    }

    public function getManualArtworkCountAttribute(): int
    {
        if (array_key_exists('manual_artwork_lines_count', $this->attributes)) {
            return (int) $this->attributes['manual_artwork_lines_count'];
        }

        if (! self::hasManualArtworkColumns()) {
            return 0;
        }

        return $this->lines()->whereNotNull('manual_artwork_completed_at')->count();
    }

    private static function hasManualArtworkColumns(): bool
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
}
