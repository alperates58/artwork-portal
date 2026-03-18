<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Hepsi sipariş listesini görebilir — scope ile filtre uygulanır
    }

    public function view(User $user, PurchaseOrder|PurchaseOrderLine $subject): bool
    {
        $supplierId = $subject instanceof PurchaseOrder
            ? $subject->supplier_id
            : $subject->purchaseOrder->supplier_id;

        if ($user->isSupplier()) {
            return $supplierId === $user->supplier_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::PURCHASING]);
    }

    public function update(User $user, PurchaseOrder $order): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::PURCHASING]);
    }

    public function delete(User $user, PurchaseOrder $order): bool
    {
        return $user->isAdmin();
    }
}
