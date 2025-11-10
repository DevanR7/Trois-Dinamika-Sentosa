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
        // Pastikan transaksi untuk atomicity
        DB::transaction(function () {

            // Ambil atau buat supplier contoh
            $supplier = Supplier::first();
            if (!$supplier) {
                $supplier = Supplier::create([
                    'supplier_name' => 'Supplier Contoh',
                    'email' => 'supplier@example.test',
                    'phone' => '081234567890',
                    'address' => 'Jl. Contoh No.1',
                ]);
            }

            // Ambil atau buat admin user contoh (untuk user_id_admin)
            $admin = User::first();
            if (!$admin) {
                $admin = User::create([
                    'full_name' => 'Admin Seeder',
                    'email' => 'admin@example.test',
                    'username' => 'adminseeder',
                    'password' => bcrypt('password'), // ganti sesuai kebutuhan
                    'is_approved' => true,
                ]);
            }

            // Ambil atau buat product contoh (jika belum ada)
            $product = Product::first();
            if (!$product) {
                $product = Product::create([
                    'supplier_id' => $supplier->supplier_id,
                    'product_code' => 'P-001',
                    'product_name' => 'Produk Contoh A',
                    'purchase_price' => 100000.00,
                    'selling_price' => 120000.00,
                    'average_cost' => 0,
                    'stock_quantity' => 0,
                ]);
            }

            // Helper: generate nomor PO berdasarkan po_counters (format PO-YYYYMM-XXX)
            $generatePoNumber = function () {
                $ym = Carbon::now()->format('Ym'); // e.g. 202511
                // Ambil row counter sekarang (lock untuk safety)
                $counter = DB::table('po_counters')->where('ym', $ym)->lockForUpdate()->first();

                if ($counter) {
                    $next = $counter->last_sequence + 1;
                    DB::table('po_counters')->where('ym', $ym)->update([
                        'last_sequence' => $next,
                        'updated_at' => now(),
                    ]);
                } else {
                    $next = 1;
                    DB::table('po_counters')->insert([
                        'ym' => $ym,
                        'last_sequence' => $next,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return sprintf('PO-%s-%03d', $ym, $next); // contoh: PO-202511-001
            };

            // Diskon berlapis
            $diskonBerlapis = [60.0, 10.0, 9.91];

            // Buat 4 PO contoh — barang sudah diterima (status completed) tetapi belum lunas
            $samplePOs = [
                ['quantity' => 10, 'purchase_price' => $product->purchase_price, 'notes' => 'Pesanan bahan baku A.'],
                ['quantity' => 5,  'purchase_price' => $product->purchase_price, 'notes' => 'Pesanan kemasan B.'],
                ['quantity' => 20, 'purchase_price' => $product->purchase_price, 'notes' => 'Pesanan alat tulis.'],
                ['quantity' => 15, 'purchase_price' => $product->purchase_price, 'notes' => 'Pesanan bahan pengemasan.'],
            ];

            foreach ($samplePOs as $i => $poSpec) {
                $qty = (int) $poSpec['quantity'];
                $hargaAwal = (float) $poSpec['purchase_price'];

                // Hitung final price per unit setelah diskon berlapis
                $finalPrice = $hargaAwal;
                foreach ($diskonBerlapis as $d) {
                    $finalPrice *= (1 - ($d / 100.0));
                }
                // subtotal berdasarkan finalPrice
                $subtotal = round($qty * $finalPrice, 2);

                // create PO — STATUS completed (telah diterima) ; PAYMENT_STATUS = unpaid (tidak lunas)
                $po = PurchaseOrder::create([
                    'po_number' => $generatePoNumber(),
                    'supplier_id' => $supplier->supplier_id,
                    'requester_user_id' => null,
                    'user_id_admin' => $admin->user_id,
                    'order_date' => Carbon::now()->subDays(10 - $i)->toDateString(),
                    'due_date' => Carbon::now()->addDays(15 + $i)->toDateString(),
                    'expected_delivery_date' => Carbon::now()->subDays(5 - $i)->toDateString(),
                    'status' => 'completed',               // barang sudah diterima
                    'payment_status' => 'unpaid',          // jangan lunas
                    'subtotal' => $subtotal,
                    'total_amount' => $subtotal,
                    'grand_total' => $subtotal,
                    'shipping_amount' => 0,
                    'dpp' => $subtotal,
                    'ppn' => 0,
                    'notes' => $poSpec['notes'] . ' (Seeder: diskon berlapis 60%,10%,9.91%)',
                ]);

                // create item
                $item = PurchaseOrderItem::create([
                    'po_id' => $po->po_id,
                    'product_id' => $product->product_id,
                    'quantity' => $qty,
                    'quantity_returned' => 0,
                    'price_per_unit' => $hargaAwal,
                    'subtotal' => $subtotal,
                ]);

                // buat diskon per-item (berlapis)
                foreach ($diskonBerlapis as $d) {
                    PurchaseOrderItemDiscount::create([
                        'purchase_order_item_id' => $item->item_id,
                        'percentage' => $d,
                    ]);
                }

                // Karena barang sudah diterima, update stok & average_cost (weighted average)
                // Perhitungan sederhana: set average_cost menjadi finalPrice jika stok sebelumnya 0
                $oldStock = (int) $product->stock_quantity;
                $oldAvg = (float) $product->average_cost;
                $newStock = $oldStock + $qty;
                $newAvg = 0.0;
                if ($newStock > 0) {
                    $newAvg = (($oldStock * $oldAvg) + ($qty * $finalPrice)) / $newStock;
                }
                $product->update([
                    'stock_quantity' => $newStock,
                    'average_cost' => round($newAvg, 2),
                    // optional: update purchase_price to last purchase price (raw)
                    //'purchase_price' => $hargaAwal,
                ]);
            }

            // selesai
            $this->command->info('✅ 4 Purchase Order (completed but unpaid) berhasil dibuat oleh seeder.');
        });
    }
}
