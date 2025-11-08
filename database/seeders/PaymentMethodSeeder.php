<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentMethod; // Pastikan Anda membuat Model ini

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus data lama agar tidak duplikat
        PaymentMethod::truncate();

        PaymentMethod::create([
            'name' => 'Cash',
            'type' => 'direct',
            'is_active' => true,
        ]);
        
        PaymentMethod::create([
            'name' => 'Manual Transfer',
            'type' => 'direct',
            'is_active' => true,
        ]);

        PaymentMethod::create([
            'name' => 'Giro / Cek',
            'type' => 'pending', // <-- Tipe 'pending' untuk Giro
            'is_active' => true,
        ]);
        
        PaymentMethod::create([
            'name' => 'Payment Gateway Midtrans',
            'type' => 'gateway',
            'is_active' => true,
        ]);
        
        PaymentMethod::create([
            'name' => 'Lainnya',
            'type' => 'direct',
            'is_active' => true,
        ]);
    }
}