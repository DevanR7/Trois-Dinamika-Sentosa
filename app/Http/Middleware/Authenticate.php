<?php

namespace App\Http\Middleware; // Pastikan namespace ini benar

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request; // Pastikan use statement ini ada

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    /** @var \Illuminate\Http\Request $request */
    protected function redirectTo(Request $request): ?string
    {
        // Jika request BUKAN mengharapkan JSON (misal dari API)
        if (! $request->expectsJson()) {

            // Cek apakah nama route saat ini dimulai dengan 'client.'
            if ($request->routeIs('client.*')) {
                // Jika ya, arahkan ke login klien
                // Pastikan route 'client.login' terdefinisi di routes/client.php
                return route('client.login');
            }

            // Jika tidak (diasumsikan route admin atau default), arahkan ke login admin
            // Pastikan route 'login' terdefinisi di routes/web.php atau auth.php
            return route('login');
        }

        // Jika request mengharapkan JSON, kembalikan null (akan melempar AuthenticationException)
        return null;
    }
}