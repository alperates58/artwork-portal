<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GithubUpdateChecker;
use App\Services\MailNotificationDispatcher;
use App\Services\MailServerConnectionTester;
use App\Services\PortalSettings;
use App\Services\PortalUpdatePreparationService;
use App\Services\PortalUpdateStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        'general',
    ];

    public function __construct(
        private PortalSettings $settings,
        private PortalUpdateStatus $updateStatus,
        private GithubUpdateChecker $githubUpdateChecker,
        private PortalUpdatePreparationService $updatePreparationService,
        private MailNotificationDispatcher $mailDispatcher,
        private MailServerConnectionTester $mailConnectionTester,
    ) {}

    public function edit(Request $request): View
    {
        return view('admin.settings.edit', [
            'activeTab' => $this->resolveTab($request),
            'spaces' => $this->settings->spacesConfig(),
            'mikro' => $this->settings->mikroFormConfig(),
            'mailServer' => $this->settings->mailServerFormConfig(),
            'mailNotifications' => $this->settings->mailNotificationFormConfig(),
            'updateStatus' => $this->updateStatus->snapshot(),
            'generalSystem' => $this->generalSystemConfig(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $activeTab = $this->resolveTab($request);
        $validated = $this->validateSettingsUpdate($request, $activeTab);

        if (array_key_exists('spaces', $validated)) {
            $this->syncSpacesSettings($validated['spaces']);
        }

        if (array_key_exists('mikro', $validated)) {
            $this->syncMikroSettings($validated['mikro']);
        }

        if (array_key_exists('mail_server', $validated)) {
            $this->syncMailServerSettings($validated['mail_server']);
        }

        if (array_key_exists('mail_notifications', $validated)) {
            $this->settings->syncMailNotificationSettings($validated['mail_notifications']);
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

        return $this->validateWithTabRedirect($request, $activeTab, $rules);
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

    private function generalSystemConfig(): array
    {
        return [
            'app_name' => (string) config('app.name'),
            'app_env' => (string) config('app.env'),
            'app_version' => (string) config('app.version'),
            'app_timezone' => (string) config('app.timezone'),
            'queue_connection' => (string) config('queue.default'),
            'cache_store' => (string) config('cache.default'),
            'session_driver' => (string) config('session.driver'),
            'filesystem_disk' => $this->settings->filesystemDisk(),
            'mail_mailer' => (string) config('mail.default'),
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
