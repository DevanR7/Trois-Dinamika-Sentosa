<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
     public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // --- INI ADALAH LOGIKA BARU YANG PENTING ---
                
                // Jika yang sudah login adalah 'client', arahkan ke dashboard klien.
                if ($guard === 'client') {
                    return redirect()->route('client.dashboard');
                }

                // Jika tidak (guard adalah 'web' atau null), arahkan ke dashboard utama.
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
