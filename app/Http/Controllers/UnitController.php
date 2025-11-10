<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    /**
     * Konstruktor: menerapkan middleware otorisasi untuk akses ke pengaturan satuan.
     */
    public function __construct()
    {
        $this->middleware('can:manage-settings');
    }

    /**
     * Menampilkan daftar satuan dengan pagination.
     */
    public function index(): View
    {
        $units = Unit::latest('unit_id')->paginate(10);
        return view('units.index', compact('units'));
    }

    /**
     * Menampilkan formulir untuk membuat satuan baru.
     */
    public function create(): View
    {
        return view('units.create');
    }

    /**
     * Menyimpan satuan baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:units,name',
        ]);

        Unit::create($validated);

        return redirect()->route('units.index')->with('success', 'Satuan baru berhasil ditambahkan.');
    }

    /**
     * Menampilkan formulir edit untuk satuan yang ada.
     */
    public function edit(Unit $unit): View
    {
        return view('units.edit', compact('unit'));
    }

    /**
     * Memperbarui data satuan yang ada.
     */
    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('units')->ignore($unit->unit_id, 'unit_id')],
        ]);

        $unit->update($validated);

        return redirect()->route('units.index')->with('success', 'Satuan berhasil diperbarui.');
    }

    /**
     * Menghapus satuan (soft delete) dengan validasi integritas data.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->products()->exists()) {
            return back()->with('error', 'Satuan ini tidak bisa dihapus karena sedang digunakan oleh produk.');
        }

        $unit->delete();

        return redirect()->route('units.index')->with('success', 'Satuan berhasil dihapus.');
    }
}