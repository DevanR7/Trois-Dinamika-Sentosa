<?php

namespace App\Policies;

use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SalesOrderPolicy
{
    /**
     * Tentukan apakah user bisa melihat semua sales order.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-sales-orders');
    }

    /**
     * Tentukan apakah user bisa melihat satu sales order.
     */
    public function view(User $user, SalesOrder $salesOrder): bool
    {
        // Bolehkan jika user punya permission, ATAU jika order ini miliknya (untuk role sales)
        return $user->can('view-sales-orders') || $user->user_id === $salesOrder->user_id_sales;
    }

    /**
     * Tentukan apakah user bisa membuat sales order.
     */
    public function create(User $user): bool
    {
        return $user->can('create-sales-orders');
    }

    /**
     * Tentukan apakah user bisa mengedit sales order.
     */
    public function update(User $user, SalesOrder $salesOrder): bool
    {
        // Bolehkan jika user punya permission, ATAU jika order ini miliknya
        return $user->can('edit-sales-orders') || $user->user_id === $salesOrder->user_id_sales;
    }

    /**
     * Tentukan apakah user bisa menghapus sales order.
     */
    public function delete(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('delete-sales-orders');
    }
}