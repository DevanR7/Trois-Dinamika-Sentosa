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
        Schema::disableForeignKeyConstraints();

        // Urutkan truncate dari tabel anak ke tabel induk
        DB::table('purchase_order_item_discounts')->truncate();
        DB::table('purchase_order_items')->truncate();
        DB::table('invoice_items')->truncate();
        DB::table('payments')->truncate();
        DB::table('purchase_order_payments')->truncate();
        DB::table('invoice_tax')->truncate();
        
        // Kosongkan tabel baru dari Spatie
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('role_has_permissions')->truncate();
        
        DB::table('purchase_orders')->truncate();
        DB::table('sales_invoices')->truncate();
        DB::table('products')->truncate();
        DB::table('clients')->truncate();
        DB::table('suppliers')->truncate();
        DB::table('users')->truncate();
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();
        
        $this->call([
            // Panggil RoleAndPermissionSeeder SEBELUM UserSeeder
            RoleAndPermissionSeeder::class, 
            SettingSeeder::class,
            UserSeeder::class,
            SupplierSeeder::class, 
            ClientSeeder::class,   
            UnitSeeder::class,
            ProductSeeder::class, 
            TaxSeeder::class, 
            PaymentMethodSeeder::class,
            // SalesInvoiceSeeder dan InvoiceItemSeeder bisa Anda nonaktifkan jika ingin data invoice kosong
            // SalesInvoiceSeeder::class,
            // InvoiceItemSeeder::class,
        ]);
        
        Schema::enableForeignKeyConstraints();
    }
}