<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankReconciliation;
use App\Models\CompanyBankAccount;
use App\Models\GeneralLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BankReconciliationController extends Controller
{
    public function __construct()
    {
        // $this->middleware('can:manage-finance'); 
    }

    public function index(): View
    {
        $reconciliations = BankReconciliation::with('account', 'user')
            ->orderBy('statement_date', 'desc')
            ->paginate(15); 

        return view('admin.bank_reconciliations.index', compact('reconciliations'));
    }

    public function create(): View
    {
        $bankAccounts = CompanyBankAccount::whereNotNull('chart_of_account_id')
            ->with('account')
            ->get();    

        return view('admin.bank_reconciliations.create', compact('bankAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_bank_account_id' => 'required|exists:company_bank_accounts,company_bank_account_id',
            'statement_date' => 'required|date',
            'statement_balance' => 'required|numeric',
        ]);

        $bankAccount = CompanyBankAccount::find($validated['company_bank_account_id']);
        $chartOfAccountId = $bankAccount->chart_of_account_id;
        $existing = BankReconciliation::where('chart_of_account_id', $chartOfAccountId)
            ->where('status', 'draft')
            ->first();
            
        if ($existing) {
            return redirect()->route('admin.bank-reconciliations.show', $existing)
                ->with('info', 'Melanjutkan draft rekonsiliasi yang belum selesai.');
        }

        $closingBalance = GeneralLedger::where('chart_of_account_id', $chartOfAccountId)
            ->where('entry_date', '<=', $validated['statement_date'])
            ->sum(DB::raw('debit - credit'));

        $reconciliation = BankReconciliation::create([
            'chart_of_account_id' => $chartOfAccountId,
            'company_bank_account_id' => $validated['company_bank_account_id'],
            'statement_date' => $validated['statement_date'],
            'statement_balance' => $validated['statement_balance'],
            'closing_balance' => $closingBalance, 
            'difference' => $closingBalance - $validated['statement_balance'],
            'status' => 'draft',
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('admin.bank-reconciliations.show', $reconciliation);
    }

    public function show(BankReconciliation $bankReconciliation): View
    {
        $bankReconciliation->load('account');
        $accountId = $bankReconciliation->chart_of_account_id;
        $statementDate = $bankReconciliation->statement_date;
        $clearedEntries = GeneralLedger::where('bank_reconciliation_id', $bankReconciliation->reconciliation_id)
            ->orderBy('entry_date')
            ->get();

        $unreconciledEntries = GeneralLedger::where('chart_of_account_id', $accountId)
            ->whereNull('bank_reconciliation_id')
            ->where('entry_date', '<=', $statementDate)
            ->orderBy('entry_date')
            ->get();

        $totalClearedNet = $clearedEntries->sum('debit') - $clearedEntries->sum('credit');
        $totalUnreconciledNet = $unreconciledEntries->sum('debit') - $unreconciledEntries->sum('credit');
        $closingBalance = $bankReconciliation->closing_balance;
        $statementBalance = $bankReconciliation->statement_balance;
        $currentClearedBalance = $closingBalance - $totalUnreconciledNet;
        $difference = $statementBalance - $currentClearedBalance;

        if(abs($bankReconciliation->difference - $difference) > 0.01) {
            $bankReconciliation->update(['difference' => $difference]);
        }

        $calcOpeningBalance = $currentClearedBalance - $totalClearedNet;
        $cleared_deposits = $clearedEntries->where('debit', '>', 0);
        $cleared_payments = $clearedEntries->where('credit', '>', 0);
        $unreconciled_deposits = $unreconciledEntries->where('debit', '>', 0);
        $unreconciled_payments = $unreconciledEntries->where('credit', '>', 0);

        return view('admin.bank_reconciliations.show', compact(
            'bankReconciliation',
            'cleared_deposits', 'cleared_payments',
            'unreconciled_deposits', 'unreconciled_payments',
            'closingBalance', 'statementBalance', 'difference',
            'calcOpeningBalance'
        ));
    }

    public function update(Request $request, BankReconciliation $bankReconciliation): RedirectResponse
    {
        $validated = $request->validate([
            'cleared_entries' => 'nullable|array',
            'cleared_entries.*' => 'exists:general_ledgers,ledger_id',
            'action' => 'required|in:save_draft,reconcile',
        ]);
        
        $accountId = $bankReconciliation->chart_of_account_id;
        $reconciliationId = $bankReconciliation->reconciliation_id;
        $clearedEntryIds = $validated['cleared_entries'] ?? [];

        DB::beginTransaction();
        try {
            GeneralLedger::where('bank_reconciliation_id', $reconciliationId)
                ->update(['bank_reconciliation_id' => null]);
            
            if (!empty($clearedEntryIds)) {
                GeneralLedger::whereIn('ledger_id', $clearedEntryIds)
                    ->where('chart_of_account_id', $accountId) // Security check
                    ->update(['bank_reconciliation_id' => $reconciliationId]);
            }

            $closingBalance = $bankReconciliation->closing_balance;
            $statementBalance = $bankReconciliation->statement_balance;

            $totalUnreconciledNet = GeneralLedger::where('chart_of_account_id', $accountId)
                ->whereNull('bank_reconciliation_id')
                ->where('entry_date', '<=', $bankReconciliation->statement_date)
                ->sum(DB::raw('debit - credit'));
            
            $reconciledBalance = $closingBalance - $totalUnreconciledNet;
            $difference = $statementBalance - $reconciledBalance;

            $bankReconciliation->update([
                'difference' => $difference,
                'user_id' => Auth::id(),
            ]);

            if ($request->action == 'reconcile') {
                if (abs($difference) > 0.01) {
                    throw new \Exception('Selisih belum nol (Rp ' . number_format($difference, 2) . '). Mohon cek kembali centangan Anda.');
                }

                $bankReconciliation->update(['status' => 'reconciled']);

                DB::commit();
                return redirect()->route('admin.bank-reconciliations.index')
                    ->with('success', 'Rekonsiliasi Berhasil! Periode ini telah dikunci.');
            }
            
            DB::commit();
            return back()->with('success', 'Draft tersimpan. Anda bisa melanjutkannya nanti.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy(BankReconciliation $bankReconciliation): RedirectResponse
    {
        if ($bankReconciliation->status == 'reconciled') {
            return back()->with('error', 'Rekonsiliasi yang sudah selesai tidak dapat dihapus.');
        }

        DB::transaction(function () use ($bankReconciliation) {
            GeneralLedger::where('bank_reconciliation_id', $bankReconciliation->reconciliation_id)
                ->update(['bank_reconciliation_id' => null]);
            $bankReconciliation->delete();
        });

        return redirect()->route('admin.bank-reconciliations.index')
            ->with('success', 'Draft rekonsiliasi berhasil dihapus.');
    }

    public function edit(BankReconciliation $bankReconciliation)
    {
        return redirect()->route('admin.bank-reconciliations.show', $bankReconciliation);
    }
}