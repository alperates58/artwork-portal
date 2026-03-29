<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataTransferRecord extends Model
{
    protected $fillable = [
        'direction',
        'entity_type',
        'entity_key',
        'selection_hash',
        'payload_hash',
        'batch_uuid',
        'transferred_at',
    ];

    protected function casts(): array
    {
        return [
            'transferred_at' => 'datetime',
        ];
    }
}
