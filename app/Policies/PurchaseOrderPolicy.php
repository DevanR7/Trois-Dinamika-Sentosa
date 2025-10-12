<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PurchaseOrderPolicy
{
    /**
     * Tentukan apakah user bisa melihat semua pesanan pembelian.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-purchase-orders');
    }

    /**
     * Tentukan apakah user bisa melihat satu pesanan pembelian.
     */
    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('view-purchase-orders');
    }

    /**
     * Tentukan apakah user bisa membuat pesanan pembelian.
     */
    public function create(User $user): bool
    {
        return $user->can('create-purchase-orders');
    }

    /**
     * Tentukan apakah user bisa mengedit pesanan pembelian.
     */
    public function update(User $user, PurchaseOrder $purchaseOrder): bool
{
    // [PERBAIKAN] Tambahkan pengecekan status di sini
    // Hanya izinkan edit jika statusnya adalah 'draft' atau 'ordered'
    if (!in_array($purchaseOrder->status, ['draft', 'ordered'])) {
        return false;
    }

    return $user->can('edit-purchase-orders');
}

    /**
     * Tentukan apakah user bisa menghapus pesanan pembelian.
     */
    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        // Kita belum membuat permission ini, tapi ini adalah pola yang baik
        // return $user->can('delete-purchase-orders');
        return false; // Sementara tidak ada yang bisa menghapus PO
    }

    /**
     * Tentukan apakah user bisa membatalkan pesanan.
     */
    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('cancel-purchase-orders');
    }

    /**
     * Tentukan apakah user bisa menandai barang telah diterima.
     */
    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('receive-purchase-orders');
    }

    /**
     * Tentukan apakah user bisa mencatat pembayaran PO.
     */
    public function pay(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('pay-purchase-orders');
    }
}