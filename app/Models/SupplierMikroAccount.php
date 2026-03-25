<?php

namespace App\Models;

use App\Enums\ErpSyncStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierMikroAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'mikro_cari_kod',
        'mikro_company_code',
        'mikro_work_year',
        'is_active',
        'last_sync_at',
        'last_sync_status',
        'last_sync_error',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_sync_at' => 'datetime',
            'last_sync_status' => ErpSyncStatus::class,
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
