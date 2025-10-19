<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tax; // Pastikan Anda sudah memiliki model Tax

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Gunakan updateOrCreate untuk menghindari duplikat jika seeder dijalankan lagi
        // Pengecekan akan dilakukan berdasarkan kolom 'name'
        
        Tax::updateOrCreate(
            ['name' => 'PPN 11%'], // Kolom untuk dicek
            [
                'rate' => 11.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        Tax::updateOrCreate(
            ['name' => 'Tanpa Pajak'], // Kolom untuk dicek
            [
                'rate' => 0.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        Tax::updateOrCreate(
            ['name' => 'PPh 23'], // Contoh pajak lain
            [
                'rate' => 2.00,
                'is_active' => false, // Dibuat tidak aktif sebagai contoh
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}