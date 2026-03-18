<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN     = 'admin';
    case PURCHASING = 'purchasing';
    case GRAPHIC   = 'graphic';
    case SUPPLIER  = 'supplier';

    public function label(): string
    {
        return match($this) {
            self::ADMIN      => 'Admin',
            self::PURCHASING => 'Satın Alma',
            self::GRAPHIC    => 'Grafik Departmanı',
            self::SUPPLIER   => 'Tedarikçi',
        };
    }

    public function isInternal(): bool
    {
        return $this !== self::SUPPLIER;
    }

    public static function internalRoles(): array
    {
        return [self::ADMIN, self::PURCHASING, self::GRAPHIC];
    }
}
