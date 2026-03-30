<?php

namespace App\Http\Middleware;

use App\Services\PortalLocalizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyPortalLocale
{
    public function __construct(private PortalLocalizationService $localization) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('portal_locale');

        if (! $this->localization->isAvailableLocale($locale)) {
            $locale = $this->localization->defaultLocale();
            $request->session()->put('portal_locale', $locale);
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
