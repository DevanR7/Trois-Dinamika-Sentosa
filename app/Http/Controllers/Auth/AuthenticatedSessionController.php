<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // 1. Autentikasi user (cek username/password)
        $request->authenticate();

        // --- MULAI KODE BARU UNTUK CEK APPROVAL ---

        // 2. Ambil user yang baru saja login
        $user = Auth::user(); 
        
        // 3. Cek apakah user adalah 'admin' atau 'superadmin' (GUNAKAN HURUF KECIL)
        $isAdmin = $user->hasRole(['admin', 'superadmin']);

        // 4. Jika user BELUM disetujui DAN BUKAN admin/superadmin
        if (!$user->is_approved && !$isAdmin) {
            
            // Dapatkan guard 'web' (default untuk user)
            $guard = Auth::guard('web');
            
            // Logout user tersebut
            $guard->logout();

            // Invalidate sesi agar tidak bisa dipakai
            $request->session()->invalidate();

            // Regenerate token untuk keamanan
            $request->session()->regenerateToken();

            // Kembalikan ke halaman login dengan pesan error
            return redirect()->route('login')
                ->with('error', 'Akun Anda sedang dalam proses verifikasi admin.');
        }
        
        // --- SELESAI KODE BARU ---

        // 5. Jika lolos (disetujui ATAU admin), lanjutkan proses login
        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}