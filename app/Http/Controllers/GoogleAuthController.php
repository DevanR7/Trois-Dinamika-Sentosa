<?php

namespace App\Http\Controllers;

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
    public function redirectToGoogle(): RedirectResponse
    {
        try {
            // Pecah menjadi dua baris
            $googleDriver = Socialite::driver('google');

            // ✅ TAMBAHKAN PETUNJUK INI untuk VS Code
            /** @var \Laravel\Socialite\Two\GoogleProvider $googleDriver */

            // Sekarang panggil method-nya
            return $googleDriver->with(['prompt' => 'select_account']) 
                               ->redirect();

        } catch (Exception $e) {
            Log::error('Google redirect error (admin)', ['message' => $e->getMessage()]);
            return redirect()->route('login')->with('error', 'Gagal menghubungkan ke Google. Silakan coba lagi.');
        }
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // 1. Cari user berdasarkan email
            $user = User::where('email', $googleUser->email)->first();

            // 2. Jika user TIDAK ADA (pendaftaran baru)
            if (!$user) {
                $user = User::create([
                    'full_name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'username' => explode('@', $googleUser->email)[0], // Pastikan ini unik, mungkin perlu logic tambahan
                    'password' => Hash::make(uniqid()),
                    'is_approved' => false, // <-- PENTING: Set ke false
                ]);

                // Beri role default (misal: 'sales')
                $user->assignRole('sales'); // Sesuaikan dengan role default Anda

                // Jangan login, kembalikan ke halaman login dengan pesan
                return redirect()->route('login')
                    ->with('error', 'Akun Anda berhasil dibuat dan sedang menunggu persetujuan admin.');
            }

            // 3. Jika user ADA (login)
            
            // Update google_id jika belum ada (misal daftar via form, login via google)
            if (empty($user->google_id)) {
                $user->google_id = $googleUser->id;
                $user->save();
            }

            // 4. INI KUNCINYA: Cek approval ATAU role Admin
            // Sesuaikan nama role 'Admin', 'Superadmin' dengan sistem Anda
            $isAdmin = $user->hasRole(['admin', 'superadmin']); 

            if (!$user->is_approved && !$isAdmin) {
                // Jika belum disetujui DAN bukan admin, tolak login
                return redirect()->route('login')
                    ->with('error', 'Akun Anda sedang dalam proses verifikasi admin.');
            }

            // 5. Jika disetujui ATAU dia adalah Admin, loginkan
            Auth::login($user, true); // Tambahkan 'true' untuk "remember me"
            return redirect()->intended('dashboard');

        } catch (Exception $e) {
            Log::error('Google login error (admin)', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('login')
                ->with('error', 'Login dengan Google gagal: ' . $e->getMessage());
        }
    }
}