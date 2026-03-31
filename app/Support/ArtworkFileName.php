<?php

namespace App\Support;

use Illuminate\Support\Str;

class ArtworkFileName
{
    public static function baseName(?string $stockCode, int $revisionNo, ?string $fallback = null): string
    {
        $source = trim((string) ($stockCode ?: $fallback ?: 'artwork'));
        $normalized = Str::of($source)
            ->replace(['/', '\\'], '-')
            ->slug('-')
            ->trim('-')
            ->value();

        return ($normalized !== '' ? $normalized : 'artwork') . '-rev-' . $revisionNo;
    }

    public static function original(?string $stockCode, int $revisionNo, string $extension, ?string $fallback = null): string
    {
        return self::baseName($stockCode, $revisionNo, $fallback) . '.' . strtolower(ltrim($extension, '.'));
    }

    public static function preview(?string $stockCode, int $revisionNo, ?string $fallback = null): string
    {
        return self::baseName($stockCode, $revisionNo, $fallback) . '-preview.png';
    }
}
