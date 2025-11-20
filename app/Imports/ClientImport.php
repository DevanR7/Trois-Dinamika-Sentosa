<?php

namespace App\Imports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;

class ClientImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // 1. Handle Nama Klien (Jika kosong, buat nama dummy unik)
        $clientName = isset($row['nama_klien']) && trim($row['nama_klien']) !== ''
            ? $row['nama_klien']
            : 'Klien Tanpa Nama - ' . time() . rand(10,99);

        // 2. Handle Email (Validasi format, jika salah/kosong jadi null)
        $email = isset($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)
            ? $row['email']
            : null;

        // 3. Handle Data Opsional (Isi '-' jika kosong agar rapi)
        $phone = $row['no_telepon'] ?? '-';
        $address = $row['alamat'] ?? '-';
        $pic = $row['pic'] ?? '-';

        // 4. Cek apakah klien sudah ada (berdasarkan Nama)
        $client = Client::where('client_name', $clientName)->first();

        if ($client) {
            // --- UPDATE DATA KLIEN LAMA ---
            // Kita update info kontak, TAPI JANGAN ubah passwordnya
            $client->update([
                'email'            => $email, // Update email jika ada di excel
                'phone_number'     => $phone,
                'address'          => $address,
                'person_in_charge' => $pic,
            ]);
            
            return $client;
        } else {
            // --- BUAT KLIEN BARU ---
            return new Client([
                'client_name'      => $clientName,
                'email'            => $email,
                'phone_number'     => $phone,
                'address'          => $address,
                'person_in_charge' => $pic,
                'password'         => Hash::make('password123'), // Default password
                'is_approved'      => true, // Langsung setujui
            ]);
        }
    }
}