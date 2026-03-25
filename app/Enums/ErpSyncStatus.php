<?php

namespace App\Enums;

enum ErpSyncStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case PARTIAL = 'partial';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
