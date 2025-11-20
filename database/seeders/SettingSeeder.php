<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
// ✅ TAMBAHKAN IMPORT MODEL INI
use App\Models\ChartOfAccount;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Setting Identitas Perusahaan
        $companyData = [
            ['key' => 'company_name', 'value' => 'PT. Trois Dinamika Sentosa'],
            ['key' => 'company_owner', 'value' => 'Nama Pemilik'],
            ['key' => 'company_address', 'value' => 'Alamat Lengkap Perusahaan Anda'],
            ['key' => 'company_city_province', 'value' => 'Semarang, Jawa Tengah'],
            ['key' => 'company_npwp', 'value' => 'XX.XXX.XXX.X-XXX.XXX'],
            ['key' => 'company_phone', 'value' => '08XX-XXXX-XXXX'],
            ['key' => 'system_version', 'value' => '1.0.0'],
        ];

        foreach ($companyData as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        // 2. ✅ Setting Akun Default Akuntansi (OTOMATISASI)
        // Kita cari ID akun berdasarkan Nomor Akun yang sudah pasti ada di ChartOfAccountsSeeder
        
        $mapping = [
            'acct_default_ar'              => '1102',    // Piutang Usaha
            'acct_default_ap'              => '2101',    // Hutang Dagang
            'acct_default_inventory'       => '1105',    // Persediaan Barang
            'acct_default_sales_revenue'   => '4101',    // Pendapatan Penjualan
            'acct_default_cogs'            => '5101',    // HPP
            'acct_default_sales_return'    => '4102',    // Retur Penjualan
            'acct_default_purchase_return' => '1105',    // Retur Beli (Ke Persediaan)
            'acct_default_client_deposit'  => '2105',    // Deposit Klien
            'acct_default_supplier_deposit'=> '1106',    // Deposit Supplier
            'acct_default_inventory_adjustment' => '6201', // Beban Selisih Stok
            'acct_default_gateway'         => '1101.99', // Kas Midtrans
            'acct_default_retained_earnings' => '3103',  // Laba Ditahan
        ];

        foreach ($mapping as $settingKey => $accountNumber) {
            // Cari ID akun di database
            $account = ChartOfAccount::where('account_number', $accountNumber)->first();

            if ($account) {
                Setting::updateOrCreate(
                    ['key' => $settingKey],
                    ['value' => $account->account_id] // Simpan ID-nya
                );
            }
        }
    }
}