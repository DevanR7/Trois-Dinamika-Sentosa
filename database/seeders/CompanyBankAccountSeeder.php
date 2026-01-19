<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CompanyBankAccount;
use App\Models\ChartOfAccount;

class CompanyBankAccountSeeder extends Seeder
{
    public function run(): void
    {
        $kasTunai    = ChartOfAccount::where('account_number', '1101.01')->first();
        $bankBca     = ChartOfAccount::where('account_number', '1101.02')->first();
        $bankMandiri = ChartOfAccount::where('account_number', '1101.03')->first();
        $kasMidtrans = ChartOfAccount::where('account_number', '1101.99')->first();

        $accounts = [
            [
                'bank_name' => 'BCA',
                'account_name' => 'PT. USAHA JAYA',
                'account_number' => '1234567890',
                'chart_of_account_id' => $bankBca?->account_id, 
                'is_active' => true,
            ],
            [
                'bank_name' => 'Mandiri',
                'account_name' => 'PT. USAHA JAYA',
                'account_number' => '9876543210',
                'chart_of_account_id' => $bankMandiri?->account_id, 
                'is_active' => true,
            ],
            [
                'bank_name' => 'Kas Tunai',
                'account_name' => 'PT. USAHA JAYA',
                'account_number' => null,
                'chart_of_account_id' => $kasTunai?->account_id, 
                'is_active' => true,
            ],
            [
                'bank_name' => 'Midtrans Payment Gateway',
                'account_name' => 'TDS',
                'account_number' => null,
                'chart_of_account_id' => $kasMidtrans?->account_id, 
                'is_active' => true,
            ],
        ];

        foreach ($accounts as $account) {
            CompanyBankAccount::create($account);
        }
    }
}