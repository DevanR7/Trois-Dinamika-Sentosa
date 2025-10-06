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

        // === BUAT SEMUA PERMISSION YANG DIBUTUHKAN ===
        Permission::create(['name' => 'manage-suppliers']);
        Permission::create(['name' => 'manage-purchases']);
        Permission::create(['name' => 'manage-clients']);
        Permission::create(['name' => 'manage-invoices']);
        Permission::create(['name' => 'view-user-management']);
        Permission::create(['name' => 'manage-settings']); // Untuk Pajak & Satuan
        
        // === BUAT ROLES ===
        $adminRole = Role::create(['name' => 'admin']);
        $salesRole = Role::create(['name' => 'sales']);
        $manajemenRole = Role::create(['name' => 'manajemen']);
        $kasirRole = Role::create(['name' => 'kasir']);

        // === BERIKAN PERMISSION KE ROLE ADMIN ===
        // Admin bisa melakukan semuanya
        $adminRole->givePermissionTo(Permission::all());

        // === BERIKAN PERMISSION KE ROLE LAIN (CONTOH) ===
        $salesRole->givePermissionTo([
            'manage-clients',
            'manage-invoices',
        ]);
        
        // Manajemen mungkin hanya bisa melihat laporan (bisa dibuat permission-nya nanti)
        // $manajemenRole->givePermissionTo('view-reports');
    }
}