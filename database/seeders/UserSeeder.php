<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'full_name'  => 'Admin Utama',
                'username'   => 'admin',
                'email'      => 'admin@example.com', // ✅ tambahkan email
                'password'   => Hash::make('password'),
                'role'       => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name'  => 'Sales Lapangan',
                'username'   => 'sales1',
                'email'      => 'sales1@example.com', // ✅ tambahkan email
                'password'   => Hash::make('password'),
                'role'       => 'sales',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name'  => 'Manajemen Kantor',
                'username'   => 'manajemen',
                'email'      => 'manajemen@example.com', // ✅ tambahkan email
                'password'   => Hash::make('password'),
                'role'       => 'manajemen',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}