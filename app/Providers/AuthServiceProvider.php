<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

// Import Models
use App\Models\User;
use App\Models\Product;
use App\Models\Order; 
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;
use App\Models\Announcement;

// Import Policies
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
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
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

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // ---------------------------------------------------------------------
        // 1. SUPERADMIN & ADMIN BYPASS
        // ---------------------------------------------------------------------
        // User dengan role superadmin/admin selalu lolos semua pengecekan
        Gate::before(function ($user, $ability) {
            if ($user->hasRole(['superadmin', 'admin'])) {
                return true; 
            }
        });

        // ---------------------------------------------------------------------
        // 2. DEFINISI GATE BERBASIS PERMISSION (SPATIE STANDARD)
        // ---------------------------------------------------------------------
        // Kita tidak lagi hardcode Role di sini. Kita cek apakah user punya Permission.
        // Permission ini didapat entah dari Role-nya, atau Direct Permission.
        
        // --- Dashboard ---
        Gate::define('view-dashboard', fn($user) => $user->hasPermissionTo('view-dashboard'));
        Gate::define('view-dashboard-financials', fn($user) => $user->hasPermissionTo('view-dashboard-financials'));
        Gate::define('view-dashboard-inventory', fn($user) => $user->hasPermissionTo('view-dashboard-inventory'));

        // =====================================================================
        // A. MASTER DATA
        // =====================================================================
        Gate::define('view-products', fn($user) => $user->hasPermissionTo('view-products'));
        Gate::define('create-products', fn($user) => $user->hasPermissionTo('create-products'));
        Gate::define('edit-products', fn($user) => $user->hasPermissionTo('edit-products'));
        Gate::define('delete-products', fn($user) => $user->hasPermissionTo('delete-products'));

        Gate::define('view-clients', fn($user) => $user->hasPermissionTo('view-clients'));
        Gate::define('create-clients', fn($user) => $user->hasPermissionTo('create-clients'));
        Gate::define('edit-clients', fn($user) => $user->hasPermissionTo('edit-clients'));
        Gate::define('delete-clients', fn($user) => $user->hasPermissionTo('delete-clients'));

        Gate::define('view-suppliers', fn($user) => $user->hasPermissionTo('view-suppliers'));
        Gate::define('create-suppliers', fn($user) => $user->hasPermissionTo('create-suppliers'));
        Gate::define('edit-suppliers', fn($user) => $user->hasPermissionTo('edit-suppliers'));
        Gate::define('delete-suppliers', fn($user) => $user->hasPermissionTo('delete-suppliers'));

        // =====================================================================
        // B. SALES MODULE
        // =====================================================================
        Gate::define('view-sales-orders', fn($user) => $user->hasPermissionTo('view-sales-orders'));
        Gate::define('create-sales-orders', fn($user) => $user->hasPermissionTo('create-sales-orders'));
        Gate::define('edit-sales-orders', fn($user) => $user->hasPermissionTo('edit-sales-orders'));
        Gate::define('delete-sales-orders', fn($user) => $user->hasPermissionTo('delete-sales-orders'));
        Gate::define('approve-client-orders', fn($user) => $user->hasPermissionTo('approve-client-orders'));
        Gate::define('reject-client-orders', fn($user) => $user->hasPermissionTo('reject-client-orders'));
        Gate::define('review-order-change-requests', fn($user) => $user->hasPermissionTo('review-order-change-requests'));

        // Invoices
        Gate::define('view-invoices', fn($user) => $user->hasPermissionTo('view-invoices'));
        Gate::define('create-invoices', fn($user) => $user->hasPermissionTo('create-invoices'));
        Gate::define('edit-invoices', fn($user) => $user->hasPermissionTo('edit-invoices'));
        Gate::define('delete-invoices', fn($user) => $user->hasPermissionTo('delete-invoices'));
        Gate::define('cancel-invoices', fn($user) => $user->hasPermissionTo('cancel-invoices'));
        
        // Manual Pay Permission (Legacy/Optional)
        Gate::define('pay-invoices', fn($user) => $user->hasPermissionTo('pay-invoices'));

        // Sales Returns & Adjustments
        Gate::define('view-sales-returns', fn($user) => $user->hasPermissionTo('view-sales-returns'));
        Gate::define('create-sales-returns', fn($user) => $user->hasPermissionTo('create-sales-returns'));
        Gate::define('delete-sales-returns', fn($user) => $user->hasPermissionTo('delete-sales-returns'));
        
        Gate::define('create-invoice-adjustments', fn($user) => $user->hasPermissionTo('create-invoice-adjustments'));
        Gate::define('delete-invoice-adjustments', fn($user) => $user->hasPermissionTo('delete-invoice-adjustments'));

        // =====================================================================
        // C. PURCHASE MODULE
        // =====================================================================
        Gate::define('view-purchase-orders', fn($user) => $user->hasPermissionTo('view-purchase-orders'));
        Gate::define('create-purchase-orders', fn($user) => $user->hasPermissionTo('create-purchase-orders'));
        Gate::define('edit-purchase-orders', fn($user) => $user->hasPermissionTo('edit-purchase-orders'));
        Gate::define('delete-purchase-orders', fn($user) => $user->hasPermissionTo('delete-purchase-orders'));
        Gate::define('cancel-purchase-orders', fn($user) => $user->hasPermissionTo('cancel-purchase-orders'));
        Gate::define('receive-purchase-orders', fn($user) => $user->hasPermissionTo('receive-purchase-orders'));
        Gate::define('pay-purchase-orders', fn($user) => $user->hasPermissionTo('pay-purchase-orders'));

        Gate::define('view-purchase-returns', fn($user) => $user->hasPermissionTo('view-purchase-returns'));
        Gate::define('create-purchase-returns', fn($user) => $user->hasPermissionTo('create-purchase-returns'));
        Gate::define('delete-purchase-returns', fn($user) => $user->hasPermissionTo('delete-purchase-returns'));
        
        Gate::define('create-purchase-adjustments', fn($user) => $user->hasPermissionTo('create-purchase-adjustments'));
        
        Gate::define('manage-stock-opnames', fn($user) => $user->hasPermissionTo('manage-stock-opnames'));

        // =====================================================================
        // D. FINANCE & ACCOUNTING
        // =====================================================================
        
        // [GATE FLEKSIBEL UNTUK MIDDLEWARE 'manage-finance']
        // Logika: User boleh masuk ke grup menu Keuangan JIKA dia punya Role 'finance'/'manager'
        // ATAU jika dia punya SALAH SATU permission kunci finance (misal diberikan ke Sales).
        Gate::define('manage-finance', function ($user) {
            // 1. Jalur Role Utama (Finance/Manager/Accountant pasti boleh)
            if ($user->hasAnyRole(['finance', 'manager', 'accountant'])) {
                return true;
            }
            
            // 2. Jalur Permission (Fleksibel)
            // Cek apakah user punya salah satu permission finance ini?
            return $user->hasAnyPermission([
                'manage-bank-accounts',
                'manage-payment-methods',
                'view-reports',
                'view-expenses',
                'manage-expenses',
                'view-loans',
                'manage-payment-clearance',
                'manage-manual-journals'
            ]);
        });

        Gate::define('view-expenses', fn($user) => $user->hasPermissionTo('view-expenses'));
        Gate::define('manage-expenses', fn($user) => $user->hasPermissionTo('manage-expenses'));

        Gate::define('view-loans', fn($user) => $user->hasPermissionTo('view-loans'));
        Gate::define('manage-loans', fn($user) => $user->hasPermissionTo('manage-loans'));

        Gate::define('view-fixed-assets', fn($user) => $user->hasPermissionTo('view-fixed-assets'));
        Gate::define('manage-fixed-assets', fn($user) => $user->hasPermissionTo('manage-fixed-assets'));

        Gate::define('manage-bank-accounts', fn($user) => $user->hasPermissionTo('manage-bank-accounts'));
        Gate::define('manage-payment-methods', fn($user) => $user->hasPermissionTo('manage-payment-methods'));

        // Payments Handling
        Gate::define('create-payments', fn($user) => $user->hasPermissionTo('create-payments'));
        Gate::define('manage-payment-clearance', fn($user) => $user->hasPermissionTo('manage-payment-clearance'));
        Gate::define('create-bulk-payments', fn($user) => $user->hasPermissionTo('create-bulk-payments'));
        Gate::define('create-bulk-purchase-payments', fn($user) => $user->hasPermissionTo('create-bulk-purchase-payments'));
        Gate::define('review-bulk-payments', fn($user) => $user->hasPermissionTo('review-bulk-payments'));

        // Accounting
        Gate::define('view-reports', fn($user) => $user->hasPermissionTo('view-reports'));
        Gate::define('manage-settings', fn($user) => $user->hasPermissionTo('manage-settings'));
        Gate::define('manage-manual-journals', fn($user) => $user->hasPermissionTo('manage-manual-journals'));
        Gate::define('manage-data-migration', fn($user) => $user->hasPermissionTo('manage-data-migration'));

        // =====================================================================
        // E. SYSTEM
        // =====================================================================
        Gate::define('manage-users', fn($user) => $user->hasPermissionTo('manage-users'));
        Gate::define('manage-roles', fn($user) => $user->hasPermissionTo('manage-roles'));
        Gate::define('manage-announcements', fn($user) => $user->hasPermissionTo('manage-announcements'));
    }    
}