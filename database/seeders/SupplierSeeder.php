<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Saya menambahkan data NPWP & Bank agar lebih realistis
        // Dan menambahkan Supplier ke-3 (BULL) agar cocok dengan ProductSeeder
        
        DB::table('suppliers')->insert([
            [
                // Supplier ID: 1
                'supplier_name' => 'PT. Pemasok Jaya',
                'person_in_charge' => 'Bapak Budi',
                'phone_number' => '081234567890',
                'address' => 'Jalan Industri No. 1, Jakarta',
                'npwp' => '01.234.567.8-111.000',
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                // Supplier ID: 2
                'supplier_name' => 'CV. Mitra Kencana',
                'person_in_charge' => 'Ibu Siti',
                'phone_number' => '081298765432',
                'address' => 'Jalan Dagang No. 2, Surabaya',
                'npwp' => '02.345.678.9-222.000',
                'bank_name' => 'Mandiri',
                'account_number' => '0987654321',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                // Supplier ID: 3 (Penting untuk Produk BULL)
                'supplier_name' => 'BULL Central Distributor',
                'person_in_charge' => 'Budi Santoso (Sales Pusat)',
                'phone_number' => '021-555-9999',
                'address' => 'Jl. Teknik Industri No. 88, Jakarta',
                'npwp' => '03.456.789.0-333.000',
                'bank_name' => 'BRI',
                'account_number' => '1122334455',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}