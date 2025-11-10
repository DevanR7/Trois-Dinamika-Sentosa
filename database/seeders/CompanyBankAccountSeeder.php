<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyBankAccountSeeder extends Seeder
{
    /**
     * Jalankan seeder.
     */
    public function run(): void
    {
        DB::table('company_bank_accounts')->insert([
            [
                'bank_name' => 'BCA',
                'account_name' => 'PT. USAHA JAYA',
                'account_number' => '1234567890',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'bank_name' => 'Mandiri',
                'account_name' => 'PT. USAHA JAYA',
                'account_number' => '9876543210',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'bank_name' => 'Kas Tunai',
                'account_name' => 'PT. USAHA JAYA',
                'account_number' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
