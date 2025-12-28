<?php

namespace App\Policies;

use App\Models\Order; 
use App\Models\User;

class OrderPolicy
{

    public function viewAny(User $user): bool
    {
        return $user->can('view-sales-orders');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('view-sales-orders') || $user->user_id === $order->user_id_sales;
    }

    public function create(User $user): bool
    {
        return $user->can('create-sales-orders');
    }

    public function update(User $user, Order $order): bool
    {
        if ($order->status === 'invoiced') {
            return false;
        }

        return $user->can('edit-sales-orders') || $user->user_id === $order->user_id_sales;
    }

    public function delete(User $user, Order $order): bool
    {
        if ($order->status === 'invoiced') {
            return false;
        }
        
        return $user->can('delete-sales-orders');
    }
}