<?php

namespace App\Http\Controllers\Client\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Exception;

/**
 * Menangani proses autentikasi (Login/Register) untuk 'client'
 * menggunakan Google Socialite (OAuth).
 */
class ClientGoogleController extends Controller
{
    /**
     * Mengarahkan (redirect) user ke halaman login Google.
     * Ini adalah langkah pertama dari alur OAuth untuk portal klien.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        try {
            // Bersihkan sesi lama untuk mencegah error CSRF / invalid state
            session()->forget('state');
            session()->forget('code_verifier');

            // Ambil konfigurasi Google khusus untuk 'client' (dari config/services.php)
            $config = config('services.google_client');

            // Buat provider Socialite secara manual dengan config kustom
            $google = Socialite::buildProvider(\Laravel\Socialite\Two\GoogleProvider::class, $config);

            // Opsi: Jika ada parameter 'prompt=select_account', paksa Google untuk
            // menampilkan pilihan akun (berguna untuk ganti akun).
            if ($request->get('prompt') === 'select_account') {
                $google->with(['prompt' => 'select_account']);
            }

            // Arahkan user ke halaman autentikasi Google
            return $google->redirect();

        } catch (Exception $e) {
            // Catat error jika proses redirect gagal
            Log::error('Google redirect error (client)', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Kembalikan ke halaman login dengan pesan error
            return redirect()->route('client.login')->with('error', 'Gagal menghubungkan ke Google. Silakan coba lagi.');
        }
    }

    /**
     * Menangani data (callback) yang dikirimkan oleh Google setelah user
     * berhasil login di sana.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            // Gunakan konfigurasi client Google khusus (harus sama dengan di redirectToGoogle)
            $config = config('services.google_client');
            $google = Socialite::buildProvider(\Laravel\Socialite\Two\GoogleProvider::class, $config);

            // Ambil data user dari Google
            $googleUser = $google->user();

            // --- 1. Cari atau Buat Client ---

            // Cari client berdasarkan email, TERMASUK yang sudah di soft-delete
            $client = Client::where('email', $googleUser->getEmail())
                            ->withTrashed() // <-- Mencari di data yang 'deleted_at' != null
                            ->first();

            // Jika client tidak ditemukan (user baru), buat client baru
            if (!$client) {
                $client = Client::create([
                    'client_name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt(Str::random(12)), // Buat password acak
                    'is_approved' => false, // Akun baru harus menunggu persetujuan
                ]);
            }

            // --- 2. Validasi Status Client ---

            // Cek 1: Apakah akun di-soft-delete (dinonaktifkan)?
            // Pengecekan ini harus *sebelum* cek 'is_approved'.
            if ($client->trashed()) {
                // Jika akunnya dihapus, beri pesan error spesifik
                return redirect()->route('client.login')
                    ->with('error', 'Akun Anda telah dinonaktifkan atau dihapus. Silakan hubungi admin.');
            }

            // Cek 2: Apakah akun sudah disetujui (approved)?
            if (!$client->is_approved) {
                
                // Beri pesan berbeda jika ini adalah user yang baru saja dibuat
                if ($client->wasRecentlyCreated) {
                     return redirect()->route('client.login')
                         ->with('error', 'Akun Anda berhasil dibuat dan sedang menunggu persetujuan admin.');
                }
                
                // Pesan untuk user lama yang login lagi tapi masih belum di-approve
                return redirect()->route('client.login')
                    ->with('error', 'Akun Anda sedang dalam proses verifikasi admin.');
            }

            // --- 3. Login Berhasil ---

            // Jika lolos semua cek (aktif dan disetujui), login-kan client
            Auth::guard('client')->login($client, true); // 'true' untuk "remember me"
            
            // Arahkan ke halaman yang dituju atau ke dashboard client
            return redirect()->intended(route('client.dashboard'));

        } catch (Exception $e) {
            // Catat error jika proses callback gagal (misal: state tidak valid)
            Log::error('Google login error (client)', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Kembalikan ke login dengan pesan error umum
            return redirect()->route('client.login')
                ->with('error', 'Terjadi kesalahan saat login menggunakan Google. Silakan coba lagi.');
        }
    }
}