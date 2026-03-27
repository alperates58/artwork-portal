<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardCacheService
{
    public const METRICS_KEY = 'dashboard.metrics';
    public const PANELS_KEY = 'dashboard.panels';

    public function rememberMetrics(Closure $callback): array
    {
        return Cache::remember(self::METRICS_KEY, 300, $callback);
    }

    public function rememberPanels(Closure $callback): array
    {
        return Cache::remember(self::PANELS_KEY, 60, $callback);
    }

    public function forgetMetrics(): void
    {
        Cache::forget(self::METRICS_KEY);
    }

    public function forgetPanels(): void
    {
        Cache::forget(self::PANELS_KEY);
    }

    public function forgetAll(): void
    {
        $this->forgetMetrics();
        $this->forgetPanels();
    }

    public function forgetMetricsAfterCommit(): void
    {
        DB::afterCommit(fn () => $this->forgetMetrics());
    }

    public function forgetAllAfterCommit(): void
    {
        DB::afterCommit(fn () => $this->forgetAll());
    }
}
