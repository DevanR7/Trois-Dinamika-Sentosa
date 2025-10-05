<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    /**
     * Izinkan admin melakukan apa saja.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->role === 'admin') {
            return true;
        }
        return null;
    }

    /**
     * Semua role boleh melihat daftar produk.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Semua role boleh melihat detail satu produk.
     */
    public function view(User $user, Product $product): bool
    {
        return true;
    }

    /**
     * Hanya admin & manajemen yang boleh membuat produk.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'manajemen']);
    }

    /**
     * Hanya admin & manajemen yang boleh mengupdate produk.
     */
    public function update(User $user, Product $product): bool
    {
        return in_array($user->role, ['admin', 'manajemen']);
    }

    /**
     * Hanya admin & manajemen yang boleh menghapus produk.
     */
    public function delete(User $user, Product $product): bool
    {
        return in_array($user->role, ['admin', 'manajemen']);
    }
}