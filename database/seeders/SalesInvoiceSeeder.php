<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SalesInvoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\CompanyBankAccount;
use Carbon\Carbon;

class SalesInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            
            // 1. Setup Data Pendukung
            $client1 = Client::first(); 
            $client2 = Client::orderBy('client_id', 'desc')->first();
            $salesUser = User::role('sales')->first() ?? User::first();
            $adminUser = User::role('admin')->first() ?? User::first();
            
            if (!$client1 || !$client2) return;

            // Produk sample
            $prod1 = Product::where('product_code', 'BL-003')->first(); // Mata Gergaji
            $prod2 = Product::where('product_code', 'MT-001')->first(); // Palu

            if (!$prod1 || !$prod2) return;

            // -----------------------------------------------------------------
            // KASUS 1: INVOICE DRAFT (Belum memotong stok, belum ada jurnal)
            // -----------------------------------------------------------------
            $qty1 = 5;
            $subtotal1 = $qty1 * $prod1->selling_price; // 5 * 185.000 = 925.000

            $inv1 = SalesInvoice::create([
                'client_id' => $client1->client_id,
                'user_id_sales' => $salesUser->user_id,
                'invoice_number' => SalesInvoice::generateInvoiceNumber($salesUser->user_id),
                'order_date' => Carbon::now(),
                'due_date' => Carbon::now()->addDays(14),
                'subtotal' => $subtotal1,
                'total_amount' => $subtotal1,
                'status' => 'draft',
                'amount_paid' => 0,
                'notes' => 'Penawaran awal, menunggu persetujuan klien.'
            ]);

            InvoiceItem::create([
                'invoice_id' => $inv1->invoice_id,
                'product_id' => $prod1->product_id,
                'quantity' => $qty1,
                'price_per_unit' => $prod1->selling_price,
                'hpp' => $prod1->average_cost,
                'subtotal' => $subtotal1
            ]);


            // -----------------------------------------------------------------
            // KASUS 2: INVOICE UNPAID (Terkonfirmasi, Stok Terpotong)
            // -----------------------------------------------------------------
            $qty2 = 10;
            $subtotal2 = $qty2 * $prod2->selling_price; // 10 * 55.000 = 550.000
            
            $inv2 = SalesInvoice::create([
                'client_id' => $client2->client_id,
                'user_id_sales' => $salesUser->user_id,
                'invoice_number' => SalesInvoice::generateInvoiceNumber($salesUser->user_id),
                'order_date' => Carbon::now()->subDays(5),
                'due_date' => Carbon::now()->addDays(25),
                'subtotal' => $subtotal2,
                'total_amount' => $subtotal2,
                'status' => 'unpaid', // Sudah confirm
                'amount_paid' => 0,
                'notes' => 'Barang sudah dikirim, menunggu pembayaran.'
            ]);

            InvoiceItem::create([
                'invoice_id' => $inv2->invoice_id,
                'product_id' => $prod2->product_id,
                'quantity' => $qty2,
                'price_per_unit' => $prod2->selling_price,
                'hpp' => $prod2->average_cost,
                'subtotal' => $subtotal2
            ]);

            // Simulasi pemotongan stok (manual di seeder)
            $prod2->decrement('stock_quantity', $qty2);


            // -----------------------------------------------------------------
            // KASUS 3: INVOICE PAID (Lunas)
            // -----------------------------------------------------------------
            $qty3 = 2;
            $subtotal3 = $qty3 * $prod1->selling_price; // 2 * 185.000 = 370.000
            
            $inv3 = SalesInvoice::create([
                'client_id' => $client1->client_id,
                'user_id_sales' => $salesUser->user_id,
                'invoice_number' => SalesInvoice::generateInvoiceNumber($salesUser->user_id),
                'order_date' => Carbon::now()->subDays(10),
                'due_date' => Carbon::now()->subDays(1), // Sudah lewat jatuh tempo tapi lunas
                'subtotal' => $subtotal3,
                'total_amount' => $subtotal3,
                'status' => 'paid',
                'amount_paid' => $subtotal3,
                'notes' => 'Lunas via Transfer BCA.'
            ]);

            InvoiceItem::create([
                'invoice_id' => $inv3->invoice_id,
                'product_id' => $prod1->product_id,
                'quantity' => $qty3,
                'price_per_unit' => $prod1->selling_price,
                'hpp' => $prod1->average_cost,
                'subtotal' => $subtotal3
            ]);

            // Simulasi pemotongan stok
            $prod1->decrement('stock_quantity', $qty3);

            // Buat Pembayaran
            $paymentMethod = PaymentMethod::where('name', 'Manual Transfer')->first();
            $bankAccount = CompanyBankAccount::where('bank_name', 'BCA')->first();

            $inv3->payments()->create([
                'payment_date' => Carbon::now()->subDays(2),
                'amount' => $subtotal3,
                'payment_method_id' => $paymentMethod->payment_method_id ?? 1,
                'company_bank_account_id' => $bankAccount->company_bank_account_id ?? 1,
                'received_by_user_id' => $adminUser->user_id,
                'status' => 'completed',
                'notes' => 'Pelunasan Seeder',
                'reference_number' => 'REF-' . rand(1000, 9999)
            ]);
        });
    }
}