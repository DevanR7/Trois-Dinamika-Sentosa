<?php

namespace App\Http\Controllers\Client\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * Menampilkan halaman form lupa password untuk klien
     */
    public function showLinkRequestForm(): View
    {
        return view('client.auth.forgot-password');
    }

    /**
     * Proses pengiriman link reset password untuk klien
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Kirim link reset menggunakan broker 'clients'
        $status = Password::broker('clients')->sendResetLink(
            $request->only('email')
        );

        // Handle response berdasarkan status pengiriman
        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()->withInput($request->only('email'))
                     ->withErrors(['email' => __($status)]);
    }
}