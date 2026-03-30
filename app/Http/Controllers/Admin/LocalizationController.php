<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortalLanguage;
use App\Services\AuditLogService;
use App\Services\PortalLocalizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class LocalizationController extends Controller
{
    public function __construct(
        private PortalLocalizationService $localization,
        private AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        abort_if(! $this->localization->hasLocalizationTables(), 503, 'Dil altyapısı henüz kurulmadı.');

        $this->localization->ensureInfrastructure();

        $locale = (string) $request->query('locale', $this->resolveEditingLocale());
        $language = PortalLanguage::query()->where('code', $locale)->firstOrFail();

        return view('admin.localization.index', [
            'languages' => $this->localization->languages(),
            'language' => $language,
            'translations' => $this->localization->translationEditor(
                locale: $language->code,
                search: $request->string('q')->toString() ?: null,
                group: $request->string('group')->toString() ?: null,
                status: $request->string('status')->toString() ?: null,
            ),
            'groups' => $this->localization->translationGroups(),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'group' => $request->string('group')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function storeLanguage(Request $request): RedirectResponse
    {
        abort_if(! $this->localization->hasLocalizationTables(), 503, 'Dil altyapısı henüz kurulmadı.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['required', 'string', 'min:2', 'max:12', 'regex:/^[a-z]{2,12}(-[a-z]{2,12})?$/i', 'unique:portal_languages,code'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $language = $this->localization->createLanguage($validated);

        $this->auditLog->log('localization.language.create', $language, [
            'code' => $language->code,
            'name' => $language->name,
        ]);

        return redirect()
            ->route('admin.localization.index', ['locale' => $language->code])
            ->with('success', 'Yeni dil oluşturuldu ve çeviri editörü hazırlandı.');
    }

    public function updateTranslations(Request $request, string $locale): RedirectResponse
    {
        abort_if(! $this->localization->hasLocalizationTables(), 503, 'Dil altyapısı henüz kurulmadı.');
        abort_if($locale === 'tr', 403);

        PortalLanguage::query()->where('code', $locale)->firstOrFail();

        $validated = $request->validate([
            'translations' => ['nullable', 'array'],
            'translations.*' => ['nullable', 'string'],
        ]);

        $translations = Arr::where($validated['translations'] ?? [], fn ($value, $key) => filled($key));
        $this->localization->saveTranslations($locale, $translations);

        $this->auditLog->log('localization.translation.update', null, [
            'locale' => $locale,
            'updated_count' => count($translations),
        ]);

        return back()->with('success', 'Çeviri değişiklikleri kaydedildi.');
    }

    public function switchLocale(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'max:12'],
        ]);

        $locale = $validated['locale'];

        if (! $this->localization->isAvailableLocale($locale)) {
            return back()->with('warning', 'Seçilen dil şu anda kullanılamıyor.');
        }

        $request->session()->put('portal_locale', $locale);

        return back();
    }

    public function export(string $locale): Response
    {
        abort_if(! $this->localization->hasLocalizationTables(), 503, 'Dil altyapısı henüz kurulmadı.');

        $payload = $this->localization->export($locale);

        $this->auditLog->log('localization.translation.export', null, [
            'locale' => $locale,
        ]);

        return response(
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            200,
            [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="lider-portal-' . $locale . '-translations.json"',
            ]
        );
    }

    public function import(Request $request): RedirectResponse
    {
        abort_if(! $this->localization->hasLocalizationTables(), 503, 'Dil altyapısı henüz kurulmadı.');

        $validated = $request->validate([
            'locale' => ['required', 'string', 'min:2', 'max:12', 'regex:/^[a-z]{2,12}(-[a-z]{2,12})?$/i'],
            'language_name' => ['nullable', 'string', 'max:80'],
            'translation_file' => ['required', 'file', 'mimes:json,txt'],
        ]);

        $content = file_get_contents($request->file('translation_file')->getRealPath());
        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            return back()->with('error', 'Yüklenen dosya geçerli bir JSON değil.');
        }

        $translations = $decoded['translations'] ?? $decoded;

        if (! is_array($translations)) {
            return back()->with('error', 'İçe aktarma dosyasında çeviri verisi bulunamadı.');
        }

        $locale = strtolower($validated['locale']);
        $language = PortalLanguage::query()->where('code', $locale)->first();

        if (! $language) {
            $languageName = $validated['language_name']
                ?? data_get($decoded, 'meta.name')
                ?? strtoupper($locale);

            $language = $this->localization->createLanguage([
                'code' => $locale,
                'name' => $languageName,
                'is_active' => true,
            ]);

            $this->auditLog->log('localization.language.create', $language, [
                'code' => $language->code,
                'name' => $language->name,
                'source' => 'import',
            ]);
        }

        $this->localization->import($language->code, $translations);

        $this->auditLog->log('localization.translation.import', null, [
            'locale' => $language->code,
            'imported_count' => count($translations),
        ]);

        return redirect()
            ->route('admin.localization.index', ['locale' => $language->code])
            ->with('success', 'Çeviriler içe aktarıldı.');
    }

    private function resolveEditingLocale(): string
    {
        if (! $this->localization->hasLocalizationTables()) {
            return 'tr';
        }

        return PortalLanguage::query()
            ->where('code', '!=', 'tr')
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->value('code') ?: 'tr';
    }
}
