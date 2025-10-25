<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class LoanPaymentController extends Controller
{
    /**
     * Menampilkan form untuk menambah pembayaran cicilan.
     * Route: loans/{loan}/payments/create
     */
    public function create(Loan $loan): View
    {
        // $this->authorize('create', [LoanPayment::class, $loan]);
        return view('loan_payments.create', compact('loan'));
    }

    /**
     * Menyimpan pembayaran cicilan baru.
     * Route: loans/{loan}/payments
     */
    public function store(Request $request, Loan $loan): RedirectResponse
    {
        // $this->authorize('create', [LoanPayment::class, $loan]);
        
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'principal_paid' => 'required|numeric|min:0',
            'interest_paid' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $totalPaid = $validated['principal_paid'] + $validated['interest_paid'];
        $sisaPokok = $loan->remaining_balance;

        // Validasi agar bayar pokok tidak melebihi sisa utang
        if ($validated['principal_paid'] > $sisaPokok) {
            return back()->with('error', 'Pembayaran pokok (Rp '.number_format($validated['principal_paid']).') melebihi sisa utang (Rp '.number_format($sisaPokok).').')->withInput();
        }
        
        // Validasi agar total bayar minimal 1
        if ($totalPaid <= 0) {
             return back()->with('error', 'Total pembayaran (Pokok + Bunga) harus lebih dari 0.')->withInput();
        }

        try {
            DB::beginTransaction();

            // 1. Catat pembayaran di tabel loan_payments
            $loan->payments()->create([
                'payment_date' => $validated['payment_date'],
                'principal_paid' => $validated['principal_paid'],
                'interest_paid' => $validated['interest_paid'],
                'total_paid' => $totalPaid,
                'notes' => $validated['notes'],
                'user_id' => Auth::id(),
            ]);

            // 2. Update sisa pokok di tabel loans
            $newRemainingBalance = $sisaPokok - $validated['principal_paid'];
            
            $loan->update([
                'remaining_balance' => $newRemainingBalance,
                'status' => ($newRemainingBalance <= 0) ? 'paid_off' : 'active' // Otomatis lunas jika sisa 0
            ]);

            DB::commit();

            return redirect()->route('loans.show', $loan)->with('success', 'Pembayaran cicilan berhasil dicatat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus data pembayaran cicilan (untuk koreksi).
     * Route: loans/{loan}/payments/{payment}
     */
    public function destroy(Loan $loan, LoanPayment $payment): RedirectResponse
    {
        // $this->authorize('delete', $payment);
        
        try {
            DB::beginTransaction();

            $principalToRestore = $payment->principal_paid;

            // 1. Kembalikan sisa pokok di tabel loans
            $loan->update([
                'remaining_balance' => $loan->remaining_balance + $principalToRestore,
                'status' => 'active' // Pasti jadi aktif lagi
            ]);

            // 2. Hapus data pembayaran
            $payment->delete();

            DB::commit();

            return redirect()->route('loans.show', $loan)->with('success', 'Data pembayaran berhasil dihapus (Rollback).');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus pembayaran: ' . $e->getMessage());
        }
    }
}