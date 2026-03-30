<?php

namespace App\Services;

use App\Models\PortalLanguage;
use App\Models\PortalTranslation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PortalLocalizationService
{
    private ?Collection $languageCache = null;

    private array $translationCache = [];

    private array $registeredKeys = [];

    private const BASE_LOCALE = 'tr';

    private const DEFAULT_REGISTRY = [
        ['key' => 'navigation.dashboard', 'value' => 'Dashboard', 'group' => 'navigation'],
        ['key' => 'navigation.orders', 'value' => 'Siparişler', 'group' => 'navigation'],
        ['key' => 'navigation.my_orders', 'value' => 'Siparişlerim', 'group' => 'navigation'],
        ['key' => 'navigation.management', 'value' => 'Yönetim', 'group' => 'navigation'],
        ['key' => 'navigation.suppliers', 'value' => 'Tedarikçiler', 'group' => 'navigation'],
        ['key' => 'navigation.users', 'value' => 'Kullanıcılar', 'group' => 'navigation'],
        ['key' => 'navigation.settings', 'value' => 'Ayarlar', 'group' => 'navigation'],
        ['key' => 'navigation.settings.portal', 'value' => 'Portal & Sipariş', 'group' => 'navigation'],
        ['key' => 'navigation.settings.formats', 'value' => 'Artwork & Formatlar', 'group' => 'navigation'],
        ['key' => 'navigation.settings.storage', 'value' => 'Depolama & Spaces', 'group' => 'navigation'],
        ['key' => 'navigation.settings.mail', 'value' => 'E-posta & Bildirim', 'group' => 'navigation'],
        ['key' => 'navigation.settings.mikro', 'value' => 'ERP Entegrasyonu', 'group' => 'navigation'],
        ['key' => 'navigation.settings.backup', 'value' => 'Yedek & Veri Aktarımı', 'group' => 'navigation'],
        ['key' => 'navigation.settings.updates', 'value' => 'Güncelleme & Versiyon', 'group' => 'navigation'],
        ['key' => 'navigation.settings.general', 'value' => 'Sistem Özeti', 'group' => 'navigation'],
        ['key' => 'navigation.settings.localization', 'value' => 'Dil Ayarları', 'group' => 'navigation'],
        ['key' => 'navigation.permissions', 'value' => 'Yetkiler', 'group' => 'navigation'],
        ['key' => 'navigation.departments', 'value' => 'Departmanlar', 'group' => 'navigation'],
        ['key' => 'navigation.reports', 'value' => 'Raporlar', 'group' => 'navigation'],
        ['key' => 'navigation.gallery', 'value' => 'Artwork Galerisi', 'group' => 'navigation'],
        ['key' => 'navigation.logs', 'value' => 'Sistem Logları', 'group' => 'navigation'],
        ['key' => 'navigation.profile', 'value' => 'Profilim', 'group' => 'navigation'],
        ['key' => 'navigation.logout', 'value' => 'Çıkış', 'group' => 'navigation'],
        ['key' => 'general.search', 'value' => 'Ara…', 'group' => 'general'],
        ['key' => 'general.language', 'value' => 'Dil', 'group' => 'general'],
        ['key' => 'general.language_settings', 'value' => 'Dil Ayarları', 'group' => 'general'],
        ['key' => 'general.manage_translations', 'value' => 'Çevirileri Yönet', 'group' => 'general'],
        ['key' => 'general.import', 'value' => 'İçe Aktar', 'group' => 'general'],
        ['key' => 'general.export', 'value' => 'Dışa Aktar', 'group' => 'general'],
        ['key' => 'general.save', 'value' => 'Kaydet', 'group' => 'general'],
        ['key' => 'general.add_language', 'value' => 'Dil Ekle', 'group' => 'general'],
        ['key' => 'general.source_text', 'value' => 'Türkçe Kaynak', 'group' => 'general'],
        ['key' => 'general.translation', 'value' => 'Çeviri', 'group' => 'general'],
    ];

    public function ensureInfrastructure(): void
    {
        if (! $this->hasLocalizationTables()) {
            return;
        }

        $baseLanguage = PortalLanguage::query()->where('code', self::BASE_LOCALE)->first();

        if (! $baseLanguage) {
            PortalLanguage::query()->create([
                'code' => self::BASE_LOCALE,
                'name' => 'Türkçe',
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ]);
        } elseif (
            $baseLanguage->name !== 'Türkçe'
            || ! $baseLanguage->is_active
            || ! $baseLanguage->is_default
        ) {
            $baseLanguage->update([
                'name' => 'Türkçe',
                'is_active' => true,
                'is_default' => true,
            ]);
        }

        $this->registerDefaults();
    }

    public function registerDefaults(): void
    {
        foreach (self::DEFAULT_REGISTRY as $entry) {
            $this->registerKey($entry['key'], $entry['value'], $entry['group']);
        }
    }

    public function text(string $key, string $default, string $group = 'general', ?string $locale = null): string
    {
        if (! $this->hasLocalizationTables()) {
            return $default;
        }

        $this->ensureInfrastructure();
        $this->registerKey($key, $default, $group);

        $locale = $locale ?: app()->getLocale();
        $normalizedDefault = trim($default);

        if ($locale === self::BASE_LOCALE) {
            return $normalizedDefault;
        }

        $cacheKey = $locale . ':' . $key;

        if (! array_key_exists($cacheKey, $this->translationCache)) {
            $this->translationCache[$cacheKey] = PortalTranslation::query()
                ->where('locale', $locale)
                ->where('key', $key)
                ->value('value');
        }

        return filled($this->translationCache[$cacheKey])
            ? (string) $this->translationCache[$cacheKey]
            : $normalizedDefault;
    }

    public function registerKey(string $key, string $default, string $group = 'general'): void
    {
        if (! $this->hasLocalizationTables()) {
            return;
        }

        $signature = $group . ':' . $key;

        if (isset($this->registeredKeys[$signature])) {
            return;
        }

        $this->registeredKeys[$signature] = true;

        PortalTranslation::query()->firstOrCreate(
            ['key' => $key, 'locale' => self::BASE_LOCALE],
            ['group' => $group, 'value' => trim($default)]
        );
    }

    public function languages(bool $activeOnly = false): Collection
    {
        if (! $this->hasLocalizationTables()) {
            return collect();
        }

        if ($this->languageCache === null) {
            $this->ensureInfrastructure();

            $this->languageCache = PortalLanguage::query()
                ->orderByDesc('is_default')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        return $activeOnly
            ? $this->languageCache->where('is_active', true)->values()
            : $this->languageCache;
    }

    public function activeLanguages(): Collection
    {
        return $this->languages(true);
    }

    public function localeOptions(): array
    {
        return $this->activeLanguages()
            ->map(fn (PortalLanguage $language) => [
                'code' => $language->code,
                'name' => $language->name,
            ])
            ->values()
            ->all();
    }

    public function defaultLocale(): string
    {
        if (! $this->hasLocalizationTables()) {
            return self::BASE_LOCALE;
        }

        return PortalLanguage::query()
            ->where('is_default', true)
            ->value('code') ?: self::BASE_LOCALE;
    }

    public function isAvailableLocale(?string $locale): bool
    {
        if (! filled($locale)) {
            return false;
        }

        return $this->activeLanguages()->contains(fn (PortalLanguage $language) => $language->code === $locale);
    }

    public function createLanguage(array $data): PortalLanguage
    {
        $this->ensureInfrastructure();

        $language = DB::transaction(function () use ($data) {
            $nextSort = ((int) PortalLanguage::query()->max('sort_order')) + 1;

            $language = PortalLanguage::query()->create([
                'code' => strtolower((string) $data['code']),
                'name' => trim((string) $data['name']),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_default' => false,
                'sort_order' => $nextSort,
            ]);

            PortalTranslation::query()
                ->where('locale', self::BASE_LOCALE)
                ->get(['group', 'key', 'value'])
                ->each(function (PortalTranslation $translation) use ($language) {
                    PortalTranslation::query()->create([
                        'group' => $translation->group,
                        'key' => $translation->key,
                        'locale' => $language->code,
                        'value' => null,
                    ]);
                });

            return $language;
        });

        $this->flushCaches();

        return $language;
    }

    public function translationEditor(string $locale, ?string $search = null, ?string $group = null, ?string $status = null): LengthAwarePaginator
    {
        $this->ensureInfrastructure();

        $query = PortalTranslation::query()
            ->from('portal_translations as base')
            ->leftJoin('portal_translations as target', function ($join) use ($locale) {
                $join->on('target.key', '=', 'base.key')
                    ->where('target.locale', '=', $locale);
            })
            ->where('base.locale', self::BASE_LOCALE)
            ->select([
                'base.group',
                'base.key',
                'base.value as base_value',
                DB::raw('target.value as target_value'),
            ])
            ->orderBy('base.group')
            ->orderBy('base.key');

        if (filled($search)) {
            $query->where(function ($nested) use ($search) {
                $nested->where('base.key', 'like', '%' . $search . '%')
                    ->orWhere('base.value', 'like', '%' . $search . '%')
                    ->orWhere('target.value', 'like', '%' . $search . '%');
            });
        }

        if (filled($group)) {
            $query->where('base.group', $group);
        }

        if ($status === 'missing') {
            $query->where(function ($nested) {
                $nested->whereNull('target.value')
                    ->orWhere('target.value', '');
            });
        }

        if ($status === 'translated') {
            $query->whereNotNull('target.value')
                ->where('target.value', '!=', '');
        }

        return $query->paginate(50)->withQueryString();
    }

    public function translationGroups(): Collection
    {
        if (! $this->hasLocalizationTables()) {
            return collect();
        }

        $this->ensureInfrastructure();

        return PortalTranslation::query()
            ->where('locale', self::BASE_LOCALE)
            ->select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group');
    }

    public function saveTranslations(string $locale, array $translations): void
    {
        $this->ensureInfrastructure();

        DB::transaction(function () use ($locale, $translations) {
            foreach ($translations as $key => $value) {
                $base = PortalTranslation::query()
                    ->where('locale', self::BASE_LOCALE)
                    ->where('key', $key)
                    ->first();

                if (! $base) {
                    continue;
                }

                PortalTranslation::query()->updateOrCreate(
                    ['key' => $key, 'locale' => $locale],
                    ['group' => $base->group, 'value' => filled($value) ? trim((string) $value) : null]
                );
            }
        });

        $this->flushCaches();
    }

    public function export(string $locale): array
    {
        $language = PortalLanguage::query()->where('code', $locale)->firstOrFail();

        $translations = PortalTranslation::query()
            ->where('locale', $locale)
            ->orderBy('group')
            ->orderBy('key')
            ->get(['group', 'key', 'value']);

        return [
            'meta' => [
                'locale' => $language->code,
                'name' => $language->name,
                'exported_at' => now()->toIso8601String(),
                'default_locale' => $this->defaultLocale(),
            ],
            'translations' => $translations->mapWithKeys(fn (PortalTranslation $translation) => [
                $translation->key => $translation->value,
            ])->all(),
        ];
    }

    public function import(string $locale, array $translations): void
    {
        $this->ensureInfrastructure();

        DB::transaction(function () use ($locale, $translations) {
            foreach ($translations as $key => $value) {
                $base = PortalTranslation::query()
                    ->where('locale', self::BASE_LOCALE)
                    ->where('key', $key)
                    ->first();

                if (! $base) {
                    $base = PortalTranslation::query()->create([
                        'group' => str($key)->before('.')->toString() ?: 'general',
                        'key' => $key,
                        'locale' => self::BASE_LOCALE,
                        'value' => $key,
                    ]);
                }

                PortalTranslation::query()->updateOrCreate(
                    ['key' => $base->key, 'locale' => $locale],
                    ['group' => $base->group, 'value' => filled($value) ? trim((string) $value) : null]
                );
            }
        });

        $this->flushCaches();
    }

    public function hasLocalizationTables(): bool
    {
        return Schema::hasTable('portal_languages') && Schema::hasTable('portal_translations');
    }

    public function flushCaches(): void
    {
        $this->languageCache = null;
        $this->translationCache = [];
        $this->registeredKeys = [];
    }
}
