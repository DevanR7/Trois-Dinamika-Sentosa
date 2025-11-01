<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tax; // Pastikan Anda sudah memiliki model Tax

class TaxSeeder extends Seeder
{
    /**
     * Jalankan seeder untuk tabel taxes.
     */
    public function run(): void
    {
        // PPN 12%
        Tax::updateOrCreate(
            ['name' => 'PPN 12%'],
            [
                'rate' => 12.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Tanpa Pajak
        Tax::updateOrCreate(
            ['name' => 'Tanpa Pajak'],
            [
                'rate' => 0.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // PPh 23 (contoh pajak lain)
        Tax::updateOrCreate(
            ['name' => 'PPh 23'],
            [
                'rate' => 2.00,
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}