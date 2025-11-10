<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SalesInvoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceAdjustment;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use App\Models\Payment;
use App\Models\ClientLedger;
use Carbon\Carbon;

class SalesInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Gunakan transaction agar jika gagal, semua data akan di-rollback
        DB::transaction(function () {
            
            // --- 1. PERSIAPAN DATA MASTER ---
            
            // Pastikan data ini ada. Jika tidak, seeder akan gagal.
            // Anda harus menjalankan seeder untuk Client, Product, dan User (dengan role 'sales') terlebih dahulu.
            $client1 = Client::first();
            $client2 = Client::skip(1)->first();
            $salesUser = User::role('sales')->first();
            $adminUser = User::role('admin')->first(); // Asumsi ada admin untuk mencatat pembayaran
            $productA = Product::find(1); // Ganti ID 1 dengan ID produk yang ada
            $productB = Product::find(2); // Ganti ID 2 dengan ID produk yang ada

            // Cek data prasyarat
            if (!$client1 || !$client2 || !$salesUser || !$adminUser || !$productA || !$productB) {
                $this->command->error('Gagal seeding: Pastikan Anda memiliki minimal 2 Klien, 2 Produk, 1 Admin, dan 1 Sales.');
                return;
            }

            // --- 2. BERSIHKAN DATA LAMA (OPSIONAL TAPI DISARANKAN) ---
            $this->command->info('Menghapus data invoices, items, adjustments, payments, dan ledgers lama...');
            InvoiceItem::query()->delete();
            InvoiceAdjustment::query()->delete();
            Payment::query()->whereNotNull('invoice_id')->delete();
            ClientLedger::query()->whereNotNull('sales_invoice_id')->delete();
            SalesInvoice::query()->delete();
            DB::table('invoice_counters')->delete();


            // =================================================================
            // CONTOH 1: INVOICE DRAFT
            // =================================================================
            $this->command->info('Membuat Invoice 1 (Draft)...');
            $subtotal1 = $productA->selling_price * 2;
            
            $inv1 = SalesInvoice::create([
                'client_id' => $client1->client_id,
                'user_id_sales' => $salesUser->user_id,
                'invoice_number' => SalesInvoice::generateInvoiceNumber($salesUser->user_id),
                'order_date' => Carbon::now()->subDays(10),
                'due_date' => Carbon::now()->addDays(20),
                'subtotal' => $subtotal1,
                'total_amount' => $subtotal1,
                'status' => 'draft',
                'notes' => 'Invoice draft, stok belum dipotong.'
            ]);

            $inv1->items()->create([
                'product_id' => $productA->product_id,
                'quantity' => 2,
                'price_per_unit' => $productA->selling_price,
                'hpp' => $productA->average_cost,
                'subtotal' => $subtotal1
            ]);

            // =================================================================
            // CONTOH 2: INVOICE UNPAID (TERKONFIRMASI)
            // =================================================================
            $this->command->info('Membuat Invoice 2 (Unpaid)...');
            $subtotal2 = $productB->selling_price * 1;

            $inv2 = SalesInvoice::create([
                'client_id' => $client2->client_id,
                'user_id_sales' => $salesUser->user_id,
                'invoice_number' => SalesInvoice::generateInvoiceNumber($salesUser->user_id, 'sales'),
                'order_date' => Carbon::now()->subDays(5),
                'due_date' => Carbon::now()->addDays(25),
                'subtotal' => $subtotal2,
                'total_amount' => $subtotal2,
                'status' => 'unpaid', // <-- Langsung unpaid (terkonfirmasi)
                'notes' => 'Invoice unpaid, stok sudah dipotong.'
            ]);

            $inv2->items()->create([
                'product_id' => $productB->product_id,
                'quantity' => 1,
                'price_per_unit' => $productB->selling_price,
                'hpp' => $productB->average_cost,
                'subtotal' => $subtotal2
            ]);
            // Meniru logika 'confirm' (potong stok)
            Product::find($productB->product_id)->decrement('stock_quantity', 1);


            // =================================================================
            // CONTOH 3: INVOICE PARTIALLY PAID
            // =================================================================
            $this->command->info('Membuat Invoice 3 (Partially Paid)...');
            $subtotal3 = $productA->selling_price * 10;
            $total3 = $subtotal3; // Asumsi tidak ada diskon/pajak
            $payment3 = $total3 / 2; // Dibayar setengah

            $inv3 = SalesInvoice::create([
                'client_id' => $client1->client_id,
                'user_id_sales' => $salesUser->user_id,
                'invoice_number' => SalesInvoice::generateInvoiceNumber($salesUser->user_id, 'sales'),
                'order_date' => Carbon::now()->subDays(3),
                'due_date' => Carbon::now()->addDays(27),
                'subtotal' => $subtotal3,
                'total_amount' => $total3,
                'amount_paid' => $payment3, // <-- Diisi manual
                'status' => 'partially_paid', // <-- Diisi manual
            ]);

            $inv3->items()->create([
                'product_id' => $productA->product_id,
                'quantity' => 10,
                'price_per_unit' => $productA->selling_price,
                'hpp' => $productA->average_cost,
                'subtotal' => $subtotal3
            ]);
            Product::find($productA->product_id)->decrement('stock_quantity', 10); // Potong stok
            
            // Catat pembayaran parsial
            $inv3->payments()->create([
                'payment_date' => Carbon::now()->subDays(2),
                'amount' => $payment3,
                'payment_method_id' => 1, // Ganti 1 dengan ID Payment Method Anda
                'company_bank_account_id' => 1, // Ganti 1 dengan ID Bank Akun Anda
                'status' => 'completed',
                'received_by_user_id' => $adminUser->user_id,
            ]);
            // $inv3->updatePaymentStatus(); // Seharusnya ini dipanggil, tapi kita set manual


            // =================================================================
            // CONTOH 4: PAID, LALU ADJUSTMENT (OVERPAYMENT)
            // =================================================================
            $this->command->info('Membuat Invoice 4 (Paid, lalu Overpayment)...');
            $subtotal4 = $productB->selling_price * 5;
            $total4 = $subtotal4; // Total awal 
            $adjustmentAmount = 10000; // Misal ada diskon tambahan 10.000

            $inv4 = SalesInvoice::create([
                'client_id' => $client2->client_id,
                'user_id_sales' => $salesUser->user_id,
                'invoice_number' => SalesInvoice::generateInvoiceNumber($salesUser->user_id, 'client'),
                'order_date' => Carbon::now()->subDays(2),
                'due_date' => Carbon::now()->addDays(28),
                'subtotal' => $subtotal4,
                'total_amount' => $total4,
                'amount_paid' => $total4, // <-- Dibayar lunas sesuai total AWAL
                'status' => 'paid', // <-- Status akhir akan 'paid'
            ]);

            $inv4->items()->create([
                'product_id' => $productB->product_id,
                'quantity' => 5,
                'price_per_unit' => $productB->selling_price,
                'hpp' => $productB->average_cost,
                'subtotal' => $subtotal4
            ]);
            Product::find($productB->product_id)->decrement('stock_quantity', 5); // Potong stok

            // 1. Buat pembayaran LUNAS
            $inv4->payments()->create([
                'payment_date' => Carbon::now()->subDay(),
                'amount' => $total4, // Dibayar lunas
                'payment_method_id' => 1, 
                'company_bank_account_id' => 1, 
                'status' => 'completed',
                'received_by_user_id' => $adminUser->user_id,
            ]);

            // 2. Buat Nota Kredit (Sebab)
            $adjustment = InvoiceAdjustment::create([
                'sales_invoice_id' => $inv4->invoice_id,
                'user_id' => $adminUser->user_id,
                'adjustment_date' => Carbon::now(),
                'type' => 'credit_note',
                'amount' => $adjustmentAmount,
                'reason' => 'Seeder: Diskon tambahan setelah lunas.',
            ]);

            // 3. Buat Client Ledger (Akibat 1)
            $ledgerEntry = ClientLedger::create([
                'client_id' => $inv4->client_id,
                'sales_invoice_id' => $inv4->invoice_id,
                'reference_type' => InvoiceAdjustment::class,
                'reference_id' => $adjustment->adjustment_id,
                'transaction_date' => Carbon::now(),
                'type' => 'credit',
                'amount' => $adjustmentAmount,
                'status' => 'available',
                'description' => 'Otomatis: Kelebihan bayar dari Inv #' . $inv4->invoice_number,
                'user_id' => $adminUser->user_id,
            ]);

            // 4. Buat Nota Debit Otomatis (Akibat 2)
            InvoiceAdjustment::create([
                'sales_invoice_id' => $inv4->invoice_id,
                'user_id' => $adminUser->user_id,
                'adjustment_date' => Carbon::now(),
                'type' => 'debit_note',
                'amount' => $adjustmentAmount,
                'reason' => 'Otomatis: Memindahkan kelebihan bayar (Rp ' . number_format($adjustmentAmount) . ') ke deposit klien (Ledger ID: ' . $ledgerEntry->ledger_id . ')',
            ]);
            
            $this->command->info('Seeding Sales Invoices selesai.');

        });
    }
}