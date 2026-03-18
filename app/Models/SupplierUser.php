<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierUser extends Model
{
    protected $fillable = [
        'supplier_id',
        'user_id',
        'title',
        'is_primary',
        'can_download',
        'can_approve',
    ];

    protected function casts(): array
    {
        return [
            'is_primary'   => 'boolean',
            'can_download' => 'boolean',
            'can_approve'  => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
