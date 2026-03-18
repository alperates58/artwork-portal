<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class PruneAuditLogs extends Command
{
    protected $signature   = 'logs:prune {--days=90 : Kaç günden eski loglar silinsin}';
    protected $description = 'Eski audit loglarını temizle';

    public function handle(): int
    {
        $days    = (int) $this->option('days');
        $deleted = AuditLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("{$deleted} log kaydı silindi ({$days} günden eski).");

        return self::SUCCESS;
    }
}
