<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Artwork;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Erp\MikroStockCardViewMappingService;
use App\Services\Erp\MikroViewMappingService;
use App\Services\GithubUpdateChecker;
use App\Services\MailNotificationDispatcher;
use App\Services\MailServerConnectionTester;
use App\Services\PortalDeployService;
use App\Services\PortalSettings;
use App\Services\PortalUpdatePreparationService;
use App\Services\PortalUpdateStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const DEFAULT_TAB = 'updates';

    private const ALLOWED_TABS = [
        'updates',
        'storage',
        'mikro',
        'mail',
        'formats',
        'portal',
        'backup',
        'general',
    ];

    private const DEFAULT_GROUPS = [
        ['key' => 'pdf',    'label' => 'PDF'],
        ['key' => 'image',  'label' => 'Görseller'],
        ['key' => 'design', 'label' => 'Tasarım'],
        ['key' => 'other',  'label' => 'Diğer'],
    ];

    private const DEFAULT_FORMATS = [
        ['ext' => 'PDF',  'label' => 'Adobe PDF',       'group' => 'pdf'],
        ['ext' => 'AI',   'label' => 'Adobe Illustrator','group' => 'design'],
        ['ext' => 'EPS',  'label' => 'EPS Vektör',       'group' => 'design'],
        ['ext' => 'PSD',  'label' => 'Adobe Photoshop',  'group' => 'design'],
        ['ext' => 'INDD', 'label' => 'Adobe InDesign',   'group' => 'design'],
        ['ext' => 'PNG',  'label' => 'PNG Görsel',        'group' => 'image'],
        ['ext' => 'JPG',  'label' => 'JPEG Görsel',       'group' => 'image'],
        ['ext' => 'JPEG', 'label' => 'JPEG Görsel',       'group' => 'image'],
        ['ext' => 'SVG',  'label' => 'SVG Vektör',        'group' => 'image'],
        ['ext' => 'WEBP', 'label' => 'WebP Görsel',       'group' => 'image'],
        ['ext' => 'ZIP',  'label' => 'ZIP Arşiv',         'group' => 'other'],
    ];

    public function __construct(
        private PortalSettings $settings,
        private PortalUpdateStatus $updateStatus,
        private GithubUpdateChecker $githubUpdateChecker,
        private PortalUpdatePreparationService $updatePreparationService,
        private MailNotificationDispatcher $mailDispatcher,
        private MailServerConnectionTester $mailConnectionTester,
        private PortalDeployService $deployService,
        private MikroViewMappingService $mikroViewMappings,
        private MikroStockCardViewMappingService $stockCardViewMappings,
    ) {}

    public function edit(Request $request): View
    {
        return view('admin.settings.edit', [
            'activeTab' => $this->resolveTab($request),
            'spaces' => $this->settings->spacesConfig(),
            'artworkStorage' => [
                'disk' => $this->settings->filesystemDisk(),
                'spaces_ready' => $this->settings->hasCompleteSpacesConfiguration(),
            ],
            'mikro' => $this->settings->mikroFormConfig(),
            'mikroViewMapping'       => $this->mikroViewMappings->formConfig(),
            'stockCardViewMapping'   => $this->stockCardViewMappings->formConfig(),
            'mailServer' => $this->settings->mailServerFormConfig(),
            'mailNotifications' => $this->settings->mailNotificationFormConfig(),
            'githubUpdate' => $this->settings->githubUpdatesFormConfig(),
            'updateStatus' => $this->updateStatus->snapshot(),
            'generalSystem' => $this->generalSystemConfig(),
            'portalConfig'  => $this->settings->portalConfig(),
            'fileFormats' => $this->fileFormatsConfig(),
            'fileGroups'  => $this->fileGroupsConfig(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $activeTab = $this->resolveTab($request);

        // Formats sekmesi için özel yetki kontrolü
        if ($activeTab === 'formats') {
            abort_if(
                ! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('formats', 'manage'),
                403
            );
        }

        $validated = $this->validateSettingsUpdate($request, $activeTab);

        if (array_key_exists('spaces', $validated)) {
            $this->syncSpacesSettings($validated['spaces']);
        }

        if (array_key_exists('mikro', $validated)) {
            $this->syncMikroSettings($validated['mikro']);
        }

        if (array_key_exists('mikro_view_mapping', $validated)) {
            $this->mikroViewMappings->save($validated['mikro_view_mapping'], $request->user());
        }

        if (array_key_exists('stock_card_view_mapping', $validated)) {
            $this->stockCardViewMappings->save($validated['stock_card_view_mapping'], $request->user());
        }

        if (array_key_exists('mail_server', $validated)) {
            $this->syncMailServerSettings($validated['mail_server']);
        }

        if (array_key_exists('mail_notifications', $validated)) {
            $this->settings->syncMailNotificationSettings($validated['mail_notifications']);
        }

        if (array_key_exists('github_updates', $validated)) {
            $this->syncGithubUpdateSettings($validated['github_updates']);
        }

        if (array_key_exists('formats', $validated)) {
            $this->syncFileFormats($validated['formats']);
        }

        if (array_key_exists('portal', $validated)) {
            $this->settings->syncPortalSettings($validated['portal']);
            $this->settings->syncArtworkStorageDisk($validated['portal']['artwork_storage_disk'] ?? null);
        }

        return $this->redirectToTab($activeTab)->with('success', 'Sistem ayarlari guncellendi.');
    }

    public function testMailConnection(Request $request): RedirectResponse
    {
        $activeTab = $this->resolveTab($request);

        try {
            $this->mailConnectionTester->test($this->settings->mailServerConfig());

            return $this->redirectToTab($activeTab)->with('success', 'Mail sunucusu baglantisi basariyla dogrulandi.');
        } catch (\Throwable $exception) {
            return $this->redirectToTab($activeTab)->with(
                'warning',
                'Mail sunucusu baglanti testi basarisiz oldu: ' . $this->safeMailErrorMessage($exception)
            );
        }
    }

    public function sendTestMail(Request $request): RedirectResponse
    {
        $activeTab = $this->resolveTab($request);
        $validated = $this->validateWithTabRedirect($request, $activeTab, [
            'test_mail_recipient' => ['nullable', 'email', 'max:255'],
        ]);

        $queued = $this->mailDispatcher->queueTestNotification(
            $validated['test_mail_recipient'] ?? null,
            $request->user()?->id
        );

        return $this->redirectToTab($activeTab)->with(
            $queued ? 'success' : 'warning',
            $queued
                ? 'Test mail bildirimi kuyruga alindi.'
                : 'Test mail icin gecerli bir alici adresi bulunamadi.'
        );
    }

    public function deploy(Request $request): JsonResponse
    {
        abort_if(! $request->user()->isAdmin(), 403);

        $result = $this->deployService->deploy();

        return response()->json($result, $result['ok'] ? 200 : 500);
    }

    public function applyOnly(Request $request): JsonResponse
    {
        abort_if(! $request->user()->isAdmin(), 403);

        $result = $this->deployService->applyOnly();

        return response()->json($result, $result['ok'] ? 200 : 500);
    }

    public function commits(Request $request): JsonResponse
    {
        abort_if(! $request->user()->isAdmin(), 403);

        $githubUpdate = $this->settings->githubUpdatesConfig();
        $repo   = $githubUpdate['repository'] ?? null;
        $branch = $githubUpdate['branch'] ?? 'main';
        $token  = $githubUpdate['token'] ?? null;

        if (blank($repo)) {
            return response()->json(['error' => 'GitHub repository tanımlı değil.'], 422);
        }

        try {
            $http = \Illuminate\Support\Facades\Http::baseUrl('https://api.github.com')
                ->acceptJson()
                ->timeout(15)
                ->withHeaders(['User-Agent' => config('app.name', 'Portal') . ' commit-list'])
                ->when(filled($token), fn ($r) => $r->withToken($token));

            // GitHub API max 100 per page — fetch all pages
            $allRaw = [];
            $page   = 1;
            do {
                $response = $http->get("/repos/{$repo}/commits", [
                    'sha'      => $branch,
                    'per_page' => 100,
                    'page'     => $page,
                ])->throw();

                $batch = $response->json();
                $allRaw = array_merge($allRaw, $batch);
                $page++;
            } while (count($batch) === 100);

            $commits = collect($allRaw)->map(fn ($c) => [
                'sha'     => substr($c['sha'], 0, 7),
                'full'    => $c['sha'],
                'message' => strtok($c['commit']['message'], "\n"),
                'author'  => $c['commit']['author']['name'] ?? '—',
                'date'    => $c['commit']['author']['date'] ?? null,
                'url'     => $c['html_url'] ?? null,
            ])->values()->all();

            return response()->json(['commits' => $commits, 'branch' => $branch]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'GitHub API hatası: ' . $e->getMessage()], 500);
        }
    }

    public function mikroViewSample(Request $request): JsonResponse
    {
        abort_if(! $request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'mikro_view_mapping.name' => ['required', 'string', 'max:120'],
            'mikro_view_mapping.view_name' => ['required', 'string', 'max:120'],
            'mikro_view_mapping.endpoint_path' => ['required', 'string', 'max:255'],
            'mikro_view_mapping.payload_mode' => ['required', 'in:flat_rows,nested_lines'],
            'mikro_view_mapping.line_array_key' => ['nullable', 'string', 'max:80'],
            'mikro_view_mapping.notes' => ['nullable', 'string', 'max:1000'],
            'mikro_view_mapping.mapping.order' => ['nullable', 'array'],
            'mikro_view_mapping.mapping.line' => ['nullable', 'array'],
        ]);

        try {
            return response()->json($this->mikroViewMappings->fetchSample($validated['mikro_view_mapping']));
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    public function checkUpdates(Request $request): RedirectResponse
    {
        $activeTab = $this->resolveTab($request);
        $result = $this->githubUpdateChecker->checkAndStore(
            actor: $request->user(),
            triggerSource: 'admin'
        );

        return $this->redirectToTab($activeTab)->with(
            $result['status'] === 'success' ? 'success' : 'warning',
            $result['message']
        );
    }

    public function prepareUpdate(Request $request): RedirectResponse
    {
        $activeTab = $this->resolveTab($request);
        $status = $this->updateStatus->snapshot();

        if (($status['update_available'] ?? null) !== true) {
            return $this->redirectToTab($activeTab)->with('warning', 'Hazirlik onayi icin once yeni bir update bulunmalidir.');
        }

        $release = $status['latest_remote_release'] ?? null;
        $incoming = $release && filled($release['version'] ?? null)
            ? [
                'current_version' => $status['current_version'],
                'target_version' => $release['version'],
                'target_release' => [
                    'version' => $release['version'],
                    'title' => $release['title'] ?? null,
                    'summary' => $release['summary'] ?? null,
                    'changes' => $release['change_summary'] ?? [],
                    'changed_modules' => $release['changed_modules'] ?? [],
                    'migrations_included' => $release['migrations_included'] ?? false,
                    'schema_changes' => $release['schema_changes'] ?? ['new_tables' => [], 'new_columns' => []],
                    'warnings' => $release['warnings'] ?? [],
                    'post_update_notes' => $release['post_update_notes'] ?? [],
                    'applied_migrations' => $release['applied_migrations'] ?? [],
                    'release_date' => $release['release_date'] ?? null,
                ],
                'release_count' => 1,
            ]
            : null;

        if (! $incoming || empty($incoming['target_release'])) {
            return $this->redirectToTab($activeTab)->with('warning', 'Onaylanacak hazir bir update paketi bulunamadi. Once GitHub kontrolu yapin.');
        }

        $this->updatePreparationService->prepare($request->user(), $incoming);

        return $this->redirectToTab($activeTab)->with(
            'success',
            'Update hazirligi kaydedildi. Bundan sonraki adim kontrollu CLI/deploy akisi ile tamamlanmalidir.'
        );
    }

    private function validateSettingsUpdate(Request $request, string $activeTab): array
    {
        $section = $request->string('settings_section')->toString();
        $rules = [];

        if ($section === 'storage' || $request->has('spaces')) {
            $rules += [
                'spaces.disk' => ['required', 'in:local,spaces'],
                'spaces.key' => ['nullable', 'string', 'max:255'],
                'spaces.secret' => ['nullable', 'string', 'max:255'],
                'spaces.endpoint' => ['nullable', 'url', 'max:255'],
                'spaces.region' => ['nullable', 'string', 'max:100'],
                'spaces.bucket' => ['nullable', 'string', 'max:255'],
                'spaces.url' => ['nullable', 'url', 'max:255'],
            ];
        }

        if ($section === 'mikro' || $request->has('mikro')) {
            $rules += [
                'mikro.enabled' => ['nullable', 'boolean'],
                'mikro.base_url' => ['nullable', 'url', 'max:255'],
                'mikro.api_key' => ['nullable', 'string', 'max:255'],
                'mikro.username' => ['nullable', 'string', 'max:255'],
                'mikro.password' => ['nullable', 'string', 'max:255'],
                'mikro.company_code' => ['nullable', 'string', 'max:100'],
                'mikro.work_year' => ['nullable', 'string', 'max:20'],
                'mikro.timeout' => ['required', 'integer', 'min:1', 'max:300'],
                'mikro.verify_ssl' => ['nullable', 'boolean'],
                'mikro.shipment_endpoint' => ['nullable', 'string', 'max:255'],
                'mikro.sync_interval_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            ];
        }

        if ($section === 'mikro_view_mapping' || $request->has('mikro_view_mapping')) {
            $rules += [
                'mikro_view_mapping.id' => ['nullable', 'integer'],
                'mikro_view_mapping.name' => ['required', 'string', 'max:120'],
                'mikro_view_mapping.view_name' => ['required', 'string', 'max:120'],
                'mikro_view_mapping.endpoint_path' => ['required', 'string', 'max:255'],
                'mikro_view_mapping.payload_mode' => ['required', 'in:flat_rows,nested_lines'],
                'mikro_view_mapping.line_array_key' => ['nullable', 'string', 'max:80'],
                'mikro_view_mapping.notes' => ['nullable', 'string', 'max:1000'],
                'mikro_view_mapping.mapping.order' => ['required', 'array'],
                'mikro_view_mapping.mapping.line' => ['required', 'array'],
            ];
        }

        if ($section === 'stock_card_view_mapping' || $request->has('stock_card_view_mapping')) {
            $rules += [
                'stock_card_view_mapping.id'                    => ['nullable', 'integer'],
                'stock_card_view_mapping.name'                  => ['required', 'string', 'max:120'],
                'stock_card_view_mapping.view_name'             => ['required', 'string', 'max:120'],
                'stock_card_view_mapping.endpoint_path'         => ['required', 'string', 'max:255'],
                'stock_card_view_mapping.notes'                 => ['nullable', 'string', 'max:1000'],
                'stock_card_view_mapping.mapping.stock_code'    => ['required', 'string', 'max:120'],
                'stock_card_view_mapping.mapping.stock_name'    => ['required', 'string', 'max:120'],
                'stock_card_view_mapping.mapping.category'      => ['required', 'string', 'max:120'],
            ];
        }

        if ($section === 'mail' || $request->has('mail_server')) {
            $rules += [
                'mail_server.host' => ['required', 'string', 'max:255'],
                'mail_server.port' => ['required', 'integer', 'min:1', 'max:65535'],
                'mail_server.username' => ['nullable', 'string', 'max:255'],
                'mail_server.password' => ['nullable', 'string', 'max:255'],
                'mail_server.encryption' => ['nullable', 'in:none,tls,ssl'],
                'mail_server.from_address' => ['required', 'email', 'max:255'],
                'mail_server.from_name' => ['required', 'string', 'max:255'],
            ];
        }

        if ($section === 'formats' || $request->has('formats')) {
            $rules += [
                'formats.list'             => ['nullable', 'array'],
                'formats.list.*.ext'       => ['required', 'string', 'max:20'],
                'formats.list.*.label'     => ['required', 'string', 'max:100'],
                'formats.list.*.group'     => ['required', 'string', 'max:50'],
                'formats.groups'           => ['nullable', 'array'],
                'formats.groups.*.key'     => ['required', 'string', 'max:50'],
                'formats.groups.*.label'   => ['required', 'string', 'max:100'],
            ];
        }

        if ($section === 'portal' || $request->has('portal')) {
            $maxUploadSizeMb = max(1, (int) config('artwork.max_file_size_mb', 1200));

            $rules += [
                'portal.order_creation_enabled'      => ['nullable', 'boolean'],
                'portal.supplier_portal_enabled'     => ['nullable', 'boolean'],
                'portal.maintenance_mode'            => ['nullable', 'boolean'],
                'portal.allow_manual_artwork'        => ['nullable', 'boolean'],
                'portal.require_2fa_for_admin'       => ['nullable', 'boolean'],
                'portal.data_transfer_allowed'       => ['nullable', 'boolean'],
                'portal.order_deletion_enabled'      => ['nullable', 'boolean'],
                'portal.preview_png_required'        => ['nullable', 'boolean'],
                'portal.supplier_auto_create'        => ['nullable', 'boolean'],
                'portal.artwork_storage_disk'        => ['nullable', 'in:local,spaces'],
                'portal.max_upload_size_mb'          => ['required', 'integer', 'min:1', 'max:' . $maxUploadSizeMb],
                'portal.max_revision_count'          => ['required', 'integer', 'min:1', 'max:100'],
                'portal.session_timeout_minutes'     => ['required', 'integer', 'min:15', 'max:10080'],
                'portal.order_deadline_warning_days' => ['required', 'integer', 'min:1', 'max:60'],
                'portal.max_orders_per_page'         => ['required', 'integer', 'min:5', 'max:200'],
                'portal.audit_log_retention_days'    => ['required', 'integer', 'min:30', 'max:3650'],
            ];
        }

        if ($section === 'mail' || $request->has('mail_notifications')) {
            $rules += [
                'mail_notifications.enabled' => ['nullable', 'boolean'],
                'mail_notifications.graphics_to' => ['nullable', 'string', 'max:1000', function ($attribute, $value, $fail) {
                    $this->validateEmailList($value, $fail);
                }],
                'mail_notifications.graphics_cc' => ['nullable', 'string', 'max:1000', function ($attribute, $value, $fail) {
                    $this->validateEmailList($value, $fail);
                }],
                'mail_notifications.graphics_bcc' => ['nullable', 'string', 'max:1000', function ($attribute, $value, $fail) {
                    $this->validateEmailList($value, $fail);
                }],
                'mail_notifications.new_order_subject' => ['nullable', 'string', 'max:255'],
                'mail_notifications.override_from_name' => ['nullable', 'string', 'max:255'],
                'mail_notifications.override_from_address' => ['nullable', 'email', 'max:255'],
                'mail_notifications.test_recipient' => ['nullable', 'email', 'max:255'],
            ];
        }

        if ($section === 'updates' || $section === 'github_updates' || $request->has('github_updates')) {
            $rules += [
                'github_updates.repository' => ['required', 'string', 'max:255', 'regex:/^(https?:\/\/github\.com\/)?[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+(\.git)?$/'],
                'github_updates.branch' => ['required', 'string', 'max:255'],
                'github_updates.token' => ['nullable', 'string', 'max:255'],
            ];
        }

        $validated = $this->validateWithTabRedirect($request, $activeTab, $rules);

        if (($validated['spaces']['disk'] ?? null) === 'spaces') {
            $missingSpacesFields = collect([
                'spaces.key' => $validated['spaces']['key'] ?? null,
                'spaces.secret' => $validated['spaces']['secret'] ?? null,
                'spaces.endpoint' => $validated['spaces']['endpoint'] ?? null,
                'spaces.region' => $validated['spaces']['region'] ?? null,
                'spaces.bucket' => $validated['spaces']['bucket'] ?? null,
            ])->filter(fn ($value) => ! filled($value))->keys();

            if ($missingSpacesFields->isNotEmpty()) {
                $validator = Validator::make($request->all(), []);

                foreach ($missingSpacesFields as $field) {
                    $validator->errors()->add($field, 'Spaces kullanımı için bu alan zorunludur.');
                }

                throw tap(new ValidationException($validator), function (ValidationException $exception) use ($activeTab): void {
                    $exception->redirectTo($this->settingsTabUrl($activeTab));
                });
            }
        }

        if (($validated['portal']['artwork_storage_disk'] ?? null) === 'spaces' && ! $this->settings->hasCompleteSpacesConfiguration()) {
            $validator = Validator::make($request->all(), []);
            $validator->errors()->add(
                'portal.artwork_storage_disk',
                'Spaces seçeneğini kullanmadan önce Storage / Spaces sekmesindeki bağlantı alanlarını tamamlayın.'
            );

            throw tap(new ValidationException($validator), function (ValidationException $exception) use ($activeTab): void {
                $exception->redirectTo($this->settingsTabUrl($activeTab));
            });
        }

        return $validated;
    }

    private function validateWithTabRedirect(Request $request, string $activeTab, array $rules): array
    {
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw tap(new ValidationException($validator), function (ValidationException $exception) use ($activeTab): void {
                $exception->redirectTo($this->settingsTabUrl($activeTab));
            });
        }

        return $validator->validated();
    }

    private function syncSpacesSettings(array $spaces): void
    {
        $this->settings->set('spaces', 'spaces.disk', $spaces['disk']);
        $this->settings->set('spaces', 'spaces.key', $spaces['key'] ?? null, true);
        $this->settings->set('spaces', 'spaces.secret', $spaces['secret'] ?? null, true);
        $this->settings->set('spaces', 'spaces.endpoint', $spaces['endpoint'] ?? null);
        $this->settings->set('spaces', 'spaces.region', $spaces['region'] ?? null);
        $this->settings->set('spaces', 'spaces.bucket', $spaces['bucket'] ?? null);
        $this->settings->set('spaces', 'spaces.url', $spaces['url'] ?? null);
    }

    private function syncMikroSettings(array $mikro): void
    {
        $mikro['api_key'] = filled($mikro['api_key'] ?? null) ? $mikro['api_key'] : '__KEEP__';
        $mikro['username'] = filled($mikro['username'] ?? null) ? $mikro['username'] : '__KEEP__';
        $mikro['password'] = filled($mikro['password'] ?? null) ? $mikro['password'] : '__KEEP__';

        $this->settings->syncMikroSettings($mikro);
    }

    private function syncMailServerSettings(array $mailServer): void
    {
        $mailServer['username'] = filled($mailServer['username'] ?? null) ? $mailServer['username'] : '__KEEP__';
        $mailServer['password'] = filled($mailServer['password'] ?? null) ? $mailServer['password'] : '__KEEP__';
        $mailServer['encryption'] = ($mailServer['encryption'] ?? 'none') === 'none'
            ? null
            : $mailServer['encryption'];

        $this->settings->syncMailServerSettings($mailServer);
    }

    private function syncGithubUpdateSettings(array $githubUpdates): void
    {
        $githubUpdates['token'] = filled($githubUpdates['token'] ?? null) ? $githubUpdates['token'] : '__KEEP__';

        $this->settings->syncGithubUpdateSettings($githubUpdates);
    }

    private function resolveTab(Request $request): string
    {
        $tab = $request->query('tab', $request->input('tab', self::DEFAULT_TAB));

        return in_array($tab, self::ALLOWED_TABS, true) ? $tab : self::DEFAULT_TAB;
    }

    private function redirectToTab(string $tab): RedirectResponse
    {
        return redirect()->to($this->settingsTabUrl($tab));
    }

    private function settingsTabUrl(string $tab): string
    {
        return route('admin.settings.edit', ['tab' => $tab]);
    }

    private function fileFormatsConfig(): array
    {
        return $this->settings->fileFormats();
    }

    private function syncFileFormats(array $data): void
    {
        // Grupları kaydet
        $groups = collect($data['groups'] ?? [])
            ->filter(fn ($row) => filled($row['key'] ?? null) && filled($row['label'] ?? null))
            ->map(fn ($row) => [
                'key'   => strtolower(preg_replace('/[^a-z0-9_]/i', '_', trim($row['key']))),
                'label' => trim($row['label']),
            ])
            ->values()
            ->all();

        if (count($groups) > 0) {
            $this->settings->set('formats', 'formats.groups', json_encode($groups));
        }

        // Format listesini kaydet
        $list = collect($data['list'] ?? [])
            ->filter(fn ($row) => filled($row['ext'] ?? null) && filled($row['label'] ?? null))
            ->map(fn ($row) => [
                'ext'   => strtoupper(trim($row['ext'])),
                'label' => trim($row['label']),
                'group' => $row['group'] ?? 'other',
            ])
            ->values()
            ->all();

        $this->settings->set('formats', 'formats.list', json_encode($list));
    }

    private function fileGroupsConfig(): array
    {
        return $this->settings->fileGroups();
    }

    private function generalSystemConfig(): array
    {
        // --- Database stats ---
        $orderCount        = PurchaseOrder::count();
        $lineCount         = PurchaseOrderLine::count();
        $pendingLineCount  = PurchaseOrderLine::where('artwork_status', 'pending')->count();
        $artworkCount      = Artwork::count();
        $revisionCount     = ArtworkRevision::count();
        $supplierCount     = Supplier::count();
        $activeSupplierCount = Supplier::where('is_active', true)->count();
        $totalUserCount    = User::count();
        $adminUserCount    = User::where('role', UserRole::ADMIN->value)->count();
        $purchasingUserCount = User::where('role', UserRole::PURCHASING->value)->count();
        $graphicUserCount  = User::where('role', UserRole::GRAPHIC->value)->count();
        $supplierUserCount = User::where('role', UserRole::SUPPLIER->value)->count();
        $internalUserCount = $adminUserCount + $purchasingUserCount + $graphicUserCount;

        // --- Local disk ---
        $storagePath    = storage_path('app');
        $diskTotal      = @disk_total_space($storagePath) ?: 0;
        $diskFree       = @disk_free_space($storagePath)  ?: 0;
        $diskUsed       = $diskTotal - $diskFree;

        // --- Spaces ---
        $spacesConfig   = $this->settings->spacesConfig();
        $spacesReady    = $this->settings->hasCompleteSpacesConfiguration();
        $spacesFileCount = null;
        if ($spacesReady) {
            try {
                $spacesFileCount = count(Storage::disk('spaces')->allFiles());
            } catch (\Throwable) {
                $spacesFileCount = null;
            }
        }

        // --- Server ---
        $serverIp       = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
        $hostname       = gethostname() ?: 'Bilinmiyor';

        // --- PHP ---
        $phpVersion     = PHP_VERSION;
        $memoryLimit    = ini_get('memory_limit');
        $uploadMax      = ini_get('upload_max_filesize');
        $maxExecution   = ini_get('max_execution_time');
        $opcache        = function_exists('opcache_get_status') && opcache_get_status(false) !== false;

        // --- DB size (MySQL) ---
        $dbName  = config('database.connections.mysql.database');
        $dbSize  = null;
        try {
            $row = DB::selectOne("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables WHERE table_schema = ?", [$dbName]);
            $dbSize = $row?->size_mb;
        } catch (\Throwable) {}

        return [
            // App
            'app_name'             => (string) config('app.name'),
            'app_env'              => (string) config('app.env'),
            'app_version'          => (string) config('app.version'),
            'app_timezone'         => (string) config('app.timezone'),
            'queue_connection'     => (string) config('queue.default'),
            'cache_store'          => (string) config('cache.default'),
            'session_driver'       => (string) config('session.driver'),
            'filesystem_disk'      => $this->settings->filesystemDisk(),
            'mail_mailer'          => (string) config('mail.default'),
            // Stats
            'order_count'          => $orderCount,
            'line_count'           => $lineCount,
            'pending_line_count'   => $pendingLineCount,
            'artwork_count'        => $artworkCount,
            'revision_count'       => $revisionCount,
            'supplier_count'       => $supplierCount,
            'active_supplier_count'=> $activeSupplierCount,
            'total_user_count'     => $totalUserCount,
            'admin_user_count'     => $adminUserCount,
            'purchasing_user_count'=> $purchasingUserCount,
            'graphic_user_count'   => $graphicUserCount,
            'internal_user_count'  => $internalUserCount,
            'supplier_user_count'  => $supplierUserCount,
            // Disk
            'disk_total_bytes'     => $diskTotal,
            'disk_used_bytes'      => $diskUsed,
            'disk_free_bytes'      => $diskFree,
            // Spaces
            'spaces_ready'         => $spacesReady,
            'spaces_bucket'        => $spacesConfig['bucket'] ?? null,
            'spaces_endpoint'      => $spacesConfig['endpoint'] ?? null,
            'spaces_disk'          => $spacesConfig['disk'] ?? 'local',
            'spaces_file_count'    => $spacesFileCount,
            // Server
            'server_ip'            => $serverIp,
            'server_hostname'      => $hostname,
            // PHP
            'php_version'          => $phpVersion,
            'memory_limit'         => $memoryLimit,
            'upload_max'           => $uploadMax,
            'max_execution'        => $maxExecution,
            'opcache_enabled'      => $opcache,
            // DB
            'db_name'              => $dbName,
            'db_size_mb'           => $dbSize,
        ];
    }

    private function safeMailErrorMessage(\Throwable $exception): string
    {
        $message = preg_replace('/\s+/', ' ', trim($exception->getMessage()));

        if (blank($message)) {
            return 'Bilinmeyen bir hata olustu.';
        }

        $sensitiveValues = collect([
            config('mail.mailers.smtp.password'),
            config('mail.mailers.smtp.username'),
        ])->filter(fn ($value) => filled($value))->all();

        if ($sensitiveValues !== []) {
            $message = str_ireplace($sensitiveValues, '[hidden]', $message);
        }

        return str($message)->limit(180)->toString();
    }

    private function validateEmailList(?string $value, \Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $items = preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($items as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $fail('Lutfen gecerli e-posta adresleri girin.');
                return;
            }
        }
    }
}
