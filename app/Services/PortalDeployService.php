<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class PortalDeployService
{
    /**
     * GitHub'dan kodu çek ve update adımlarını uygula.
     */
    public function deploy(): array
    {
        $steps = [];

        $gitStep = $this->runGitPull();
        $steps[] = $gitStep;

        if (! $gitStep['ok']) {
            return ['ok' => false, 'steps' => $steps, 'git_failed' => true];
        }

        return array_merge($this->applyUpdatePipeline($steps), ['git_failed' => false]);
    }

    /**
     * Sadece update adımlarını uygula (git pull manuel yapıldıysa).
     */
    public function applyOnly(): array
    {
        return array_merge($this->applyUpdatePipeline([]), ['git_failed' => false]);
    }

    private function runGitPull(): array
    {
        $base = base_path();
        $gitArgs = ['git', '-c', "safe.directory={$base}", '-C', $base, 'pull', 'origin', 'main'];

        $step = $this->runProcessCommand('git pull origin main', $gitArgs, $base);
        if ($step['ok']) {
            return $step;
        }

        $firstOutput = $step['output'] ?? '';

        if (str_contains(strtolower($firstOutput), 'permission denied')) {
            $sudoStep = $this->runProcessCommand(
                'git pull origin main (sudo)',
                ['sudo', '-n', ...$gitArgs],
                $base
            );

            if ($sudoStep['ok']) {
                return $sudoStep;
            }

            $hint = "\n\n──────────────────────────────────────\n"
                . "İzin hatası: PHP konteyneri .git/ dizinine yazamıyor.\n"
                . "Sunucuda şu komutu çalıştırın:\n\n"
                . "  sudo chown -R www-data:www-data /var/www/artwork-portal\n\n"
                . "Veya git pull'u host'tan manuel çalıştırın,\n"
                . "ardından \"Artisan Adımlarını Uygula\" butonunu kullanın.";

            return [
                'cmd' => 'git pull origin main',
                'output' => trim(($firstOutput ?: '(çıktı yok)') . $hint),
                'ok' => false,
            ];
        }

        return $step;
    }

    private function applyUpdatePipeline(array $steps): array
    {
        $ok = true;

        $steps[] = $this->runArtisan('config:clear');
        $steps[] = $this->runArtisan('cache:clear');

        $portalUpdateStep = $this->runArtisan('portal:update');
        $steps[] = $portalUpdateStep;

        if (! $portalUpdateStep['ok']) {
            return ['ok' => false, 'steps' => $steps];
        }

        $frontendBuild = $this->buildFrontendAssets();
        foreach ($frontendBuild['steps'] as $frontendStep) {
            $steps[] = $frontendStep;
        }

        if (! $frontendBuild['ok']) {
            $ok = false;
        }

        $steps[] = $this->runArtisan('config:clear');
        $steps[] = $this->runArtisan('cache:clear');

        return ['ok' => $ok, 'steps' => $steps];
    }

    private function buildFrontendAssets(): array
    {
        $base = base_path();
        $steps = [];
        $hasLock = file_exists(base_path('package-lock.json'));

        if ($this->binaryExists('npm')) {
            $installStep = $this->runProcessCommand(
                $hasLock ? 'npm ci --no-audit --no-fund' : 'npm install --no-audit --no-fund',
                $hasLock
                    ? ['npm', 'ci', '--no-audit', '--no-fund']
                    : ['npm', 'install', '--no-audit', '--no-fund'],
                $base,
                1200
            );
            $steps[] = $installStep;

            if (! $installStep['ok']) {
                // Bazı ortamlarda lock dosyası eski olabilir; npm install ile ikinci deneme yap.
                if ($hasLock) {
                    $fallbackInstall = $this->runProcessCommand(
                        'npm install --no-audit --no-fund (fallback)',
                        ['npm', 'install', '--no-audit', '--no-fund'],
                        $base,
                        1200
                    );
                    $steps[] = $fallbackInstall;

                    if (! $fallbackInstall['ok']) {
                        return ['ok' => false, 'steps' => $steps];
                    }
                } else {
                    return ['ok' => false, 'steps' => $steps];
                }
            }

            $buildStep = $this->runProcessCommand('npm run build', ['npm', 'run', 'build'], $base, 1200);
            $steps[] = $buildStep;

            return ['ok' => $buildStep['ok'], 'steps' => $steps];
        }

        if ($this->binaryExists('docker')) {
            $installStep = $this->runProcessCommand(
                $hasLock
                    ? 'docker compose run --rm node npm ci --no-audit --no-fund'
                    : 'docker compose run --rm node npm install --no-audit --no-fund',
                $hasLock
                    ? ['docker', 'compose', 'run', '--rm', 'node', 'npm', 'ci', '--no-audit', '--no-fund']
                    : ['docker', 'compose', 'run', '--rm', 'node', 'npm', 'install', '--no-audit', '--no-fund'],
                $base,
                1800
            );
            $steps[] = $installStep;

            if (! $installStep['ok']) {
                if ($hasLock) {
                    $fallbackInstall = $this->runProcessCommand(
                        'docker compose run --rm node npm install --no-audit --no-fund (fallback)',
                        ['docker', 'compose', 'run', '--rm', 'node', 'npm', 'install', '--no-audit', '--no-fund'],
                        $base,
                        1800
                    );
                    $steps[] = $fallbackInstall;

                    if (! $fallbackInstall['ok']) {
                        return ['ok' => false, 'steps' => $steps];
                    }
                } else {
                    return ['ok' => false, 'steps' => $steps];
                }
            }

            $buildStep = $this->runProcessCommand(
                'docker compose run --rm node npm run build',
                ['docker', 'compose', 'run', '--rm', 'node', 'npm', 'run', 'build'],
                $base,
                1800
            );
            $steps[] = $buildStep;

            return ['ok' => $buildStep['ok'], 'steps' => $steps];
        }

        $steps[] = [
            'cmd' => 'frontend asset build',
            'output' => "Node.js ortamı bulunamadı.\n"
                . "Otomatik build için app konteynerinde npm kurulmalı veya deploy ortamında docker compose erişimi olmalı.\n"
                . "Manuel fallback:\n"
                . "docker compose run --rm node npm ci\n"
                . "docker compose run --rm node npm run build",
            'ok' => false,
        ];

        return ['ok' => false, 'steps' => $steps];
    }

    private function runArtisan(string $command, array $params = []): array
    {
        try {
            $exitCode = Artisan::call($command, $params);
            $output = trim(Artisan::output()) ?: '(çıktı yok)';

            return [
                'cmd' => 'php artisan ' . $command,
                'output' => $output,
                'ok' => $exitCode === 0,
            ];
        } catch (\Throwable $e) {
            return [
                'cmd' => 'php artisan ' . $command,
                'output' => $e->getMessage(),
                'ok' => false,
            ];
        }
    }

    private function runProcessCommand(
        string $displayCommand,
        array $command,
        string $workingDirectory,
        int $timeoutSeconds = 120
    ): array {
        try {
            $process = new Process($command, $workingDirectory);
            $process->setTimeout($timeoutSeconds);
            $process->run();

            $output = trim($process->getOutput() . "\n" . $process->getErrorOutput()) ?: '(çıktı yok)';

            return [
                'cmd' => $displayCommand,
                'output' => $output,
                'ok' => $process->isSuccessful(),
            ];
        } catch (\Throwable $exception) {
            return [
                'cmd' => $displayCommand,
                'output' => $exception->getMessage(),
                'ok' => false,
            ];
        }
    }

    private function binaryExists(string $binary): bool
    {
        try {
            $process = new Process(['sh', '-lc', 'command -v ' . escapeshellarg($binary)]);
            $process->setTimeout(10);
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }
}
