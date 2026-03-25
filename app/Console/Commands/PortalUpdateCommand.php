<?php

namespace App\Console\Commands;

use App\Services\PortalUpdateStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PortalUpdateCommand extends Command
{
    protected $signature = 'portal:update {--skip-cache : Cache warmup adimlarini atla}';

    protected $description = 'Git pull sonrasi guvenli portal guncelleme adimlarini uygular.';

    public function handle(PortalUpdateStatus $updateStatus): int
    {
        $steps = [
            'migrate --force',
            'storage:link',
            'optimize:clear',
            'queue:restart',
        ];

        try {
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

            $updateStatus->markRun('success', 'portal:update basariyla tamamlandi.');
        } catch (\Throwable $exception) {
            $updateStatus->markRun('failed', $exception->getMessage());

            throw $exception;
        }

        $this->newLine();
        $this->info('Portal update akisi tamamlandi.');
        $this->line('Not: Scheduler ve queue supervisor servisleri ayri olarak calisir durumda olmalidir.');

        return self::SUCCESS;
    }
}
