<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash; // ✅ ini wajib ditambahkan

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('clients')->insert([
            [
                'client_name'      => 'Toko Besi Maju Mundur',
                'person_in_charge' => 'Pak Eko',
                'address'          => 'Jl. Pahlawan No. 10, Semarang',
                'phone_number'     => '085678901234',
                'email'            => 'ekomaju@example.com',
                'password'         => Hash::make('password'), // ✅ sudah bisa dipakai
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'client_name'      => 'Toko Bangunan Kokoh',
                'person_in_charge' => 'Ibu Wati',
                'address'          => 'Jl. Gajah Mada No. 25, Semarang',
                'phone_number'     => '085712345678',
                'email'            => 'watikokoh@example.com',
                'password'         => Hash::make('password'),
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ]);
    }
}
