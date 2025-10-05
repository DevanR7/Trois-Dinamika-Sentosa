<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PurchaseOrderPolicy
{
    /**
     * Izinkan admin melakukan apa saja, ini akan meng-override method lain.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->role === 'admin') {
            return true;
        }
        return null; // Lanjutkan ke pemeriksaan method di bawah jika bukan admin
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'manajemen']);
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return in_array($user->role, ['admin', 'manajemen']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'manajemen']);
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        // Izinkan jika pesanan belum selesai diproses
        return in_array($user->role, ['admin', 'manajemen']) && $purchaseOrder->status !== 'completed';
    }

    /**
     * Tentukan apakah user bisa membatalkan pesanan.
     */
    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        // Izinkan jika pesanan belum selesai diproses
        return in_array($user->role, ['admin', 'manajemen']) && $purchaseOrder->status !== 'completed';
    }
}
