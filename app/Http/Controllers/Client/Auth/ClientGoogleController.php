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

class ClientGoogleController extends Controller
{
    /**
     * Redirect user ke halaman login Google (untuk portal klien).
     */
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        try {
            // Bersihkan sesi lama untuk mencegah error CSRF / invalid state
            session()->forget('state');
            session()->forget('code_verifier');

            // Ambil konfigurasi client Google untuk klien
            $config = config('services.google_client');

            $google = Socialite::buildProvider(\Laravel\Socialite\Two\GoogleProvider::class, $config);

            // Jika ingin memaksa pemilihan akun
            if ($request->get('prompt') === 'select_account') {
                $google->with(['prompt' => 'select_account']);
            }

            return $google->redirect();

        } catch (Exception $e) {
            Log::error('Google redirect error (client)', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('client.login')->with('error', 'Gagal menghubungkan ke Google. Silakan coba lagi.');
        }
    }

    /**
     * Tangani callback dari Google setelah user login.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            // Gunakan konfigurasi client Google khusus klien
            $config = config('services.google_client');
            $google = Socialite::buildProvider(\Laravel\Socialite\Two\GoogleProvider::class, $config);

            $googleUser = $google->user();

            // Cari client berdasarkan email
            $client = Client::where('email', $googleUser->getEmail())->first();

            // Jika belum ada, buat user baru (belum disetujui admin)
            if (!$client) {
                $client = Client::create([
                    'client_name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt(Str::random(12)),
                    'is_approved' => false, // Belum disetujui admin
                ]);
            }

            // Jika akun belum disetujui
            if (!$client->is_approved) {
                return redirect()->route('client.login')
                    ->with('error', 'Akun Anda sedang dalam proses verifikasi admin.');
            }

            // Jika sudah disetujui, login dan arahkan ke dashboard
            Auth::guard('client')->login($client, true);
            return redirect()->intended(route('client.dashboard'));

        } catch (Exception $e) {
            Log::error('Google login error (client)', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('client.login')
                ->with('error', 'Terjadi kesalahan saat login menggunakan Google. Silakan coba lagi.');
        }
    }
}
