<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EquityTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\ChartOfAccount;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use App\Traits\ValidatesAccountingPeriod;

class EquityTransactionController extends Controller
{   
    use ValidatesAccountingPeriod;
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
        $this->middleware('can:view-reports')->only(['index']);
    }

    public function index(Request $request): View
    {
        $query = EquityTransaction::with(['user', 'equityAccount', 'cashBankAccount']);

        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->latest('transaction_date')->paginate(15)->appends($request->query());

        $queryTotals = clone $query; 
        $totalInvestment = $queryTotals->where('type', 'investment')->sum('amount');
        
        $queryTotals = clone $query; 
        $totalDrawing = $queryTotals->where('type', 'drawing')->sum('amount');
        $netModal = $totalInvestment - $totalDrawing;

        return view('admin.equity_transactions.index', compact(
            'transactions', 
            'totalInvestment', 
            'totalDrawing', 
            'netModal'
        ));
    }

    public function create(): View
    {
        // $this->authorize('create', EquityTransaction::class);
        
        $equityAccounts = ChartOfAccount::where('account_type', 'Ekuitas')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();

        $cashAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();

        return view('admin.equity_transactions.create', compact('equityAccounts', 'cashAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        // $this->authorize('create', EquityTransaction::class);
        
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'equity_account_id' => 'required|exists:chart_of_accounts,account_id',
            'cash_bank_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);

        $equityAccount = ChartOfAccount::find($validated['equity_account_id']);
        $type = ($equityAccount->normal_balance == 'Kredit') ? 'investment' : 'drawing';

        DB::beginTransaction();
        try {
            $transaction = EquityTransaction::create([
                'transaction_date' => $validated['transaction_date'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'user_id' => Auth::id(),
                'equity_account_id' => $validated['equity_account_id'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
                'type' => $type,
            ]);

            $journalGroupId = "EQ-" . $transaction->transaction_id;
            $description = "Modal: " . $transaction->description;
            $debitEntries = [];
            $creditEntries = [];

            if ($type == 'investment') {
                $debitEntries[] = [$validated['cash_bank_account_id'], $validated['amount']];
                $creditEntries[] = [$validated['equity_account_id'], $validated['amount']];
            } else {
                $debitEntries[] = [$validated['equity_account_id'], $validated['amount']];
                $creditEntries[] = [$validated['cash_bank_account_id'], $validated['amount']];
            }

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['transaction_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $transaction, 
                Auth::id()
            );

            DB::commit();
            return redirect()->route('admin.equity-transactions.index')->with('success', 'Transaksi modal berhasil dicatat dan dijurnal.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(EquityTransaction $equityTransaction): View
    {
        // $this->authorize('update', $equityTransaction);
        
        $equityAccounts = ChartOfAccount::where('account_type', 'Ekuitas')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
        $cashAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
        
        $transaction = $equityTransaction; 
        
        return view('admin.equity_transactions.edit', compact('transaction', 'equityAccounts', 'cashAccounts'));
    }

    public function update(Request $request, EquityTransaction $equityTransaction): RedirectResponse
    {   
        $journalGroupId = "EQ-" . $equityTransaction->transaction_id;
        if ($error = $this->checkTransactionLock($equityTransaction->transaction_date, $journalGroupId)) {
            return back()->with('error', "Gagal Update: " . $error);
        }

        if ($request->filled('transaction_date') && $this->isDateClosed($request->transaction_date)) {
             return back()->with('error', "Gagal Update: Tanggal baru masuk periode tutup buku.");
        }

        // $this->authorize('update', $equityTransaction);
        
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'equity_account_id' => 'required|exists:chart_of_accounts,account_id',
            'cash_bank_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);

        $equityAccount = ChartOfAccount::find($validated['equity_account_id']);
        $type = ($equityAccount->normal_balance == 'Kredit') ? 'investment' : 'drawing';

        DB::beginTransaction();
        try {
            $equityTransaction->update([
                'transaction_date' => $validated['transaction_date'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'equity_account_id' => $validated['equity_account_id'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
                'type' => $type,
            ]);

            $journalGroupId = "EQ-" . $equityTransaction->transaction_id;
            $description = "Modal (Update): " . $equityTransaction->description;
            $debitEntries = [];
            $creditEntries = [];

            if ($type == 'investment') {
                $debitEntries[] = [$validated['cash_bank_account_id'], $validated['amount']];
                $creditEntries[] = [$validated['equity_account_id'], $validated['amount']];
            } else {
                $debitEntries[] = [$validated['equity_account_id'], $validated['amount']];
                $creditEntries[] = [$validated['cash_bank_account_id'], $validated['amount']];
            }

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['transaction_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $equityTransaction,
                Auth::id()
            );

            DB::commit();
            return redirect()->route('admin.equity-transactions.index')->with('success', 'Transaksi modal berhasil diupdate.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate data: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(EquityTransaction $equityTransaction): RedirectResponse
    {   
        $journalGroupId = "EQ-" . $equityTransaction->transaction_id;
        if ($error = $this->checkTransactionLock($equityTransaction->transaction_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus: " . $error);
        }
        
        // $this->authorize('delete', $equityTransaction);
        
        DB::beginTransaction();
        try {
            $journalGroupId = "EQ-REVERSAL-" . $equityTransaction->transaction_id;
            $description = "Reversal Modal: " . $equityTransaction->description;
            
            $debitEntries = [];
            $creditEntries = [];

            if ($equityTransaction->type == 'investment') {
                $debitEntries[] = [$equityTransaction->equity_account_id, $equityTransaction->amount];
                $creditEntries[] = [$equityTransaction->cash_bank_account_id, $equityTransaction->amount];
            } else {
                $debitEntries[] = [$equityTransaction->cash_bank_account_id, $equityTransaction->amount];
                $creditEntries[] = [$equityTransaction->equity_account_id, $equityTransaction->amount];
            }
            
            $this->accountingService->postJournal(
                $journalGroupId,
                now(), 
                $description,
                $debitEntries,
                $creditEntries,
                $equityTransaction,
                Auth::id()
            );
            
            DB::table('general_ledgers')->where('journal_group_id', "EQ-" . $equityTransaction->transaction_id)->delete();

            $equityTransaction->delete();
            
            DB::commit();
            return redirect()->route('admin.equity-transactions.index')->with('success', 'Transaksi modal berhasil dihapus.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}