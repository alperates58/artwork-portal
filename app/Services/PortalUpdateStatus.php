<?php

namespace App\Services;

use App\Models\PortalUpdateEvent;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;

class PortalUpdateStatus
{
    public function __construct(
        private PortalVersionService $versionService,
        private ReleaseManifestService $manifestService,
    ) {}

    public function snapshot(): array
    {
        $currentVersion = $this->versionService->current();
        $lastRunAt = $this->setting('system.update.last_run_at');
        $lastVersion = $this->setting('system.update.last_version');
        $lastStatus = $this->setting('system.update.last_status', 'never');
        $lastMessage = $this->setting('system.update.last_message');
        $lastCheckAt = $this->setting('system.update.last_checked_at');
        $lastCheckStatus = $this->setting('system.update.last_check_status', 'never');
        $lastCheckMessage = $this->setting('system.update.last_check_message');
        $latestRemoteCommit = $this->setting('system.update.latest_remote_commit');
        $latestRemoteBranch = $this->setting('system.update.latest_remote_branch');
        $updateAvailable = $this->normalizeNullableBoolean($this->setting('system.update.update_available'));
        $currentRelease = $this->manifestService->currentRelease($currentVersion['version']);
        $pendingPreparation = $this->pendingPreparation();

        return [
            'current_version' => $currentVersion['version'],
            'app_version' => $currentVersion['app_version'],
            'current_branch' => $currentVersion['branch'],
            'current_commit' => $currentVersion['commit'],
            'current_release' => $currentRelease,
            'last_deployed_version' => $lastVersion,
            'last_run_at' => $lastRunAt,
            'last_status' => $lastStatus,
            'last_message' => $lastMessage,
            'last_checked_at' => $lastCheckAt,
            'last_check_status' => $lastCheckStatus,
            'last_check_message' => $lastCheckMessage,
            'latest_remote_commit' => $latestRemoteCommit,
            'latest_remote_branch' => $latestRemoteBranch,
            'update_available' => $updateAvailable,
            'latest_remote_release' => $this->latestRemoteRelease(),
            'pending_preparation' => $pendingPreparation,
            'history' => $this->history(),
            'check_command' => 'php artisan portal:update:check',
            'update_command' => 'git pull origin ' . ($currentVersion['branch'] ?: 'main') . ' && php artisan portal:update',
            'safe_update_steps' => [
                'git fetch --all --prune',
                'git status',
                'git pull origin ' . ($currentVersion['branch'] ?: 'main'),
                'composer install --no-dev --optimize-autoloader',
                'php artisan portal:update',
            ],
            'is_out_of_sync' => filled($lastVersion) && filled($currentVersion['version']) && $lastVersion !== $currentVersion['version'],
        ];
    }

    public function markRun(string $status, ?string $message = null, array $context = []): void
    {
        if (! $this->hasSettingsTable()) {
            return;
        }

        $current = $this->versionService->current();
        $currentRelease = $this->manifestService->currentRelease($current['version']);
        $payload = $currentRelease ? $this->manifestService->buildHistoryPayload($currentRelease) : [];
        $fromVersion = $context['from_version'] ?? $this->setting('system.update.last_version');
        $appliedMigrations = $context['applied_migrations'] ?? [];

        $this->store('system.update.last_status', $status);
        $this->store('system.update.last_run_at', now()->toIso8601String());
        $this->store('system.update.last_message', $message);
        $this->store('system.update.last_branch', $current['branch']);

        if ($status === 'success') {
            $this->store('system.update.last_version', $current['version']);
            $this->store('system.update.last_commit', $current['commit']);
        }

        if ($this->hasEventsTable()) {
            PortalUpdateEvent::query()->create([
                'type' => 'run',
                'status' => $status,
                'trigger_source' => 'cli',
                'branch' => $current['branch'],
                'local_commit' => $current['full_commit'],
                'local_version' => $current['version'],
                'message' => $message,
                'started_at' => now(),
                'completed_at' => now(),
                'from_version' => $fromVersion,
                'to_version' => $current['version'],
                ...$payload,
                'migrations_included' => ! empty($appliedMigrations) || ($payload['migrations_included'] ?? false),
                'applied_migrations' => $appliedMigrations ?: ($payload['applied_migrations'] ?? []),
            ]);

            PortalUpdateEvent::query()
                ->where('type', 'prepare')
                ->where('status', 'pending')
                ->where('to_version', $current['version'])
                ->update([
                    'status' => $status === 'success' ? 'applied' : 'failed',
                    'completed_at' => now(),
                ]);
        }
    }

    public function markCheck(
        string $status,
        ?string $message,
        ?string $branch,
        ?string $remoteCommit,
        string $checkedAt,
        ?bool $updateAvailable,
    ): void {
        if (! $this->hasSettingsTable()) {
            return;
        }

        $this->store('system.update.last_check_status', $status);
        $this->store('system.update.last_check_message', $message);
        $this->store('system.update.last_checked_at', $checkedAt);
        $this->store('system.update.latest_remote_commit', $remoteCommit);
        $this->store('system.update.latest_remote_branch', $branch);
        $this->store('system.update.update_available', is_null($updateAvailable) ? null : ($updateAvailable ? '1' : '0'));
    }

