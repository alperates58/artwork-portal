<?php

namespace App\Services;

use App\Models\PortalUpdateEvent;
use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class GithubUpdateChecker
{
    public function __construct(
        private PortalVersionService $versionService,
        private PortalUpdateStatus $updateStatus,
        private ReleaseManifestService $manifestService,
    ) {}

    public function checkAndStore(?User $actor = null, string $triggerSource = 'manual'): array
    {
        $current = $this->versionService->current();
        $branch = $current['branch'] ?: config('services.github_updates.branch', 'main');
        $checkedAt = now();
        $status = 'failed';
        $message = 'GitHub kontrolu yapilamadi.';
        $remoteCommit = null;
        $remoteVersion = null;
        $remoteManifest = null;
        $incomingChanges = null;
        $meta = [];
        $updateAvailable = null;

        try {
            $response = Http::baseUrl('https://api.github.com')
                ->acceptJson()
                ->timeout(10)
                ->withHeaders([
                    'User-Agent' => config('app.name', 'Lider Portal') . ' update-check',
                ])
                ->when(
                    filled(config('services.github_updates.token')),
                    fn ($request) => $request->withToken((string) config('services.github_updates.token'))
                )
                ->get('/repos/' . config('services.github_updates.repository') . '/commits/' . $branch)
                ->throw();

            $data = $response->json();
            $remoteCommit = substr((string) data_get($data, 'sha'), 0, 7) ?: null;
            $remoteManifest = $this->manifestService->remoteManifest($branch);
            $incomingChanges = $remoteManifest
                ? $this->manifestService->summarizeIncomingChanges($current['version'], $remoteManifest)
                : null;
            $remoteVersion = $incomingChanges['target_version'] ?? $remoteCommit;
            $updateAvailable = filled($remoteCommit) && filled($current['commit']) ? $remoteCommit !== $current['commit'] : null;
            if ($incomingChanges) {
                $updateAvailable = version_compare((string) $incomingChanges['target_version'], (string) $current['version'], '>');
            }
            $status = 'success';
            $message = $updateAvailable
                ? 'GitHub uzerinde daha yeni bir surum bulundu.'
                : 'Kurulu surum ile GitHub manifest surumu ayni.';
            $meta = [
                'html_url' => data_get($data, 'html_url'),
                'commit_message' => data_get($data, 'commit.message'),
                'commit_author_date' => data_get($data, 'commit.author.date'),
                'manifest_available' => $remoteManifest !== null,
                'incoming_changes' => $incomingChanges,
            ];
        } catch (RequestException $exception) {
            $response = $exception->response;
            $rateLimited = $response?->status() === 403 && $response->header('X-RateLimit-Remaining') === '0';
            $message = $rateLimited
                ? 'GitHub API rate limit doldu. Bir sure sonra tekrar deneyin veya token tanimlayin.'
                : 'GitHub erisimi basarisiz oldu: HTTP ' . ($response?->status() ?? 'baglanti');
            $meta = [
                'http_status' => $response?->status(),
                'response' => $response?->json(),
            ];
        } catch (\Throwable $exception) {
            $message = 'GitHub erisimi basarisiz oldu: ' . $exception->getMessage();
            $meta = [
                'exception' => $exception::class,
            ];
        }

        if ($this->hasEventsTable()) {
            PortalUpdateEvent::query()->create($this->eventPayload([
                'type' => 'check',
                'status' => $status,
                'trigger_source' => $triggerSource,
                'actor_id' => $actor?->id,
                'branch' => $branch,
                'local_commit' => $current['full_commit'],
                'local_version' => $current['version'],
                'remote_commit' => $remoteCommit,
                'remote_version' => $remoteVersion,
                'from_version' => $current['version'],
                'to_version' => $incomingChanges['target_version'] ?? $remoteVersion,
                'update_available' => $updateAvailable,
                'message' => $message,
                'meta' => $meta,
                'started_at' => $checkedAt,
                'completed_at' => now(),
                'release_title' => $incomingChanges['title'] ?? null,
                'release_summary' => $incomingChanges['summary'] ?? null,
                'change_summary' => $incomingChanges['changes'] ?? null,
                'changed_modules' => $incomingChanges['changed_modules'] ?? null,
                'migrations_included' => $incomingChanges['migrations_included'] ?? null,
                'schema_changes' => $incomingChanges['schema_changes'] ?? null,
                'warnings' => $incomingChanges['warnings'] ?? null,
                'post_update_notes' => $incomingChanges['post_update_notes'] ?? null,
                'applied_migrations' => $incomingChanges['applied_migrations'] ?? null,
                'release_date' => data_get($incomingChanges, 'target_release.release_date'),
            ]));
        }

        $this->updateStatus->markCheck(
            status: $status,
            message: $message,
            branch: $branch,
            remoteCommit: $remoteCommit,
            checkedAt: $checkedAt->toIso8601String(),
            updateAvailable: $updateAvailable
        );

        return [
            'status' => $status,
            'checked_at' => $checkedAt,
            'message' => $message,
            'branch' => $branch,
            'current_commit' => $current['commit'],
            'remote_commit' => $remoteCommit,
            'remote_version' => $remoteVersion,
            'update_available' => $updateAvailable,
            'incoming_changes' => $incomingChanges,
            'meta' => $meta,
        ];
    }

    private function hasEventsTable(): bool
    {
        return Schema::hasTable('portal_update_events');
    }

    private function eventPayload(array $payload): array
    {
        static $columns = null;

        if ($columns === null) {
            $columns = $this->hasEventsTable()
                ? Schema::getColumnListing('portal_update_events')
                : [];
        }

        return array_intersect_key($payload, array_flip($columns));
    }
}
