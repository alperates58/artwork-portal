<?php

namespace App\Providers;

use App\Models\Artwork;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Policies\ArtworkPolicy;
use App\Policies\OrderPolicy;
use App\Services\PortalSettings;
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
        Gate::policy(PurchaseOrder::class, OrderPolicy::class);
        Gate::policy(PurchaseOrderLine::class, OrderPolicy::class);
        Gate::policy(Artwork::class, ArtworkPolicy::class);
        Gate::policy(ArtworkRevision::class, ArtworkPolicy::class);

        \Illuminate\Pagination\Paginator::useTailwind();

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
    }
}
