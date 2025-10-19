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

        if (!$client) {
            return $next($request);
        }

        $requiredFields = ['client_name', 'address', 'phone_number', 'person_in_charge'];

        foreach ($requiredFields as $field) {
            $isProfileRoute = $request->routeIs('client.profile.edit') || $request->routeIs('client.profile.update');

            // Ambil nilai field saat ini
            $currentValue = $client->$field ?? null; // Gunakan null coalescing operator

            // Cek apakah dianggap kosong
            $isValueEmpty = empty($currentValue);

            // Kondisi yang menyebabkan redirect
            $shouldRedirect = $isValueEmpty && !$isProfileRoute;

            // Jika HARUS redirect, TAMPILKAN DEBUG dan hentikan
            if ($shouldRedirect) {
                dd(
                    "REDIRECTING! Check failed ON THIS FIELD:",
                    "Field:", $field, // Field yang *sebenarnya* menyebabkan masalah
                    "Value:", $currentValue,
                    "Is considered empty?", $isValueEmpty,
                    "Is Profile Route?", $isProfileRoute,
                    "Current Route:", $request->route()->getName()
                );

                // Baris redirect asli (sekarang tidak akan tercapai jika dd() aktif)
                return redirect()->route('client.profile.edit')
                    ->with('info', 'Harap lengkapi informasi profil Anda (Nama, Alamat, Telepon, PIC) untuk melanjutkan.');
            }
        }

        // Jika lolos loop tanpa redirect
        return $next($request);
    }
}