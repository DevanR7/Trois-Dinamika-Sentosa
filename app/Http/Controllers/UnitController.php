<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{   
    public function __construct()
    {
        // [PERBAIKAN] Menambahkan proteksi route sesuai seeder
        $this->middleware('can:manage-settings');
    }
    
    public function index(): View
    {
        $units = Unit::latest('unit_id')->paginate(10);
        return view('units.index', compact('units'));
    }

    public function create(): View
    {
        return view('units.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:units,name',
        ]);

        Unit::create($validated);
        return redirect()->route('units.index')->with('success', 'Satuan baru berhasil ditambahkan.');
    }

    public function edit(Unit $unit): View
    {
        return view('units.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('units')->ignore($unit->unit_id, 'unit_id')],
        ]);

        $unit->update($validated);
        return redirect()->route('units.index')->with('success', 'Satuan berhasil diperbarui.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        // Tambahkan validasi untuk mencegah penghapusan jika unit sudah digunakan
        if ($unit->products()->exists()) {
            return back()->with('error', 'Satuan ini tidak bisa dihapus karena sedang digunakan oleh produk.');
        }

        $unit->delete();
        return redirect()->route('units.index')->with('success', 'Satuan berhasil dihapus.');
    }
}