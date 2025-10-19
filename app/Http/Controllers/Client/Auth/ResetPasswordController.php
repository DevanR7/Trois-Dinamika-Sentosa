<?php

namespace App\Http\Controllers\Client\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Client; // Pastikan model Client di-import

class ResetPasswordController extends Controller
{
    /**
     * Ke mana harus mengarahkan klien setelah password direset (SEKARANG TIDAK DIGUNAKAN).
     * Kita ganti dengan redirect ke halaman login.
     *
     * @var string
     */
    protected $redirectTo = '/client/dashboard'; 

    /**
     * Menampilkan halaman 'reset password' untuk klien.
     */
    public function showResetForm(Request $request, $token = null): View
    {
        // Mengarahkan ke view yang benar
        return view('client.auth.reset-password')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    /**
     * Menangani permintaan reset password.
     * (Logika baru untuk Laravel 10)
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Gunakan broker 'clients' yang sudah kita setup di config/auth.php
        $status = Password::broker('clients')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($client) use ($request) {
                // Callback ini akan dieksekusi jika reset berhasil
                // $client adalah instance App\Models\Client
                
                $client->forceFill([
                    'password' => Hash::make($request->password)
                ])->setRememberToken(Str::random(60)); // <-- Reset remember token

                $client->save();

                event(new PasswordReset($client));
            }
        );

        // Berikan respon berdasarkan status
        if ($status == Password::PASSWORD_RESET) {

            //// JANGAN login otomatis (kita komentari/nonaktifkan)
            // $client = Client::where('email', $request->email)->first();
            // if ($client) {
            //     Auth::guard('client')->login($client);
            // }
            
            // Arahkan kembali ke halaman login dengan notifikasi 'success'
            return redirect()->route('client.login')
                             ->with('success', 'Password Anda telah berhasil direset. Silakan login dengan password baru Anda.');
        }

        // Jika gagal (cth: token salah atau email tidak cocok)
        return back()->withInput($request->only('email'))
                     ->withErrors(['email' => __($status)]);
    }
}   