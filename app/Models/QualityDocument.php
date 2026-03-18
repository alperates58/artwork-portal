<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualityDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'purchase_order_line_id',
        'title',
        'type',
        'spaces_path',
        'original_filename',
        'mime_type',
        'file_size',
        'valid_until',
        'is_active',
        'uploaded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'is_active'   => 'boolean',
            'file_size'   => 'integer',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'certificate'  => 'Sertifika',
            'test_report'  => 'Test Raporu',
            'specification'=> 'Teknik Şartname',
            'drawing'      => 'Teknik Çizim',
            'other'        => 'Diğer',
            default        => $this->type,
        };
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->valid_until
            && ! $this->isExpired()
            && $this->valid_until->diffInDays(now()) <= $days;
    }
}
