<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{   
    public function __construct()
    {
        $this->middleware('can:view-suppliers')->only(['index', 'show']);
        $this->middleware('can:create-suppliers')->only(['create', 'store']);
        $this->middleware('can:edit-suppliers')->only(['edit', 'update']);
        $this->middleware('can:delete-suppliers')->only(['destroy', 'restore']);
    }
    
    public function index(Request $request): View
{
    $this->authorize('viewAny', Supplier::class);

    // Tambahkan withCount('products') agar bisa menampilkan jumlah produk
    $query = Supplier::withCount('products'); 

    if ($request->get('status') === 'deleted') {
        $query->onlyTrashed();
    }

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('supplier_name', 'like', "%{$search}%")
              ->orWhere('person_in_charge', 'like', "%{$search}%")
              ->orWhere('phone_number', 'like', "%{$search}%");
        });
    }

    $suppliers = $query->latest('supplier_id')->paginate(10);
    return view('admin.suppliers.index', compact('suppliers'));
}

    public function create(): View
    {
        $this->authorize('create', Supplier::class);
        return view('admin.suppliers.create');
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

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier baru berhasil ditambahkan.');
    }

    public function show(Supplier $supplier): View
    {
        $this->authorize('view', $supplier);

        $ledgers = $supplier->ledgers()
            ->latest('transaction_date')
            ->latest('ledger_id')
            ->paginate(10, ['*'], 'ledger_page');

        return view('admin.suppliers.show', compact('supplier', 'ledgers'));
    }

    public function edit(Supplier $supplier): View
    {
        $this->authorize('update', $supplier);
        return view('admin.suppliers.edit', compact('supplier'));
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

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier berhasil diupdate.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);

        if ($supplier->purchaseOrders()->exists()) {
            return back()->with('error', 'Supplier ini tidak bisa dihapus karena sudah memiliki data Pesanan Pembelian.');
        }

        $supplier->delete();

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier berhasil diarsipkan.');
    }

    public function restore(Supplier $supplier): RedirectResponse
    {
        $this->authorize('restore', $supplier);

        if ($supplier->trashed()) {
            $supplier->restore();
            return back()->with('success', 'Supplier ' . $supplier->supplier_name . ' telah dipulihkan.');
        }

        return back()->with('error', 'Supplier tidak terhapus.');
    }
}