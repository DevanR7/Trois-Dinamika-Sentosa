<?php

namespace App\Http\Controllers\Client\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Client;

class ResetPasswordController extends Controller
{
    /**
     * Path redirect setelah reset password (tidak digunakan)
     */
    protected $redirectTo = '/client/dashboard';

    /**
     * Menampilkan form reset password untuk klien
     */
    public function showResetForm(Request $request, $token = null): View
    {
        return view('client.auth.reset-password')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    /**
     * Menangani proses reset password untuk klien
     */
    public function reset(Request $request): RedirectResponse
    {
        // Validasi input
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Reset password menggunakan broker 'clients'
        $status = Password::broker('clients')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($client) use ($request) {
                // Update password dan reset remember token
                $client->forceFill([
                    'password' => Hash::make($request->password)
                ])->setRememberToken(Str::random(60));

                $client->save();

                event(new PasswordReset($client));
            }
        );

        // Handle response berdasarkan status
        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('client.login')
                             ->with('success', 'Password Anda telah berhasil direset. Silakan login dengan password baru Anda.');
        }

        return back()->withInput($request->only('email'))
                     ->withErrors(['email' => __($status)]);
    }
}