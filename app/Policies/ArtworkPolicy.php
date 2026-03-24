<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\PurchaseOrderLine;
use App\Models\User;

class ArtworkPolicy
{
    public function view(User $user, Artwork $artwork): bool
    {
        if ($user->isSupplier()) {
            return $user->canAccessOrder($artwork->orderLine->purchaseOrder);
        }

        return true;
    }

    public function uploadArtwork(User $user, PurchaseOrderLine $line): bool
    {
        return $user->canUploadArtwork();
    }

    public function manageRevisions(User $user, Artwork $artwork): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::GRAPHIC], true);
    }
}
