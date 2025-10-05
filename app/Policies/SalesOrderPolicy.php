<?php

namespace App\Policies;

use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SalesOrderPolicy
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
     * Tentukan apakah user bisa melihat daftar pesanan.
     * (Daftar akan kita filter di Controller)
     */
    public function viewAny(User $user): bool
    {
        return true; // Semua role boleh melihat halaman daftar pesanan
    }

    /**
     * Tentukan apakah user bisa melihat detail pesanan spesifik.
     */
    public function view(User $user, SalesOrder $salesOrder): bool
    {
        // Jika rolenya kasir/manajemen, izinkan.
        if (in_array($user->role, ['kasir', 'manajemen'])) {
            return true;
        }
        // Jika rolenya sales, hanya izinkan jika itu pesanan miliknya.
        return $user->user_id === $salesOrder->user_id_sales;
    }

    /**
     * Tentukan apakah user bisa membuat pesanan.
     */
    public function create(User $user): bool
    {
        // Sesuai permintaan Anda, semua role internal bisa
        return in_array($user->role, ['admin', 'sales', 'kasir', 'manajemen']);
    }

    /**
     * Tentukan apakah user bisa mengupdate pesanan.
     */
    public function update(User $user, SalesOrder $salesOrder): bool
    {
        // Jika rolenya kasir/manajemen, izinkan.
        if (in_array($user->role, ['kasir', 'manajemen','admin'])) {
            return true;
        }
        // Jika rolenya sales, hanya izinkan jika itu pesanan miliknya.
        return $user->user_id === $salesOrder->user_id_sales;
    }

    /**
     * Tentukan apakah user bisa menghapus pesanan.
     */
    public function delete(User $user, SalesOrder $salesOrder): bool
    {
         // Jika rolenya kasir/manajemen, izinkan.
        if (in_array($user->role, ['kasir', 'manajemen','admin'])) {
            return true;
        }
        // Jika rolenya sales, hanya izinkan jika itu pesanan miliknya.
        return $user->user_id === $salesOrder->user_id_sales;
    }
}