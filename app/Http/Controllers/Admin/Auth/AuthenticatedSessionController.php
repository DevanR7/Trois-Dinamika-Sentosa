<?php

namespace App\Http\Controllers\Admin\Auth;

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
     * Menampilkan halaman/view formulir login.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('admin.auth.login');
    }

    /**
     * Menangani permintaan login yang masuk (memproses submit form).
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // 1. Menjalankan proses autentikasi (memvalidasi email, password, dll.)
        $request->authenticate();

        // 2. Mengambil data user yang berhasil login
        $user = Auth::user();

        // 3. Pengecekan role (peran) user
        $isAdmin = $user->hasRole(['admin', 'superadmin']);

        // 4. Bagian Kustom: Validasi Status Persetujuan (Approval) Akun
        // Jika user BELUM disetujui (is_approved == false) DAN user BUKAN admin/superadmin,
        // maka user akan otomatis di-logout kembali.
        if (!$user->is_approved && !$isAdmin) {
            
            // Dapatkan guard 'web' dan lakukan logout
            $guard = Auth::guard('web');
            $guard->logout();

            // Batalkan sesi (invalidate session)
            $request->session()->invalidate();

            // Buat ulang token sesi (untuk keamanan)
            $request->session()->regenerateToken();

            // Kembalikan ke halaman login dengan pesan error spesifik
            return redirect()->route('admin.login')
                ->with('error', 'Akun Anda sedang dalam proses verifikasi admin.');
        }
        
        // 5. Jika user lolos (sudah disetujui atau dia adalah admin):
        // Buat ulang ID sesi untuk mencegah session fixation.
        $request->session()->regenerate();

        // Arahkan user ke halaman yang dituju sebelumnya (intended) atau ke HOME.
        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Menghancurkan sesi (session) yang terautentikasi (proses Logout).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    // Langsung paksa ke halaman login admin
    return redirect()->route('admin.login'); 
    }
}