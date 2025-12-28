<?php

namespace App\Http\Controllers\Client\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client; // Pastikan model Client di-import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Menampilkan halaman login.
     */
    public function create(): View
    {
        return view('client.auth.login');
    }

    /**
     * Menangani proses login dengan validasi status akun spesifik.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 1. Cari Client berdasarkan Email (Termasuk yang di-soft delete jika perlu logic restore, tapi disini kita anggap aktif saja)
        $client = Client::where('email', $request->email)->first();

        // 2. Cek apakah Email ditemukan
        if (! $client) {
            throw ValidationException::withMessages([
                'email' => 'Akun dengan email tersebut tidak ditemukan.',
            ]);
        }

        // 3. Cek Status: Terkunci (Locked)
        if ($client->is_locked) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Akun Anda telah dikunci karena alasan keamanan. Silakan hubungi admin.');
        }

        // 4. Cek Status: Belum Disetujui (Pending Approval)
        if (! $client->is_approved) {
            return back()
                ->withInput($request->only('email'))
                ->with('warning', 'Akun Anda sedang menunggu persetujuan Admin. Silakan cek kembali nanti.');
        }

        // 5. Cek Password & Login
        // Kita gunakan attempt() di sini karena status akun sudah aman
        if (! Auth::guard('client')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Password yang Anda masukkan salah.',
            ]);
        }

        // 6. Regenerasi Sesi & Redirect
        $request->session()->regenerate();

        return redirect()->intended(route('client.dashboard'));
    }

    /**
     * Logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('client.login');
    }
}