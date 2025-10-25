<?php

namespace App\Http\Controllers;

use App\Models\EquityTransaction; // Pastikan Model di-import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class EquityTransactionController extends Controller
{
    // Definisikan tipe transaksi agar konsisten
    const TRANSACTION_TYPES = [
        'investment' => 'Setoran Modal',
        'drawing' => 'Penarikan Modal (Prive)',
    ];

    /**
     * Menampilkan daftar semua transaksi modal.
     */
    public function index(Request $request): View
    {
        // $this->authorize('viewAny', EquityTransaction::class);
        $query = EquityTransaction::with('user');

        // Filter Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }
        // Filter Tipe
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->latest('transaction_date')->paginate(15)->appends($request->query());

        // Hitung total untuk summary
        $queryTotals = clone $query; // Clone query sebelum di-paginate
        $totalInvestment = $queryTotals->where('type', 'investment')->sum('amount');
        
        $queryTotals = clone $query; // Reset clone
        $totalDrawing = $queryTotals->where('type', 'drawing')->sum('amount');

        $netModal = $totalInvestment - $totalDrawing;

        return view('equity_transactions.index', compact(
            'transactions', 
            'totalInvestment', 
            'totalDrawing', 
            'netModal'
        ));
    }

    /**
     * Menampilkan form untuk membuat transaksi baru.
     */
    public function create(): View
    {
        // $this->authorize('create', EquityTransaction::class);
        $types = self::TRANSACTION_TYPES;
        return view('equity_transactions.create', compact('types'));
    }

    /**
     * Menyimpan transaksi baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        // $this->authorize('create', EquityTransaction::class);

        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'type' => 'required|in:investment,drawing', // Pastikan tipenya valid
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
        ]);

        EquityTransaction::create([
            'transaction_date' => $validated['transaction_date'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('equity-transactions.index')->with('success', 'Transaksi modal berhasil dicatat.');
    }

    /**
     * Menampilkan form untuk mengedit transaksi.
     */
    public function edit(EquityTransaction $equityTransaction): View
    {
        // $this->authorize('update', $equityTransaction);
        $types = self::TRANSACTION_TYPES;
        
        // Ganti nama variabel agar tidak bentrok dengan nama 'route resource'
        $transaction = $equityTransaction; 
        
        return view('equity_transactions.edit', compact('transaction', 'types'));
    }

    /**
     * Mengupdate data transaksi di database.
     */
    public function update(Request $request, EquityTransaction $equityTransaction): RedirectResponse
    {
        // $this->authorize('update', $equityTransaction);

        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'type' => 'required|in:investment,drawing',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
        ]);

        $equityTransaction->update($validated);

        return redirect()->route('equity-transactions.index')->with('success', 'Transaksi modal berhasil diupdate.');
    }

    /**
     * Menghapus data transaksi dari database.
     */
    public function destroy(EquityTransaction $equityTransaction): RedirectResponse
    {
        // $this->authorize('delete', $equityTransaction);
        
        try {
            $equityTransaction->delete();
            return redirect()->route('equity-transactions.index')->with('success', 'Transaksi modal berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}