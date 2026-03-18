<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\PurchaseOrderLine;
use App\Models\User;

class ArtworkPolicy
{
    /**
     * Artwork görüntüleme — tedarikçi sadece kendi siparişine ait olanı görebilir
     */
    public function view(User $user, Artwork $artwork): bool
    {
        if ($user->isSupplier()) {
            $supplierId = $artwork->orderLine->purchaseOrder->supplier_id;
            return $supplierId === $user->supplier_id;
        }

        return true;
    }

    /**
     * Artwork yükleme — sadece admin ve grafik departmanı
     */
    public function uploadArtwork(User $user, PurchaseOrderLine $line): bool
    {
        return $user->canUploadArtwork();
    }

    /**
     * Revizyon yönetimi — sadece admin ve grafik departmanı
     */
    public function manageRevisions(User $user, Artwork $artwork): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::GRAPHIC]);
    }
}
