<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        PaymentMethod::truncate();

        // 1. CASH
        // Client: Tidak mungkin cash online (tapi jika ada fitur setor tunai, mungkin butuh bukti)
        // Internal: Bebas (langsung terima duit)
        PaymentMethod::create([
            'name' => 'Cash / Tunai',
            'type' => 'direct',
            'client_input_config' => 'none', 
            'client_status_default' => 'pending_verification', // Client setor tunai harus diverifikasi admin
            'internal_input_config' => 'none', // Admin terima tunai langsung lunas
            'internal_status_default' => 'completed',
            'is_active' => true,
        ]);
        
        // 2. TRANSFER BANK
        // Client: Wajib Upload Bukti
        // Internal: Cukup Referensi (opsional) atau None
        PaymentMethod::create([
            'name' => 'Transfer Bank Manual',
            'type' => 'direct',
            'client_input_config' => 'proof_only', 
            'client_status_default' => 'pending_verification',
            'internal_input_config' => 'none', // Admin bisa input tanpa bukti jika lihat mutasi
            'internal_status_default' => 'completed',
            'is_active' => true,
        ]);

        // 3. GIRO / CEK (SOLUSI ANDA)
        // Client: Wajib Foto Fisik Giro & No Giro -> Status Pending
        // Internal: Cukup No Giro (Referensi) -> Status Completed (atau pending clearance)
        PaymentMethod::create([
            'name' => 'Giro / Cek',
            'type' => 'pending', 
            'client_input_config' => 'proof_and_reference', 
            'client_status_default' => 'pending_verification',
            'internal_input_config' => 'reference_only', // Admin cukup input nomor giro
            'internal_status_default' => 'completed', // Atau 'pending_clearance' jika ingin flow kliring
            'is_active' => true,
        ]);
        
        // 4. PAYMENT GATEWAY
        PaymentMethod::create([
            'name' => 'Payment Gateway Midtrans',
            'type' => 'gateway',
            'client_input_config' => 'none', 
            'client_status_default' => 'pending_verification', // Nanti diupdate callback
            'internal_input_config' => 'none',
            'internal_status_default' => 'completed',
            'is_active' => true,
        ]);
    }
}