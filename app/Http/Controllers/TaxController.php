<?php

namespace App\Http\Controllers; // <-- Sudah diperbaiki

use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TaxController extends Controller
{

     /**
     * Menerapkan middleware Gate untuk semua method di controller ini.
     */
     public function __construct()
    {
        $this->middleware('can:manage-settings');
    }
    
    public function index(): View
    {
        $taxes = Tax::latest()->paginate(10);
        return view('taxes.index', compact('taxes'));
    }

    public function create(): View
    {
        return view('taxes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
        ]);
        $validated['is_active'] = $request->has('is_active');

        // Logika disederhanakan: langsung buat data baru
        Tax::create($validated);

        return redirect()->route('taxes.index')->with('success', 'Tarif pajak baru berhasil dibuat.');
    }

    public function edit(Tax $tax): View
    {
        return view('taxes.edit', compact('tax'));
    }

    public function update(Request $request, Tax $tax): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
        ]);
        $validated['is_active'] = $request->has('is_active');

        // Logika disederhanakan: langsung update data
        $tax->update($validated);

        return redirect()->route('taxes.index')->with('success', 'Tarif pajak berhasil diupdate.');
    }

    public function destroy(Tax $tax): RedirectResponse
    {
        $tax->delete();
        return redirect()->route('taxes.index')->with('success', 'Tarif pajak berhasil dihapus.');
    }
}