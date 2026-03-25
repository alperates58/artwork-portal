<?php

namespace App\Console\Commands;

use App\Services\PortalUpdateStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PortalUpdateCommand extends Command
{
    protected $signature = 'portal:update {--skip-cache : Cache warmup adimlarini atla}';

    protected $description = 'Git pull sonrasi guvenli portal guncelleme adimlarini uygular.';

    public function handle(PortalUpdateStatus $updateStatus): int
    {
        $fromVersion = Schema::hasTable('system_settings')
            ? DB::table('system_settings')->where('key', 'system.update.last_version')->value('value')
            : null;
        $ranMigrationsBefore = $this->ranMigrations();
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

            $updateStatus->markRun('success', 'portal:update basariyla tamamlandi.', [
                'from_version' => $fromVersion,
                'applied_migrations' => array_values(array_diff($this->ranMigrations(), $ranMigrationsBefore)),
            ]);
        } catch (\Throwable $exception) {
            $updateStatus->markRun('failed', $exception->getMessage(), [
                'from_version' => $fromVersion,
            ]);

            throw $exception;
        }

        $this->newLine();
        $this->info('Portal update akisi tamamlandi.');
        $this->line('Not: Scheduler ve queue supervisor servisleri ayri olarak calisir durumda olmalidir.');

        return self::SUCCESS;
    }

    private function ranMigrations(): array
    {
        if (! Schema::hasTable('migrations')) {
            return [];
        }

        return DB::table('migrations')
            ->orderBy('id')
            ->pluck('migration')
            ->all();
    }
}
