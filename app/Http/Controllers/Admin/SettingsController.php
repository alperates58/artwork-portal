<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GithubUpdateChecker;
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
    ) {}

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'spaces' => $this->settings->spacesConfig(),
            'mikro' => $this->settings->mikroFormConfig(),
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
            'mikro.use_direct_db' => ['nullable', 'boolean'],
            'mikro.sync_interval_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
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

        return back()->with('success', 'Sistem ayarlari guncellendi.');
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
}
