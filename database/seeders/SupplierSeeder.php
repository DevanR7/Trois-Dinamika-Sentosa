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
         DB::table('suppliers')->insert([
            [
                'supplier_name' => 'PT. Pemasok Jaya',
                'person_in_charge' => 'Bapak Budi',
                'phone_number' => '081234567890',
                'address' => 'Jalan Industri No. 1, Jakarta',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_name' => 'CV. Mitra Kencana',
                'person_in_charge' => 'Ibu Siti',
                'phone_number' => '081298765432',
                'address' => 'Jalan Dagang No. 2, Surabaya',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
