<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Log;

// DAFTAR MODEL
use App\Models\User;
use App\Models\Product;
use App\Models\Order; 
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;
use App\Models\Announcement;

// DAFTAR POLICY
use App\Policies\ProductPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\SalesInvoicePolicy;
use App\Policies\SalesReturnPolicy;
use App\Policies\PurchaseReturnPolicy;
use App\Policies\AnnouncementPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Order::class => OrderPolicy::class, 
        PurchaseOrder::class => PurchaseOrderPolicy::class,
        Supplier::class => SupplierPolicy::class,
        Product::class => ProductPolicy::class,
        SalesInvoice::class => SalesInvoicePolicy::class,
        SalesReturn::class => SalesReturnPolicy::class,
        PurchaseReturn::class => PurchaseReturnPolicy::class,
        Announcement::class => AnnouncementPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // =====================================================================
        // 1. SUPER ADMIN BYPASS (Kunci Fleksibilitas Utama)
        // =====================================================================
        // Gate::before berjalan SEBELUM semua pengecekan lain.
        // Jika user punya role 'admin' atau 'superadmin', langsung izinkan (return true).
        // Kita gunakan strtolower agar 'Admin', 'ADMIN', 'admin' dianggap sama.
        
        Gate::before(function ($user, $ability) {
            // Cek menggunakan kolom manual 'role' ATAU library Spatie 'hasRole'
            $role = strtolower($user->role ?? '');
            
            if ($role === 'admin' || $role === 'superadmin') {
                return true; 
            }
            
            // Jika menggunakan Spatie:
            if (method_exists($user, 'hasRole')) {
                if ($user->hasRole('admin') || $user->hasRole('superadmin')) {
                    return true;
                }
            }
        });

        // =====================================================================
        // 2. GATE DEFINITIONS (Fleksibel dengan Array)
        // =====================================================================

        // Helper function kecil untuk cek role (agar coding di bawah rapi)
        $checkRoles = function($user, array $allowedRoles) {
            $userRole = strtolower($user->role ?? '');
            // Cek kolom manual
            if (in_array($userRole, array_map('strtolower', $allowedRoles))) {
                return true;
            }
            // Cek Spatie
            if (method_exists($user, 'hasRole') && $user->hasAnyRole($allowedRoles)) {
                return true;
            }
            return false;
        };

        // --- Gate: Akuntansi & Pengaturan ---
        // Fleksibel: Tinggal tambah 'finance_manager' atau role lain ke array ini
        Gate::define('manage-settings', function (User $user) use ($checkRoles) {
            return $checkRoles($user, ['admin', 'accountant', 'superadmin']);
        });

        // --- Gate: Keuangan (Rekonsiliasi & Kliring) ---
        Gate::define('manage-finance', function (User $user) use ($checkRoles) {
            return $checkRoles($user, ['admin', 'accountant', 'finance', 'finance_manager']); 
        });

        // --- Gate: View Reports ---
        Gate::define('view-reports', function (User $user) use ($checkRoles) {
            return $checkRoles($user, ['admin', 'accountant', 'manager', 'finance', 'director']);
        });

        // --- Gate: Create Adjustments ---
        Gate::define('create-invoice-adjustments', function (User $user) use ($checkRoles) {
            return $checkRoles($user, ['admin', 'accountant', 'sales_manager']);
        });
        
        // --- Gate: Delete Adjustments (YANG ERROR TADI) ---
        // Sekarang jika ada role baru misal 'audit', tinggal tambahkan ke array.
        Gate::define('delete-invoice-adjustments', function (User $user) use ($checkRoles) {
            return $checkRoles($user, ['admin', 'superadmin', 'finance_manager']);
        });
        
        // --- Gate: Payment Methods ---
        Gate::define('manage-payment-methods', function (User $user) use ($checkRoles) {
            return $checkRoles($user, ['admin', 'superadmin', 'it_support']);
        });

        // --- Gate: Manage Payment Clearance ---
        Gate::define('manage-payment-clearance', function (User $user) use ($checkRoles) {
            return $checkRoles($user, ['admin', 'finance', 'accountant']);
        });
    }    
}