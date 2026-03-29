<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfSetupComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if (self::isInstalled()) {
            abort(403, 'Kurulum zaten tamamlandi.');
        }

        return $next($request);
    }

    public static function isInstalled(): bool
    {
        if (app()->environment('testing')) {
            return (bool) config('app.installed', false);
        }

        return file_exists(storage_path('app/.setup_complete'))
            && (bool) config('app.installed', false);
    }
}
