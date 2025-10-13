<?php

namespace App\Http\Controllers\Client\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\RedirectResponse;

class ClientGoogleController extends Controller
{
    /**
     * Redirect ke halaman autentikasi Google.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        // Ambil konfigurasi dari config/services.php
        $config = config('services.google_client');
        
        // Buat provider Google dengan konfigurasi kustom
        return Socialite::buildProvider(\Laravel\Socialite\Two\GoogleProvider::class, $config)
            ->redirect();
    }

    /**
     * Menangani callback dari Google.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            // Ambil konfigurasi
            $config = config('services.google_client');
            
            // Ambil data user dari Google menggunakan konfigurasi kustom
            $googleUser = Socialite::buildProvider(\Laravel\Socialite\Two\GoogleProvider::class, $config)
                ->user();

            $client = Client::updateOrCreate(
                ['google_id' => $googleUser->id],
                [
                    'client_name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => Hash::make(uniqid()),
                ]
            );

            Auth::guard('client')->login($client);

            return redirect()->intended(route('client.dashboard'));

        } catch (\Exception $e) {
            return redirect()->route('client.login')->with('error', 'Login dengan Google gagal: ' . $e->getMessage());
        }
    }
}