<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogSlowRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $thresholdMs = (int) env('APP_SLOW_REQUEST_THRESHOLD_MS', 0);

        if ($thresholdMs <= 0) {
            return $next($request);
        }

        $queryCount = 0;
        $queryTimeMs = 0.0;
        $startedAt = hrtime(true);

        DB::listen(function ($query) use (&$queryCount, &$queryTimeMs) {
            $queryCount++;
            $queryTimeMs += (float) $query->time;
        });

        $response = $next($request);

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;

        if ($durationMs >= $thresholdMs) {
            Log::warning('slow_request', [
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
                'status' => $response->getStatusCode(),
                'duration_ms' => round($durationMs, 2),
                'query_count' => $queryCount,
                'query_time_ms' => round($queryTimeMs, 2),
                'user_id' => $request->user()?->id,
            ]);
        }

        return $response;
    }
}
