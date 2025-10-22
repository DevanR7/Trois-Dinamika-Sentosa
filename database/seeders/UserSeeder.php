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
        // 1. ✅ Membuat User Super Admin (BARU)
        $superadmin = User::create([
            'full_name'  => 'Trois Dinamika Sentosa', // Ganti namanya
            'username'   => 'superadmin', // Ganti username
            'email'      => 'troisdinamikasentosa@gmail.com', // <-- PAKAI EMAIL ASLI DI SINI
            'password'   => Hash::make('PasswordSuperAman123!'), // Password lokal (opsional)
            'sales_code' => null,
            'is_approved' => true,
        ]);
        // Memberikan role 'superadmin' ke user ini
        $superadmin->assignRole('superadmin');

        // 2. Membuat User Admin
        $admin = User::create([
            'full_name'  => 'Admin Utama',
            'username'   => 'admin',
            'email'      => 'admin@example.com',
            'password'   => Hash::make('password'), // Ganti dengan password yang aman
            'sales_code' => null, // Admin tidak punya kode sales
            'is_approved' => true, // <-- PENTING
        ]);
        // Memberikan role 'admin' ke user ini
        $admin->assignRole('admin');


        // 3. Membuat User Sales
        $sales = User::create([
            'full_name'  => 'Sales Lapangan',
            'username'   => 'sales1',
            'email'      => 'sales1@example.com',
            'password'   => Hash::make('password'),
            'sales_code' => 'SL01', // Contoh kode sales
            'is_approved' => true, // <-- PENTING (Asumsi user default langsung aktif)
        ]);
        // Memberikan role 'sales' ke user ini
        $sales->assignRole('sales');

        
        // 4. Membuat User Manajemen
        $manajemen = User::create([
            'full_name'  => 'Manajemen Kantor',
            'username'   => 'manajemen',
            'email'      => 'manajemen@example.com',
            'password'   => Hash::make('password'),
            'sales_code' => null,
            'is_approved' => true, // <-- PENTING (Asumsi user default langsung aktif)
        ]);
        // Memberikan role 'manajemen'
        $manajemen->assignRole('manajemen'); // <-- Saya uncomment ini agar role-nya ter-assign
    }
}