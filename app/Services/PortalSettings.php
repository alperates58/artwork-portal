<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class PortalSettings
{
    private ?array $cache = null;

    private const MIKRO_SECRET_KEYS = [
        'mikro.api_key',
        'mikro.username',
        'mikro.password',
    ];

    private const MAIL_SERVER_SECRET_KEYS = [
        'mail.username',
        'mail.password',
    ];

    private const MAIL_NOTIFICATION_DEFAULT_SUBJECT = 'Yeni siparis geldi: {order_no}';

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
            'enabled' => filter_var($this->get('mikro.enabled', config('mikro.enabled')), FILTER_VALIDATE_BOOL),
            'base_url' => $this->get('mikro.base_url', config('erp.mikro.base_url')),
            'api_key' => $this->get('mikro.api_key', config('mikro.api_key')),
            'username' => $this->get('mikro.username', config('mikro.username')),
            'password' => $this->get('mikro.password', config('mikro.password')),
            'company_code' => $this->get('mikro.company_code', config('mikro.company_code')),
            'work_year' => $this->get('mikro.work_year', config('mikro.work_year')),
            'timeout' => (int) $this->get('mikro.timeout', config('mikro.timeout')),
            'verify_ssl' => filter_var($this->get('mikro.verify_ssl', config('mikro.verify_ssl')), FILTER_VALIDATE_BOOL),
            'shipment_endpoint' => $this->get('mikro.shipment_endpoint', config('erp.mikro.shipment_endpoint')),
            'sync_interval_minutes' => (int) $this->get('mikro.sync_interval_minutes', config('erp.mikro.sync_interval_minutes')),
        ];
    }

    public function mikroFormConfig(): array
    {
        $config = $this->mikroConfig();

        return [
            'enabled' => $config['enabled'],
            'base_url' => $config['base_url'],
            'api_key' => '',
            'username' => '',
            'password' => '',
            'company_code' => $config['company_code'],
            'work_year' => $config['work_year'],
            'timeout' => $config['timeout'],
            'verify_ssl' => $config['verify_ssl'],
            'shipment_endpoint' => $config['shipment_endpoint'],
            'sync_interval_minutes' => $config['sync_interval_minutes'],
            'has_api_key' => filled($config['api_key']),
            'has_username' => filled($config['username']),
            'has_password' => filled($config['password']),
        ];
    }

    public function syncMikroSettings(array $settings): void
    {
        $this->set('mikro', 'mikro.enabled', (string) ($settings['enabled'] ?? false));
        $this->set('mikro', 'mikro.base_url', $settings['base_url'] ?? null);
        $this->set('mikro', 'mikro.company_code', $settings['company_code'] ?? null);
        $this->set('mikro', 'mikro.work_year', $settings['work_year'] ?? null);
        $this->set('mikro', 'mikro.timeout', (string) ($settings['timeout'] ?? config('mikro.timeout')));
        $this->set('mikro', 'mikro.verify_ssl', (string) ($settings['verify_ssl'] ?? true));
        $this->set('mikro', 'mikro.shipment_endpoint', $settings['shipment_endpoint'] ?? null);
        $this->set('mikro', 'mikro.sync_interval_minutes', (string) ($settings['sync_interval_minutes'] ?? config('mikro.sync_interval_minutes')));

        foreach (self::MIKRO_SECRET_KEYS as $key) {
            $field = str($key)->after('mikro.')->toString();

            if (! array_key_exists($field, $settings)) {
                continue;
            }

            $value = $settings[$field];

            if ($value === '__KEEP__') {
                continue;
            }

            if (blank($value)) {
                $this->forget($key);
                continue;
            }

            $this->set('mikro', $key, $value, true);
        }
    }

    public function mailNotificationConfig(): array
    {
        return [
            'enabled' => filter_var($this->get('mail_notifications.enabled', false), FILTER_VALIDATE_BOOL),
            'graphics_to' => (string) $this->get('mail_notifications.graphics_to', ''),
            'graphics_cc' => (string) $this->get('mail_notifications.graphics_cc', ''),
            'graphics_bcc' => (string) $this->get('mail_notifications.graphics_bcc', ''),
            'new_order_subject' => (string) $this->get('mail_notifications.new_order_subject', self::MAIL_NOTIFICATION_DEFAULT_SUBJECT),
            'override_from_name' => $this->get('mail_notifications.override_from_name'),
            'override_from_address' => $this->get('mail_notifications.override_from_address'),
            'test_recipient' => $this->get('mail_notifications.test_recipient'),
        ];
    }

    public function mailNotificationFormConfig(): array
    {
        return $this->mailNotificationConfig();
    }

    public function mailServerConfig(): array
    {
        return [
            'host' => $this->get('mail.host', config('mail.mailers.smtp.host')),
            'port' => (int) $this->get('mail.port', config('mail.mailers.smtp.port')),
            'username' => $this->get('mail.username', config('mail.mailers.smtp.username')),
            'password' => $this->get('mail.password', config('mail.mailers.smtp.password')),
            'encryption' => $this->normalizeMailEncryption(
                $this->get(
                    'mail.encryption',
                    config('mail.mailers.smtp.encryption', config('mail.mailers.smtp.scheme'))
                )
            ),
            'from_address' => $this->get('mail.from_address', config('mail.from.address')),
            'from_name' => $this->get('mail.from_name', config('mail.from.name')),
        ];
    }

    public function mailServerFormConfig(): array
    {
        $config = $this->mailServerConfig();

        return [
            'host' => $config['host'],
            'port' => $config['port'],
            'username' => '',
            'password' => '',
            'encryption' => $config['encryption'],
            'from_address' => $config['from_address'],
            'from_name' => $config['from_name'],
            'has_username' => filled($config['username']),
            'has_password' => filled($config['password']),
        ];
    }

    public function syncMailServerSettings(array $settings): void
    {
        $this->set('mail', 'mail.host', $settings['host'] ?? null);
        $this->set('mail', 'mail.port', isset($settings['port']) ? (string) $settings['port'] : null);
        $this->set('mail', 'mail.encryption', $this->normalizeMailEncryption($settings['encryption'] ?? null));
        $this->set('mail', 'mail.from_address', $settings['from_address'] ?? null);
        $this->set('mail', 'mail.from_name', $settings['from_name'] ?? null);

        foreach (self::MAIL_SERVER_SECRET_KEYS as $key) {
            $field = str($key)->after('mail.')->toString();

            if (! array_key_exists($field, $settings)) {
                continue;
            }

            $value = $settings[$field];

            if ($value === '__KEEP__') {
                continue;
            }

            if (blank($value)) {
                $this->forget($key);
                continue;
            }

            $this->set('mail', $key, $value, true);
        }
    }

    public function syncMailNotificationSettings(array $settings): void
    {
        $this->set('mail_notifications', 'mail_notifications.enabled', (string) ($settings['enabled'] ?? false));
        $this->set('mail_notifications', 'mail_notifications.graphics_to', $settings['graphics_to'] ?? null);
        $this->set('mail_notifications', 'mail_notifications.graphics_cc', $settings['graphics_cc'] ?? null);
        $this->set('mail_notifications', 'mail_notifications.graphics_bcc', $settings['graphics_bcc'] ?? null);
        $this->set(
            'mail_notifications',
            'mail_notifications.new_order_subject',
            $settings['new_order_subject'] ?? self::MAIL_NOTIFICATION_DEFAULT_SUBJECT
        );
        $this->set('mail_notifications', 'mail_notifications.override_from_name', $settings['override_from_name'] ?? null);
        $this->set('mail_notifications', 'mail_notifications.override_from_address', $settings['override_from_address'] ?? null);
        $this->set('mail_notifications', 'mail_notifications.test_recipient', $settings['test_recipient'] ?? null);
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

    private function normalizeMailEncryption(mixed $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '', 'none', 'null' => null,
            'ssl', 'tls' => $normalized,
            'smtps' => 'ssl',
            default => null,
        };
    }
}
