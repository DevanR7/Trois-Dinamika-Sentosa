<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct()
    {
        // Kosongkan constructor ini. 
        // Kita akan pindahkan pemeriksaan hak akses ke setiap method.
    }

    public function index(): View
    {
        // [AUTH] Panggil policy untuk memeriksa permission 'view-suppliers'
        $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::latest()->paginate(10);
        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        // [AUTH] Panggil policy untuk memeriksa permission 'create-suppliers'
        $this->authorize('create', Supplier::class);

        return view('suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        // [AUTH] Panggil policy untuk memeriksa permission 'create-suppliers'
        $this->authorize('create', Supplier::class);

        $request->validate([
            'supplier_name' => 'required|string|max:150|unique:suppliers,supplier_name',
            'person_in_charge' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        Supplier::create($request->all());
        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier): View
    {
        // [AUTH] Panggil policy untuk memeriksa permission 'edit-suppliers'
        $this->authorize('update', $supplier);

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        // [AUTH] Panggil policy untuk memeriksa permission 'edit-suppliers'
        $this->authorize('update', $supplier);

        $request->validate([
            'supplier_name' => 'required|string|max:150|unique:suppliers,supplier_name,' . $supplier->supplier_id . ',supplier_id',
            'person_in_charge' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $supplier->update($request->all());
        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil diupdate.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        // [AUTH] Panggil policy untuk memeriksa permission 'delete-suppliers'
        $this->authorize('delete', $supplier);

        // Tambahkan validasi agar supplier yang sudah punya PO tidak bisa dihapus
        if ($supplier->purchaseOrders()->exists()) {
            return back()->with('error', 'Supplier ini tidak bisa dihapus karena sudah memiliki data Pesanan Pembelian.');
        }

        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil dihapus.');
    }
}