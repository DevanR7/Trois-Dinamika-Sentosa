<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderItemDiscount;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // 1. Setup Supplier (SESUAI TABEL SUPPLIERS KAMU)
            $supplier = Supplier::firstOrCreate(
                ['supplier_name' => 'BULL Central Distributor'], // Kunci pencarian
                [
                    // Data jika belum ada (sesuai kolom di migration kamu)
                    'person_in_charge' => 'Budi Santoso (Sales Pusat)',
                    'phone_number' => '021-555-9999', // Menggunakan phone_number
                    'address' => 'Jl. Teknik Industri No. 88, Jakarta',
                    'npwp' => '01.234.567.8-901.000',
                ]
            );

            // 2. Setup Admin (Asumsi tabel users standar Laravel/breeze)
            $admin = User::firstOrCreate(
                ['username' => 'admin_gudang'],
                [
                    'full_name' => 'Admin Gudang',
                    'email' => 'admingudang@example.com',
                    'password' => bcrypt('password'),
                    'is_approved' => true,
                ]
            );

            // 3. Helper Generate PO Number
            $generatePoNumber = function () {
                $ym = Carbon::now()->format('Ym');
                $counter = DB::table('po_counters')->where('ym', $ym)->lockForUpdate()->first();
                if ($counter) {
                    $next = $counter->last_sequence + 1;
                    DB::table('po_counters')->where('ym', $ym)->update(['last_sequence' => $next, 'updated_at' => now()]);
                } else {
                    $next = 1;
                    DB::table('po_counters')->insert(['ym' => $ym, 'last_sequence' => $next, 'created_at' => now(), 'updated_at' => now()]);
                }
                return sprintf('PO-%s-%03d', $ym, $next);
            };

            // 4. DATA TRANSAKSI (Sesuai Nota Gambar)
            $itemsData = [
                ['code' => 'BL-001', 'qty' => 3, 'price' => 400000, 'discounts' => [61.00, 10.00, 9.91]],
                ['code' => 'BL-002', 'qty' => 2, 'price' => 300000, 'discounts' => [61.00, 10.00, 9.91]],
                ['code' => 'BL-003', 'qty' => 3, 'price' => 144000, 'discounts' => [61.00, 5.00, 9.91]],
                ['code' => 'BL-004', 'qty' => 2, 'price' => 158000, 'discounts' => [61.00, 5.00, 9.91]],
                ['code' => 'BL-005', 'qty' => 4, 'price' => 174000, 'discounts' => [61.00, 5.00, 9.91]],
                ['code' => 'BL-006', 'qty' => 5, 'price' => 120000, 'discounts' => [61.00, 5.00, 9.91]],
                ['code' => 'BL-007', 'qty' => 12, 'price' => 80000, 'discounts' => [61.00, 10.00, 9.91]],
                ['code' => 'BL-008', 'qty' => 12, 'price' => 100000, 'discounts' => [61.00, 10.00, 9.91]],
                ['code' => 'BL-009', 'qty' => 6, 'price' => 88000, 'discounts' => [61.00, 10.00, 9.91]],
                ['code' => 'BL-010', 'qty' => 200, 'price' => 16000, 'discounts' => [61.00, 10.00, 9.91]],
                ['code' => 'BL-011', 'qty' => 30, 'price' => 20000, 'discounts' => [61.00, 10.00, 9.91]],
            ];

            // 5. Kalkulasi Total
            $poGrandTotal = 0;
            $preparedItems = [];

            foreach ($itemsData as $data) {
                $product = Product::where('product_code', $data['code'])->first();
                
                if ($product) {
                    $basePrice = $data['price'];
                    $currentPrice = $basePrice;
                    
                    // Hitung Netto
                    foreach ($data['discounts'] as $discPercentage) {
                        $currentPrice = $currentPrice * (1 - ($discPercentage / 100));
                    }
                    
                    $netPricePerUnit = round($currentPrice, 2);
                    $lineTotal = $netPricePerUnit * $data['qty'];
                    
                    $poGrandTotal += $lineTotal;

                    $preparedItems[] = [
                        'product' => $product,
                        'qty' => $data['qty'],
                        'base_price' => $basePrice,
                        'net_price' => $netPricePerUnit,
                        'subtotal' => $lineTotal,
                        'discounts' => $data['discounts']
                    ];
                }
            }

            // 6. Buat Header PO (STATUS = DRAFT)
            $po = PurchaseOrder::create([
                'po_number' => $generatePoNumber(),
                'supplier_id' => $supplier->supplier_id,
                'requester_user_id' => null,
                'user_id_admin' => $admin->user_id,
                'order_date' => Carbon::now()->toDateString(),
                'due_date' => Carbon::now()->addDays(30)->toDateString(),
                'expected_delivery_date' => Carbon::now()->addDays(3)->toDateString(),
                'status' => 'draft',           // Draft
                'payment_status' => 'unpaid',
                'subtotal' => $poGrandTotal,
                'total_amount' => $poGrandTotal,
                'grand_total' => $poGrandTotal,
                'dpp' => $poGrandTotal,
                'ppn' => 0, 
                'shipping_amount' => 0,
                'notes' => 'Draft PO Restock BULL (Waiting Approval)',
            ]);

            // 7. Simpan Item & Diskon
            foreach ($preparedItems as $item) {
                $poItem = PurchaseOrderItem::create([
                    'po_id' => $po->po_id,
                    'product_id' => $item['product']->product_id,
                    'quantity' => $item['qty'],
                    'quantity_returned' => 0,
                    'price_per_unit' => $item['base_price'], 
                    'subtotal' => $item['subtotal'],
                ]);

                foreach ($item['discounts'] as $discValue) {
                    PurchaseOrderItemDiscount::create([
                        'purchase_order_item_id' => $poItem->item_id,
                        'percentage' => $discValue
                    ]);
                }
            }

            $this->command->info("✅ PO Draft {$po->po_number} Berhasil! Supplier menggunakan 'phone_number'.");
        });
    }
}