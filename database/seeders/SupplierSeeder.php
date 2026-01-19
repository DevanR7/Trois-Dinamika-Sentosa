<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        // ... (Data supplier sebelumnya tetap ada)

        // Hapus data lama jika perlu, atau gunakan updateOrInsert
        // Di sini saya gunakan insert ignore logic via array manual check atau biarkan nambah
        
        $suppliers = [
            [
                'supplier_name' => 'PT. Pemasok Jaya',
                'person_in_charge' => 'Bapak Budi',
                'phone_number' => '081234567890',
                'address' => 'Jalan Industri No. 1, Jakarta',
                'npwp' => '01.234.567.8-111.000',
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
            ],
            [
                'supplier_name' => 'CV. Mitra Kencana',
                'person_in_charge' => 'Ibu Siti',
                'phone_number' => '081298765432',
                'address' => 'Jalan Dagang No. 2, Surabaya',
                'npwp' => '02.345.678.9-222.000',
                'bank_name' => 'Mandiri',
                'account_number' => '0987654321',
            ],
            [
                'supplier_name' => 'BULL Central Distributor',
                'person_in_charge' => 'Budi Santoso (Sales Pusat)',
                'phone_number' => '021-555-9999',
                'address' => 'Jl. Teknik Industri No. 88, Jakarta',
                'npwp' => '03.456.789.0-333.000',
                'bank_name' => 'BRI',
                'account_number' => '1122334455',
            ],
            // DATA BARU DARI NOTA
            [
                'supplier_name' => 'CV. BUDI LUHUR',
                'person_in_charge' => 'Admin Penjualan',
                'phone_number' => '085866313945', // Dari Nota
                'address' => 'Jl. Magelang Purworejo KM10 RT001 RW002 Tempur Rejo, Tempuran, Kab Magelang',
                'npwp' => '082.174.014.6-524.000',
                'bank_name' => 'BCA',
                'account_number' => '344-970-7070',
            ]
        ];

        foreach ($suppliers as $data) {
            DB::table('suppliers')->updateOrInsert(
                ['supplier_name' => $data['supplier_name']],
                array_merge($data, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}