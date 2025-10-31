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
        
        // Klien boleh melihat daftar (nanti difilter di controller)
        if ($user instanceof Client) {
            return true; 
        }

        return false;
    }

    public function view(Authenticatable $user, SalesInvoice $salesInvoice): bool
    {
        // --- Cek 1: Jika yang login adalah ADMIN/STAF (User) ---
        if ($user instanceof User) {
            // Sales hanya bisa melihat invoice miliknya sendiri
            if ($user->hasRole('sales')) {
                return $salesInvoice->user_id_sales === $user->user_id;
            }
            // Admin/Superadmin, dll.
            return $user->can('view-invoices');
        }

        // --- Cek 2: Jika yang login adalah KLIEN (Client) ---
        if ($user instanceof Client) {
            // Klien HANYA BISA melihat invoice milik mereka sendiri
            return $salesInvoice->client_id === $user->client_id;
        }

        // Jika model tidak dikenal (bukan User atau Client), tolak
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