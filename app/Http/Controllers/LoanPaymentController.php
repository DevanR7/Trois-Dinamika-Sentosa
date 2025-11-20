<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\ChartOfAccount;
use App\Services\AccountingService;
use App\Traits\ValidatesAccountingPeriod;

class LoanPaymentController extends Controller
{   
    use ValidatesAccountingPeriod;

    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Menampilkan form untuk menambah pembayaran cicilan.
     * Route: loans/{loan}/payments/create
     */
    public function create(Loan $loan): View
    {
        // $this->authorize('create', [LoanPayment::class, $loan]);
        
        // ✅ Ambil akun Beban Bunga dari COA
        $expenseAccounts = ChartOfAccount::where('account_type', 'Beban')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
        
        // ✅ Ambil akun Sumber Dana (Kas/Bank) dari COA
        $cashAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();

        return view('loan_payments.create', compact('loan', 'expenseAccounts', 'cashAccounts'));
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
            // ✅ Validasi kolom baru
            'interest_expense_account_id' => 'required_with:interest_paid|nullable|exists:chart_of_accounts,account_id',
            'cash_bank_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);

        if ($this->isDateClosed($request->payment_date)) {
            return back()->with('error', 'Gagal: Tanggal pembayaran masuk periode tutup buku.')->withInput();
        }
        
        $totalPaid = $validated['principal_paid'] + $validated['interest_paid'];
        $sisaPokok = $loan->remaining_balance;
        
        if ($validated['principal_paid'] > $sisaPokok) {
            return back()->with('error', 'Pembayaran pokok (Rp '.number_format($validated['principal_paid']).') melebihi sisa utang (Rp '.number_format($sisaPokok).').')->withInput();
        }
        if ($totalPaid <= 0) {
             return back()->with('error', 'Total pembayaran (Pokok + Bunga) harus lebih dari 0.')->withInput();
        }
        if ($validated['interest_paid'] > 0 && empty($validated['interest_expense_account_id'])) {
             return back()->with('error', 'Akun Beban Bunga harus dipilih jika ada pembayaran bunga.')->withInput();
        }
        
        try {
            DB::beginTransaction();
            // 1. Catat pembayaran di tabel loan_payments
            $payment = $loan->payments()->create([
                'payment_date' => $validated['payment_date'],
                'principal_paid' => $validated['principal_paid'],
                'interest_paid' => $validated['interest_paid'],
                'total_paid' => $totalPaid,
                'notes' => $validated['notes'],
                'user_id' => Auth::id(),
                'interest_expense_account_id' => $validated['interest_expense_account_id'] ?? null,
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
            ]);
            
            // 2. Update sisa pokok di tabel loans
            $newRemainingBalance = $sisaPokok - $validated['principal_paid'];
            $loan->update([
                'remaining_balance' => $newRemainingBalance,
                'status' => ($newRemainingBalance <= 0) ? 'paid_off' : 'active'
            ]);

            // 3. Post Jurnal Akuntansi (Pembayaran Cicilan)
            $journalGroupId = "LOANPAY-" . $payment->payment_id;
            $description = "Bayar Cicilan Pinjaman: " . ($loan->lender_name);

            $debitEntries = [];
            // Debit 1: Akun Utang Pinjaman (sebesar bayar pokok)
            $debitEntries[] = [$loan->loan_account_id, $validated['principal_paid']];
            
            // Debit 2: Akun Beban Bunga (jika ada)
            if ($validated['interest_paid'] > 0) {
                $debitEntries[] = [$validated['interest_expense_account_id'], $validated['interest_paid']];
            }
            
            $creditEntries = [
                // Kredit: Akun Kas/Bank (sebesar total bayar)
                [$validated['cash_bank_account_id'], $totalPaid]
            ];

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['payment_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $payment,
                Auth::id() // Model referensi
            );

            DB::commit();
            return redirect()->route('loans.show', $loan)->with('success', 'Pembayaran cicilan berhasil dicatat dan dijurnal.');
            
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
        $journalGroupId = "LOANPAY-" . $payment->payment_id;
        if ($error = $this->checkTransactionLock($payment->payment_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus Pembayaran: " . $error);
        }
        
        // $this->authorize('delete', $payment);
        
        try {
            DB::beginTransaction();
            $principalToRestore = $payment->principal_paid;
            
            // 1. Kembalikan sisa pokok di tabel loans
            $loan->update([
                'remaining_balance' => $loan->remaining_balance + $principalToRestore,
                'status' => 'active'
            ]);
            
            // 2. Post Jurnal Reversal (Pembalikan)
            $journalGroupId = "LOANPAY-REVERSAL-" . $payment->payment_id;
            $description = "Reversal Bayar Cicilan: " . $loan->lender_name;

            $debitEntries = [
                // [Akun Kas/Bank, Jumlah] (Kas kembali)
                [$payment->cash_bank_account_id, $payment->total_paid]
            ];
            
            $creditEntries = [];
            // [Akun Utang, Jumlah] (Utang kembali)
            $creditEntries[] = [$loan->loan_account_id, $payment->principal_paid];
            // [Akun Beban Bunga, Jumlah] (Beban Bunga batal)
            if ($payment->interest_paid > 0) {
                $creditEntries[] = [$payment->interest_expense_account_id, $payment->interest_paid];
            }
            
            $this->accountingService->postJournal(
                $journalGroupId,
                now(), // Tanggal reversal
                $description,
                $debitEntries,
                $creditEntries,
                $payment,
                Auth::id()
            );

            // 3. Hapus Jurnal Asli (LOANPAY-...)
            DB::table('general_ledgers')->where('journal_group_id', "LOANPAY-" . $payment->payment_id)->delete();
            
            // 4. Hapus data pembayaran
            $payment->delete();
            
            DB::commit();
            return redirect()->route('loans.show', $loan)->with('success', 'Data pembayaran berhasil dihapus (Rollback).');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus pembayaran: ' . $e->getMessage());
        }
    }
}