<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GithubUpdateChecker;
use App\Services\MailNotificationDispatcher;
use App\Services\PortalUpdatePreparationService;
use App\Services\PortalSettings;
use App\Services\PortalUpdateStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private PortalSettings $settings,
        private PortalUpdateStatus $updateStatus,
        private GithubUpdateChecker $githubUpdateChecker,
        private PortalUpdatePreparationService $updatePreparationService,
        private MailNotificationDispatcher $mailDispatcher,
    ) {}

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'spaces' => $this->settings->spacesConfig(),
            'mikro' => $this->settings->mikroFormConfig(),
            'mailNotifications' => $this->settings->mailNotificationFormConfig(),
            'updateStatus' => $this->updateStatus->snapshot(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'spaces.disk' => ['required', 'in:local,spaces'],
            'spaces.key' => ['nullable', 'string', 'max:255'],
            'spaces.secret' => ['nullable', 'string', 'max:255'],
            'spaces.endpoint' => ['nullable', 'url', 'max:255'],
            'spaces.region' => ['nullable', 'string', 'max:100'],
            'spaces.bucket' => ['nullable', 'string', 'max:255'],
            'spaces.url' => ['nullable', 'url', 'max:255'],
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
        ]);

        $this->settings->set('spaces', 'spaces.disk', $validated['spaces']['disk']);
        $this->settings->set('spaces', 'spaces.key', $validated['spaces']['key'] ?? null, true);
        $this->settings->set('spaces', 'spaces.secret', $validated['spaces']['secret'] ?? null, true);
        $this->settings->set('spaces', 'spaces.endpoint', $validated['spaces']['endpoint'] ?? null);
        $this->settings->set('spaces', 'spaces.region', $validated['spaces']['region'] ?? null);
        $this->settings->set('spaces', 'spaces.bucket', $validated['spaces']['bucket'] ?? null);
        $this->settings->set('spaces', 'spaces.url', $validated['spaces']['url'] ?? null);

        $mikro = $validated['mikro'] ?? [];
        $mikro['api_key'] = filled($mikro['api_key'] ?? null) ? $mikro['api_key'] : '__KEEP__';
        $mikro['username'] = filled($mikro['username'] ?? null) ? $mikro['username'] : '__KEEP__';
        $mikro['password'] = filled($mikro['password'] ?? null) ? $mikro['password'] : '__KEEP__';

        $this->settings->syncMikroSettings($mikro);
        if (array_key_exists('mail_notifications', $validated)) {
            $this->settings->syncMailNotificationSettings($validated['mail_notifications'] ?? []);
        }

        return back()->with('success', 'Sistem ayarlari guncellendi.');
    }

    public function sendTestMail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_mail_recipient' => ['nullable', 'email', 'max:255'],
        ]);

        $queued = $this->mailDispatcher->queueTestNotification(
            $validated['test_mail_recipient'] ?? null,
            $request->user()?->id
        );

        return back()->with(
            $queued ? 'success' : 'warning',
            $queued
                ? 'Test mail bildirimi kuyruga alindi.'
                : 'Test mail icin gecerli bir alici adresi bulunamadi.'
        );
    }

    public function checkUpdates(Request $request): RedirectResponse
    {
        $result = $this->githubUpdateChecker->checkAndStore(
            actor: $request->user(),
            triggerSource: 'admin'
        );

        return back()->with(
            $result['status'] === 'success' ? 'success' : 'warning',
            $result['message']
        );
    }

    public function prepareUpdate(Request $request): RedirectResponse
    {
        $status = $this->updateStatus->snapshot();

        if (($status['update_available'] ?? null) !== true) {
            return back()->with('warning', 'Hazırlık onayı için önce yeni bir update bulunmalıdır.');
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
            return back()->with('warning', 'Onaylanacak hazir bir update paketi bulunamadi. Once GitHub kontrolu yapin.');
        }

        $this->updatePreparationService->prepare($request->user(), $incoming);

        return back()->with('success', 'Update hazirligi kaydedildi. Bundan sonraki adim kontrollu CLI/deploy akisi ile tamamlanmalidir.');
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
