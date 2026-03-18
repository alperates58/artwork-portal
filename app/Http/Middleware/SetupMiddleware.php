<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Çift koruma: lock dosyası + .env bayrağı
 */
class RedirectIfSetupComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if (self::isInstalled()) {
            abort(403, 'Kurulum zaten tamamlandı.');
        }
        return $next($request);
    }

    public static function isInstalled(): bool
    {
        return file_exists(storage_path('app/.setup_complete'))
            && env('APP_INSTALLED') === 'true';
    }
}

class RedirectToSetupIfNotComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('setup.*') || $request->is('up', 'api/*')) {
            return $next($request);
        }

        if (! RedirectIfSetupComplete::isInstalled()) {
            return redirect()->route('setup.index');
        }

        return $next($request);
    }
}
