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
        $clientName = isset($row['client_name']) ? trim($row['client_name']) : '';

        if (empty($clientName)) {
            return null;
        }

        $email = isset($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)
            ? trim($row['email'])
            : null;

        $phone = isset($row['phone_number']) ? trim($row['phone_number']) : null;
        $address = isset($row['address']) ? trim($row['address']) : '-';
        $pic = isset($row['person_in_charge']) ? trim($row['person_in_charge']) : '-';

        if (empty($phone)) {
            if (preg_match('/\b08[0-9]{8,13}\b/', $address, $matches)) {
                $phone = $matches[0]; 
                $address = trim(str_replace($phone, '', $address)); 
            } else {
                $phone = '-';
            }
        }

        $client = Client::where('client_name', $clientName)->first();

        if ($client) {
            $client->update([
                'email'            => $email,
                'phone_number'     => $phone,
                'address'          => $address,
                'person_in_charge' => $pic,
            ]);
            return $client;
        } else {
            return new Client([
                'client_name'      => $clientName,
                'email'            => $email,
                'phone_number'     => $phone,
                'address'          => $address,
                'person_in_charge' => $pic,
                'password'         => Hash::make('12345678'), 
                'is_approved'      => false, 
                'google_id'        => null 
            ]);
        }
    }
}