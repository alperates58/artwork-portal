<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogSlowRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $thresholdMs = (int) env('APP_SLOW_REQUEST_THRESHOLD_MS', 0);
        $profileEnabled = $this->shouldProfile($request);

        if (! $profileEnabled && $thresholdMs <= 0) {
            return $next($request);
        }

        $queryCount = 0;
        $queryTimeMs = 0.0;
        $startedAt = hrtime(true);
        $startedMemory = memory_get_usage(true);
        $startedPeakMemory = memory_get_peak_usage(true);

        DB::listen(function ($query) use (&$queryCount, &$queryTimeMs) {
            $queryCount++;
            $queryTimeMs += (float) $query->time;
        });

        $response = $next($request);

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $memoryDeltaMb = (memory_get_usage(true) - $startedMemory) / 1024 / 1024;
        $peakMemoryDeltaMb = (memory_get_peak_usage(true) - $startedPeakMemory) / 1024 / 1024;
        $queryRatio = $durationMs > 0 ? min(100, ($queryTimeMs / $durationMs) * 100) : 0;
        $routeName = $request->route()?->getName();

        if ($profileEnabled) {
            $response->headers->set('X-Portal-Profile-Route', $routeName ?? 'unnamed');
            $response->headers->set('X-Portal-Profile-Duration-Ms', number_format($durationMs, 2, '.', ''));
            $response->headers->set('X-Portal-Profile-Query-Count', (string) $queryCount);
            $response->headers->set('X-Portal-Profile-Query-Time-Ms', number_format($queryTimeMs, 2, '.', ''));
            $response->headers->set('X-Portal-Profile-Query-Ratio', number_format($queryRatio, 1, '.', '').'%');
            $response->headers->set('X-Portal-Profile-App-Time-Ms', number_format(max(0, $durationMs - $queryTimeMs), 2, '.', ''));
            $response->headers->set('X-Portal-Profile-Memory-Mb', number_format($memoryDeltaMb, 2, '.', ''));
            $response->headers->set('X-Portal-Profile-Peak-Memory-Mb', number_format($peakMemoryDeltaMb, 2, '.', ''));
            $response->headers->set('X-Portal-Profile-Response-Kb', number_format(strlen((string) $response->getContent()) / 1024, 1, '.', ''));

            Log::info('request_profile', [
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $routeName,
                'status' => $response->getStatusCode(),
                'duration_ms' => round($durationMs, 2),
                'query_count' => $queryCount,
                'query_time_ms' => round($queryTimeMs, 2),
                'app_time_ms' => round(max(0, $durationMs - $queryTimeMs), 2),
                'query_ratio_pct' => round($queryRatio, 1),
                'memory_mb' => round($memoryDeltaMb, 2),
                'peak_memory_mb' => round($peakMemoryDeltaMb, 2),
                'response_kb' => round(strlen((string) $response->getContent()) / 1024, 1),
                'user_id' => $request->user()?->id,
            ]);
        }

        if ($durationMs >= $thresholdMs) {
            Log::warning('slow_request', [
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $routeName,
                'status' => $response->getStatusCode(),
                'duration_ms' => round($durationMs, 2),
                'query_count' => $queryCount,
                'query_time_ms' => round($queryTimeMs, 2),
                'user_id' => $request->user()?->id,
            ]);
        }

        return $response;
    }

    private function shouldProfile(Request $request): bool
    {
        if (! app()->environment(['local', 'testing', 'production'])) {
            return false;
        }

        if ($request->boolean('profile')) {
            return true;
        }

        $header = $request->headers->get('X-Portal-Profile');

        return filled($header) && Str::lower($header) !== 'off';
    }
}
