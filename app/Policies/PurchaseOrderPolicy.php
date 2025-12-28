<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-purchase-orders');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('view-purchase-orders');
    }

    public function create(User $user): bool
    {
        return $user->can('create-purchase-orders');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
    if (!in_array($purchaseOrder->status, ['draft', 'ordered'])) {
        return false;
    }
        return $user->can('edit-purchase-orders');
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return false; 
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('cancel-purchase-orders');
    }

    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('receive-purchase-orders');
    }

    public function pay(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('pay-purchase-orders');
    }
}