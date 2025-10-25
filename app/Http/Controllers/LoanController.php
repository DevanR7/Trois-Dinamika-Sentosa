<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class LoanController extends Controller
{
    /**
     * Menampilkan daftar pinjaman.
     */
    public function index(Request $request): View
    {
        // $this->authorize('viewAny', Loan::class);
        $query = Loan::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $loans = $query->latest('loan_date')->paginate(15)->appends($request->query());

        return view('loans.index', compact('loans'));
    }

    /**
     * Menampilkan form untuk membuat pinjaman baru.
     */
    public function create(): View
    {
        // $this->authorize('create', Loan::class);
        return view('loans.create');
    }

    /**
     * Menyimpan pinjaman baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        // $this->authorize('create', Loan::class);

        $validated = $request->validate([
            'lender_name' => 'required|string|max:255',
            'loan_date' => 'required|date',
            'principal_amount' => 'required|numeric|min:1',
            'description' => 'nullable|string',
        ]);

        Loan::create([
            'lender_name' => $validated['lender_name'],
            'loan_date' => $validated['loan_date'],
            'principal_amount' => $validated['principal_amount'],
            // Penting: Sisa pokok = Jumlah pokok saat dibuat
            'remaining_balance' => $validated['principal_amount'], 
            'description' => $validated['description'],
            'status' => 'active',
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('loans.index')->with('success', 'Data pinjaman baru berhasil disimpan.');
    }

    /**
     * Menampilkan detail pinjaman (DAN riwayat pembayarannya).
     */
    public function show(Loan $loan): View
    {
        // $this->authorize('view', $loan);
        
        // Load relasi pembayaran beserta user yang mencatat pembayaran
        $loan->load('payments.user'); 
        
        return view('loans.show', compact('loan'));
    }

    /**
     * Menampilkan form untuk mengedit pinjaman.
     */
    public function edit(Loan $loan): View
    {
        // $this->authorize('update', $loan);
        return view('loans.edit', compact('loan'));
    }

    /**
     * Mengupdate data pinjaman di database.
     */
    public function update(Request $request, Loan $loan): RedirectResponse
    {
        // $this->authorize('update', $loan);
        
        // Hanya izinkan edit jika belum ada pembayaran
        if ($loan->payments()->exists()) {
            return back()->with('error', 'Pinjaman ini tidak bisa diedit karena sudah memiliki riwayat pembayaran.');
        }

        $validated = $request->validate([
            'lender_name' => 'required|string|max:255',
            'loan_date' => 'required|date',
            'principal_amount' => 'required|numeric|min:1',
            'description' => 'nullable|string',
        ]);

        $loan->update([
            'lender_name' => $validated['lender_name'],
            'loan_date' => $validated['loan_date'],
            'principal_amount' => $validated['principal_amount'],
            'remaining_balance' => $validated['principal_amount'], // Update sisa pokok juga
            'description' => $validated['description'],
        ]);

        return redirect()->route('loans.index')->with('success', 'Data pinjaman berhasil diupdate.');
    }

    /**
     * Menghapus pinjaman (hanya jika belum ada pembayaran).
     */
    public function destroy(Loan $loan): RedirectResponse
    {
        // $this->authorize('delete', $loan);
        
        // Hanya izinkan hapus jika belum ada pembayaran
        if ($loan->payments()->exists()) {
            return back()->with('error', 'Pinjaman ini tidak bisa dihapus karena sudah memiliki riwayat pembayaran.');
        }
        
        try {
            $loan->delete();
            return redirect()->route('loans.index')->with('success', 'Data pinjaman berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}