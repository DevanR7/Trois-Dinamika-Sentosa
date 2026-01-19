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

        if ($request->routeIs('client.logout')) {
            return $next($request);
        }

        if ($request->routeIs('client.profile.edit') || $request->routeIs('client.profile.update')) {
            return $next($request);
        }

        $requiredFields = ['client_name', 'address', 'phone_number', 'person_in_charge'];
        foreach ($requiredFields as $field) {
        if (empty($client->$field)) {
            return redirect()->route('client.profile.edit')
                ->with('info', 'Harap lengkapi informasi profil Anda (Nama, Alamat, Telepon, PIC) untuk melanjutkan.');
        }
    }

    return $next($request);
    }
}