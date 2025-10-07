<?php

namespace App\Policies;

use App\Models\SalesInvoice;
use App\Models\User;

class SalesInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-invoices');
    }

    public function view(User $user, SalesInvoice $salesInvoice): bool
    {
        // Sales hanya bisa melihat invoice miliknya sendiri
        if ($user->hasRole('sales')) {
            return $salesInvoice->user_id_sales === $user->user_id;
        }
        return $user->can('view-invoices');
    }

    public function create(User $user): bool
    {
        return $user->can('create-invoices');
    }

    public function update(User $user, SalesInvoice $salesInvoice): bool
    {
        return $user->can('edit-invoices');
    }

    public function delete(User $user, SalesInvoice $salesInvoice): bool
    {
        return $user->can('delete-invoices');
    }
    
    public function cancel(User $user, SalesInvoice $salesInvoice): bool
    {
        return $user->can('cancel-invoices');
    }
}