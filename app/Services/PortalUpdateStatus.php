<?php

namespace App\Services;

use App\Models\PortalUpdateEvent;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;

class PortalUpdateStatus
{
    public function __construct(
        private PortalVersionService $versionService,
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

        return [
            'current_version' => $currentVersion['version'],
            'app_version' => $currentVersion['app_version'],
            'current_branch' => $currentVersion['branch'],
            'current_commit' => $currentVersion['commit'],
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

    public function markRun(string $status, ?string $message = null): void
    {
        if (! $this->hasSettingsTable()) {
            return;
        }

        $current = $this->versionService->current();

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
            ->get([
                'type',
                'status',
                'trigger_source',
                'branch',
                'local_version',
                'local_commit',
                'remote_version',
                'remote_commit',
                'update_available',
                'message',
                'completed_at',
                'created_at',
            ])
            ->map(fn (PortalUpdateEvent $event) => [
                'type' => $event->type,
                'status' => $event->status,
                'trigger_source' => $event->trigger_source,
                'branch' => $event->branch,
                'local_version' => $event->local_version,
                'local_commit' => $event->local_commit ? substr($event->local_commit, 0, 7) : null,
                'remote_version' => $event->remote_version,
                'remote_commit' => $event->remote_commit ? substr($event->remote_commit, 0, 7) : null,
                'update_available' => $event->update_available,
                'message' => $event->message,
                'completed_at' => optional($event->completed_at ?? $event->created_at)?->toIso8601String(),
            ])
            ->all();
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
}
