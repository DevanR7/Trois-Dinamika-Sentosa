<?php

namespace App\Http\Controllers\Client\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Controller ini menangani proses autentikasi (login & logout)
 * khusus untuk guard 'client'.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Menampilkan halaman/view formulir login untuk client.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('client.auth.login');
    }

    /**
     * Menangani permintaan login (submit form) untuk guard 'client'.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validasi input yang masuk (email dan password wajib diisi)
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 2. Mencoba melakukan autentikasi menggunakan guard 'client'
        // 'remember' digunakan untuk fitur "Ingat Saya" (opsional)
        if (Auth::guard('client')->attempt($credentials, $request->boolean('remember'))) {
            
            // Jika berhasil: Regenerasi session untuk keamanan (mencegah session fixation)
            $request->session()->regenerate();

            // Arahkan ke halaman dashboard client
            return redirect()->intended(route('client.dashboard'));
        }

        // 3. Jika autentikasi gagal:
        // Kembalikan ke halaman sebelumnya (login) dengan pesan error
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email'); // Mengembalikan input 'email' saja (password tidak)
    }

    /**
     * Menghancurkan sesi (logout) untuk guard 'client'.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        // 1. Logout user dari guard 'client'
        Auth::guard('client')->logout();

        // 2. Batalkan sesi (invalidate session)
        $request->session()->invalidate();

        // 3. Buat ulang token (regenerate token) untuk keamanan
        $request->session()->regenerateToken();

        // 4. Arahkan kembali ke halaman login client
        return redirect()->route('client.login');
    }
}