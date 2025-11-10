<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TaxController extends Controller
{
    /**
     * Konstruktor: menerapkan middleware otorisasi untuk akses ke pengaturan pajak.
     */
    public function __construct()
    {
        $this->middleware('can:manage-settings');
    }

    /**
     * Menampilkan daftar tarif pajak dengan pagination.
     */
    public function index(): View
    {
        $taxes = Tax::latest()->paginate(10);
        return view('taxes.index', compact('taxes'));
    }

    /**
     * Menampilkan formulir untuk membuat tarif pajak baru.
     */
    public function create(): View
    {
        return view('taxes.create');
    }

    /**
     * Menyimpan tarif pajak baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Tax::create($validated);

        return redirect()->route('taxes.index')->with('success', 'Tarif pajak baru berhasil dibuat.');
    }

    /**
     * Menampilkan formulir edit untuk tarif pajak yang ada.
     */
    public function edit(Tax $tax): View
    {
        return view('taxes.edit', compact('tax'));
    }

    /**
     * Memperbarui data tarif pajak yang ada.
     */
    public function update(Request $request, Tax $tax): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $tax->update($validated);

        return redirect()->route('taxes.index')->with('success', 'Tarif pajak berhasil diupdate.');
    }

    /**
     * Menghapus tarif pajak (soft delete jika diaktifkan).
     */
    public function destroy(Tax $tax): RedirectResponse
    {
        $tax->delete();

        return redirect()->route('taxes.index')->with('success', 'Tarif pajak berhasil dihapus.');
    }
}