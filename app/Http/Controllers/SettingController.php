<?php
namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    // Menampilkan halaman form pengaturan
    public function index()
    {
        // Ambil semua settings dan ubah menjadi format yang mudah diakses di view
        $settings = Setting::all()->pluck('value', 'key');
        return view('settings.index', compact('settings'));
    }

    // Menyimpan perubahan dari form
    public function update(Request $request): RedirectResponse
    {
        foreach ($request->except('_token') as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        return back()->with('success', 'Pengaturan berhasil diperbarui.');
    }
}