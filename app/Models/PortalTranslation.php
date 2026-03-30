<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalTranslation extends Model
{
    protected $fillable = [
        'group',
        'key',
        'locale',
        'value',
    ];
}
