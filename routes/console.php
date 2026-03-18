<?php

use Illuminate\Support\Facades\Schedule;

// ERP sync — her saat
Schedule::command('erp:sync')->hourly()->withoutOverlapping()->runInBackground();

// Log temizleme — ayda bir
Schedule::command('logs:prune --days=90')->monthly()->runInBackground();

// Failed queue temizleme — haftalık
Schedule::command('queue:prune-failed --hours=168')->weekly();
