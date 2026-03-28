<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;

class PortalDeployService
{
    /**
     * GitHub'dan kodu çek ve gerekli post-deploy adımlarını uygula.
     * Çalıştırılan komutlar ve çıktıları adım adım döner.
     */
    public function deploy(): array
    {
        $steps = [];
        $ok    = true;

        // ── 1. git pull ──────────────────────────────────────────────
        $lines    = [];
        $exitCode = 0;

        exec(
            'git -C ' . escapeshellarg(base_path()) . ' pull origin main 2>&1',
            $lines,
            $exitCode
        );

        $gitOutput = implode("\n", $lines);
        $gitOk     = $exitCode === 0;

        $steps[] = [
            'cmd'    => 'git pull origin main',
            'output' => $gitOutput ?: '(çıktı yok)',
            'ok'     => $gitOk,
        ];

        if (! $gitOk) {
            return ['ok' => false, 'steps' => $steps];
        }

        // ── 2. config:clear ─────────────────────────────────────────
        $steps[] = $this->runArtisan('config:clear');

        // ── 3. cache:clear ──────────────────────────────────────────
        $steps[] = $this->runArtisan('cache:clear');

        // ── 4. portal:update (migrate + optimize + queue:restart) ───
        $step = $this->runArtisan('portal:update', ['--skip-cache' => true]);
        $steps[] = $step;

        if (! $step['ok']) {
            $ok = false;
        }

        return ['ok' => $ok, 'steps' => $steps];
    }

    private function runArtisan(string $command, array $params = []): array
    {
        try {
            $exitCode = Artisan::call($command, $params);
            $output   = trim(Artisan::output()) ?: '(çıktı yok)';

            return [
                'cmd'    => 'php artisan ' . $command,
                'output' => $output,
                'ok'     => $exitCode === 0,
            ];
        } catch (\Throwable $e) {
            return [
                'cmd'    => 'php artisan ' . $command,
                'output' => $e->getMessage(),
                'ok'     => false,
            ];
        }
    }
}
