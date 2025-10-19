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
        // Format: aksi-modul

        // Dashboard
        Permission::updateOrCreate(['name' => 'view-dashboard']); // Gunakan updateOrCreate

        // Suppliers
        Permission::updateOrCreate(['name' => 'view-suppliers']);
        Permission::updateOrCreate(['name' => 'create-suppliers']);
        Permission::updateOrCreate(['name' => 'edit-suppliers']);
        Permission::updateOrCreate(['name' => 'delete-suppliers']);

        // Purchase Orders
        Permission::updateOrCreate(['name' => 'view-purchase-orders']);
        Permission::updateOrCreate(['name' => 'create-purchase-orders']);
        Permission::updateOrCreate(['name' => 'edit-purchase-orders']);
        Permission::updateOrCreate(['name' => 'cancel-purchase-orders']);
        Permission::updateOrCreate(['name' => 'receive-purchase-orders']); // Terima barang
        Permission::updateOrCreate(['name' => 'pay-purchase-orders']);   // Pembayaran PO

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

        // Sales Orders (Now Orders)
        Permission::updateOrCreate(['name' => 'view-sales-orders']); // Keep name for consistency or rename
        Permission::updateOrCreate(['name' => 'create-sales-orders']);
        Permission::updateOrCreate(['name' => 'edit-sales-orders']);
        Permission::updateOrCreate(['name' => 'delete-sales-orders']);

        // ✅ TAMBAHKAN PERMISSION BARU UNTUK REVIEW REQUEST
        Permission::updateOrCreate(['name' => 'review-order-change-requests']);

        // Invoices
        Permission::updateOrCreate(['name' => 'view-invoices']);
        Permission::updateOrCreate(['name' => 'create-invoices']);
        Permission::updateOrCreate(['name' => 'edit-invoices']);
        Permission::updateOrCreate(['name' => 'delete-invoices']);
        Permission::updateOrCreate(['name' => 'cancel-invoices']);
        Permission::updateOrCreate(['name' => 'pay-invoices']);

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

        // System (Simplify or keep detailed as needed)
        Permission::updateOrCreate(['name' => 'manage-users']);
        Permission::updateOrCreate(['name' => 'manage-roles']);
        Permission::updateOrCreate(['name' => 'manage-settings']); // Pajak, Satuan, Perusahaan


        // === BUAT ROLES (Gunakan updateOrCreate agar aman dijalankan ulang) ===
        $adminRole = Role::updateOrCreate(['name' => 'admin']);
        $manajemenRole = Role::updateOrCreate(['name' => 'manajemen']);
        $kasirRole = Role::updateOrCreate(['name' => 'kasir']);
        $salesRole = Role::updateOrCreate(['name' => 'sales']);

        // === BERIKAN PERMISSION KE SETIAP ROLE ===
        // JANGAN GUNAKAN Permission::all() lagi jika seeder dijalankan ulang
        // Lebih baik sync permission yang spesifik

        // ADMIN & MANAJEMEN: Beri semua permission yang *didefinisikan di atas*
        // (Permission baru akan otomatis masuk)
        $allDefinedPermissions = Permission::pluck('name'); // Ambil nama semua permission yg dibuat
        $adminRole->syncPermissions($allDefinedPermissions);
        $manajemenRole->syncPermissions($allDefinedPermissions);


        // KASIR (Gunakan syncPermissions)
        $kasirRole->syncPermissions([
            'view-dashboard',
            'view-suppliers', 'create-suppliers', 'edit-suppliers', 'delete-suppliers',
            'view-purchase-orders', 'create-purchase-orders', 'edit-purchase-orders', 'receive-purchase-orders',
            'view-products', 'create-products', 'edit-products', 'delete-products',
            'view-clients', 'create-clients', 'edit-clients', 'delete-clients',
            'view-sales-orders', 'create-sales-orders', 'edit-sales-orders', 'delete-sales-orders',
            'view-invoices', 'create-invoices', 'edit-invoices', 'delete-invoices', 'pay-invoices',
            'manage-settings', // Hanya akses bagian Pajak & Satuan (perlu diatur di controller/view)
            'view-purchase-returns', // Hanya view retur beli
            'view-sales-returns', // Hanya view retur jual
        ]);

        // SALES (Gunakan syncPermissions)
        $salesRole->syncPermissions([
            'view-dashboard',
            'view-suppliers',
            'view-products',
            'view-clients', 'create-clients', 'edit-clients', 'delete-clients',
            'view-sales-orders', 'create-sales-orders', 'edit-sales-orders', 'delete-sales-orders',
             'view-invoices', // Sales hanya bisa view invoice terkait mereka (logic di controller)
        ]);
    }
}