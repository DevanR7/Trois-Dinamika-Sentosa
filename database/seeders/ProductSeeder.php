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
                'product_code' => 'PT-001',
                'product_name' => 'Bor Tangan Listrik 13mm',
                'description' => 'Bor listrik kecepatan tinggi untuk pekerjaan konstruksi.',
                'purchase_price' => 550000.00,
                'selling_price' => 550000.00,
                'stock_quantity' => 10,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 2,
                'product_code' => 'PT-002',
                'product_name' => 'Gerinda Tangan 4 Inch 680W',
                'description' => 'Gerinda serbaguna untuk potong dan amplas besi.',
                'purchase_price' => 620000.00,
                'selling_price' => 620000.00,
                'stock_quantity' => 10,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 3,
                'product_code' => 'PT-003',
                'product_name' => 'Mesin Potong Besi 14 Inch',
                'description' => 'Mesin potong dengan pisau baja kuat dan stabil.',
                'purchase_price' => 1250000.00,
                'selling_price' => 1250000.00,
                'stock_quantity' => 10,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'AB-001',
                'product_name' => 'Sekop Pasir Baja',
                'description' => 'Sekop baja kuat untuk pekerjaan bangunan.',
                'purchase_price' => 85000.00,
                'selling_price' => 85000.00,
                'stock_quantity' => 10,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'AB-002',
                'product_name' => 'Cangkul Baja 2 Kg',
                'description' => 'Cangkul kuat dengan gagang kayu jati.',
                'purchase_price' => 95000.00,
                'selling_price' => 95000.00,
                'stock_quantity' => 10,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 2,
                'product_code' => 'AB-003',
                'product_name' => 'Palu Besi 500 Gram',
                'description' => 'Palu besi dengan gagang kayu ergonomis.',
                'purchase_price' => 55000.00,
                'selling_price' => 55000.00,
                'stock_quantity' => 10,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 3,
                'product_code' => 'AB-004',
                'product_name' => 'Gergaji Kayu 18 Inch',
                'description' => 'Gergaji tajam untuk potongan halus dan rapi.',
                'purchase_price' => 48000.00,
                'selling_price' => 48000.00,
                'stock_quantity' => 10,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 2,
                'product_code' => 'BB-001',
                'product_name' => 'Pipa Besi Ø1 Inch (6 meter)',
                'description' => 'Pipa besi galvanis tahan karat dan kuat.',
                'purchase_price' => 240000.00,
                'selling_price' => 240000.00,
                'stock_quantity' => 10,
                'unit_id' => 2, // box atau batang
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 2,
                'product_code' => 'BB-002',
                'product_name' => 'Plat Besi 3mm (1x2 meter)',
                'description' => 'Plat besi kualitas tinggi untuk fabrikasi.',
                'purchase_price' => 310000.00,
                'selling_price' => 310000.00,
                'stock_quantity' => 10,
                'unit_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 3,
                'product_code' => 'BB-003',
                'product_name' => 'Besi Beton 10mm',
                'description' => 'Batang besi beton polos 10mm, panjang 12 meter.',
                'purchase_price' => 120000.00,
                'selling_price' => 120000.00,
                'stock_quantity' => 10,
                'unit_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 1,
                'product_code' => 'PT-004',
                'product_name' => 'Mesin Las Listrik 450A',
                'description' => 'Mesin las inverter untuk pekerjaan besi profesional.',
                'purchase_price' => 980000.00,
                'selling_price' => 980000.00,
                'stock_quantity' => 10,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'supplier_id' => 3,
                'product_code' => 'PT-005',
                'product_name' => 'Sander Orbital 300W',
                'description' => 'Sander ringan untuk finishing kayu dan logam.',
                'purchase_price' => 430000.00,
                'selling_price' => 430000.00,
                'stock_quantity' => 10,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
