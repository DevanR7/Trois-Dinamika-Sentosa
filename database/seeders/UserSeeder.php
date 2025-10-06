<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Membuat User Admin
        $admin = User::create([
            'full_name'  => 'Admin Utama',
            'username'   => 'admin',
            'email'      => 'admin@example.com',
            'password'   => Hash::make('password'), // Ganti dengan password yang aman
            'sales_code' => null, // Admin tidak punya kode sales
        ]);
        // Memberikan role 'admin' ke user ini
        $admin->assignRole('admin');


        // 2. Membuat User Sales
        $sales = User::create([
            'full_name'  => 'Sales Lapangan',
            'username'   => 'sales1',
            'email'      => 'sales1@example.com',
            'password'   => Hash::make('password'),
            'sales_code' => 'SL01', // Contoh kode sales
        ]);
        // Memberikan role 'sales' ke user ini
        $sales->assignRole('sales');

        
        // 3. Membuat User Manajemen (Contoh)
        $manajemen = User::create([
            'full_name'  => 'Manajemen Kantor',
            'username'   => 'manajemen',
            'email'      => 'manajemen@example.com',
            'password'   => Hash::make('password'),
            'sales_code' => null,
        ]);
        // Anda bisa memberikan role 'manajemen' jika sudah dibuat di RoleAndPermissionSeeder
        // $manajemen->assignRole('manajemen');
    }
}