<?php

use App\Jobs\SyncAllActiveSuppliersJob;
use Illuminate\Support\Facades\Schedule;

$mikroSyncInterval = max(5, (int) config('mikro.sync_interval_minutes', 5));

// Mikro supplier sync: tek aktif zamanlanmis yol
Schedule::job(new SyncAllActiveSuppliersJob())
    ->cron("*/{$mikroSyncInterval} * * * *")
    ->withoutOverlapping();

// Log temizleme: ayda bir
Schedule::command('logs:prune --days=90')->monthly()->runInBackground();

// Failed queue temizleme: haftalik
Schedule::command('queue:prune-failed --hours=168')->weekly();
