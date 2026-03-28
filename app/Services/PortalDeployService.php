<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;

class PortalDeployService
{
    /**
     * GitHub'dan kodu çek ve artisan adımlarını uygula.
     */
    public function deploy(): array
    {
        $steps = [];

        // ── 1. git pull ──────────────────────────────────────────────
        $gitStep = $this->runGitPull();
        $steps[] = $gitStep;

        if (! $gitStep['ok']) {
            return ['ok' => false, 'steps' => $steps, 'git_failed' => true];
        }

        return array_merge($this->applyArtisan($steps), ['git_failed' => false]);
    }

    /**
     * Sadece artisan adımlarını uygula (git pull el ile yapıldıysa).
     */
    public function applyOnly(): array
    {
        return array_merge($this->applyArtisan([]), ['git_failed' => false]);
    }

    // ── Private ──────────────────────────────────────────────────────

    private function runGitPull(): array
    {
        $base = base_path();
        $gitFlag = '-c safe.directory=' . escapeshellarg($base) . ' -C ' . escapeshellarg($base);

        // Önce normal dene
        $lines = [];
        $exitCode = 0;
        exec('git ' . $gitFlag . ' pull origin main 2>&1', $lines, $exitCode);

        if ($exitCode === 0) {
            return [
                'cmd'    => 'git pull origin main',
                'output' => implode("\n", $lines) ?: '(çıktı yok)',
                'ok'     => true,
            ];
        }

        $firstOutput = implode("\n", $lines);

        // Permission hatası varsa sudo ile dene
        if (str_contains($firstOutput, 'Permission denied') || str_contains($firstOutput, 'permission denied')) {
            $lines2    = [];
            $exitCode2 = 0;
            exec('sudo -n git ' . $gitFlag . ' pull origin main 2>&1', $lines2, $exitCode2);

            if ($exitCode2 === 0) {
                return [
                    'cmd'    => 'git pull origin main (sudo)',
                    'output' => implode("\n", $lines2) ?: '(çıktı yok)',
                    'ok'     => true,
                ];
            }

            // sudo da başarısız — izin hatası açıklaması ekle
            $hint = "\n\n──────────────────────────────────────\n"
                . "İzin hatası: PHP konteyneri .git/ dizinine yazamıyor.\n"
                . "Sunucuda şu komutu çalıştırın:\n\n"
                . "  sudo chown -R www-data:www-data /var/www/artwork-portal\n\n"
                . "Veya git pull'u host'tan manuel çalıştırın,\n"
                . "ardından \"Artisan Adımlarını Uygula\" butonunu kullanın.";

            return [
                'cmd'    => 'git pull origin main',
                'output' => $firstOutput . $hint,
                'ok'     => false,
            ];
        }

        return [
            'cmd'    => 'git pull origin main',
            'output' => $firstOutput ?: '(çıktı yok)',
            'ok'     => false,
        ];
    }

    private function applyArtisan(array $steps): array
    {
        $ok = true;

        // Önce temizle
        $steps[] = $this->runArtisan('config:clear');
        $steps[] = $this->runArtisan('cache:clear');

        // portal:update: migrate, storage:link, optimize:clear, queue:restart
        // Production'da config:cache/route:cache/view:cache de rebuild eder
        $step = $this->runArtisan('portal:update');
        $steps[] = $step;

        if (! $step['ok']) {
            $ok = false;
        }

        // portal:update production'da cache rebuild eder; stale cache'i önlemek için tekrar temizle
        $steps[] = $this->runArtisan('config:clear');
        $steps[] = $this->runArtisan('cache:clear');

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
