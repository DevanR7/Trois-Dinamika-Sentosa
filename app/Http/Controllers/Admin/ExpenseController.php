<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ChartOfAccount;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Traits\ValidatesAccountingPeriod;

use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{   
    use ValidatesAccountingPeriod;

    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
        
        $this->middleware('can:view-expenses')->only(['index', 'show']);
        $this->middleware('can:manage-expenses')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }
    
    public function index(Request $request): View
    {
        // $this->authorize('viewAny', Expense::class); 
        $query = Expense::with(['user', 'expenseAccount', 'cashBankAccount']); 

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('start_date')) {
            $query->whereDate('expense_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('expense_date', '<=', $request->end_date);
        }

        $expenses = $query->latest('expense_date')->paginate(15)->appends($request->query());
        $totalExpenses = $query->sum('amount'); 
        
        return view('admin.expenses.index', compact('expenses', 'totalExpenses'));
    }

    public function create(): View
    {
        // $this->authorize('create', Expense::class);
        
        $expenseAccounts = ChartOfAccount::where('account_type', 'Beban')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();

        $cashAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();

        return view('admin.expenses.create', compact('expenseAccounts', 'cashAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        // $this->authorize('create', Expense::class);
        
        $validated = $request->validate([
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,account_id',
            'cash_bank_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);

        $expenseAccount = ChartOfAccount::find($validated['chart_of_account_id']);
        
        DB::beginTransaction();
        try {
            $expense = Expense::create([
                'expense_date' => $validated['expense_date'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'user_id' => Auth::id(),
                'chart_of_account_id' => $validated['chart_of_account_id'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
                'category' => $expenseAccount->account_name, // Simpan nama akun
            ]);

            $journalGroupId = "EXP-" . $expense->expense_id;
            $description = "Beban: " . $expense->description;

            $debitEntries = [
                [$validated['chart_of_account_id'], $validated['amount']]
            ];
            $creditEntries = [
                [$validated['cash_bank_account_id'], $validated['amount']]
            ];

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['expense_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $expense,
                Auth::id()
            );

            DB::commit();
            return redirect()->route('admin.expenses.index')->with('success', 'Data pengeluaran berhasil disimpan dan dijurnal.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(Expense $expense): View
    {
        // $this->authorize('update', $expense);

        $expenseAccounts = ChartOfAccount::where('account_type', 'Beban')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
        
        $cashAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();

        return view('admin.expenses.edit', compact('expense', 'expenseAccounts', 'cashAccounts'));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {   
        $journalGroupId = "EXP-" . $expense->expense_id;

        if ($error = $this->checkTransactionLock($expense->expense_date, $journalGroupId)) {
            return back()->with('error', "Gagal Update: " . $error);
        }
        if ($request->filled('expense_date') && $this->isDateClosed($request->expense_date)) {
            return back()->with('error', "Gagal Update: Tanggal baru masuk periode tutup buku.");
        }

        // $this->authorize('update', $expense);
        
        $validated = $request->validate([
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,account_id',
            'cash_bank_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);

        $expenseAccount = ChartOfAccount::find($validated['chart_of_account_id']);

        DB::beginTransaction();
        try {
            $expense->update([
                'expense_date' => $validated['expense_date'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'chart_of_account_id' => $validated['chart_of_account_id'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
                'category' => $expenseAccount->account_name, // Simpan nama akun
            ]);

            $journalGroupId = "EXP-" . $expense->expense_id;
            $description = "Beban (Update): " . $expense->description;

            $debitEntries = [
                [$validated['chart_of_account_id'], $validated['amount']]
            ];
            $creditEntries = [
                [$validated['cash_bank_account_id'], $validated['amount']]
            ];

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['expense_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $expense, 
                Auth::id()
            );

            DB::commit();
            return redirect()->route('admin.expenses.index')->with('success', 'Data pengeluaran berhasil diupdate.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate data: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Expense $expense): RedirectResponse
    {   
        $journalGroupId = "EXP-" . $expense->expense_id;
        if ($error = $this->checkTransactionLock($expense->expense_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus: " . $error);
        }
        
        // $this->authorize('delete', $expense);
        
        DB::beginTransaction();
        try {
            $journalGroupId = "EXP-REVERSAL-" . $expense->expense_id;
            $description = "Reversal Beban: " . $expense->description;

            $debitEntries = [
                [$expense->cash_bank_account_id, $expense->amount]
            ];
            $creditEntries = [
                [$expense->chart_of_account_id, $expense->amount]
            ];
            
            $this->accountingService->postJournal(
                $journalGroupId,
                now(), 
                $description,
                $debitEntries,
                $creditEntries,
                $expense,
                Auth::id()
            );
            
            DB::table('general_ledgers')->where('journal_group_id', "EXP-" . $expense->expense_id)->delete();

            $expense->delete();
            
            DB::commit();
            return redirect()->route('admin.expenses.index')->with('success', 'Data pengeluaran berhasil dihapus.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}