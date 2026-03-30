<?php

namespace App\Support;

use App\Services\PortalLocalizationService;

class PortalTranslation
{
    public static function get(string $key, string $default, string $group = 'general', ?string $locale = null): string
    {
        return app(PortalLocalizationService::class)->text(
            key: $key,
            default: $default,
            group: $group,
            locale: $locale,
        );
    }
}
