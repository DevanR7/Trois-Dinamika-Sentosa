<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset cache permission
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // =====================================================================
        // 2. DAFTAR SEMUA PERMISSION
        // =====================================================================
        $permissions = [
            // --- Dashboard ---
            'view-dashboard',
            'view-dashboard-financials',
            'view-dashboard-inventory',

            // --- Master Data ---
            'view-products', 'create-products', 'edit-products', 'delete-products',
            'view-clients', 'create-clients', 'edit-clients', 'delete-clients',
            'view-suppliers', 'create-suppliers', 'edit-suppliers', 'delete-suppliers', 'restore-suppliers',

            // --- Sales Module ---
            'view-sales-orders', 'create-sales-orders', 'edit-sales-orders', 'delete-sales-orders',
            'review-client-orders', 'approve-client-orders', 'reject-client-orders',
            'review-order-change-requests',
            
            // Invoices
            'view-invoices', 'create-invoices', 'edit-invoices', 'delete-invoices', 'cancel-invoices',
            'pay-invoices', // Permission lama (opsional)
            'create-invoice-adjustments', 'delete-invoice-adjustments',
            
            // Sales Returns
            'view-sales-returns', 'create-sales-returns', 'delete-sales-returns',

            // --- Purchasing Module ---
            'view-purchase-orders', 'create-purchase-orders', 'edit-purchase-orders', 'delete-purchase-orders', 'cancel-purchase-orders',
            'receive-purchase-orders', 
            'pay-purchase-orders',
            'create-purchase-adjustments',
            
            // Purchase Returns
            'view-purchase-returns', 'create-purchase-returns', 'delete-purchase-returns',
            
            // Inventory Ops
            'manage-stock-opnames',

            // --- Finance Module ---
            'view-expenses', 'manage-expenses',
            'view-loans', 'manage-loans',
            'view-fixed-assets', 'manage-fixed-assets',
            'manage-bank-accounts', 'manage-payment-methods',
            
            // Payments Handling (Revisi Middleware)
            'create-payments',             // Hak untuk input pembayaran (Sales/Finance)
            'manage-payment-clearance',    // Hak untuk approve/reject pembayaran (Finance)
            'create-bulk-payments',        // Terima piutang massal
            'create-bulk-purchase-payments', // Bayar hutang massal
            'review-bulk-payments',        // Approval bulk

            // --- Accounting Module ---
            'view-reports',
            'manage-manual-journals', 

            // --- System ---
            'manage-users', 
            'manage-roles', 
            'manage-settings', 
            'manage-announcements', 
            'manage-data-migration',
        ];

        // Buat Permission di Database
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission]);
        }

        // =====================================================================
        // 3. SETUP ROLES
        // =====================================================================
        $superadmin = Role::updateOrCreate(['name' => 'superadmin']); 
        $admin      = Role::updateOrCreate(['name' => 'admin']);      
        $manager    = Role::updateOrCreate(['name' => 'manager']);    
        $finance    = Role::updateOrCreate(['name' => 'finance']);    
        $sales      = Role::updateOrCreate(['name' => 'sales']);      
        $inventory  = Role::updateOrCreate(['name' => 'inventory']);  
        $hr         = Role::updateOrCreate(['name' => 'hr']);         
        $accountant = Role::updateOrCreate(['name' => 'accountant']); 

        // =====================================================================
        // 4. ASSIGN PERMISSION KE ROLE
        // =====================================================================

        // SUPERADMIN & ADMIN:
        // Di AuthServiceProvider sudah ada Gate::before untuk bypass, 
        // tapi kita sync semua permission untuk kelengkapan data.
        $allPermissions = Permission::all();
        $superadmin->syncPermissions($allPermissions);
        $admin->syncPermissions($allPermissions);

        // MANAGER:
        // Hampir semua akses
        $manager->syncPermissions($allPermissions);

        // FINANCE (Keuangan & Kasir):
        $finance->syncPermissions([
            'view-dashboard', 'view-dashboard-financials',
            'view-invoices', 'create-invoices', 'edit-invoices', 'delete-invoices', 'cancel-invoices', 
            'pay-invoices', 
            'create-payments',              // Input Bayar
            'manage-payment-clearance',     // Approve Bayar
            'create-bulk-payments', 'create-bulk-purchase-payments', 'review-bulk-payments',
            'view-purchase-orders', 'pay-purchase-orders',
            'view-expenses', 'manage-expenses',
            'view-loans', 'manage-loans',
            'view-fixed-assets', 'manage-fixed-assets',
            'manage-bank-accounts', 'manage-payment-methods',
            'create-invoice-adjustments', 
            'view-reports', 
            'view-suppliers', 'view-clients', 'view-products', 
            'manage-manual-journals'
        ]);

        // SALES (Tim Penjualan):
        $sales->syncPermissions([
            'view-dashboard',
            'view-clients', 'create-clients', 'edit-clients',
            'view-products',
            'view-sales-orders', 'create-sales-orders', 'edit-sales-orders',
            'review-client-orders', 'approve-client-orders', 'reject-client-orders', 
            'review-order-change-requests',
            'view-invoices', 'create-invoices', 
            'create-payments', // Sales boleh input pembayaran (misal terima tunai), tapi statusnya pending verification
            'view-sales-returns', 'create-sales-returns',
            'manage-announcements',
        ]);

        // INVENTORY (Tim Gudang):
        $inventory->syncPermissions([
            'view-dashboard', 'view-dashboard-inventory',
            'view-products', 'create-products', 'edit-products',
            'view-suppliers', 'create-suppliers', 'edit-suppliers',
            'view-purchase-orders', 'create-purchase-orders', 'edit-purchase-orders',
            'receive-purchase-orders', // Terima Barang
            'view-purchase-returns', 'create-purchase-returns',
            'manage-stock-opnames',
        ]);

        // ACCOUNTANT (Pencatatan & Laporan):
        $accountant->syncPermissions([
            'view-dashboard', 'view-dashboard-financials', 
            'view-reports',
            'manage-manual-journals', 
            'view-invoices', 'view-purchase-orders', 'view-expenses',
            'view-fixed-assets', 'view-loans', 
            'create-invoice-adjustments', 'create-purchase-adjustments',
            'manage-stock-opnames',
        ]);

        // HR (Manajemen User):
        $hr->syncPermissions([
            'view-dashboard',
            'manage-users',
            'manage-announcements'
        ]);
    }
}