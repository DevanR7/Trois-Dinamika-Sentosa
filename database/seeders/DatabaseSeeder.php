<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Nonaktifkan Cek Foreign Key
        Schema::disableForeignKeyConstraints();

        // 2. Kosongkan tabel-tabel (urutkan dari tabel anak ke tabel induk)
        DB::table('purchase_order_items')->truncate();
        DB::table('invoice_items')->truncate();
        DB::table('payments')->truncate();
        DB::table('billing_logs')->truncate();
        DB::table('payment_gateway_callbacks')->truncate();
        DB::table('purchase_orders')->truncate();
        DB::table('sales_invoices')->truncate();
        DB::table('products')->truncate();
        DB::table('clients')->truncate();
        DB::table('suppliers')->truncate();
        DB::table('users')->truncate();
        
        // 3. Panggil Seeder yang dibutuhkan
        $this->call([
            UserSeeder::class,
            SupplierSeeder::class, 
            ClientSeeder::class,   
            UnitSeeder::class,
            ProductSeeder::class,  
            SalesInvoiceSeeder::class,
            InvoiceItemSeeder::class,
        ]);
        
        // 4. Aktifkan kembali Cek Foreign Key
        Schema::enableForeignKeyConstraints();
    }
}
