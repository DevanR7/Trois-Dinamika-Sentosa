<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-settings');
    }

    public function index(): View
    {
        $units = Unit::latest('unit_id')->paginate(10);
        return view('admin.units.index', compact('units'));
    }

    public function create(): View
    {
        return view('admin.units.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:units,name',
        ]);

        Unit::create($validated);

        return redirect()->route('admin.units.index')->with('success', 'Satuan baru berhasil ditambahkan.');
    }

    public function edit(Unit $unit): View
    {
        return view('admin.units.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('units')->ignore($unit->unit_id, 'unit_id')],
        ]);

        $unit->update($validated);

        return redirect()->route('admin.units.index')->with('success', 'Satuan berhasil diperbarui.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->products()->exists()) {
            return back()->with('error', 'Satuan ini tidak bisa dihapus karena sedang digunakan oleh produk.');
        }

        $unit->delete();

        return redirect()->route('admin.units.index')->with('success', 'Satuan berhasil dihapus.');
    }
}