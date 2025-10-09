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
use App\Models\Supplier;
use App\Policies\SupplierPolicy;
use App\Models\SalesInvoice;
use App\Policies\SalesInvoicePolicy;
use App\Models\SalesReturn;
use App\Policies\SalesReturnPolicy;
use App\Models\PurchaseReturn;
use App\Policies\PurchaseReturnPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        SalesOrder::class => SalesOrderPolicy::class,
        PurchaseOrder::class => PurchaseOrderPolicy::class,
        Supplier::class => SupplierPolicy::class,
        Product::class => ProductPolicy::class,
        User::class => SupplierPolicy::class, 
        SalesInvoice::class => SalesInvoicePolicy::class,
        SalesReturn::class => SalesReturnPolicy::class,
        PurchaseReturn::class => PurchaseReturnPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
    
    }  
    
}