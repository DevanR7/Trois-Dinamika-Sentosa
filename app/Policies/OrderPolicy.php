<?php

namespace App\Policies;

use App\Models\Order; // ✅ BERUBAH: Menggunakan model Order
use App\Models\User;

// ✅ BERUBAH: Nama class diubah menjadi OrderPolicy
class OrderPolicy
{
    /**
     * Tentukan apakah user bisa melihat semua order.
     */
    public function viewAny(User $user): bool
    {
        // Nama permission tidak harus diubah, tapi Anda bisa jika mau
        return $user->can('view-sales-orders');
    }

    /**
     * Tentukan apakah user bisa melihat satu order.
     */
    // ✅ BERUBAH: Type-hint diubah ke Order $order
    public function view(User $user, Order $order): bool
    {
        // Bolehkan jika user punya permission, ATAU jika order ini miliknya (untuk role sales)
        // ✅ BERUBAH: Menggunakan variabel $order
        return $user->can('view-sales-orders') || $user->user_id === $order->user_id_sales;
    }

    /**
     * Tentukan apakah user bisa membuat order.
     */
    public function create(User $user): bool
    {
        return $user->can('create-sales-orders');
    }

    /**
     * Tentukan apakah user bisa mengedit order.
     */
    // ✅ BERUBAH: Type-hint diubah ke Order $order
    public function update(User $user, Order $order): bool
    {
        // [PERBAIKAN] HANYA boleh edit jika statusnya BUKAN 'invoiced'
        // ✅ BERUBAH: Menggunakan variabel $order
        if ($order->status === 'invoiced') {
            return false;
        }

        // Bolehkan jika user punya permission, ATAU jika order ini miliknya
        // ✅ BERUBAH: Menggunakan variabel $order
        return $user->can('edit-sales-orders') || $user->user_id === $order->user_id_sales;
    }

    /**
     * Tentukan apakah user bisa menghapus order.
     */
    // ✅ BERUBAH: Type-hint diubah ke Order $order
    public function delete(User $user, Order $order): bool
    {
        // [PERBAIKAN] HANYA boleh hapus jika statusnya BUKAN 'invoiced'
        // ✅ BERUBAH: Menggunakan variabel $order
        if ($order->status === 'invoiced') {
            return false;
        }
        
        return $user->can('delete-sales-orders');
    }
}