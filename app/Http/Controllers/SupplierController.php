<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function __construct()
    {
        // Kosongkan constructor ini. 
        // Kita akan pindahkan pemeriksaan hak akses ke setiap method.
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Supplier::class);

        $query = Supplier::query();

        // Logika untuk melihat arsip (data terhapus)
        if ($request->get('status') === 'deleted') {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('supplier_name', 'like', "%{$search}%")
                  ->orWhere('person_in_charge', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->latest('supplier_id')->paginate(10);
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
        $this->authorize('create', Supplier::class);

        $request->validate([
            'supplier_name' => 'required|string|max:150|unique:suppliers,supplier_name',
            'person_in_charge' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'npwp' => ['nullable', 'string', 'max:30', Rule::unique('suppliers')],
            'bank_name' => 'nullable|string|max:50',
            'account_number' => 'nullable|string|max:50',
        ]);

        Supplier::create($request->all());
        return redirect()->route('suppliers.index')->with('success', 'Supplier baru berhasil ditambahkan.');
    }

    public function show(Supplier $supplier): View
    {
        // [AUTH] Panggil policy untuk memeriksa permission 'view-suppliers'
        $this->authorize('view', $supplier);

        // Load relasi ledgers dengan paginasi, urutkan dari yg terbaru
        $ledgers = $supplier->ledgers()
                         ->latest('transaction_date')
                         ->latest('ledger_id') // Urutan kedua
                         ->paginate(10, ['*'], 'ledger_page');
        
        return view('suppliers.show', compact('supplier', 'ledgers'));
    }

    public function edit(Supplier $supplier): View
    {
        // [AUTH] Panggil policy untuk memeriksa permission 'edit-suppliers'
        $this->authorize('update', $supplier);

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);

        $request->validate([
            'supplier_name' => ['required', 'string', 'max:150', Rule::unique('suppliers')->ignore($supplier->supplier_id, 'supplier_id')],
            'person_in_charge' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'npwp' => ['nullable', 'string', 'max:30', Rule::unique('suppliers')->ignore($supplier->supplier_id, 'supplier_id')],
            'bank_name' => 'nullable|string|max:50',
            'account_number' => 'nullable|string|max:50',
        ]);

        $supplier->update($request->all());
        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil diupdate.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);

        if ($supplier->purchaseOrders()->exists()) {
            return back()->with('error', 'Supplier ini tidak bisa dihapus karena sudah memiliki data Pesanan Pembelian.');
        }

        $supplier->delete(); // Ini akan soft delete
        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil diarsipkan.'); // Ganti pesan
    }

    public function restore(Supplier $supplier): RedirectResponse
    {
        $this->authorize('restore', $supplier); // Asumsi Anda punya policy 'restore'

        if ($supplier->trashed()) {
            $supplier->restore();
            return back()->with('success', 'Supplier ' . $supplier->supplier_name . ' telah dipulihkan.');
        }
        return back()->with('error', 'Supplier tidak terhapus.');
    }
}