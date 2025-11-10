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
     * Menampilkan halaman form registrasi klien
     */
    public function create(): View
    {
        return view('client.auth.register');
    }

    /**
     * Menangani proses registrasi akun klien baru
     */
    public function store(Request $request): RedirectResponse
    {
        // Validasi data input
        $request->validate([
            'client_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:'.Client::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Buat akun klien baru
        $client = Client::create([
            'client_name' => $request->client_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Trigger event registrasi
        event(new Registered($client));

        // Redirect ke login dengan pesan sukses
        return redirect()->route('client.login')
                         ->with('success', 'Registrasi berhasil! Akun Anda sedang menunggu persetujuan Admin.');
    }
}