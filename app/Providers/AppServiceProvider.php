<?php

namespace App\Providers;

use App\Models\Artwork;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Policies\ArtworkPolicy;
use App\Policies\OrderPolicy;
use App\Services\Office365OAuthTransportFactory;
use App\Services\PortalSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        date_default_timezone_set((string) config('app.timezone', 'Europe/Istanbul'));
        Carbon::setLocale((string) config('app.locale', 'tr'));

        Gate::policy(PurchaseOrder::class, OrderPolicy::class);
        Gate::policy(PurchaseOrderLine::class, OrderPolicy::class);
        Gate::policy(Artwork::class, ArtworkPolicy::class);
        Gate::policy(ArtworkRevision::class, ArtworkPolicy::class);

        \Illuminate\Pagination\Paginator::useTailwind();

        if (app()->bound('mail.manager')) {
            app('mail.manager')->extend('office365-oauth', function (array $config) {
                return app(Office365OAuthTransportFactory::class)->make($config);
            });
        }

        if (! app()->bound(PortalSettings::class)) {
            return;
        }

        $settings = app(PortalSettings::class);

        if (! $settings->hasSettingsTable()) {
            return;
        }

        $spaces = $settings->spacesConfig();

        config([
            'filesystems.default' => $settings->filesystemDisk(),
            'filesystems.disks.spaces.key' => $spaces['key'],
            'filesystems.disks.spaces.secret' => $spaces['secret'],
            'filesystems.disks.spaces.endpoint' => $spaces['endpoint'],
            'filesystems.disks.spaces.region' => $spaces['region'],
            'filesystems.disks.spaces.bucket' => $spaces['bucket'],
            'filesystems.disks.spaces.url' => $spaces['url'],
        ]);

        $mail = $settings->mailServerConfig();
        $localDomain = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';

        config([
            'mail.default' => $settings->defaultMailDriver(),
            'mail.mailers.smtp.host' => $mail['host'],
            'mail.mailers.smtp.port' => $mail['port'],
            'mail.mailers.smtp.username' => $mail['username'],
            'mail.mailers.smtp.password' => $mail['password'],
            'mail.mailers.smtp.encryption' => $mail['encryption'],
            'mail.mailers.smtp.scheme' => $mail['encryption'] === 'ssl' ? 'smtps' : null,
            'mail.mailers.office365_oauth.transport' => 'office365-oauth',
            'mail.mailers.office365_oauth.host' => $mail['host'],
            'mail.mailers.office365_oauth.port' => $mail['port'],
            'mail.mailers.office365_oauth.timeout' => 30,
            'mail.mailers.office365_oauth.local_domain' => $localDomain,
            'mail.mailers.office365_oauth.tenant_id' => $mail['oauth_tenant_id'],
            'mail.mailers.office365_oauth.client_id' => $mail['oauth_client_id'],
            'mail.mailers.office365_oauth.client_secret' => $mail['oauth_client_secret'],
            'mail.mailers.office365_oauth.sender' => $mail['oauth_sender'] ?: $mail['from_address'],
            'mail.from.address' => $mail['from_address'],
            'mail.from.name' => $mail['from_name'],
        ]);
    }
}
