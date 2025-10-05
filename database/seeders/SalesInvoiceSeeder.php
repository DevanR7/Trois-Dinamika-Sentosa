<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('sales_invoices')->insert([
            [
                'invoice_number' => 'INV-2025-001',
                'client_id' => 1, // ID dari Toko Besi Maju Mundur
                'user_id_sales' => 2, // ID dari Sales Lapangan
                'invoice_date' => Carbon::now()->subDays(10), // 10 hari yang lalu
                'due_date' => Carbon::now()->addDays(20), // 20 hari dari sekarang
                'total_amount' => 195000.00,
                'amount_paid' => 0.00,
                'status' => 'unpaid',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'invoice_number' => 'INV-2025-002',
                'client_id' => 2, // ID dari Toko Bangunan Kokoh
                'user_id_sales' => 2, // ID dari Sales Lapangan
                'invoice_date' => Carbon::now()->subDays(5), // 5 hari yang lalu
                'due_date' => Carbon::now()->addDays(25), // 25 hari dari sekarang
                'total_amount' => 40000.00,
                'amount_paid' => 40000.00,
                'status' => 'paid',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}