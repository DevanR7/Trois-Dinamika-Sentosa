<?php

namespace App\Http\Controllers\Client\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password; // <-- Import fasad
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * Menampilkan halaman 'lupa password' untuk klien.
     */
    public function showLinkRequestForm(): View
    {
        // View ini sudah benar
        return view('client.auth.forgot-password');
    }

    /**
     * Menangani permintaan pengiriman link reset password.
     * (Menggantikan logic dari trait yang hilang)
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Gunakan broker 'clients' yang sudah kita setup di config/auth.php
        $status = Password::broker('clients')->sendResetLink(
            $request->only('email')
        );

        // Berikan respon berdasarkan status
        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()->withInput($request->only('email'))
                     ->withErrors(['email' => __($status)]);
    }
}