<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            // Cek apakah user sedang login menggunakan guard tersebut
            if (Auth::guard($guard)->check()) {
                
                // 1. Jika login sebagai CLIENT
                if ($guard === 'client') {
                    return redirect()->route('client.dashboard');
                }

                // 2. Jika login sebagai ADMIN (guard 'web' atau null)
                // Kita arahkan langsung ke admin dashboard
                if ($guard === 'web' || $guard === null) {
                    return redirect()->route('admin.dashboard');
                }
            }
        }

        return $next($request);
    }
}