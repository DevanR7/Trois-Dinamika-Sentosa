<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            return Socialite::driver('google')->redirect();

        } catch (Exception $e) {
            Log::error('Google redirect error', ['message' => $e->getMessage()]);
            return redirect()->route('admin.login')->with('error', 'Gagal menghubungkan ke Google. Silakan coba lagi.');
        }
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
                $user = User::create([
                    'full_name'   => $googleUser->name,
                    'email'       => $googleUser->email,
                    'google_id'   => $googleUser->id,
                    'username'    => explode('@', $googleUser->email)[0],
                    'password'    => Hash::make(uniqid()),
                    'is_approved' => false, 
                ]);

                $user->assignRole('sales');

                return redirect()->route('admin.login')
                    ->with('error', 'Akun Anda berhasil dibuat dan sedang menunggu persetujuan admin.');
            }

            if (empty($user->google_id)) {
                $user->google_id = $googleUser->id;
                $user->save();
            }

            $isAdmin = $user->hasRole(['admin', 'superadmin']);

            if (!$user->is_approved && !$isAdmin) {
                return redirect()->route('admin.login')
                    ->with('error', 'Akun Anda sedang dalam proses verifikasi admin.');
            }

            Auth::login($user, true);

            return redirect()->intended(route('admin.dashboard'));

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
