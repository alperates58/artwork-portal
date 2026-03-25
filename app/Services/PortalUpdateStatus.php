<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;

class PortalUpdateStatus
{
    public function snapshot(): array
    {
        $currentVersion = $this->currentVersion();
        $lastRunAt = $this->setting('system.update.last_run_at');
        $lastVersion = $this->setting('system.update.last_version');
        $lastStatus = $this->setting('system.update.last_status', 'never');
        $lastMessage = $this->setting('system.update.last_message');

        return [
            'current_version' => $currentVersion['version'],
            'current_branch' => $currentVersion['branch'],
            'current_commit' => $currentVersion['commit'],
            'last_deployed_version' => $lastVersion,
            'last_run_at' => $lastRunAt,
            'last_status' => $lastStatus,
            'last_message' => $lastMessage,
            'command' => 'php artisan portal:update --skip-cache',
            'is_out_of_sync' => filled($lastVersion) && filled($currentVersion['version']) && $lastVersion !== $currentVersion['version'],
        ];
    }

    public function markRun(string $status, ?string $message = null): void
    {
        if (! $this->hasSettingsTable()) {
            return;
        }

        $this->store('system.update.last_status', $status);
        $this->store('system.update.last_run_at', now()->toIso8601String());
        $this->store('system.update.last_message', $message);

        if ($status === 'success') {
            $this->store('system.update.last_version', $this->currentVersion()['version']);
        }
    }

    private function currentVersion(): array
    {
        $headPath = base_path('.git/HEAD');

        if (! is_file($headPath)) {
            return [
                'version' => config('app.env'),
                'branch' => null,
                'commit' => null,
            ];
        }

        $head = trim((string) file_get_contents($headPath));
        $branch = null;
        $commit = null;

        if (str_starts_with($head, 'ref: ')) {
            $ref = trim(substr($head, 5));
            $branch = basename($ref);
            $refPath = base_path('.git/' . $ref);

            if (is_file($refPath)) {
                $commit = trim((string) file_get_contents($refPath));
            }
        } else {
            $commit = $head;
        }

        $shortCommit = $commit ? substr(trim($commit), 0, 7) : null;

        return [
            'version' => $shortCommit ?? config('app.env'),
            'branch' => $branch,
            'commit' => $shortCommit,
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
}
