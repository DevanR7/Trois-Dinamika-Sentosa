<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientProfileIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $client = Auth::guard('client')->user();

        // 1. Jika tidak login, biarkan (akan di-handle middleware auth)
        if (!$client) {
            return $next($request);
        }

        // 2. Cek apakah user SUDAH di halaman profil. 
        //    Jika ya, biarkan mereka, agar mereka bisa mengisi form.
        if ($request->routeIs('client.profile.edit') || $request->routeIs('client.profile.update')) {
            return $next($request);
        }

        // 3. Daftar field yang wajib diisi
        $requiredFields = ['client_name', 'address', 'phone_number', 'person_in_charge'];

        // 4. Loop dan cek satu per satu
        foreach ($requiredFields as $field) {
            
            // Cek apakah nilai field kosong (null, "", 0, "0" dianggap empty)
            if (empty($client->$field)) {
                
                // HAPUS DEBUG DD() DARI SINI
                /*
                dd(
                    "REDIRECTING! Check failed ON THIS FIELD:",
                    "Field:", $field, 
                    "Value:", $client->$field,
                    "Is considered empty?", true,
                    "Is Profile Route?", false,
                    "Current Route:", $request->route()->getName()
                );
                */

                // 5. Jika ada 1 field saja yang kosong, LANGSUNG redirect
                return redirect()->route('client.profile.edit')
                    ->with('info', 'Harap lengkapi informasi profil Anda (Nama, Alamat, Telepon, PIC) untuk melanjutkan.');
            }
        }

        // 6. Jika semua field terisi (lolos loop), lanjutkan ke dashboard
        return $next($request);
    }
}