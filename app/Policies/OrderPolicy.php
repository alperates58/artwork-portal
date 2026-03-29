<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use App\Services\PortalSettings;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PurchaseOrder|PurchaseOrderLine $subject): bool
    {
        $supplierId = $subject instanceof PurchaseOrder
            ? $subject->supplier_id
            : $subject->purchaseOrder->supplier_id;

        if ($user->isSupplier()) {
            return $user->accessibleSupplierIds()->contains($supplierId);
        }

        return true;
    }

    public function create(User $user): bool
    {
        if (! app(PortalSettings::class)->portalConfig()['order_creation_enabled']) {
            return false;
        }

        if ($user->isSupplier()) return false;
        return $user->hasPermission('orders', 'create');
    }

    public function uploadArtwork(User $user, PurchaseOrderLine $line): bool
    {
        return $user->canUploadArtwork();
    }

    public function update(User $user, PurchaseOrder $order): bool
    {
        if ($user->isSupplier()) return false;
        return $user->hasPermission('orders', 'edit');
    }

    public function manualArtwork(User $user, PurchaseOrderLine $line): bool
    {
        if ($user->isSupplier()) {
            return false;
        }

        return $user->hasPermission('orders', 'edit') || $user->canUploadArtwork();
    }

    public function delete(User $user, PurchaseOrder $order): bool
    {
        if ($user->isSupplier()) return false;
        return $user->hasPermission('orders', 'delete');
    }
}
