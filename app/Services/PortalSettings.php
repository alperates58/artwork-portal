<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class PortalSettings
{
    private ?array $cache = null;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $group, string $key, mixed $value, bool $encrypted = false): void
    {
        $storedValue = $value;

        if ($encrypted && filled($value)) {
            $storedValue = Crypt::encryptString((string) $value);
        }

        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => blank($value) ? null : (string) $storedValue,
                'is_encrypted' => $encrypted,
            ]
        );

        $this->cache = null;
    }

    public function forget(string $key): void
    {
        SystemSetting::query()->where('key', $key)->delete();
        $this->cache = null;
    }

    public function filesystemDisk(): string
    {
        return (string) $this->get('spaces.disk', config('filesystems.default', 'local'));
    }

    public function spacesConfig(): array
    {
        return [
            'disk' => $this->filesystemDisk(),
            'key' => $this->get('spaces.key', config('filesystems.disks.spaces.key')),
            'secret' => $this->get('spaces.secret', config('filesystems.disks.spaces.secret')),
            'endpoint' => $this->get('spaces.endpoint', config('filesystems.disks.spaces.endpoint')),
            'region' => $this->get('spaces.region', config('filesystems.disks.spaces.region')),
            'bucket' => $this->get('spaces.bucket', config('filesystems.disks.spaces.bucket')),
            'url' => $this->get('spaces.url', config('filesystems.disks.spaces.url')),
        ];
    }

    public function mikroConfig(): array
    {
        return [
            'base_url' => $this->get('mikro.base_url', config('erp.mikro.base_url')),
            'api_key' => $this->get('mikro.api_key', config('erp.mikro.api_key')),
            'shipment_endpoint' => $this->get('mikro.shipment_endpoint', config('erp.mikro.shipment_endpoint')),
            'use_direct_db' => filter_var($this->get('mikro.use_direct_db', config('erp.mikro.use_direct_db')), FILTER_VALIDATE_BOOL),
            'sync_interval_minutes' => (int) $this->get('mikro.sync_interval_minutes', config('erp.mikro.sync_interval_minutes')),
        ];
    }

    public function hasSettingsTable(): bool
    {
        return Schema::hasTable('system_settings');
    }

    private function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (! $this->hasSettingsTable()) {
            return $this->cache = [];
        }

        $settings = [];

        foreach (SystemSetting::query()->get(['key', 'value', 'is_encrypted']) as $setting) {
            $value = $setting->value;

            if ($setting->is_encrypted && filled($value)) {
                try {
                    $value = Crypt::decryptString($value);
                } catch (DecryptException) {
                    $value = null;
                }
            }

            $settings[$setting->key] = $value;
        }

        return $this->cache = $settings;
    }
}
