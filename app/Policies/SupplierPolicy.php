<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
// ✅ 1. TAMBAHKAN USE STATEMENT INI
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPolicy
{
    // ✅ 2. TAMBAHKAN TRAIT INI
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

    // ✅ 3. TAMBAHKAN METHOD RESTORE INI
    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Supplier $supplier): bool
    {
        // Gunakan permission baru 'restore-suppliers' yang sudah Anda buat
        return $user->can('restore-suppliers');
    }

}