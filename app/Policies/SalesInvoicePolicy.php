<?php

namespace App\Policies;

use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Client;
use Illuminate\Contracts\Auth\Authenticatable;

class SalesInvoicePolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        if ($user instanceof User) {
            return $user->can('view-invoices');
        }
        if ($user instanceof Client) {
            return true; 
        }

        return false;
    }

    public function view(Authenticatable $user, SalesInvoice $salesInvoice): bool
    {
        if ($user instanceof User) {
            if ($user->hasRole('sales')) {
                return $salesInvoice->user_id_sales === $user->user_id;
            }
            return $user->can('view-invoices');
        }

        if ($user instanceof Client) {
            return $salesInvoice->client_id === $user->client_id;
        }

        return false;
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