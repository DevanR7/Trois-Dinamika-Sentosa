<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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

        // --- 1. SUPER ADMIN (Bypass semua cek) ---
        // Jika role user adalah 'admin', otomatis boleh melakukan apa saja
        Gate::before(function ($user, $ability) {
            if ($user->role === 'admin') { 
                return true;
            }
        });

        // --- 2. GATE AKUNTANSI & PENGATURAN ---
        // Digunakan di: ClosingBook, ManualJournal, Settings, PaymentMethod
        Gate::define('manage-settings', function (User $user) {
            return in_array($user->role, ['admin', 'accountant']); 
        });

        // Digunakan di: BankReconciliation, PaymentClearance
        Gate::define('manage-finance', function (User $user) {
            return in_array($user->role, ['admin', 'accountant', 'finance']); 
        });

        // --- 3. GATE LAPORAN (View Reports) ---
        // Digunakan di: ReportController, GeneralLedger, Expense, Loan (Index)
        Gate::define('view-reports', function (User $user) {
             return in_array($user->role, ['admin', 'accountant', 'manager', 'finance']);
        });

        // --- 4. GATE PENYESUAIAN INVOICE/PO (Adjustments) ---
        Gate::define('create-invoice-adjustments', function (User $user) {
            return in_array($user->role, ['admin', 'accountant']);
        });
        
        Gate::define('delete-invoice-adjustments', function (User $user) {
            return $user->role === 'admin'; // Hanya admin yang boleh hapus
        });
        
        // Gate khusus untuk manage payment methods (jika belum ada)
        Gate::define('manage-payment-methods', function (User $user) {
            return $user->role === 'admin';
        });
    }   
}