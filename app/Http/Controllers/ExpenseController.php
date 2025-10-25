<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ExpenseController extends Controller
{
    // Kategori pengeluaran yang umum (bisa Anda sesuaikan)
    const EXPENSE_CATEGORIES = [
        'Gaji & Tunjangan' => 'Gaji & Tunjangan',
        'Listrik, Air, & Internet' => 'Listrik, Air, & Internet',
        'Sewa Kantor/Gudang' => 'Sewa Kantor/Gudang',
        'Transportasi & Bensin' => 'Transportasi & Bensin',
        'Perlengkapan Kantor (ATK)' => 'Perlengkapan Kantor (ATK)',
        'Biaya Pemasaran' => 'Biaya Pemasaran',
        'Biaya Perbaikan & Perawatan' => 'Biaya Perbaikan & Perawatan',
        'Lain-lain' => 'Lain-lain',
    ];

    /**
     * Menampilkan daftar pengeluaran.
     */
    public function index(Request $request): View
    {
        // $this->authorize('viewAny', Expense::class); // Aktifkan jika Anda membuat Policy

        $query = Expense::with('user');

        // Filter berdasarkan pencarian (deskripsi / kategori)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan rentang tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('expense_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('expense_date', '<=', $request->end_date);
        }

        $expenses = $query->latest('expense_date')->paginate(15)->appends($request->query());
        $totalExpenses = $query->sum('amount'); // Total dari hasil filter

        return view('expenses.index', compact('expenses', 'totalExpenses'));
    }

    /**
     * Menampilkan form untuk membuat pengeluaran baru.
     */
    public function create(): View
    {
        // $this->authorize('create', Expense::class);
        $categories = self::EXPENSE_CATEGORIES;
        return view('expenses.create', compact('categories'));
    }

    /**
     * Menyimpan pengeluaran baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        // $this->authorize('create', Expense::class);

        $validated = $request->validate([
            'expense_date' => 'required|date',
            'category' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'amount' => 'required|numeric|min:1',
        ]);

        Expense::create([
            'expense_date' => $validated['expense_date'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('expenses.index')->with('success', 'Data pengeluaran berhasil disimpan.');
    }

    /**
     * Menampilkan form untuk mengedit pengeluaran.
     */
    public function edit(Expense $expense): View
    {
        // $this->authorize('update', $expense);
        $categories = self::EXPENSE_CATEGORIES;
        return view('expenses.edit', compact('expense', 'categories'));
    }

    /**
     * Mengupdate data pengeluaran di database.
     */
    public function update(Request $request, Expense $expense): RedirectResponse
    {
        // $this->authorize('update', $expense);

        $validated = $request->validate([
            'expense_date' => 'required|date',
            'category' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'amount' => 'required|numeric|min:1',
        ]);

        $expense->update($validated);

        return redirect()->route('expenses.index')->with('success', 'Data pengeluaran berhasil diupdate.');
    }

    /**
     * Menghapus data pengeluaran dari database.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        // $this->authorize('delete', $expense);
        
        try {
            $expense->delete();
            return redirect()->route('expenses.index')->with('success', 'Data pengeluaran berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}