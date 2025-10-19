<?php

namespace App\Http\Controllers\Client\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Auth\Events\Registered;

class RegisteredClientController extends Controller
{
    /**
     * Menampilkan halaman registrasi.
     */
    public function create(): View
    {
        // View ini akan kita buat di langkah 4
        return view('client.auth.register');
    }

    /**
     * Menangani permintaan registrasi.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validasi input
        $request->validate([
            'client_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:'.Client::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 2. Buat Klien
        $client = Client::create([
            'client_name' => $request->client_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // 'is_approved' akan otomatis 'false' berdasarkan default database
        ]);

        // 3. Kirim event (berguna jika Anda ingin mengirim notifikasi)
        event(new Registered($client));

        // 4. Redirect kembali ke login dengan pesan sukses
        //    PENTING: Kita TIDAK login-kan klien
        return redirect()->route('client.login')
                         ->with('success', 'Registrasi berhasil! Akun Anda sedang menunggu persetujuan Admin.');
    }
}