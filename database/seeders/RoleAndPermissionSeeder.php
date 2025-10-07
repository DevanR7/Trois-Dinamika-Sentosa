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
        Permission::create(['name' => 'view-dashboard']);

        // Suppliers
        Permission::create(['name' => 'view-suppliers']);
        Permission::create(['name' => 'create-suppliers']);
        Permission::create(['name' => 'edit-suppliers']);
        Permission::create(['name' => 'delete-suppliers']);

        // Purchase Orders
        Permission::create(['name' => 'view-purchase-orders']);
        Permission::create(['name' => 'create-purchase-orders']);
        Permission::create(['name' => 'edit-purchase-orders']);
        Permission::create(['name' => 'cancel-purchase-orders']);
        Permission::create(['name' => 'receive-purchase-orders']); // Terima barang
        Permission::create(['name' => 'pay-purchase-orders']);   // Pembayaran PO

        // Products
        Permission::create(['name' => 'view-products']);
        Permission::create(['name' => 'create-products']);
        Permission::create(['name' => 'edit-products']);
        Permission::create(['name' => 'delete-products']);

        // Clients
        Permission::create(['name' => 'view-clients']);
        Permission::create(['name' => 'create-clients']);
        Permission::create(['name' => 'edit-clients']);
        Permission::create(['name' => 'delete-clients']);

        // Sales Orders
        Permission::create(['name' => 'view-sales-orders']);
        Permission::create(['name' => 'create-sales-orders']);
        Permission::create(['name' => 'edit-sales-orders']);
        Permission::create(['name' => 'delete-sales-orders']);

        // Invoices
        Permission::create(['name' => 'view-invoices']);
        Permission::create(['name' => 'create-invoices']);
        Permission::create(['name' => 'edit-invoices']);
        Permission::create(['name' => 'delete-invoices']);
        Permission::create(['name' => 'cancel-invoices']);
        Permission::create(['name' => 'pay-invoices']);

        // System
        Permission::create(['name' => 'manage-users']);
        Permission::create(['name' => 'manage-roles']);
        Permission::create(['name' => 'manage-settings']); // Untuk Pajak & Satuan

        // === BUAT ROLES ===
        $adminRole = Role::create(['name' => 'admin']);
        $manajemenRole = Role::create(['name' => 'manajemen']);
        $kasirRole = Role::create(['name' => 'kasir']);
        $salesRole = Role::create(['name' => 'sales']);

        // === BERIKAN PERMISSION KE SETIAP ROLE ===
        
        // ADMIN & MANAJEMEN: Bisa melakukan segalanya
        $adminRole->givePermissionTo(Permission::all());
        $manajemenRole->givePermissionTo(Permission::all());

        // KASIR
        $kasirRole->givePermissionTo([
            'view-dashboard',
            'view-suppliers', 'create-suppliers', 'edit-suppliers', 'delete-suppliers',
            'view-purchase-orders', 'create-purchase-orders', 'edit-purchase-orders', 'receive-purchase-orders', // Tidak bisa bayar PO
            'view-products', 'create-products', 'edit-products', 'delete-products',
            'view-clients', 'create-clients', 'edit-clients', 'delete-clients',
            'view-sales-orders', 'create-sales-orders', 'edit-sales-orders', 'delete-sales-orders',
            'view-invoices', 'create-invoices', 'edit-invoices', 'delete-invoices', 'pay-invoices', // Bisa catat pembayaran invoice
            'manage-settings',
            'view-suppliers', 'create-suppliers', 'edit-suppliers', 'delete-suppliers', // Bisa atur pajak & satuan
        ]);

        // SALES
        $salesRole->givePermissionTo([
            'view-dashboard',
            'view-suppliers', // Hanya lihat supplier
            'view-products', // Hanya lihat produk
            'view-clients', 'create-clients', 'edit-clients', 'delete-clients', // Bisa kelola klien
            'view-sales-orders', 'create-sales-orders', 'edit-sales-orders', 'delete-sales-orders', // Bisa kelola SO
        ]);
    }
}