<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role; // Tambahkan ini
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
            
            // Cari user berdasarkan email
            $user = User::where('email', $googleUser->email)->first();

            // Jika user belum ada, buat baru
            if (!$user) {
                // Generate username unik sederhana
                $baseUsername = explode('@', $googleUser->email)[0];
                $uniqueUsername = $baseUsername . rand(100, 999);

                $user = User::create([
                    'full_name'   => $googleUser->name,
                    'email'       => $googleUser->email,
                    'google_id'   => $googleUser->id,
                    'username'    => $uniqueUsername,
                    'password'    => Hash::make(uniqid()), // Password random aman
                    'is_approved' => false, // Tetap butuh approval admin
                    'avatar_path' => $googleUser->avatar, // Opsional: simpan foto google
                ]);

                // REVISI: Safety Check Role
                if (Role::where('name', 'sales')->exists()) {
                    $user->assignRole('sales');
                } else {
                    Log::error("Role 'sales' tidak ditemukan saat user Google {$googleUser->email} mendaftar.");
                }

                return redirect()->route('admin.login')
                    ->with('error', 'Akun Anda berhasil dibuat dan sedang menunggu persetujuan admin.');
            }

            // Jika user sudah ada tapi belum link Google ID
            if (empty($user->google_id)) {
                $user->google_id = $googleUser->id;
                $user->save();
            }

            // Cek Approval & Role Admin
            $isAdmin = $user->hasRole(['admin', 'superadmin']);

            if (!$user->is_approved && !$isAdmin) {
                return redirect()->route('admin.login')
                    ->with('error', 'Akun Anda sedang dalam proses verifikasi admin.');
            }

            // Login sukses
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