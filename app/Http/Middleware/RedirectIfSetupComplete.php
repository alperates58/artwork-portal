<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

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
        if ((bool) config('app.installed', false)) {
            return true;
        }

        if (! app()->environment('testing') && file_exists(storage_path('app/.setup_complete'))) {
            return true;
        }

        if (! Schema::hasTable('users')) {
            return false;
        }

        try {
            return DB::table('users')->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}
