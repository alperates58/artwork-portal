<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class PortalDeployService
{
    public function __construct(
        private PortalSettings $settings,
    ) {}

    /**
     * GitHub'dan kodu cek ve update adimlarini uygula.
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
     * Sadece update adimlarini uygula (git pull manuel yapildiysa).
     */
    public function applyOnly(): array
    {
        return array_merge($this->applyUpdatePipeline([]), ['git_failed' => false]);
    }

    private function runGitPull(): array
    {
        $base = base_path();
        $githubUpdate = $this->settings->githubUpdatesConfig();
        $branch = $githubUpdate['branch'] ?? 'main';
        $repository = $githubUpdate['repository'] ?? null;
        $token = $githubUpdate['token'] ?? null;
        $gitArgs = ['git', '-c', "safe.directory={$base}", '-C', $base];
        $displayCommand = 'git pull origin ' . $branch;

        if (filled($repository) && filled($token)) {
            $displayCommand = 'git pull github-auth ' . $branch;
            $gitArgs[] = '-c';
            $gitArgs[] = 'http.extraHeader=Authorization: Basic ' . base64_encode('x-access-token:' . $token);
            $gitArgs[] = 'pull';
            $gitArgs[] = 'https://github.com/' . trim($repository, '/') . '.git';
            $gitArgs[] = $branch;
        } else {
            $gitArgs[] = 'pull';
            $gitArgs[] = 'origin';
            $gitArgs[] = $branch;
        }

        $step = $this->runProcessCommand($displayCommand, $gitArgs, $base);
        if ($step['ok']) {
            return $step;
        }

        $firstOutput = $step['output'] ?? '';

        if (str_contains(strtolower($firstOutput), 'permission denied')) {
            $sudoStep = $this->runProcessCommand(
                $displayCommand . ' (sudo)',
                ['sudo', '-n', ...$gitArgs],
                $base
            );

            if ($sudoStep['ok']) {
                return $sudoStep;
            }

            $hint = "\n\n--------------------------------------\n"
                . "Izin hatasi: PHP konteyneri .git/ dizinine yazamiyor.\n"
                . "Sunucuda su komutu calistirin:\n\n"
                . "  sudo chown -R www-data:www-data /var/www/artwork-portal\n\n"
                . "Veya git pull'u host'tan manuel calistirin,\n"
                . "ardindan \"Artisan Adimlarini Uygula\" butonunu kullanin.";

            return [
                'cmd' => $displayCommand,
                'output' => trim(($firstOutput ?: '(cikti yok)') . $hint),
                'ok' => false,
            ];
        }

        return $step;
    }

    private function applyUpdatePipeline(array $steps): array
    {
        $hasWarnings = false;
        $base = base_path();

        $steps[] = $this->runArtisan('config:clear');
        $steps[] = $this->runArtisan('cache:clear');

        $repositoryEnvironment = $this->prepareRepositoryEnvironment($base);
        $repositoryStep = $repositoryEnvironment['step'];
        if (! $repositoryEnvironment['ok']) {
            $repositoryStep['cmd'] = 'UYARI · ' . $repositoryStep['cmd'];
            $repositoryStep['ok'] = true;
            $hasWarnings = true;
        }
        $steps[] = $repositoryStep;

        $composerStep = $this->runComposerInstall($repositoryEnvironment['env']);
        if (! $composerStep['ok']) {
            $composerStep['cmd'] = 'UYARI · ' . $composerStep['cmd'];
            $composerStep['output'] = "Composer bagimliliklari guncellenemedi. Yeni PHP paketi eklendiyse manuel olarak calistirin:\n"
                . "  docker compose exec app composer install --no-dev --optimize-autoloader\n\n"
                . ($composerStep['output'] ?? '(cikti yok)');
            $composerStep['ok'] = true;
            $hasWarnings = true;
        }
        $steps[] = $composerStep;

        $portalUpdateStep = $this->runArtisan('portal:update');
        $steps[] = $portalUpdateStep;

        if (! $portalUpdateStep['ok']) {
            return ['ok' => false, 'steps' => $steps, 'warning' => false];
        }

        $frontendBuild = $this->buildFrontendAssets();
        foreach ($frontendBuild['steps'] as $frontendStep) {
            if (! ($frontendStep['ok'] ?? false)) {
                $frontendStep['cmd'] = 'UYARI · ' . $frontendStep['cmd'];
                $frontendStep['output'] = "Frontend build adimi basarisiz oldu. Kod guncellendi, gerekirse manuel build calistirin.\n\n"
                    . ($frontendStep['output'] ?? '(cikti yok)');
                $frontendStep['ok'] = true;
                $hasWarnings = true;
            }
            $steps[] = $frontendStep;
        }

        $finalConfigClear = $this->runArtisan('config:clear');
        if (! $finalConfigClear['ok']) {
            $finalConfigClear['cmd'] = 'UYARI · ' . $finalConfigClear['cmd'];
            $finalConfigClear['output'] = "Final config temizligi basarisiz oldu.\n\n" . ($finalConfigClear['output'] ?? '(cikti yok)');
            $finalConfigClear['ok'] = true;
            $hasWarnings = true;
        }
        $steps[] = $finalConfigClear;

        $finalCacheClear = $this->runArtisan('cache:clear');
        if (! $finalCacheClear['ok']) {
            $finalCacheClear['cmd'] = 'UYARI · ' . $finalCacheClear['cmd'];
            $finalCacheClear['output'] = "Final cache temizligi basarisiz oldu.\n\n" . ($finalCacheClear['output'] ?? '(cikti yok)');
            $finalCacheClear['ok'] = true;
            $hasWarnings = true;
        }
        $steps[] = $finalCacheClear;

        return ['ok' => true, 'steps' => $steps, 'warning' => $hasWarnings];
    }

    private function runComposerInstall(array $environment = []): array
    {
        $base = base_path();

        return $this->runProcessCommand(
            'composer install --no-dev --optimize-autoloader',
            ['composer', 'install', '--no-dev', '--optimize-autoloader', '--no-interaction'],
            $base,
            300,
            $environment
        );
    }

    private function buildFrontendAssets(): array
    {
        $base = base_path();
        $steps = [];
        $hasLock = file_exists(base_path('package-lock.json'));
        $preparedEnvironment = $this->prepareFrontendBuildEnvironment($base);

        if (! $preparedEnvironment['ok']) {
            $steps[] = $preparedEnvironment['step'];

            return ['ok' => false, 'steps' => $steps];
        }

        $steps[] = $preparedEnvironment['step'];
        $npmEnvironment = $preparedEnvironment['env'];

        if ($this->binaryExists('npm')) {
            $installStep = $this->runProcessCommand(
                $hasLock ? 'npm ci --no-audit --no-fund' : 'npm install --no-audit --no-fund',
                $hasLock
                    ? ['npm', 'ci', '--no-audit', '--no-fund']
                    : ['npm', 'install', '--no-audit', '--no-fund'],
                $base,
                1200,
                $npmEnvironment
            );
            $steps[] = $installStep;

            if (! $installStep['ok']) {
                if ($hasLock) {
                    $fallbackInstall = $this->runProcessCommand(
                        'npm install --no-audit --no-fund (fallback)',
                        ['npm', 'install', '--no-audit', '--no-fund'],
                        $base,
                        1200,
                        $npmEnvironment
                    );
                    $steps[] = $fallbackInstall;

                    if (! $fallbackInstall['ok']) {
                        return ['ok' => false, 'steps' => $steps];
                    }
                } else {
                    return ['ok' => false, 'steps' => $steps];
                }
            }

            $buildStep = $this->runProcessCommand('npm run build', ['npm', 'run', 'build'], $base, 1200, $npmEnvironment);
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
            'output' => "Node.js ortami bulunamadi.\n"
                . "Otomatik build icin app konteynerinde npm kurulmali veya deploy ortaminda docker compose erisimi olmali.\n"
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
            $output = trim(Artisan::output()) ?: '(cikti yok)';

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
        int $timeoutSeconds = 120,
        array $environment = []
    ): array {
        try {
            $process = new Process($command, $workingDirectory);
            $process->setTimeout($timeoutSeconds);
            if ($environment !== []) {
                $process->setEnv($environment + $_ENV);
            }
            $process->run();

            $output = trim($process->getOutput() . "\n" . $process->getErrorOutput()) ?: '(cikti yok)';

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

    private function prepareFrontendBuildEnvironment(string $base): array
    {
        $paths = [
            'cache' => $base . '/storage/framework/npm-cache',
            'home' => $base . '/storage/framework/npm-home',
            'config' => $base . '/storage/framework/npm-config',
            'public_build' => $base . '/public/build',
            'node_modules' => $base . '/node_modules',
        ];

        try {
            foreach ($paths as $path) {
                if (! is_dir($path) && ! mkdir($path, 0775, true) && ! is_dir($path)) {
                    throw new \RuntimeException($path . ' klasoru olusturulamadi.');
                }
            }

            return [
                'ok' => true,
                'step' => [
                    'cmd' => 'npm build ortamı hazırla',
                    'output' => "NPM cache/config klasorleri storage/framework altinda hazirlandi.\n"
                        . 'Cache: ' . $paths['cache'] . "\n"
                        . 'Home: ' . $paths['home'],
                    'ok' => true,
                ],
                'env' => [
                    'HOME' => $paths['home'],
                    'XDG_CONFIG_HOME' => $paths['config'],
                    'npm_config_cache' => $paths['cache'],
                    'NPM_CONFIG_CACHE' => $paths['cache'],
                ],
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'step' => [
                    'cmd' => 'npm build ortamı hazırla',
                    'output' => $exception->getMessage(),
                    'ok' => false,
                ],
                'env' => [],
            ];
        }
    }

    private function prepareRepositoryEnvironment(string $base): array
    {
        $paths = [
            'git_home' => $base . '/storage/framework/git-home',
            'composer_home' => $base . '/storage/framework/composer-home',
        ];

        try {
            foreach ($paths as $path) {
                if (! is_dir($path) && ! mkdir($path, 0775, true) && ! is_dir($path)) {
                    throw new \RuntimeException($path . ' klasoru olusturulamadi.');
                }
            }

            $environment = [
                'HOME' => $paths['git_home'],
                'XDG_CONFIG_HOME' => $paths['git_home'],
                'COMPOSER_HOME' => $paths['composer_home'],
                'COMPOSER_ALLOW_SUPERUSER' => '1',
            ];

            $gitConfigStep = $this->runProcessCommand(
                'git safe.directory hazırla',
                ['git', 'config', '--global', '--add', 'safe.directory', $base],
                $base,
                30,
                $environment
            );

            if (! $gitConfigStep['ok']) {
                throw new \RuntimeException("safe.directory tanimi yapilamadi.\n" . ($gitConfigStep['output'] ?? '(cikti yok)'));
            }

            $nonWritablePaths = collect([
                $base . '/vendor',
                $base . '/storage',
                $base . '/bootstrap/cache',
            ])->filter(fn (string $path) => file_exists($path) && ! is_writable($path))->values()->all();

            if ($nonWritablePaths !== []) {
                return [
                    'ok' => false,
                    'step' => [
                        'cmd' => 'repository ortamını hazırla',
                        'output' => "Git safe.directory tanimi yapildi, ancak bazi klasorler yazilabilir degil:\n"
                            . implode("\n", $nonWritablePaths)
                            . "\n\nSunucuda bir kez su komutu calistirin:\n"
                            . '  docker compose exec -u root app sh -lc "chown -R www-data:www-data /var/www/html /var/www/html/vendor /var/www/html/storage /var/www/html/bootstrap/cache"',
                        'ok' => false,
                    ],
                    'env' => $environment,
                ];
            }

            return [
                'ok' => true,
                'step' => [
                    'cmd' => 'repository ortamını hazırla',
                    'output' => "Git ve Composer klasorleri hazirlandi.\n"
                        . 'Git home: ' . $paths['git_home'] . "\n"
                        . 'Composer home: ' . $paths['composer_home'],
                    'ok' => true,
                ],
                'env' => $environment,
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'step' => [
                    'cmd' => 'repository ortamını hazırla',
                    'output' => $exception->getMessage(),
                    'ok' => false,
                ],
                'env' => [],
            ];
        }
    }
}
