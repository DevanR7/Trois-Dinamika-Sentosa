<?php

namespace App\Providers;


use App\Models\User; // <-- Tambahkan ini
use Illuminate\Support\Facades\Gate; // <-- Tambahkan ini
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Product;
use App\Policies\ProductPolicy;
use App\Models\SalesOrder;
use App\Policies\SalesOrderPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Models\PurchaseOrder;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
         SalesOrder::class => SalesOrderPolicy::class, // <-- TAMBAHKAN BARIS INI
         PurchaseOrder::class => PurchaseOrderPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // --- TAMBAHKAN KODE GATE DI SINI ---
        Gate::define('view-user-management', function (User $user) {
            return $user->role === 'admin';
        });
        // --- AKHIR KODE GATE ---

        Gate::define('manage-invoices', function (User $user) {
        return in_array($user->role, ['admin', 'kasir']);
    });

    Gate::define('manage-settings', function (User $user) {
        return $user->role === 'admin';
    });

    Gate::define('manage-suppliers', function (User $user) {
        return $user->role === 'admin';
    });

    Gate::define('manage-purchases', function (User $user) {
        // Contoh: Hanya admin dan manajemen yang bisa mengelola pembelian
        return in_array($user->role, ['admin', 'manajemen']);
    });

    Gate::define('manage-clients', function ($user) {
        // Ganti 'admin' dengan nama role yang Anda inginkan.
        // Anda bisa menambahkan role lain dengan operator ATAU (||)
        // contoh: return $user->role === 'admin' || $user->role === 'sales_manager';
        return $user->role === 'admin'; 
    });
    
    }  
    
}