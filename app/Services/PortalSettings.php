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
        'mail.oauth_client_secret',
    ];

    private const GITHUB_UPDATE_SECRET_KEYS = [
        'github_updates.token',
    ];

    private const SUPPLIER_REGISTRATION_SUBMITTED_DEFAULT_SUBJECT = '{portal_adi} - Kayıt talebiniz alındı';
    private const SUPPLIER_REGISTRATION_APPROVED_DEFAULT_SUBJECT = '{portal_adi} - Hoş geldiniz';

    private const MAIL_NOTIFICATION_DEFAULT_SUBJECT = 'Yeni siparis geldi: {order_no}';
    private const MAIL_NOTIFICATION_DEFAULT_ARTWORK_SUBJECT = 'Yeni artwork yüklendi: {order_no} / {product_code}';

    private const MAIL_NOTIFICATION_EVENT_DEFINITIONS = [
        'new_order' => [
            'label' => 'Yeni sipariş geldiğinde',
            'description' => 'Mikro entegrasyonundan ilk kez içeri alınan siparişlerde çalışır.',
            'default_enabled' => true,
            'subject_default' => self::MAIL_NOTIFICATION_DEFAULT_SUBJECT,
            'tokens' => ['{order_no}', '{supplier}', '{order_date}', '{line_count}'],
        ],
        'artwork_uploaded' => [
            'label' => 'Artwork yüklendiğinde',
            'description' => 'Artwork yükleme veya galeriden reuse işlemi tamamlandığında çalışır.',
            'default_enabled' => false,
            'subject_default' => self::MAIL_NOTIFICATION_DEFAULT_ARTWORK_SUBJECT,
            'tokens' => ['{order_no}', '{supplier}', '{product_code}', '{revision_no}', '{uploaded_by}'],
        ],
    ];

    private const SUPPLIER_REGISTRATION_MAIL_DEFINITIONS = [
        'submitted' => [
            'label' => 'Kayıt talebi oluşturulduğunda',
            'description' => 'Tedarikçi kayıt formu gönderildiğinde başvuru sahibine onay beklediği bilgisini yollar.',
            'default_enabled' => false,
            'subject_default' => self::SUPPLIER_REGISTRATION_SUBMITTED_DEFAULT_SUBJECT,
            'body_default' => "Merhaba {{kayit_user}},\n\n{{firma_adi}} için oluşturduğunuz kayıt talebiniz alınmıştır.\nTalebiniz incelendikten sonra size bilgi verilecektir.\n\nKayıt e-posta adresi: {{kayit_email}}\nKayıt tarihi: {{kayit_tarihi}}\n\nBu mesaj {{portal_adi}} tarafından otomatik olarak gönderilmiştir.",
            'tokens' => ['{{kayit_user}}', '{{firma_adi}}', '{{kayit_email}}', '{{portal_adi}}', '{{login_url}}', '{{kayit_tarihi}}'],
        ],
        'approved' => [
            'label' => 'Kayıt onaylandığında',
            'description' => 'Yönetici kaydı onaylayıp kullanıcı oluşturduğunda başvuru sahibine hoş geldiniz / erişim bilgilendirmesi yollar.',
            'default_enabled' => true,
            'subject_default' => self::SUPPLIER_REGISTRATION_APPROVED_DEFAULT_SUBJECT,
            'body_default' => "Merhaba {{kayit_user}},\n\n{{firma_adi}} için oluşturduğunuz kayıt talebiniz onaylanmıştır.\nPortala aşağıdaki bağlantıdan giriş yapabilirsiniz:\n{{login_url}}\n\nKullanıcı e-posta adresi: {{kayit_email}}\nOnay tarihi: {{onay_tarihi}}\n\nBu mesaj {{portal_adi}} tarafından otomatik olarak gönderilmiştir.",
            'tokens' => ['{{kayit_user}}', '{{firma_adi}}', '{{kayit_email}}', '{{portal_adi}}', '{{login_url}}', '{{kayit_tarihi}}', '{{onay_tarihi}}', '{{onaylayan_kullanici}}'],
        ],
    ];

    private const DEFAULT_FILE_FORMATS = [
        ['ext' => 'PDF',  'label' => 'Adobe PDF',        'group' => 'pdf'],
        ['ext' => 'AI',   'label' => 'Adobe Illustrator', 'group' => 'design'],
        ['ext' => 'EPS',  'label' => 'EPS Vektör',        'group' => 'design'],
        ['ext' => 'PSD',  'label' => 'Adobe Photoshop',   'group' => 'design'],
        ['ext' => 'INDD', 'label' => 'Adobe InDesign',    'group' => 'design'],
        ['ext' => 'PNG',  'label' => 'PNG Görsel',        'group' => 'image'],
        ['ext' => 'JPG',  'label' => 'JPEG Görsel',       'group' => 'image'],
        ['ext' => 'JPEG', 'label' => 'JPEG Görsel',       'group' => 'image'],
        ['ext' => 'SVG',  'label' => 'SVG Vektör',        'group' => 'image'],
        ['ext' => 'WEBP', 'label' => 'WebP Görsel',       'group' => 'image'],
        ['ext' => 'ZIP',  'label' => 'ZIP Arşiv',         'group' => 'other'],
    ];

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

    public function hasCompleteSpacesConfiguration(): bool
    {
        $spaces = $this->spacesConfig();

        foreach (['key', 'secret', 'endpoint', 'region', 'bucket'] as $field) {
            if (! filled($spaces[$field] ?? null)) {
                return false;
            }
        }

        return true;
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
        $this->set('mikro', 'mikro.enabled', $this->booleanSettingValue($settings['enabled'] ?? false));
        $this->set('mikro', 'mikro.base_url', $settings['base_url'] ?? null);
        $this->set('mikro', 'mikro.company_code', $settings['company_code'] ?? null);
        $this->set('mikro', 'mikro.work_year', $settings['work_year'] ?? null);
        $this->set('mikro', 'mikro.timeout', (string) ($settings['timeout'] ?? config('mikro.timeout')));
        $this->set('mikro', 'mikro.verify_ssl', $this->booleanSettingValue($settings['verify_ssl'] ?? true));
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
        $eventDefinitions = $this->mailNotificationEventDefinitions();
        $newOrderEvent = $this->mailNotificationEventConfig('new_order', $eventDefinitions['new_order']);
        $artworkUploadedEvent = $this->mailNotificationEventConfig('artwork_uploaded', $eventDefinitions['artwork_uploaded']);

        return [
            'enabled' => filter_var($this->get('mail_notifications.enabled', false), FILTER_VALIDATE_BOOL),
            'graphics_to' => $newOrderEvent['to'],
            'graphics_cc' => $newOrderEvent['cc'],
            'graphics_bcc' => $newOrderEvent['bcc'],
            'new_order_subject' => $newOrderEvent['subject'],
            'override_from_name' => $this->get('mail_notifications.override_from_name'),
            'override_from_address' => $this->get('mail_notifications.override_from_address'),
            'test_recipient' => $this->get('mail_notifications.test_recipient'),
            'events' => [
                'new_order' => $newOrderEvent,
                'artwork_uploaded' => $artworkUploadedEvent,
            ],
        ];
    }

    public function mailNotificationFormConfig(): array
    {
        return $this->mailNotificationConfig();
    }

    public function mailNotificationEventDefinitions(): array
    {
        return self::MAIL_NOTIFICATION_EVENT_DEFINITIONS;
    }

    public function supplierRegistrationMailConfig(): array
    {
        $definitions = $this->supplierRegistrationMailDefinitions();

        return [
            'events' => [
                'submitted' => $this->supplierRegistrationMailEventConfig('submitted', $definitions['submitted']),
                'approved' => $this->supplierRegistrationMailEventConfig('approved', $definitions['approved']),
            ],
        ];
    }

    public function supplierRegistrationMailFormConfig(): array
    {
        return $this->supplierRegistrationMailConfig();
    }

    public function supplierRegistrationMailDefinitions(): array
    {
        return self::SUPPLIER_REGISTRATION_MAIL_DEFINITIONS;
    }

    public function mailServerConfig(): array
    {
        $provider = $this->normalizeMailProvider($this->get('mail.provider', 'smtp'));
        $defaults = $this->defaultMailServerDefaults($provider);

        return [
            'provider' => $provider,
            'host' => $this->get('mail.host', $defaults['host']),
            'port' => (int) $this->get('mail.port', $defaults['port']),
            'username' => $this->get('mail.username', config('mail.mailers.smtp.username')),
            'password' => $this->get('mail.password', config('mail.mailers.smtp.password')),
            'encryption' => $this->normalizeMailEncryption(
                $this->get(
                    'mail.encryption',
                    $defaults['encryption']
                )
            ),
            'from_address' => $this->get('mail.from_address', config('mail.from.address')),
            'from_name' => $this->get('mail.from_name', config('mail.from.name')),
            'oauth_tenant_id' => $this->get('mail.oauth_tenant_id'),
            'oauth_client_id' => $this->get('mail.oauth_client_id'),
            'oauth_client_secret' => $this->get('mail.oauth_client_secret'),
            'oauth_sender' => $this->get('mail.oauth_sender', $this->get('mail.from_address', config('mail.from.address'))),
        ];
    }

    public function mailServerFormConfig(): array
    {
        $config = $this->mailServerConfig();

        return [
            'provider' => $config['provider'],
            'host' => $config['host'],
            'port' => $config['port'],
            'username' => '',
            'password' => '',
            'encryption' => $config['encryption'],
            'from_address' => $config['from_address'],
            'from_name' => $config['from_name'],
            'oauth_tenant_id' => $config['oauth_tenant_id'],
            'oauth_client_id' => $config['oauth_client_id'],
            'oauth_client_secret' => '',
            'oauth_sender' => $config['oauth_sender'],
            'has_username' => filled($config['username']),
            'has_password' => filled($config['password']),
            'has_oauth_client_secret' => filled($config['oauth_client_secret']),
        ];
    }

    public function defaultMailDriver(): string
    {
        return $this->usesOffice365OAuthMailer() ? 'office365_oauth' : 'smtp';
    }

    public function usesOffice365OAuthMailer(): bool
    {
        return ($this->mailServerConfig()['provider'] ?? 'smtp') === 'office365_oauth';
    }

    public function hasUsableMailConfiguration(): bool
    {
        $config = $this->mailServerConfig();

        if (! filled($config['from_address'] ?? null) || ! filled($config['from_name'] ?? null)) {
            return false;
        }

        return match ($config['provider'] ?? 'smtp') {
            'office365_oauth' => $this->hasOffice365OAuthConfiguration($config),
            default => filled($config['host'] ?? null) && (int) ($config['port'] ?? 0) > 0,
        };
    }

    public function githubUpdatesConfig(): array
    {
        return [
            'repository' => $this->normalizeGithubRepository(
                $this->get('github_updates.repository', config('services.github_updates.repository'))
            ),
            'branch' => (string) $this->get('github_updates.branch', config('services.github_updates.branch', 'main')),
            'token' => $this->get('github_updates.token', config('services.github_updates.token')),
        ];
    }

    public function githubUpdatesFormConfig(): array
    {
        $config = $this->githubUpdatesConfig();

        return [
            'repository' => $config['repository'],
            'branch' => $config['branch'],
            'token' => '',
            'has_token' => filled($config['token']),
            'repository_url' => $config['repository']
                ? 'https://github.com/' . $config['repository']
                : null,
        ];
    }

    public function syncGithubUpdateSettings(array $settings): void
    {
        $repository = $this->normalizeGithubRepository($settings['repository'] ?? null);

        $this->set('github_updates', 'github_updates.repository', $repository);
        $this->set('github_updates', 'github_updates.branch', $settings['branch'] ?? 'main');

        foreach (self::GITHUB_UPDATE_SECRET_KEYS as $key) {
            $field = str($key)->after('github_updates.')->toString();

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

            $this->set('github_updates', $key, $value, true);
        }
    }

    public function syncMailServerSettings(array $settings): void
    {
        $provider = $this->normalizeMailProvider($settings['provider'] ?? null);

        $this->set('mail', 'mail.provider', $provider);
        $this->set('mail', 'mail.host', $settings['host'] ?? null);
        $this->set('mail', 'mail.port', isset($settings['port']) ? (string) $settings['port'] : null);
        $this->set('mail', 'mail.encryption', $this->normalizeMailEncryption($settings['encryption'] ?? null));
        $this->set('mail', 'mail.from_address', $settings['from_address'] ?? null);
        $this->set('mail', 'mail.from_name', $settings['from_name'] ?? null);
        $this->set('mail', 'mail.oauth_tenant_id', $settings['oauth_tenant_id'] ?? null);
        $this->set('mail', 'mail.oauth_client_id', $settings['oauth_client_id'] ?? null);
        $this->set('mail', 'mail.oauth_sender', $settings['oauth_sender'] ?? null);

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

    public function portalConfig(): array
    {
        return [
            'order_creation_enabled'         => filter_var($this->get('portal.order_creation_enabled', true), FILTER_VALIDATE_BOOL),
            'supplier_portal_enabled'        => filter_var($this->get('portal.supplier_portal_enabled', true), FILTER_VALIDATE_BOOL),
            'maintenance_mode'               => filter_var($this->get('portal.maintenance_mode', false), FILTER_VALIDATE_BOOL),
            'allow_manual_artwork'           => filter_var($this->get('portal.allow_manual_artwork', true), FILTER_VALIDATE_BOOL),
            'max_upload_size_mb'             => (int) $this->get('portal.max_upload_size_mb', config('artwork.max_file_size_mb', 1200)),
            'max_revision_count'             => (int) $this->get('portal.max_revision_count', 10),
            'session_timeout_minutes'        => (int) $this->get('portal.session_timeout_minutes', 120),
            'order_deadline_warning_days'    => (int) $this->get('portal.order_deadline_warning_days', 7),
            'max_orders_per_page'            => (int) $this->get('portal.max_orders_per_page', 25),
            'require_2fa_for_admin'          => filter_var($this->get('portal.require_2fa_for_admin', false), FILTER_VALIDATE_BOOL),
            'data_transfer_allowed'          => filter_var($this->get('portal.data_transfer_allowed', true), FILTER_VALIDATE_BOOL),
            'audit_log_retention_days'       => (int) $this->get('portal.audit_log_retention_days', 365),
            'order_deletion_enabled'         => filter_var($this->get('portal.order_deletion_enabled', true), FILTER_VALIDATE_BOOL),
            'preview_png_required'           => filter_var($this->get('portal.preview_png_required', false), FILTER_VALIDATE_BOOL),
            'supplier_auto_create'           => filter_var($this->get('portal.supplier_auto_create', false), FILTER_VALIDATE_BOOL),
        ];
    }

    public function syncPortalSettings(array $s): void
    {
        $boolKeys = [
            'order_creation_enabled', 'supplier_portal_enabled', 'maintenance_mode',
            'allow_manual_artwork', 'require_2fa_for_admin', 'data_transfer_allowed',
            'order_deletion_enabled', 'preview_png_required', 'supplier_auto_create',
        ];
        $intKeys = [
            'max_upload_size_mb', 'max_revision_count', 'session_timeout_minutes',
            'order_deadline_warning_days', 'max_orders_per_page', 'audit_log_retention_days',
        ];
        foreach ($boolKeys as $k) {
            $this->set('portal', "portal.{$k}", $this->booleanSettingValue($s[$k] ?? false));
        }
        foreach ($intKeys as $k) {
            $this->set('portal', "portal.{$k}", (string) (int) ($s[$k] ?? 0));
        }
    }

    public function syncArtworkStorageDisk(?string $disk): void
    {
        if (! in_array($disk, ['local', 'spaces'], true)) {
            return;
        }

        $this->set('spaces', 'spaces.disk', $disk);
    }

    public function syncMailNotificationSettings(array $settings): void
    {
        $this->set('mail_notifications', 'mail_notifications.enabled', $this->booleanSettingValue($settings['enabled'] ?? false));
        $this->set('mail_notifications', 'mail_notifications.override_from_name', $settings['override_from_name'] ?? null);
        $this->set('mail_notifications', 'mail_notifications.override_from_address', $settings['override_from_address'] ?? null);
        $this->set('mail_notifications', 'mail_notifications.test_recipient', $settings['test_recipient'] ?? null);

        $eventDefinitions = $this->mailNotificationEventDefinitions();
        $submittedEvents = $settings['events'] ?? [];

        $newOrderEvent = [
            'enabled' => $submittedEvents['new_order']['enabled'] ?? true,
            'department_ids' => $submittedEvents['new_order']['department_ids'] ?? [],
            'to' => $settings['graphics_to'] ?? data_get($submittedEvents, 'new_order.to'),
            'cc' => $settings['graphics_cc'] ?? data_get($submittedEvents, 'new_order.cc'),
            'bcc' => $settings['graphics_bcc'] ?? data_get($submittedEvents, 'new_order.bcc'),
            'subject' => $settings['new_order_subject'] ?? data_get($submittedEvents, 'new_order.subject'),
        ];

        $this->syncMailNotificationEventSettings('new_order', $newOrderEvent, $eventDefinitions['new_order']);

        $this->set('mail_notifications', 'mail_notifications.graphics_to', $newOrderEvent['to'] ?? null);
        $this->set('mail_notifications', 'mail_notifications.graphics_cc', $newOrderEvent['cc'] ?? null);
        $this->set('mail_notifications', 'mail_notifications.graphics_bcc', $newOrderEvent['bcc'] ?? null);
        $this->set(
            'mail_notifications',
            'mail_notifications.new_order_subject',
            $newOrderEvent['subject'] ?? $eventDefinitions['new_order']['subject_default']
        );

        $this->syncMailNotificationEventSettings(
            'artwork_uploaded',
            $submittedEvents['artwork_uploaded'] ?? [],
            $eventDefinitions['artwork_uploaded']
        );
    }

    public function syncSupplierRegistrationMailSettings(array $settings): void
    {
        $definitions = $this->supplierRegistrationMailDefinitions();
        $events = $settings['events'] ?? [];

        $this->syncSupplierRegistrationMailEventSettings(
            'submitted',
            $events['submitted'] ?? [],
            $definitions['submitted']
        );

        $this->syncSupplierRegistrationMailEventSettings(
            'approved',
            $events['approved'] ?? [],
            $definitions['approved']
        );
    }

    private const DEFAULT_FILE_GROUPS = [
        ['key' => 'pdf',    'label' => 'PDF'],
        ['key' => 'image',  'label' => 'Görseller'],
        ['key' => 'design', 'label' => 'Tasarım'],
        ['key' => 'other',  'label' => 'Diğer'],
    ];

    public function fileFormats(): array
    {
        $stored = $this->get('formats.list', null);

        if ($stored) {
            $decoded = is_string($stored) ? json_decode($stored, true) : $stored;

            if (is_array($decoded)) {
                $formats = collect($decoded)
                    ->filter(fn ($row) => filled($row['ext'] ?? null) && filled($row['label'] ?? null))
                    ->map(fn ($row) => [
                        'ext' => strtoupper(trim((string) ($row['ext'] ?? ''))),
                        'label' => trim((string) ($row['label'] ?? '')),
                        'group' => trim((string) ($row['group'] ?? 'other')) ?: 'other',
                    ])
                    ->filter(fn ($row) => $row['ext'] !== '' && $row['label'] !== '')
                    ->values()
                    ->all();

                if ($formats !== []) {
                    return $formats;
                }
            }
        }

        return self::DEFAULT_FILE_FORMATS;
    }

    public function allowedArtworkExtensions(): array
    {
        return collect($this->fileFormats())
            ->pluck('ext')
            ->map(fn ($ext) => strtolower(trim((string) $ext)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function allowedArtworkValidationRule(): string
    {
        return 'mimes:' . implode(',', $this->allowedArtworkExtensions());
    }

    public function allowedArtworkAcceptAttribute(): string
    {
        return collect($this->allowedArtworkExtensions())
            ->map(fn ($ext) => '.' . $ext)
            ->implode(',');
    }

    public function allowedArtworkExtensionsLabel(): string
    {
        return collect($this->allowedArtworkExtensions())
            ->map(fn ($ext) => strtoupper($ext))
            ->implode(', ');
    }

    private function booleanSettingValue(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOL) ? '1' : '0';
    }

    public function fileGroups(): array
    {
        $stored = $this->get('formats.groups', null);
        if ($stored) {
            $decoded = is_string($stored) ? json_decode($stored, true) : $stored;
            if (is_array($decoded) && count($decoded) > 0) {
                return $decoded;
            }
        }
        return self::DEFAULT_FILE_GROUPS;
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

    private function normalizeMailProvider(mixed $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'office365_smtp', 'office365-oauth', 'office365_oauth' => str_contains($normalized, 'oauth')
                ? 'office365_oauth'
                : 'office365_smtp',
            default => 'smtp',
        };
    }

    private function defaultMailServerDefaults(string $provider): array
    {
        return match ($provider) {
            'office365_smtp', 'office365_oauth' => [
                'host' => 'smtp.office365.com',
                'port' => 587,
                'encryption' => 'tls',
            ],
            default => [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption', config('mail.mailers.smtp.scheme')),
            ],
        };
    }

    private function hasOffice365OAuthConfiguration(array $config): bool
    {
        foreach (['host', 'port', 'oauth_tenant_id', 'oauth_client_id', 'oauth_client_secret', 'oauth_sender'] as $field) {
            if (! filled($config[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeGithubRepository(mixed $value): ?string
    {
        $repository = trim((string) $value);

        if ($repository === '') {
            return null;
        }

        $repository = preg_replace('#^https?://github\.com/#i', '', $repository) ?? $repository;
        $repository = preg_replace('#\.git$#i', '', $repository) ?? $repository;
        $repository = trim($repository, '/');

        return $repository !== '' ? $repository : null;
    }

    private function mailNotificationEventConfig(string $eventKey, array $definition): array
    {
        $legacyMap = $eventKey === 'new_order'
            ? [
                'to' => 'mail_notifications.graphics_to',
                'cc' => 'mail_notifications.graphics_cc',
                'bcc' => 'mail_notifications.graphics_bcc',
                'subject' => 'mail_notifications.new_order_subject',
            ]
            : [];

        return [
            'enabled' => filter_var(
                $this->get(
                    "mail_notifications.{$eventKey}_enabled",
                    $definition['default_enabled'] ?? false
                ),
                FILTER_VALIDATE_BOOL
            ),
            'department_ids' => $this->normalizeDepartmentIds(
                $this->get("mail_notifications.{$eventKey}_department_ids", '[]')
            ),
            'to' => (string) $this->get(
                "mail_notifications.{$eventKey}_to",
                isset($legacyMap['to']) ? $this->get($legacyMap['to'], '') : ''
            ),
            'cc' => (string) $this->get(
                "mail_notifications.{$eventKey}_cc",
                isset($legacyMap['cc']) ? $this->get($legacyMap['cc'], '') : ''
            ),
            'bcc' => (string) $this->get(
                "mail_notifications.{$eventKey}_bcc",
                isset($legacyMap['bcc']) ? $this->get($legacyMap['bcc'], '') : ''
            ),
            'subject' => (string) $this->get(
                "mail_notifications.{$eventKey}_subject",
                isset($legacyMap['subject'])
                    ? $this->get($legacyMap['subject'], $definition['subject_default'] ?? '')
                    : ($definition['subject_default'] ?? '')
            ),
        ];
    }

    private function syncMailNotificationEventSettings(string $eventKey, array $settings, array $definition): void
    {
        $this->set(
            'mail_notifications',
            "mail_notifications.{$eventKey}_enabled",
            $this->booleanSettingValue($settings['enabled'] ?? ($definition['default_enabled'] ?? false))
        );
        $this->set(
            'mail_notifications',
            "mail_notifications.{$eventKey}_department_ids",
            json_encode(
                $this->normalizeDepartmentIds($settings['department_ids'] ?? []),
                JSON_UNESCAPED_UNICODE
            )
        );
        $this->set('mail_notifications', "mail_notifications.{$eventKey}_to", $settings['to'] ?? null);
        $this->set('mail_notifications', "mail_notifications.{$eventKey}_cc", $settings['cc'] ?? null);
        $this->set('mail_notifications', "mail_notifications.{$eventKey}_bcc", $settings['bcc'] ?? null);
        $this->set(
            'mail_notifications',
            "mail_notifications.{$eventKey}_subject",
            $settings['subject'] ?? ($definition['subject_default'] ?? null)
        );
    }

    private function supplierRegistrationMailEventConfig(string $eventKey, array $definition): array
    {
        return [
            'enabled' => filter_var(
                $this->get(
                    "mail_notifications.supplier_registration_{$eventKey}_enabled",
                    $definition['default_enabled'] ?? false
                ),
                FILTER_VALIDATE_BOOL
            ),
            'subject' => (string) $this->get(
                "mail_notifications.supplier_registration_{$eventKey}_subject",
                $definition['subject_default'] ?? ''
            ),
            'body' => (string) $this->get(
                "mail_notifications.supplier_registration_{$eventKey}_body",
                $definition['body_default'] ?? ''
            ),
        ];
    }

    private function syncSupplierRegistrationMailEventSettings(string $eventKey, array $settings, array $definition): void
    {
        $this->set(
            'mail_notifications',
            "mail_notifications.supplier_registration_{$eventKey}_enabled",
            $this->booleanSettingValue($settings['enabled'] ?? ($definition['default_enabled'] ?? false))
        );
        $this->set(
            'mail_notifications',
            "mail_notifications.supplier_registration_{$eventKey}_subject",
            $settings['subject'] ?? ($definition['subject_default'] ?? null)
        );
        $this->set(
            'mail_notifications',
            "mail_notifications.supplier_registration_{$eventKey}_body",
            $settings['body'] ?? ($definition['body_default'] ?? null)
        );
    }

    private function normalizeDepartmentIds(mixed $value): array
    {
        $decoded = is_array($value)
            ? $value
            : json_decode((string) $value, true);

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
