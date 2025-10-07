<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
             $googleUser = Socialite::driver('google')->user();

            // Cari user berdasarkan google_id, atau buat user baru jika tidak ada
            $user = User::updateOrCreate(
                [
                    'google_id' => $googleUser->id,
                ],
                [
                    'full_name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'username' => explode('@', $googleUser->email)[0],
                    'password' => Hash::make(uniqid()), // Buat password acak karena tidak akan dipakai
                ]
            );

            // Jika user ini baru dibuat, berikan role default (misal: 'sales')
            if ($user->wasRecentlyCreated) {
                $user->assignRole('sales'); // Sesuaikan dengan sistem role Spatie Anda
            }

            Auth::login($user);

            return redirect()->intended('dashboard');

        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Login dengan Google gagal: ' . $e->getMessage());
        }
    }
}