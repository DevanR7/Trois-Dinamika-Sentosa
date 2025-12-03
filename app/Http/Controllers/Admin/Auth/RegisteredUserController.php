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
        // 1. SESUAIKAN ATURAN VALIDASI
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:'.User::class],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 2. BUAT USER DENGAN 'IS_APPROVED' = FALSE
        $user = User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_approved' => false, // <-- PENTING: Set ke false
        ]);

        // 3. BERIKAN ROLE DEFAULT UNTUK USER BARU
        // Misalnya, setiap user baru yang mendaftar akan diberi role 'sales'
        // Pastikan role 'sales' (huruf kecil) ada di seeder Anda
        $user->assignRole('sales');

        // event(new Registered($user)); // Kita tidak perlu ini jika user tidak login

        // 4. JANGAN LOGIN OTOMATIS
        // Auth::login($user); // <-- HAPUS ATAU KOMENTARI BARIS INI

        // 5. KEMBALIKAN KE HALAMAN LOGIN DENGAN PESAN
        return redirect()->route('admin.login')
            ->with('status', 'Pendaftaran berhasil. Akun Anda sedang menunggu persetujuan admin.');
    }
}