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

        // Tentukan field mana saja yang wajib diisi
        $requiredFields = ['client_name', 'address', 'phone_number', 'person_in_charge'];

        foreach ($requiredFields as $field) {
            // Jika salah satu field kosong DAN klien tidak sedang mencoba mengakses halaman profil
            if (empty($client->$field) && !$request->routeIs('client.profile.*')) {
                // Paksa redirect ke halaman edit profil dengan pesan
                return redirect()->route('client.profile.edit')
                    ->with('info', 'Harap lengkapi informasi profil Anda untuk melanjutkan.');
            }
        }

        // Jika semua field sudah terisi, izinkan akses
        return $next($request);
    }
}