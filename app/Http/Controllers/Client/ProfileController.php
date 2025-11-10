<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    /**
     * Menampilkan form edit profil klien
     */
    public function edit(Request $request): View
    {
        return view('client.profile.edit', [
            'client' => $request->user('client')
        ]);
    }

    /**
     * Mengupdate data profil klien
     */
    public function update(Request $request): RedirectResponse
    {
        $client = $request->user('client');

        // Validasi input data
        $validated = $request->validate([
            'client_name' => 'required|string|max:150',
            'person_in_charge' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
            'current_password' => [
                'nullable', 
                'required_with:password',
                'current_password:client'
            ],
            'password' => [
                'nullable', 
                'confirmed',
                Password::defaults()
            ],
        ]);

        // Update informasi dasar
        $client->fill($request->except('password', 'password_confirmation', 'current_password'))->save();

        // Update password jika diisi
        if ($request->filled('password')) {
            $client->forceFill([
                'password' => Hash::make($request->password),
            ])->save();
        }

        return redirect()->route('client.profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }
}