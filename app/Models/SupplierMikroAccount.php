<?php

namespace App\Models;

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
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_sync_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
