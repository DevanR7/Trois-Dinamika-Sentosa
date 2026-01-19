<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\ChartOfAccount;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Profil Perusahaan
        $settings = [
            ['key' => 'company_name', 'value' => 'PT. Trois Dinamika Sentosa'],
            ['key' => 'company_owner', 'value' => 'Owner Name'],
            ['key' => 'company_address', 'value' => 'Jl. Contoh No. 123, Semarang'],
            ['key' => 'company_city_province', 'value' => 'Semarang, Jawa Tengah'],
            ['key' => 'company_npwp', 'value' => '01.234.567.8-123.000'],
            ['key' => 'company_phone', 'value' => '024-1234567'],
            ['key' => 'system_version', 'value' => '1.0.0'],
            ['key' => 'target_sales_monthly', 'value' => '500000000'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], ['value' => $s['value']]);
        }

        // 2. Mapping Akun Default (PENTING UNTUK CONTROLLER)
        $accountMapping = [
            'acct_default_ar'                => '1102', // Piutang Usaha
            'acct_default_ap'                => '2101', // Hutang Dagang
            'acct_default_inventory'         => '1105', // Persediaan
            'acct_default_sales_revenue'     => '4101', // Pendapatan
            'acct_default_cogs'              => '5101', // HPP
            'acct_default_sales_return'      => '4102', // Retur Jual
            'acct_default_purchase_return'   => '5102', // Retur Beli
            'acct_default_client_deposit'    => '2105', // Deposit Klien
            'acct_default_supplier_deposit'  => '1106', // Deposit Supplier
            'acct_default_inventory_adjustment' => '6201', // Selisih Stok
            'acct_default_gateway'           => '1101.99', // Midtrans
            'acct_default_retained_earnings' => '3103', // Laba Ditahan
        ];

        foreach ($accountMapping as $key => $accNumber) {
            $account = ChartOfAccount::where('account_number', $accNumber)->first();
            if ($account) {
                Setting::updateOrCreate(['key' => $key], ['value' => $account->account_id]);
            }
        }
    }
}