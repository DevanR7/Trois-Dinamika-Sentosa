<?php
namespace App\Policies;
use App\Models\PurchaseReturn;
use App\Models\User;

class PurchaseReturnPolicy
{
    public function viewAny(User $user): bool { return $user->can('view-purchase-returns'); }
    public function view(User $user, PurchaseReturn $purchaseReturn): bool { return $user->can('view-purchase-returns'); }
    public function create(User $user): bool { return $user->can('create-purchase-returns'); }
    public function delete(User $user, PurchaseReturn $purchaseReturn): bool { return $user->can('delete-purchase-returns'); }
}