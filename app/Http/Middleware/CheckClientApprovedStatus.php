<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckClientApprovedStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah klien sudah login DAN (tanda seru) belum disetujui
        if (Auth::guard('client')->check() && !Auth::guard('client')->user()->is_approved) {
            
            // Jika belum disetujui, paksa logout
            Auth::guard('client')->logout();

            // Hapus session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Redirect kembali ke halaman login dengan pesan error
            return redirect()->route('client.login')
                ->with('error', 'Akun Anda sedang ditinjau dan belum disetujui oleh Admin.');
        }

        // Jika disetujui, lanjutkan request
        return $next($request);
    }
}