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

    public function index(Request $request): View
    {
        $query = Unit::query();

        if ($request->get('status') === 'trash') {
            $query->onlyTrashed();
        }

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $units = $query->latest('unit_id')->paginate(10)->appends($request->query());

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
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

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
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $unit->update($validated);

        return redirect()->route('admin.units.index')->with('success', 'Satuan berhasil diperbarui.');
    }

    // Soft Delete (Pindah ke Sampah)
    public function destroy(Unit $unit): RedirectResponse
    {
        // REVISI: Cek produk aktif DAN yang di sampah (withTrashed)
        if ($unit->products()->withTrashed()->count() > 0) {
             return back()->with('error', 'Gagal: Satuan ini digunakan oleh Produk (Aktif/Arsip). Non-aktifkan saja jika tidak ingin digunakan.');
        }

        $unit->delete();
        return redirect()->route('admin.units.index')->with('success', 'Satuan dipindahkan ke sampah.');
    }

    // Restore (Pulihkan dari Sampah)
    public function restore($id): RedirectResponse
    {
        $unit = Unit::onlyTrashed()->findOrFail($id);
        $unit->restore();

        return redirect()->route('admin.units.index', ['status' => 'trash'])
            ->with('success', 'Satuan berhasil dipulihkan.');
    }

    // Force Delete (Hapus Permanen)
    public function forceDelete($id): RedirectResponse
    {
        $unit = Unit::onlyTrashed()->findOrFail($id);
        
        // REVISI: Cek produk historis
        if ($unit->products()->withTrashed()->exists()) {
            return back()->with('error', 'Gagal: Satuan ini terikat dengan data produk historis. Tidak bisa dihapus permanen.');
        }

        $unit->forceDelete();

        return redirect()->route('admin.units.index', ['status' => 'trash'])
            ->with('success', 'Satuan dihapus permanen.');
    }
}