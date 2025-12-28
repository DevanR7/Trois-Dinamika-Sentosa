<?php

namespace App\Policies;

use App\Models\SalesReturn;
use App\Models\User;

class SalesReturnPolicy
{
    public function viewAny(User $user): bool { return $user->can('view-sales-returns'); }
    public function view(User $user, SalesReturn $salesReturn): bool { return $user->can('view-sales-returns'); }
    public function create(User $user): bool { return $user->can('create-sales-returns'); }
    public function delete(User $user, SalesReturn $salesReturn): bool { return $user->can('delete-sales-returns'); }
}