<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Tax;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Setup Data Pendukung
        $tax12 = Tax::firstOrCreate(['rate' => 12.00], ['name' => 'PPN 12%', 'is_active' => true]);
        $admin = User::role('admin')->first() ?? User::first(); // Mengisi user_id_admin
        
        // Cari Supplier CV. BUDI LUHUR
        $supplier = Supplier::where('supplier_name', 'CV. BUDI LUHUR')->first();
        
        if(!$supplier) {
            $this->command->warn('Supplier CV. BUDI LUHUR tidak ditemukan. Pastikan SupplierSeeder sudah dijalankan.');
            return;
        }

        DB::transaction(function () use ($admin, $tax12, $supplier) {
            
            // ---------------------------------------------------------
            // 2. LOGIKA GENERATE PO NUMBER (Manual via po_counters)
            // ---------------------------------------------------------
            // Kita set tanggal order sesuai nota: 23 September 2025
            $orderDate = Carbon::create(2025, 9, 23);
            $ymString  = $orderDate->format('Ym'); // "202509"

            // Cek counter terakhir di tabel po_counters
            $counter = DB::table('po_counters')
                ->where('ym', $ymString)
                ->where('supplier_id', $supplier->supplier_id)
                ->lockForUpdate()
                ->first();

            $nextSequence = $counter ? $counter->last_sequence + 1 : 1;

            // Update atau Insert ke tabel po_counters
            DB::table('po_counters')->updateOrInsert(
                ['ym' => $ymString, 'supplier_id' => $supplier->supplier_id],
                ['last_sequence' => $nextSequence, 'updated_at' => now()]
            );

            // Format PO Number: PO/YYYYMM/SUPPLIER_ID/SEQUENCE (Contoh format standar)
            // Hasil: PO/202509/4/0001
            $generatedPoNumber = sprintf(
                "PO/%s/%s/%04d", 
                $ymString, 
                $supplier->supplier_id, 
                $nextSequence
            );

            // ---------------------------------------------------------
            // 3. PERSIAPAN DATA NOMINAL (Hardcode sesuai Nota Gambar)
            // ---------------------------------------------------------
            // Subtotal dari penjumlahan item di kolom "Jumlah"
            $subtotalNota = 3303050; 
            
            // Angka di pojok kanan bawah nota
            $dppValue     = 3027796; // DPP (11/12 x Hrg Jual)
            $ppnValue     = 363335;  // PPN 12%
            $grandTotal   = 3666386; // Jumlah Total

            // ---------------------------------------------------------
            // 4. CREATE HEADER PO
            // ---------------------------------------------------------
            $po = PurchaseOrder::create([
                // Generated otomatis by system (simulasi)
                'po_number'               => $generatedPoNumber, 
                
                // Nomor Faktur dari Kertas Nota
                'supplier_invoice_number' => 'E59750', 
                
                'supplier_id'             => $supplier->supplier_id,
                'user_id_admin'           => $admin->user_id,
                'requester_user_id'       => $admin->user_id, // Opsional, disamakan saja
                
                'order_date'              => $orderDate,
                'due_date'                => $orderDate, // Jatuh tempo sama dgn tgl nota (tunai/tempo)
                'expected_delivery_date'  => $orderDate->copy()->addDays(3),
                
                'status'                  => 'draft', // SESUAI REQUEST: DRAFT
                'payment_status'          => 'unpaid',
                
                // Nominal
                'subtotal'                => $subtotalNota,
                'tax_id'                  => $tax12->id,
                'dpp'                     => $dppValue,
                'taxable_amount'          => $dppValue, // Biasanya sama dengan DPP
                'ppn'                     => $ppnValue,
                'grand_total'             => $grandTotal,
                'total_amount'            => $grandTotal, // Total amount biasanya sama dgn Grand Total
                
                'notes'                   => 'Input data historis dari Nota Fisik E59750',
                'created_at'              => $orderDate,
                'updated_at'              => $orderDate,
            ]);

            // ---------------------------------------------------------
            // 5. CREATE PO ITEMS
            // ---------------------------------------------------------
            // Data produk & harga sesuai kolom "Jumlah" di nota
            $notaItems = [
                ['BL-ARM-870', 3, 400000, 379459],
                ['BL-ARM-602', 2, 300000, 189729],
                ['BL-CSB-24T', 3, 144000, 144194],
                ['BL-CSB-40T', 2, 158000, 105475],
                ['BL-CSB-60T', 4, 174000, 232313],
                ['BL-SIG-601', 5, 120000, 200270],
                ['BL-CAP-100', 12, 80000, 303567],
                ['BL-CAP-200', 12, 100000, 379459],
                ['BL-GLS-TRM', 6, 88000, 166962],
                ['BL-CB-411A', 200, 16000, 1011890],
                ['BL-CB-2000', 30, 20000, 189729],
            ];

            foreach ($notaItems as $item) {
                $code     = $item[0];
                $qty      = $item[1];
                $priceRaw = $item[2]; // Harga List
                $subtotal = $item[3]; // Harga Netto (setelah diskon ribet)

                $product = Product::where('product_code', $code)->first();

                if ($product) {
                    PurchaseOrderItem::create([
                        'po_id'          => $po->po_id,
                        'product_id'     => $product->product_id,
                        'quantity'       => $qty,
                        'price_per_unit' => $priceRaw, 
                        'subtotal'       => $subtotal,
                        'created_at'     => $orderDate,
                        'updated_at'     => $orderDate,
                    ]);
                }
            }
        });
    }
}