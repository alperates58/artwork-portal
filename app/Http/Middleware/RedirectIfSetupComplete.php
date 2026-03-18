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
            return filter_var(env('APP_INSTALLED', false), FILTER_VALIDATE_BOOLEAN);
        }

        return file_exists(storage_path('app/.setup_complete'))
            && filter_var(env('APP_INSTALLED', false), FILTER_VALIDATE_BOOLEAN);
    }
}
