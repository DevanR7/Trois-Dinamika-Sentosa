<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            [
                'supplier_id' => 1,
                'product_code' => 'P-001',
                'product_name' => 'Palu Kambing 10 OZ',
                'description' => 'Palu kambing gagang fiber.',
                'purchase_price' => 400000.00,
                'selling_price' => 55000.00,
                'stock_quantity' => 100,
                'unit_id' => 1, // <-- PERUBAHAN DI SINI (Asumsi 'pcs' adalah id=1)
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'P-002',
                'product_name' => 'Palu Kambing 20 OZ',
                'description' => 'Palu kambing gagang fiber.',
                'purchase_price' => 300000.00,
                'selling_price' => 55000.00,
                'stock_quantity' => 100,
                'unit_id' => 1, // <-- PERUBAHAN DI SINI (Asumsi 'pcs' adalah id=1)
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'P-003',
                'product_name' => 'Palu Kambing 30 OZ',
                'description' => 'Palu kambing gagang fiber.',
                'purchase_price' => 144000.00,
                'selling_price' => 55000.00,
                'stock_quantity' => 100,
                'unit_id' => 1, // <-- PERUBAHAN DI SINI (Asumsi 'pcs' adalah id=1)
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'P-004',
                'product_name' => 'Palu Kambing 40 OZ',
                'description' => 'Palu kambing gagang fiber.',
                'purchase_price' => 158000.00,
                'selling_price' => 55000.00,
                'stock_quantity' => 100,
                'unit_id' => 1, // <-- PERUBAHAN DI SINI (Asumsi 'pcs' adalah id=1)
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'P-005',
                'product_name' => 'Palu Kambing 50 OZ',
                'description' => 'Palu kambing gagang fiber.',
                'purchase_price' => 174000.00,
                'selling_price' => 55000.00,
                'stock_quantity' => 100,
                'unit_id' => 1, // <-- PERUBAHAN DI SINI (Asumsi 'pcs' adalah id=1)
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'P-006',
                'product_name' => 'Palu Kambing 60 OZ',
                'description' => 'Palu kambing gagang fiber.',
                'purchase_price' => 120000.00,
                'selling_price' => 55000.00,
                'stock_quantity' => 100,
                'unit_id' => 1, // <-- PERUBAHAN DI SINI (Asumsi 'pcs' adalah id=1)
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'P-008',
                'product_name' => 'Palu Kambing 80 OZ',
                'description' => 'Palu kambing gagang fiber.',
                'purchase_price' => 80000.00,
                'selling_price' => 55000.00,
                'stock_quantity' => 100,
                'unit_id' => 1, // <-- PERUBAHAN DI SINI (Asumsi 'pcs' adalah id=1)
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'P-009',
                'product_name' => 'Palu Kambing 90 OZ',
                'description' => 'Palu kambing gagang fiber.',
                'purchase_price' => 100000.00,
                'selling_price' => 55000.00,
                'stock_quantity' => 100,
                'unit_id' => 1, // <-- PERUBAHAN DI SINI (Asumsi 'pcs' adalah id=1)
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'P-0010',
                'product_name' => 'Palu Kambing 100 OZ',
                'description' => 'Palu kambing gagang fiber.',
                'purchase_price' => 88000.00,
                'selling_price' => 55000.00,
                'stock_quantity' => 100,
                'unit_id' => 1, // <-- PERUBAHAN DI SINI (Asumsi 'pcs' adalah id=1)
                'created_at' => now(),
                'updated_at' => now(),

                ],
            
                [
                'supplier_id' => 1,
                'product_code' => 'P-0011',
                'product_name' => 'Palu Kambing 110 OZ',
                'description' => 'Palu kambing gagang fiber.',
                'purchase_price' => 16000.00,
                'selling_price' => 55000.00,
                'stock_quantity' => 100,
                'unit_id' => 1, // <-- PERUBAHAN DI SINI (Asumsi 'pcs' adalah id=1)
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'P-0012',
                'product_name' => 'Palu Kambing 120 OZ',
                'description' => 'Palu kambing gagang fiber.',
                'purchase_price' => 20000.00,
                'selling_price' => 55000.00,
                'stock_quantity' => 100,
                'unit_id' => 1, // <-- PERUBAHAN DI SINI (Asumsi 'pcs' adalah id=1)
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            [
                'supplier_id' => 1,
                'product_code' => 'G-001',
                'product_name' => 'Gergaji Kayu 18 inch',
                'description' => 'Gergaji kayu tajam dan awet.',
                'purchase_price' => 45000.00,
                'selling_price' => 70000.00,
                'stock_quantity' => 75,
                'unit_id' => 1, // <-- PERUBAHAN DI SINI (Asumsi 'pcs' adalah id=1)
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 2,
                'product_code' => 'B-001',
                'product_name' => 'Baut Mur 12mm (Box)',
                'description' => 'Isi 100 set per box.',
                'purchase_price' => 25000.00,
                'selling_price' => 40000.00,
                'stock_quantity' => 250,
                'unit_id' => 2, // <-- PERUBAHAN DI SINI (Asumsi 'box' adalah id=2)
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}