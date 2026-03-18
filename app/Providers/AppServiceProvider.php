<?php

namespace App\Providers;

use App\Models\Artwork;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Policies\ArtworkPolicy;
use App\Policies\OrderPolicy;
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
        // Policy registrations
        Gate::policy(PurchaseOrder::class, OrderPolicy::class);
        Gate::policy(PurchaseOrderLine::class, OrderPolicy::class);
        Gate::policy(Artwork::class, ArtworkPolicy::class);
        Gate::policy(ArtworkRevision::class, ArtworkPolicy::class);

        // Paginator style
        \Illuminate\Pagination\Paginator::useTailwind();
    }
}
