<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Supplier;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ---------------------------------------------------------------------
        // 1. SETUP DATA REFERENSI
        // ---------------------------------------------------------------------
        
        // Units
        $pcs  = Unit::firstOrCreate(['name' => 'pcs'])->unit_id;
        $set  = Unit::firstOrCreate(['name' => 'set'])->unit_id;
        $box  = Unit::firstOrCreate(['name' => 'box'])->unit_id;
        $unit = Unit::firstOrCreate(['name' => 'unit'])->unit_id; // Untuk Mesin
        $roll = Unit::firstOrCreate(['name' => 'roll'])->unit_id;
        $pail = Unit::firstOrCreate(['name' => 'pail'])->unit_id;

        // Categories
        $catTools = Category::firstOrCreate(['name' => 'Hand Tools', 'slug' => 'hand-tools'])->category_id;
        $catPower = Category::firstOrCreate(['name' => 'Power Tools', 'slug' => 'power-tools'])->category_id; // Kategori Mesin
        $catAcc   = Category::firstOrCreate(['name' => 'Accessories', 'slug' => 'accessories'])->category_id;
        $catMat   = Category::firstOrCreate(['name' => 'Building Materials', 'slug' => 'building-materials'])->category_id;

        // Suppliers (Ambil ID masing-masing)
        $suppJaya    = Supplier::where('supplier_name', 'PT. Pemasok Jaya')->first()->supplier_id ?? 1;
        $suppMitra   = Supplier::where('supplier_name', 'CV. Mitra Kencana')->first()->supplier_id ?? 1;
        
        // Supplier Nota (Sparepart)
        $suppBudi    = Supplier::where('supplier_name', 'CV. BUDI LUHUR')->first()->supplier_id ?? 1;
        
        // Supplier Pusat (Mesin) - INI YANG KITA TAMBAHKAN
        $suppBullPusat = Supplier::where('supplier_name', 'BULL Central Distributor')->first()->supplier_id ?? 1;

        // ---------------------------------------------------------------------
        // 2. DAFTAR PRODUK
        // ---------------------------------------------------------------------
        $products = [
            // =================================================================
            // A. SUPPLIER: BULL CENTRAL DISTRIBUTOR (Power Tools / Mesin) - BARU
            // =================================================================
            [
                'code' => 'BL-PT-001',
                'name' => 'BULL Cordless Drill 12V (Bor Baterai)',
                'cat'  => $catPower,
                'supp' => $suppBullPusat,
                'unit' => $unit,
                'buy'  => 350000,
                'sell' => 480000,
            ],
            [
                'code' => 'BL-PT-002',
                'name' => 'BULL Angle Grinder 4" BL-954 (Gerinda Tangan)',
                'cat'  => $catPower,
                'supp' => $suppBullPusat,
                'unit' => $unit,
                'buy'  => 280000,
                'sell' => 375000,
            ],
            [
                'code' => 'BL-PT-003',
                'name' => 'BULL Circular Saw 7" (Gergaji Kayu Listrik)',
                'cat'  => $catPower,
                'supp' => $suppBullPusat,
                'unit' => $unit,
                'buy'  => 550000,
                'sell' => 720000,
            ],
            [
                'code' => 'BL-PT-004',
                'name' => 'BULL Cut Off Machine 14" (Mesin Potong Besi)',
                'cat'  => $catPower,
                'supp' => $suppBullPusat,
                'unit' => $unit,
                'buy'  => 1200000,
                'sell' => 1650000,
            ],
            [
                'code' => 'BL-PT-005',
                'name' => 'BULL Impact Drill 13mm (Bor Tembok)',
                'cat'  => $catPower,
                'supp' => $suppBullPusat,
                'unit' => $unit,
                'buy'  => 420000,
                'sell' => 580000,
            ],
            [
                'code' => 'BL-PT-006',
                'name' => 'BULL Demolition Hammer (Mesin Bobok Beton)',
                'cat'  => $catPower,
                'supp' => $suppBullPusat,
                'unit' => $unit,
                'buy'  => 1800000,
                'sell' => 2400000,
            ],

            // =================================================================
            // B. SUPPLIER: CV. BUDI LUHUR (Spareparts sesuai Nota)
            // =================================================================
            [
                'code' => 'BL-ARM-870',
                'name' => 'BULL ARMATURE FOR MT870/871/M8700/01 (30)',
                'cat'  => $catAcc,
                'supp' => $suppBudi, // Punya Budi Luhur
                'unit' => $pcs,
                'buy'  => 400000,
                'sell' => 550000,
            ],
            [
                'code' => 'BL-ARM-602',
                'name' => 'BULL ARMATURE FOR MT602/603 (30)',
                'cat'  => $catAcc,
                'supp' => $suppBudi,
                'unit' => $pcs,
                'buy'  => 300000,
                'sell' => 420000,
            ],
            [
                'code' => 'BL-CSB-24T',
                'name' => 'BULL CSB/NEW-G SUPERTHIN 7"X24Tx20 (50)',
                'cat'  => $catAcc,
                'supp' => $suppBudi,
                'unit' => $pcs,
                'buy'  => 144000,
                'sell' => 185000,
            ],
            [
                'code' => 'BL-CSB-40T',
                'name' => 'BULL CSB/NEW-G SUPERTHIN 7"X40Tx20 (50)',
                'cat'  => $catAcc,
                'supp' => $suppBudi,
                'unit' => $pcs,
                'buy'  => 158000,
                'sell' => 200000,
            ],
            [
                'code' => 'BL-CSB-60T',
                'name' => 'BULL CSB/NEW-G SUPERTHIN 7"X60T (50)',
                'cat'  => $catAcc,
                'supp' => $suppBudi,
                'unit' => $pcs,
                'buy'  => 174000,
                'sell' => 230000,
            ],
            [
                'code' => 'BL-SIG-601',
                'name' => 'BLCL02-601 SIGMAT CARBON DGTL6" HTM(100)',
                'cat'  => $catTools,
                'supp' => $suppBudi,
                'unit' => $pcs,
                'buy'  => 120000,
                'sell' => 165000,
            ],
            [
                'code' => 'BL-CAP-100',
                'name' => 'BULL CAPASITOR 100MF (6/180)',
                'cat'  => $catAcc,
                'supp' => $suppBudi,
                'unit' => $pcs,
                'buy'  => 80000,
                'sell' => 110000,
            ],
            [
                'code' => 'BL-CAP-200',
                'name' => 'BULL CAPASITOR 200MF (6/180)',
                'cat'  => $catAcc,
                'supp' => $suppBudi,
                'unit' => $pcs,
                'buy'  => 100000,
                'sell' => 135000,
            ],
            [
                'code' => 'BL-GLS-TRM',
                'name' => 'BULL GLASS TRIMMER ANTI PECAH (100)',
                'cat'  => $catAcc,
                'supp' => $suppBudi,
                'unit' => $pcs,
                'buy'  => 88000,
                'sell' => 125000,
            ],
            [
                'code' => 'BL-CB-411A',
                'name' => 'BULL CB BLISTER AUTO CB411A',
                'cat'  => $catAcc,
                'supp' => $suppBudi,
                'unit' => $set,
                'buy'  => 16000,
                'sell' => 25000,
            ],
            [
                'code' => 'BL-CB-2000',
                'name' => 'BULL CB BLISTER AUTO GC2000/200/20-180',
                'cat'  => $catAcc,
                'supp' => $suppBudi,
                'unit' => $set,
                'buy'  => 20000,
                'sell' => 30000,
            ],

            // =================================================================
            // C. SUPPLIER: PT. PEMASOK JAYA (Hand Tools)
            // =================================================================
            [
                'code' => 'MT-001',
                'name' => 'Palu Kambing 16oz Gagang Fiber',
                'cat'  => $catTools,
                'supp' => $suppJaya,
                'unit' => $pcs,
                'buy'  => 35000,
                'sell' => 55000,
            ],
            [
                'code' => 'MT-002',
                'name' => 'Gergaji Tangan Kayu 18 Inch Super Tajam',
                'cat'  => $catTools,
                'supp' => $suppJaya,
                'unit' => $pcs,
                'buy'  => 45000,
                'sell' => 70000,
            ],
            [
                'code' => 'MT-003',
                'name' => 'Obeng Set (+) (-) 6 Pcs Magnetic',
                'cat'  => $catTools,
                'supp' => $suppJaya,
                'unit' => $set,
                'buy'  => 65000,
                'sell' => 95000,
            ],
            [
                'code' => 'MT-004',
                'name' => 'Tang Kombinasi 7 Inch Heavy Duty',
                'cat'  => $catTools,
                'supp' => $suppJaya,
                'unit' => $pcs,
                'buy'  => 42000,
                'sell' => 60000,
            ],
            [
                'code' => 'MT-005',
                'name' => 'Kunci Inggris 10 Inch Chrome Vanadium',
                'cat'  => $catTools,
                'supp' => $suppJaya,
                'unit' => $pcs,
                'buy'  => 55000,
                'sell' => 85000,
            ],
            [
                'code' => 'MT-006',
                'name' => 'Meteran Karet Tahan Banting 5 Meter',
                'cat'  => $catTools,
                'supp' => $suppJaya,
                'unit' => $pcs,
                'buy'  => 25000,
                'sell' => 40000,
            ],

            // =================================================================
            // D. SUPPLIER: CV. MITRA KENCANA (Bahan Bangunan)
            // =================================================================
            [
                'code' => 'MK-001',
                'name' => 'Paku Beton 3cm (Box Kecil)',
                'cat'  => $catMat,
                'supp' => $suppMitra,
                'unit' => $box,
                'buy'  => 12000,
                'sell' => 18000,
            ],
            [
                'code' => 'MK-002',
                'name' => 'Lem Fox Putih PVAc 150gr',
                'cat'  => $catMat,
                'supp' => $suppMitra,
                'unit' => $pcs,
                'buy'  => 8000,
                'sell' => 12000,
            ],
            [
                'code' => 'MK-003',
                'name' => 'Kuas Cat 3 Inch Bulu Putih',
                'cat'  => $catMat,
                'supp' => $suppMitra,
                'unit' => $pcs,
                'buy'  => 7500,
                'sell' => 12500,
            ],
            [
                'code' => 'MK-004',
                'name' => 'Amplas Kertas No. 100 (Lembaran)',
                'cat'  => $catMat,
                'supp' => $suppMitra,
                'unit' => $pcs,
                'buy'  => 2500,
                'sell' => 5000,
            ],
            [
                'code' => 'MK-005',
                'name' => 'Lakban Kertas / Masking Tape 2 Inch',
                'cat'  => $catAcc,
                'supp' => $suppMitra,
                'unit' => $roll,
                'buy'  => 11000,
                'sell' => 16000,
            ],
            [
                'code' => 'MK-006',
                'name' => 'Thinner A Special 1 Liter',
                'cat'  => $catMat,
                'supp' => $suppMitra,
                'unit' => $pcs,
                'buy'  => 22000,
                'sell' => 30000,
            ],
            [
                'code' => 'MK-100',
                'name' => 'Cat Tembok Putih Polos 5Kg',
                'cat'  => $catMat,
                'supp' => $suppMitra,
                'unit' => $pail,
                'buy'  => 95000,
                'sell' => 125000,
            ],
        ];

        // 3. EKSEKUSI INSERT
        foreach ($products as $p) {
            Product::updateOrCreate(
                ['product_code' => $p['code']],
                [
                    'product_name'   => $p['name'],
                    'category_id'    => $p['cat'],
                    'supplier_id'    => $p['supp'],
                    'unit_id'        => $p['unit'],
                    'purchase_price' => $p['buy'],
                    'selling_price'  => $p['sell'],
                    'stock_quantity' => 50,
                    'average_cost'   => $p['buy'],
                    'description'    => 'Seeder Data ' . date('Y'),
                    'is_active'      => true,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]
            );
        }
    }
}