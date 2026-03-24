<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PortalUpdateCommand extends Command
{
    protected $signature = 'portal:update {--skip-cache : Cache warmup adımlarını atla}';

    protected $description = 'Git pull sonrası güvenli portal güncelleme adımlarını uygular.';

    public function handle(): int
    {
        $steps = [
            'migrate --force',
            'storage:link',
            'optimize:clear',
            'queue:restart',
        ];

        foreach ($steps as $step) {
            $this->components->task($step, function () use ($step) {
                Artisan::call($step);

                return Artisan::output();
            });
        }

        if (! $this->option('skip-cache') && app()->environment('production')) {
            foreach (['config:cache', 'route:cache', 'view:cache'] as $step) {
                $this->components->task($step, function () use ($step) {
                    Artisan::call($step);

                    return Artisan::output();
                });
            }
        }

        $this->newLine();
        $this->info('Portal update akışı tamamlandı.');
        $this->line('Not: Scheduler/queue supervisor servisleri ayrı olarak çalışır durumda olmalıdır.');

        return self::SUCCESS;
    }
}
