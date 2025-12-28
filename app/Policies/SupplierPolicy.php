<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-suppliers');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('view-suppliers');
    }

    public function create(User $user): bool
    {
        return $user->can('create-suppliers');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('edit-suppliers');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('delete-suppliers');
    }

    public function restore(User $user, Supplier $supplier): bool
    {
        return $user->can('restore-suppliers');
    }
}