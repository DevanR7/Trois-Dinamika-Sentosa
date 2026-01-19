<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Spatie\Permission\Models\Role; // Tambahkan ini
use Illuminate\Support\Facades\Log; // Tambahkan ini

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('admin.auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validasi Input
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:'.User::class],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 2. Buat User (Default Belum Approved)
        $user = User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_approved' => false, // User harus menunggu persetujuan admin
        ]);

        // 3. Berikan Role Default (Safety Check)
        // REVISI: Cek apakah role tersedia untuk mencegah Crash sistem
        if (Role::where('name', 'sales')->exists()) {
            $user->assignRole('sales');
        } else {
            // Log error agar developer sadar, tapi biarkan user terbuat (tanpa role) agar tidak error 500
            Log::error("Role 'sales' tidak ditemukan saat user {$user->email} mendaftar. Harap jalankan seeder.");
        }

        // event(new Registered($user)); // Opsional, jika butuh email verifikasi

        // 4. Jangan Login Otomatis (Karena butuh approval)
        // Auth::login($user); 

        // 5. Redirect ke Login dengan Pesan
        return redirect()->route('admin.login')
            ->with('status', 'Pendaftaran berhasil. Akun Anda sedang menunggu persetujuan admin.');
    }
}