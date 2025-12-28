<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

    public function create(Loan $loan): View
    {
        // $this->authorize('create', [LoanPayment::class, $loan]);
        
        $expenseAccounts = ChartOfAccount::where('account_type', 'Beban')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
        
        $cashAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();

        return view('admin.loan_payments.create', compact('loan', 'expenseAccounts', 'cashAccounts'));
    }

    public function store(Request $request, Loan $loan): RedirectResponse
    {
        // $this->authorize('create', [LoanPayment::class, $loan]);
        
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'principal_paid' => 'required|numeric|min:0',
            'interest_paid' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
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
            
            $newRemainingBalance = $sisaPokok - $validated['principal_paid'];
            $loan->update([
                'remaining_balance' => $newRemainingBalance,
                'status' => ($newRemainingBalance <= 0) ? 'paid_off' : 'active'
            ]);

            $journalGroupId = "LOANPAY-" . $payment->payment_id;
            $description = "Bayar Cicilan Pinjaman: " . ($loan->lender_name);

            $debitEntries = [];
            $debitEntries[] = [$loan->loan_account_id, $validated['principal_paid']];
            
            if ($validated['interest_paid'] > 0) {
                $debitEntries[] = [$validated['interest_expense_account_id'], $validated['interest_paid']];
            }
            
            $creditEntries = [
                [$validated['cash_bank_account_id'], $totalPaid]
            ];

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['payment_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $payment,
                Auth::id()
            );

            DB::commit();
            return redirect()->route('admin.loans.show', $loan)->with('success', 'Pembayaran cicilan berhasil dicatat dan dijurnal.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan pembayaran: ' . $e->getMessage());
        }
    }

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
            
            $loan->update([
                'remaining_balance' => $loan->remaining_balance + $principalToRestore,
                'status' => 'active'
            ]);
            
            $journalGroupId = "LOANPAY-REVERSAL-" . $payment->payment_id;
            $description = "Reversal Bayar Cicilan: " . $loan->lender_name;

            $debitEntries = [
                [$payment->cash_bank_account_id, $payment->total_paid]
            ];
            
            $creditEntries = [];
            $creditEntries[] = [$loan->loan_account_id, $payment->principal_paid];
            if ($payment->interest_paid > 0) {
                $creditEntries[] = [$payment->interest_expense_account_id, $payment->interest_paid];
            }
            
            $this->accountingService->postJournal(
                $journalGroupId,
                now(), 
                $description,
                $debitEntries,
                $creditEntries,
                $payment,
                Auth::id()
            );

            DB::table('general_ledgers')->where('journal_group_id', "LOANPAY-" . $payment->payment_id)->delete();
            
            $payment->delete();
            
            DB::commit();
            return redirect()->route('admin.loans.show', $loan)->with('success', 'Data pembayaran berhasil dihapus (Rollback).');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus pembayaran: ' . $e->getMessage());
        }
    }
}