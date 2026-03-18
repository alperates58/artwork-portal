<?php

namespace App\Enums;

enum ArtworkStatus: string
{
    case PENDING  = 'pending';   // Artwork henüz yüklenmedi
    case UPLOADED = 'uploaded';  // Yüklendi, aktif revizyon var
    case REVISION = 'revision';  // Revizyon gerekiyor
    case APPROVED = 'approved';  // Tedarikçi onayladı

    public function label(): string
    {
        return match($this) {
            self::PENDING  => 'Bekliyor',
            self::UPLOADED => 'Yüklendi',
            self::REVISION => 'Revizyon Gerekli',
            self::APPROVED => 'Onaylandı',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::PENDING  => 'badge-warning',
            self::UPLOADED => 'badge-success',
            self::REVISION => 'badge-danger',
            self::APPROVED => 'badge-info',
        };
    }
}
