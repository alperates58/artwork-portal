<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ReleaseManifestService
{
    private const MANIFEST_PATH = 'releases/manifest.json';

    public function __construct(
        private PortalSettings $settings,
    ) {}

    public function localManifest(): array
    {
        $path = base_path(self::MANIFEST_PATH);

        if (! is_file($path)) {
            return $this->emptyManifest();
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : $this->emptyManifest();
    }

    public function latestLocalRelease(): ?array
    {
        return $this->findRelease($this->localManifest(), $this->localManifest()['latest'] ?? null);
    }

    public function resolveCurrentVersion(?string $configuredVersion): ?string
    {
        return $configuredVersion ?: ($this->localManifest()['latest'] ?? null);
    }

    public function currentRelease(?string $version = null): ?array
    {
        $version ??= $this->resolveCurrentVersion(config('app.version'));

        return $this->findRelease($this->localManifest(), $version);
    }

    public function remoteManifest(?string $branch = null): ?array
    {
        $githubUpdate = $this->settings->githubUpdatesConfig();
        $repository = (string) ($githubUpdate['repository'] ?? '');
        $branch ??= $githubUpdate['branch'] ?? 'main';

        if ($repository === '' || $branch === '') {
            return null;
        }

        $response = Http::baseUrl('https://api.github.com')
            ->acceptJson()
            ->timeout(10)
            ->withHeaders([
                'User-Agent' => config('app.name', 'Lider Portal') . ' release-manifest-check',
            ])
            ->when(
                filled($githubUpdate['token'] ?? null),
                fn ($request) => $request->withToken((string) $githubUpdate['token'])
            )
            ->get('/repos/' . trim($repository, '/') . '/contents/' . self::MANIFEST_PATH, [
                'ref' => $branch,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $content = base64_decode((string) data_get($response->json(), 'content'), true);
        $decoded = json_decode($content ?: '', true);

        return is_array($decoded) ? $decoded : null;
    }

    public function summarizeIncomingChanges(?string $currentVersion, array $manifest): ?array
    {
        $latestVersion = $manifest['latest'] ?? null;
        $targetRelease = $this->findRelease($manifest, $latestVersion);

        if (! $targetRelease) {
            return null;
        }

        $incomingReleases = collect($manifest['releases'] ?? [])
            ->filter(fn ($release) => is_array($release) && filled($release['version'] ?? null))
            ->filter(function (array $release) use ($currentVersion) {
                if (! $currentVersion) {
                    return true;
                }

                return version_compare((string) $release['version'], $currentVersion, '>');
            })
            ->sortBy(fn (array $release) => $release['version'], SORT_NATURAL)
            ->values();

        if ($incomingReleases->isEmpty()) {
            return null;
        }

        $modules = $incomingReleases
            ->flatMap(fn (array $release) => $release['changed_modules'] ?? [])
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();

        $features = $incomingReleases
            ->flatMap(fn (array $release) => $release['changes'] ?? [])
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->values()
            ->all();

        $warnings = $incomingReleases
            ->flatMap(fn (array $release) => $release['warnings'] ?? [])
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();

        $postUpdateNotes = $incomingReleases
            ->flatMap(fn (array $release) => $release['post_update_notes'] ?? [])
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();

        $newTables = $incomingReleases
            ->flatMap(fn (array $release) => data_get($release, 'schema_changes.new_tables', []))
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();

        $newColumns = $incomingReleases
            ->flatMap(fn (array $release) => data_get($release, 'schema_changes.new_columns', []))
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();

        $appliedMigrations = $incomingReleases
            ->flatMap(fn (array $release) => $release['applied_migrations'] ?? [])
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();

        return [
            'current_version' => $currentVersion,
            'target_version' => $targetRelease['version'],
            'target_release' => $targetRelease,
            'release_count' => $incomingReleases->count(),
            'releases' => $incomingReleases->all(),
            'title' => $targetRelease['title'] ?? null,
            'summary' => $targetRelease['summary'] ?? null,
            'changes' => $features,
            'changed_modules' => $modules,
            'migrations_included' => ! empty($appliedMigrations) || ! empty($newTables) || ! empty($newColumns) || $incomingReleases->contains(fn (array $release) => (bool) ($release['migrations_included'] ?? false)),
            'schema_changes' => [
                'new_tables' => $newTables,
                'new_columns' => $newColumns,
            ],
            'warnings' => $warnings,
            'post_update_notes' => $postUpdateNotes,
            'applied_migrations' => $appliedMigrations,
        ];
    }

    public function buildHistoryPayload(array $release): array
    {
        return [
            'release_title' => $release['title'] ?? null,
            'release_summary' => $release['summary'] ?? null,
            'change_summary' => $release['changes'] ?? [],
            'changed_modules' => $release['changed_modules'] ?? [],
            'migrations_included' => (bool) ($release['migrations_included'] ?? false),
            'schema_changes' => $release['schema_changes'] ?? [
                'new_tables' => [],
                'new_columns' => [],
            ],
            'warnings' => $release['warnings'] ?? [],
            'post_update_notes' => $release['post_update_notes'] ?? [],
            'applied_migrations' => $release['applied_migrations'] ?? [],
            'release_date' => $release['release_date'] ?? null,
        ];
    }

    private function findRelease(array $manifest, ?string $version): ?array
    {
        if (! $version) {
            return null;
        }

        foreach (($manifest['releases'] ?? []) as $release) {
            if (is_array($release) && ($release['version'] ?? null) === $version) {
                return $release;
            }
        }

        return null;
    }

    private function emptyManifest(): array
    {
        return [
            'schema_version' => 1,
            'generated_at' => null,
            'latest' => null,
            'releases' => [],
        ];
    }
}
