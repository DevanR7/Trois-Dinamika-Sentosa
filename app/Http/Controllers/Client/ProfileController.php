<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('client.profile.edit', [
            'client' => $request->user('client')
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $client = $request->user('client');

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

        $client->fill($request->except('password', 'password_confirmation', 'current_password'))->save();

        if ($request->filled('password')) {
            $client->forceFill([
                'password' => Hash::make($request->password),
            ])->save();
        }

        return redirect()->route('client.profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }
}