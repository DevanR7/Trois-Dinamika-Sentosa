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
        // Saya menggunakan now() di luar loop agar waktu insert seragam
        $now = now();

        DB::table('products')->insert([
            [
                'supplier_id' => 1,
                'product_code' => 'BL-001',
                'product_name' => 'BULL ARMATURE FOR MT870/871/M8700/01 (30)',
                'description' => 'Armature untuk mesin MT870/871/M8700.',
                'purchase_price' => 400000.00,
                'selling_price' => 400000.00,
                'stock_quantity' => 10,
                'unit_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'BL-002',
                'product_name' => 'BULL ARMATURE FOR MT602/603 (30)',
                'description' => 'Armature untuk mesin MT602/603.',
                'purchase_price' => 300000.00,
                'selling_price' => 300000.00,
                'stock_quantity' => 10,
                'unit_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'supplier_id' => 2,
                'product_code' => 'BL-003',
                'product_name' => 'BULL CSB/NEW-G SUPERTHIN 7"X24Tx20 (50)',
                'description' => 'Circular Saw Blade Superthin 7 inch 24T.',
                'purchase_price' => 144000.00,
                'selling_price' => 144000.00,
                'stock_quantity' => 50,
                'unit_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'supplier_id' => 2,
                'product_code' => 'BL-004',
                'product_name' => 'BULL CSB/NEW-G SUPERTHIN 7"X40Tx20 (50)',
                'description' => 'Circular Saw Blade Superthin 7 inch 40T.',
                'purchase_price' => 158000.00,
                'selling_price' => 158000.00,
                'stock_quantity' => 50,
                'unit_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'supplier_id' => 2,
                'product_code' => 'BL-005',
                'product_name' => 'BULL CSB/NEW-G SUPERTHIN 7"X60T (50)',
                'description' => 'Circular Saw Blade Superthin 7 inch 60T.',
                'purchase_price' => 174000.00,
                'selling_price' => 174000.00,
                'stock_quantity' => 50,
                'unit_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'supplier_id' => 3,
                'product_code' => 'BL-006',
                'product_name' => 'BLCL02-601 SIGMAT CARBON DGTL6" HTM(100)',
                'description' => 'Sigmat Carbon Digital 6 Inch Hitam.',
                'purchase_price' => 120000.00,
                'selling_price' => 120000.00,
                'stock_quantity' => 10,
                'unit_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'supplier_id' => 3,
                'product_code' => 'BL-007',
                'product_name' => 'BULL CAPASITOR 100MF (6/180)',
                'description' => 'Kapasitor ukuran 100MF.',
                'purchase_price' => 80000.00,
                'selling_price' => 80000.00,
                'stock_quantity' => 20,
                'unit_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'supplier_id' => 3,
                'product_code' => 'BL-008',
                'product_name' => 'BULL CAPASITOR 200MF (6/180)',
                'description' => 'Kapasitor ukuran 200MF.',
                'purchase_price' => 100000.00,
                'selling_price' => 100000.00,
                'stock_quantity' => 20,
                'unit_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'BL-009',
                'product_name' => 'BULL GLASS TRIMMER ANTI PECAH (100)',
                'description' => 'Glass trimmer material anti pecah.',
                'purchase_price' => 88000.00,
                'selling_price' => 88000.00,
                'stock_quantity' => 10,
                'unit_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'BL-010',
                'product_name' => 'BULL CB BLISTER AUTO CB411A',
                'description' => 'Carbon Brush Blister Auto CB411A.',
                'purchase_price' => 16000.00,
                'selling_price' => 16000.00,
                'stock_quantity' => 100,
                'unit_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'BL-011',
                'product_name' => 'BULL CB BLISTER AUTO GCO2000/200/20-180',
                'description' => 'Carbon Brush Blister Auto tipe GCO2000.',
                'purchase_price' => 20000.00,
                'selling_price' => 20000.00,
                'stock_quantity' => 100,
                'unit_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}