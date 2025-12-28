<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use App\Models\User;
use App\Models\Product;
use App\Models\Order; 
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;
use App\Models\Announcement;

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
        
        Gate::before(function ($user, $ability) {
            $role = strtolower($user->role ?? '');
    
            if ($role === 'admin' || $role === 'superadmin') {
                return true; 
            }
            if (method_exists($user, 'hasRole')) {
                if ($user->hasRole('admin') || $user->hasRole('superadmin')) {
                    return true;
                }
            }
        });

        $checkRoles = function($user, array $allowedRoles) {
            $userRole = strtolower($user->role ?? '');
            if (in_array($userRole, array_map('strtolower', $allowedRoles))) {
                return true;
            }
            if (method_exists($user, 'hasRole') && $user->hasAnyRole($allowedRoles)) {
                return true;
            }
            return false;
        };

        Gate::define('manage-settings', function (User $user) use ($checkRoles) {
            return $checkRoles($user, ['admin', 'accountant', 'superadmin']);
        });

        Gate::define('manage-finance', function (User $user) use ($checkRoles) {
            return $checkRoles($user, ['admin', 'accountant', 'finance', 'finance_manager']); 
        });

        Gate::define('view-reports', function (User $user) use ($checkRoles) {
            return $checkRoles($user, ['admin', 'accountant', 'manager', 'finance', 'director']);
        });

        Gate::define('create-invoice-adjustments', function (User $user) use ($checkRoles) {
            return $checkRoles($user, ['admin', 'accountant', 'sales_manager']);
        });
        
        Gate::define('delete-invoice-adjustments', function (User $user) use ($checkRoles) {
            return $checkRoles($user, ['admin', 'superadmin', 'finance_manager']);
        });
        
        Gate::define('manage-payment-methods', function (User $user) use ($checkRoles) {
            return $checkRoles($user, ['admin', 'superadmin', 'it_support']);
        });

        Gate::define('manage-payment-clearance', function (User $user) use ($checkRoles) {
            return $checkRoles($user, ['admin', 'finance', 'accountant']);
        });
    }    
}