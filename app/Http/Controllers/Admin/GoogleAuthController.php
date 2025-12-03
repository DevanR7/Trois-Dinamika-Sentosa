<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleAuthController extends Controller
{
    /**
     * Mengarahkan user ke halaman login Google.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        try {
            // Mengarahkan user ke halaman autentikasi Google
            return Socialite::driver('google')->redirect();

        } catch (Exception $e) {
            Log::error('Google redirect error', ['message' => $e->getMessage()]);
            return redirect()->route('admin.login')->with('error', 'Gagal menghubungkan ke Google. Silakan coba lagi.');
        }
    }

    /**
     * Menangani callback setelah user login dengan Google.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            // Ambil data user dari Google
            $googleUser = Socialite::driver('google')->user();

            // Cari user berdasarkan email
            $user = User::where('email', $googleUser->email)->first();

            // Jika user belum ada, buat baru
            if (!$user) {
                $user = User::create([
                    'full_name'   => $googleUser->name,
                    'email'       => $googleUser->email,
                    'google_id'   => $googleUser->id,
                    'username'    => explode('@', $googleUser->email)[0],
                    'password'    => Hash::make(uniqid()),
                    'is_approved' => false, // menunggu persetujuan admin
                ]);

                // Beri role default (ubah sesuai sistem kamu)
                $user->assignRole('sales');

                // Tidak langsung login, beri notifikasi
                return redirect()->route('admin.login')
                    ->with('error', 'Akun Anda berhasil dibuat dan sedang menunggu persetujuan admin.');
            }

            // Update google_id jika belum ada
            if (empty($user->google_id)) {
                $user->google_id = $googleUser->id;
                $user->save();
            }

            // Cek apakah user sudah disetujui atau admin
            $isAdmin = $user->hasRole(['admin', 'superadmin']);

            if (!$user->is_approved && !$isAdmin) {
                return redirect()->route('admin.login')
                    ->with('error', 'Akun Anda sedang dalam proses verifikasi admin.');
            }

            // Login user yang disetujui
            Auth::login($user, true);

            return redirect()->intended('dashboard');

        } catch (Exception $e) {
            Log::error('Google login error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.login')
                ->with('error', 'Login dengan Google gagal: ' . $e->getMessage());
        }
    }
}