    private function history(): array
    {
        if (! $this->hasEventsTable()) {
            return [];
        }

        return PortalUpdateEvent::query()
            ->latest('id')
            ->limit(6)
            ->get($this->eventColumns([
                'id',
                'type',
                'status',
                'trigger_source',
                'branch',
                'local_version',
                'local_commit',
                'remote_version',
                'remote_commit',
                'from_version',
                'to_version',
                'release_title',
                'release_summary',
                'change_summary',
                'changed_modules',
                'migrations_included',
                'schema_changes',
                'warnings',
                'post_update_notes',
                'applied_migrations',
                'release_date',
                'update_available',
                'message',
                'completed_at',
                'created_at',
            ]))
            ->map(fn (PortalUpdateEvent $event) => [
                'id' => $event->id,
                'type' => $event->type,
                'status' => $event->status,
                'trigger_source' => $event->trigger_source,
                'branch' => $event->branch,
                'local_version' => $event->local_version,
                'local_commit' => $event->local_commit ? substr($event->local_commit, 0, 7) : null,
                'remote_version' => $event->remote_version,
                'remote_commit' => $event->remote_commit ? substr($event->remote_commit, 0, 7) : null,
                'from_version' => $event->from_version,
                'to_version' => $event->to_version,
                'release_title' => $event->release_title,
                'release_summary' => $event->release_summary,
                'change_summary' => $event->change_summary ?? [],
                'changed_modules' => $event->changed_modules ?? [],
                'migrations_included' => $event->migrations_included,
                'schema_changes' => $event->schema_changes ?? ['new_tables' => [], 'new_columns' => []],
                'warnings' => $event->warnings ?? [],
                'post_update_notes' => $event->post_update_notes ?? [],
                'applied_migrations' => $event->applied_migrations ?? [],
                'release_date' => optional($event->release_date)?->toDateString(),
                'update_available' => $event->update_available,
                'message' => $event->message,
                'completed_at' => optional($event->completed_at ?? $event->created_at)?->toIso8601String(),
            ])
            ->all();
    }

    private function latestRemoteRelease(): ?array
    {
        if (! $this->hasEventsTable() || ! $this->hasEventColumn('to_version')) {
            return null;
        }

        $history = PortalUpdateEvent::query()
            ->where('type', 'check')
            ->whereNotNull('to_version')
            ->latest('id')
            ->first($this->eventColumns([
                'id',
                'to_version',
                'release_title',
                'release_summary',
                'change_summary',
                'changed_modules',
                'migrations_included',
                'schema_changes',
                'warnings',
                'post_update_notes',
                'applied_migrations',
                'release_date',
            ]));

        if (! $history) {
            return null;
        }

        return [
            'version' => $history->to_version,
            'title' => $history->release_title,
            'summary' => $history->release_summary,
            'release_date' => optional($history->release_date)?->toDateString(),
            'change_summary' => $history->change_summary ?? [],
            'changed_modules' => $history->changed_modules ?? [],
            'migrations_included' => $history->migrations_included,
            'schema_changes' => $history->schema_changes ?? ['new_tables' => [], 'new_columns' => []],
            'warnings' => $history->warnings ?? [],
            'post_update_notes' => $history->post_update_notes ?? [],
            'applied_migrations' => $history->applied_migrations ?? [],
        ];
    }

    private function pendingPreparation(): ?array
    {
        if (! $this->hasEventsTable()) {
            return null;
        }

        $event = PortalUpdateEvent::query()
            ->where('type', 'prepare')
            ->where('status', 'pending')
            ->latest('id')
            ->first($this->eventColumns([
                'id',
                'from_version',
                'to_version',
                'release_title',
                'release_summary',
                'change_summary',
                'changed_modules',
                'migrations_included',
                'schema_changes',
                'warnings',
                'post_update_notes',
                'applied_migrations',
                'created_at',
            ]));

        if (! $event) {
            return null;
        }

        return [
            'id' => $event->id,
            'from_version' => $event->from_version,
            'to_version' => $event->to_version,
            'release_title' => $event->release_title,
            'release_summary' => $event->release_summary,
            'change_summary' => $event->change_summary ?? [],
            'changed_modules' => $event->changed_modules ?? [],
            'migrations_included' => $event->migrations_included,
            'schema_changes' => $event->schema_changes ?? ['new_tables' => [], 'new_columns' => []],
            'warnings' => $event->warnings ?? [],
            'post_update_notes' => $event->post_update_notes ?? [],
            'applied_migrations' => $event->applied_migrations ?? [],
            'created_at' => optional($event->created_at)?->toIso8601String(),
        ];
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        if (! $this->hasSettingsTable()) {
            return $default;
        }

        return SystemSetting::query()->where('key', $key)->value('value') ?? $default;
    }

    private function store(string $key, ?string $value): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => 'system',
                'value' => $value,
                'is_encrypted' => false,
            ]
        );
    }

    private function hasSettingsTable(): bool
    {
        return Schema::hasTable('system_settings');
    }

    private function hasEventsTable(): bool
    {
        return Schema::hasTable('portal_update_events');
    }

    private function normalizeNullableBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }

    private function eventColumns(array $columns): array
    {
        static $available = null;

        if ($available === null) {
            $available = Schema::hasTable('portal_update_events')
                ? Schema::getColumnListing('portal_update_events')
                : [];
        }

        return array_values(array_intersect($columns, $available));
    }

    private function hasEventColumn(string $column): bool
    {
        return in_array($column, $this->eventColumns([$column]), true);
    }
}
