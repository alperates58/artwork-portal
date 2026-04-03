<?php

use App\Jobs\SyncAllActiveSuppliersJob;
use App\Jobs\SyncStockCardsJob;
use Illuminate\Support\Facades\Schedule;

$mikroSyncInterval = max(5, (int) config('mikro.sync_interval_minutes', 5));

// Mikro supplier sync: tek aktif zamanlanmis yol
Schedule::job(new SyncAllActiveSuppliersJob())
    ->cron("*/{$mikroSyncInterval} * * * *")
    ->withoutOverlapping();

// Stok kartı sync: her gece 02:00 (sipariş sync'inden çok daha az sıklıkta yeterli)
Schedule::job(new SyncStockCardsJob())
    ->dailyAt('02:00')
    ->withoutOverlapping();

// Log temizleme: ayda bir
Schedule::command('logs:prune --days=90')->monthly()->runInBackground();

// Failed queue temizleme: haftalik
Schedule::command('queue:prune-failed --hours=168')->weekly();
