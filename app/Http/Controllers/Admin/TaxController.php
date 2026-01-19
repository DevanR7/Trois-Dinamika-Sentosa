<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class TaxController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-settings');
    }

    public function index(Request $request): View
    {
        $query = Tax::query();

        // Filter Sampah
        if ($request->get('status') === 'trash') {
            $query->onlyTrashed();
        }

        // Pencarian
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $taxes = $query->latest()->paginate(10)->appends($request->query());

        return view('admin.taxes.index', compact('taxes'));
    }

    public function create(): View
    {
        return view('admin.taxes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:taxes,name',
            'rate' => 'required|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        
        Tax::create($validated);

        return redirect()->route('admin.taxes.index')->with('success', 'Tarif pajak baru berhasil dibuat.');
    }

    public function edit(Tax $tax): View
    {
        return view('admin.taxes.edit', compact('tax'));
    }

    public function update(Request $request, Tax $tax): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('taxes')->ignore($tax->id)],
            'rate' => 'required|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        
        $tax->update($validated);

        return redirect()->route('admin.taxes.index')->with('success', 'Tarif pajak berhasil diupdate.');
    }

    // Soft Delete
    public function destroy(Tax $tax): RedirectResponse
    {
        // Cek apakah pajak ini dipakai di Invoice aktif?
        // Sebaiknya soft delete tetap diizinkan karena invoice menyimpan snapshot nilai pajak.
        $tax->delete();
        return redirect()->route('admin.taxes.index')->with('success', 'Tarif pajak dipindahkan ke sampah.');
    }

    // Restore
    public function restore($id): RedirectResponse
    {
        $tax = Tax::onlyTrashed()->findOrFail($id);
        $tax->restore();

        return redirect()->route('admin.taxes.index', ['status' => 'trash'])
            ->with('success', 'Tarif pajak berhasil dipulihkan.');
    }

    // Force Delete
    public function forceDelete($id): RedirectResponse
    {
        $tax = Tax::onlyTrashed()->findOrFail($id);
        
        // Cek relasi ke invoice_tax
        if ($tax->salesInvoices()->count() > 0) {
            return back()->with('error', 'Gagal: Pajak ini masih terikat dengan data Invoice historis. Tidak bisa dihapus permanen.');
        }

        $tax->forceDelete();

        return redirect()->route('admin.taxes.index', ['status' => 'trash'])
            ->with('success', 'Tarif pajak dihapus permanen.');
    }
}