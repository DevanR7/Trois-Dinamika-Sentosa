<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckClientLockStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $client = Auth::guard('client')->user();

        // Jika klien login dan statusnya terkunci
        if ($client && $client->is_locked) {

            // Logout klien
            Auth::guard('client')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Redirect ke halaman login dengan pesan error
            return redirect()->route('client.login')
                             ->with('error', 'Akun Anda saat ini sedang dikunci. Silakan hubungi admin.');
        }

        // Jika tidak terkunci atau tidak login, lanjutkan request
        return $next($request);
    }
}
