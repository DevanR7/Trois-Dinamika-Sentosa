<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'company_name', 'value' => 'PT. Trois Dinamika Sentosa'],
            ['key' => 'company_owner', 'value' => 'Nama Pemilik'],
            ['key' => 'company_address', 'value' => 'Alamat Lengkap Perusahaan Anda'],
            ['key' => 'company_city_province', 'value' => 'Semarang, Jawa Tengah'],
            ['key' => 'company_npwp', 'value' => 'XX.XXX.XXX.X-XXX.XXX'],
            ['key' => 'company_phone', 'value' => '08XX-XXXX-XXXX'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}