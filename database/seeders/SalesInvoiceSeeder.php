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
                'client_id' => 1,
                'user_id_sales' => 2,
                'order_date' => Carbon::now()->subDays(10), // <-- DIPERBAIKI
                'due_date' => Carbon::now()->addDays(20),
                'total_amount' => 195000.00,
                'amount_paid' => 0.00,
                'status' => 'unpaid',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'invoice_number' => 'INV-2025-002',
                'client_id' => 2,
                'user_id_sales' => 2,
                'order_date' => Carbon::now()->subDays(5), // <-- DIPERBAIKI
                'due_date' => Carbon::now()->addDays(25),
                'total_amount' => 40000.00,
                'amount_paid' => 40000.00,
                'status' => 'paid',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}