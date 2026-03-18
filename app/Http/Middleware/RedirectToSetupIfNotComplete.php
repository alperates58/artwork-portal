<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
