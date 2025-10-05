<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('invoice_items')->insert([
            // Item untuk Invoice ID 1 (INV-2025-001)
            [
                'invoice_id' => 1,
                'product_id' => 1, // Palu Kambing
                'quantity' => 2,
                'price_per_unit' => 55000.00,
                'subtotal' => 110000.00,
            ],
            [
                'invoice_id' => 1,
                'product_id' => 2, // Gergaji Kayu
                'quantity' => 1,
                'price_per_unit' => 70000.00,
                'subtotal' => 70000.00,
            ],
            [
                'invoice_id' => 1,
                'product_id' => 3, // Baut Mur
                'quantity' => 1,
                'price_per_unit' => 15000.00, // Harga jual custom
                'subtotal' => 15000.00,
            ],

            // Item untuk Invoice ID 2 (INV-2025-002)
            [
                'invoice_id' => 2,
                'product_id' => 3, // Baut Mur
                'quantity' => 1,
                'price_per_unit' => 40000.00,
                'subtotal' => 40000.00,
            ],
        ]);
    }
}