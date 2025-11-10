<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    /**
     * Konstruktor: menerapkan middleware otorisasi untuk akses ke pengaturan sistem.
     */
    public function __construct()
    {
        $this->middleware('can:manage-settings');
    }

    /**
     * Menampilkan halaman form pengaturan sistem.
     */
    public function index()
    {
        // Ambil semua pengaturan dan format sebagai array asosiatif [key => value]
        $settings = Setting::all()->pluck('value', 'key');

        return view('settings.index', compact('settings'));
    }

    /**
     * Memperbarui semua pengaturan berdasarkan input dari form.
     */
    public function update(Request $request): RedirectResponse
    {
        // Iterasi semua input kecuali token CSRF
        foreach ($request->except('_token') as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return back()->with('success', 'Pengaturan berhasil diperbarui.');
    }
}