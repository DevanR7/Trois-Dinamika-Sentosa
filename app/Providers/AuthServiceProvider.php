<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

// DAFTAR MODEL
use App\Models\User;
use App\Models\Product;
use App\Models\Order; // ✅ BERUBAH: Menggunakan model Order
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;
use App\Models\Announcement;

// DAFTAR POLICY
use App\Policies\ProductPolicy;
use App\Policies\OrderPolicy; // ✅ BERUBAH: Menggunakan policy OrderPolicy
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
        // ✅ PERBAIKAN 1: Mapping yang benar
        Order::class => OrderPolicy::class, 
        PurchaseOrder::class => PurchaseOrderPolicy::class,
        Supplier::class => SupplierPolicy::class,
        Product::class => ProductPolicy::class,
        SalesInvoice::class => SalesInvoicePolicy::class,
        SalesReturn::class => SalesReturnPolicy::class,
        PurchaseReturn::class => PurchaseReturnPolicy::class,
        Announcement::class => AnnouncementPolicy::class,

        // ❗️ PERBAIKAN 3: Baris ini dihapus karena salah
        // User::class => SupplierPolicy::class, 
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }   
}