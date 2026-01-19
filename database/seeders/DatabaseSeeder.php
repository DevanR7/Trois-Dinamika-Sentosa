<?php

namespace Database\Seeders;

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
        // 1. Matikan Foreign Key Check secara Global
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::disableForeignKeyConstraints();

        try {
            // 2. Kosongkan Tabel (Urutan: Anak -> Induk)
            $this->truncateTables();

            // 3. Panggil Seeder (Data Baru)
            $this->call([
                // A. System & Auth (Pondasi)
                RoleAndPermissionSeeder::class, 
                UserSeeder::class,              
                
                // B. Master Data Akuntansi & Keuangan
                ChartOfAccountsSeeder::class,   
                SettingSeeder::class, // Setting butuh COA
                CompanyBankAccountSeeder::class, // Bank butuh COA
                PaymentMethodSeeder::class,
                TaxSeeder::class,
                UnitSeeder::class,
                
                // C. Master Data Bisnis
                SupplierSeeder::class,
                ClientSeeder::class,
                ProductSeeder::class,           
                
                // D. Data Transaksi Awal (Opsional - untuk demo)
                PurchaseOrderSeeder::class,      
                SalesInvoiceSeeder::class, // Ini sekaligus buat item dan payment dummy
            ]);

        } finally {
            // 4. Hidupkan kembali Foreign Key Check (Wajib)
            Schema::enableForeignKeyConstraints();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function truncateTables()
    {
        // List semua tabel yang perlu dikosongkan
        // Urutan: Tabel Anak (yang punya foreign key) -> Tabel Induk
        $tables = [
            // Level 4 (Paling Bawah - Detail Transaksi)
            'general_ledgers', 'manual_journal_entries', 
            'purchase_order_item_discounts', 'purchase_order_items', 'purchase_order_payments',
            'purchase_returns', 'purchase_return_items', 
            'stock_opname_items', 
            'invoice_items', 'invoice_tax', 'invoice_additional_costs', 'invoice_adjustments',
            'sales_return_items', 
            'client_ledgers', 'supplier_ledgers', 
            'loan_payments', 'depreciations', 
            'order_items', 'order_change_request_items',
            'payment_gateway_callbacks', 'announcement_client',

            // Level 3 (Header Transaksi)
            'manual_journals', 'audit_logs',
            'stock_opnames', 
            'purchase_order_adjustments', 
            'payments', 'sales_returns', 
            'expenses', 'loans', 
            'fixed_assets', 'equity_transactions', 'bank_reconciliations',
            'bulk_sales_payments', 'bulk_purchase_payments', 
            'order_change_requests', 
            
            // Level 2 (Transaksi Utama)
            'orders', 'purchase_orders', 'sales_invoices', 

            // Level 1 (Master Data)
            'products', 
            'categories', 'clients', 'suppliers', 'company_bank_accounts', 'payment_methods',
            'taxes', 'units', 'announcements',

            // Level 0 (System Core)
            'model_has_roles', 'model_has_permissions', 'role_has_permissions',
            'users', 'roles', 'permissions', 'settings', 'chart_of_accounts',
            
            // Counters
            'po_counters', 'invoice_counters', 'sales_order_counters', 
            'sales_return_counters', 'purchase_return_counters', 'bulk_payment_counters'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
    }
}