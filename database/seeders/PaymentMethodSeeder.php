<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        // HAPUS BARIS INI: PaymentMethod::truncate(); 
        // DatabaseSeeder sudah melakukannya.

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
            'type' => 'pending', 
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