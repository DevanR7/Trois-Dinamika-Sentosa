<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin
        $superadmin = User::create([
            'full_name'  => 'Trois Dinamika Sentosa',
            'username'   => 'superadmin',
            'email'      => 'troisdinamikasentosa@gmail.com',
            'password'   => Hash::make('password'),
            'sales_code' => null,
            'is_approved' => true,
            'email_verified_at' => now(), // [REVISI]
        ]);
        $superadmin->assignRole('superadmin');

        // 2. Admin Operasional
        $admin = User::create([
            'full_name'  => 'Admin Utama',
            'username'   => 'admin',
            'email'      => 'admin@example.com',
            'password'   => Hash::make('password'),
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // 3. Manager
        $manager = User::create([
            'full_name'  => 'Manajer Umum',
            'username'   => 'manager',
            'email'      => 'manager@example.com',
            'password'   => Hash::make('password'),
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('manager');

        // 4. Staff Finance
        $finance = User::create([
            'full_name'  => 'Staff Keuangan',
            'username'   => 'finance',
            'email'      => 'finance@example.com',
            'password'   => Hash::make('password'),
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);
        $finance->assignRole('finance');

        // 5. Staff Gudang
        $gudang = User::create([
            'full_name'  => 'Staff Gudang',
            'username'   => 'gudang',
            'email'      => 'gudang@example.com',
            'password'   => Hash::make('password'),
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);
        $gudang->assignRole('inventory');

        // 6. Sales
        $sales = User::create([
            'full_name'  => 'Sales Lapangan',
            'username'   => 'sales1',
            'email'      => 'sales1@example.com',
            'password'   => Hash::make('password'),
            'sales_code' => 'SL01',
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);
        $sales->assignRole('sales');
        
        // 7. Akuntan
        $accountant = User::create([
            'full_name'  => 'Akuntan Perusahaan',
            'username'   => 'akuntan',
            'email'      => 'akuntan@example.com',
            'password'   => Hash::make('password'),
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);
        $accountant->assignRole('accountant');
    }
}