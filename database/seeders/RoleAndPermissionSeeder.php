<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache roles dan permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // === BUAT SEMUA PERMISSION YANG DIBUTUHKAN SECARA DETAIL ===

        // Dashboard
        Permission::updateOrCreate(['name' => 'view-dashboard']);

        // Suppliers
        Permission::updateOrCreate(['name' => 'view-suppliers']);
        Permission::updateOrCreate(['name' => 'create-suppliers']);
        Permission::updateOrCreate(['name' => 'edit-suppliers']);
        Permission::updateOrCreate(['name' => 'delete-suppliers']);
        Permission::updateOrCreate(['name' => 'restore-suppliers']);

        // Purchase Orders
        Permission::updateOrCreate(['name' => 'view-purchase-orders']);
        Permission::updateOrCreate(['name' => 'create-purchase-orders']);
        Permission::updateOrCreate(['name' => 'edit-purchase-orders']);
        Permission::updateOrCreate(['name' => 'cancel-purchase-orders']);
        Permission::updateOrCreate(['name' => 'receive-purchase-orders']);
        Permission::updateOrCreate(['name' => 'pay-purchase-orders']);
        Permission::updateOrCreate(['name' => 'create-batch-purchase-payments']);
        
        // ===========================================
        // ✅ TAMBAHKAN PERMISSION PO ADJUSTMENT DI SINI
        // ===========================================
        Permission::updateOrCreate(['name' => 'create-purchase-adjustments']);


        // Products
        Permission::updateOrCreate(['name' => 'view-products']);
        Permission::updateOrCreate(['name' => 'create-products']);
        Permission::updateOrCreate(['name' => 'edit-products']);
        Permission::updateOrCreate(['name' => 'delete-products']);

        // Clients
        Permission::updateOrCreate(['name' => 'view-clients']);
        Permission::updateOrCreate(['name' => 'create-clients']);
        Permission::updateOrCreate(['name' => 'edit-clients']);
        Permission::updateOrCreate(['name' => 'delete-clients']);

        // Sales Orders (Internal Input)
        Permission::updateOrCreate(['name' => 'view-sales-orders']);
        Permission::updateOrCreate(['name' => 'create-sales-orders']);
        Permission::updateOrCreate(['name' => 'edit-sales-orders']);
        Permission::updateOrCreate(['name' => 'delete-sales-orders']);

        

        // Order Change Requests (From Client for Sales Orders)
        Permission::updateOrCreate(['name' => 'review-order-change-requests']);

        // Review Pesanan Klien
        Permission::updateOrCreate(['name' => 'review-client-orders']); // Untuk melihat daftar & detail
        Permission::updateOrCreate(['name' => 'approve-client-orders']); // Untuk menyetujui
        Permission::updateOrCreate(['name' => 'reject-client-orders']); // Untuk menolak

        // Invoices
        Permission::updateOrCreate(['name' => 'view-invoices']);
        Permission::updateOrCreate(['name' => 'create-invoices']);
        Permission::updateOrCreate(['name' => 'edit-invoices']);
        Permission::updateOrCreate(['name' => 'delete-invoices']);
        Permission::updateOrCreate(['name' => 'cancel-invoices']);
        Permission::updateOrCreate(['name' => 'pay-invoices']);
        Permission::updateOrCreate(['name' => 'create-batch-payments']);
        
        // ===========================================
        // ✅ PINDAHKAN INVOICE ADJUSTMENT KE SINI
        // ===========================================
        Permission::updateOrCreate(['name' => 'create-invoice-adjustments']);
        Permission::updateOrCreate(['name' => 'review-batch-payments']);


        // Sales Returns
        Permission::updateOrCreate(['name' => 'view-sales-returns']);
        Permission::updateOrCreate(['name' => 'create-sales-returns']);
        Permission::updateOrCreate(['name' => 'delete-sales-returns']);

        // Purchase Returns
        Permission::updateOrCreate(['name' => 'view-purchase-returns']);
        Permission::updateOrCreate(['name' => 'create-purchase-returns']);
        Permission::updateOrCreate(['name' => 'delete-purchase-returns']);

        // Reports
        Permission::updateOrCreate(['name' => 'view-reports']);

        // System
        Permission::updateOrCreate(['name' => 'manage-users']);
        Permission::updateOrCreate(['name' => 'manage-roles']);
        Permission::updateOrCreate(['name' => 'manage-settings']);

        // Announcements
        Permission::updateOrCreate(['name' => 'manage-announcements']);

        // === BUAT ROLES ===
        $superadminRole = Role::updateOrCreate(['name' => 'superadmin']);
        $adminRole = Role::updateOrCreate(['name' => 'admin']);
        $manajemenRole = Role::updateOrCreate(['name' => 'manajemen']);
        $kasirRole = Role::updateOrCreate(['name' => 'kasir']);
        $salesRole = Role::updateOrCreate(['name' => 'sales']);

        // === BERIKAN PERMISSION KE SETIAP ROLE ===

        // ADMIN & MANAJEMEN: Beri semua permission yang didefinisikan
        // $allDefinedPermissions akan OTOMATIS mengambil 2 permission baru yang kita tambahkan di atas
        $allDefinedPermissions = Permission::pluck('name');
        $superadminRole->syncPermissions($allDefinedPermissions);
        $adminRole->syncPermissions($allDefinedPermissions);
        $manajemenRole->syncPermissions($allDefinedPermissions);

        // KASIR
        // ===========================================
        // ✅ TAMBAHKAN 2 PERMISSION BARU KE KASIR
        // ===========================================
        $kasirRole->syncPermissions([
            'view-dashboard',
            'view-suppliers', 'create-suppliers', 'edit-suppliers', 'delete-suppliers',
            'view-purchase-orders', 'create-purchase-orders', 'edit-purchase-orders', 'receive-purchase-orders',
            'view-products', 'create-products', 'edit-products', 'delete-products',
            'view-clients', 'create-clients', 'edit-clients', 'delete-clients',
            'view-sales-orders', 'create-sales-orders', 'edit-sales-orders', 'delete-sales-orders',
            'view-invoices', 'create-invoices', 'edit-invoices', 'delete-invoices', 'pay-invoices',
            'manage-settings','view-purchase-returns','view-sales-returns','create-batch-purchase-payments',
            
            'create-invoice-adjustments', // <-- TAMBAHKAN INI
            'create-purchase-adjustments' // <-- TAMBAHKAN INI
        ]);

        // SALES
        $salesRole->syncPermissions([
            'view-dashboard',
            'view-suppliers',
            'view-products',
            'view-clients', 'create-clients', 'edit-clients', 'delete-clients',
            'view-sales-orders', 'create-sales-orders', 'edit-sales-orders', 'delete-sales-orders',
             'view-invoices',
        ]);
    }
}