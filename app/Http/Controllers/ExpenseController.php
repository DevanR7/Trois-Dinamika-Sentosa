<?php

namespace App\Http\Controllers;

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
        
        // ✅ TAMBAHKAN BLOK INI
        // Hanya yang bisa 'view-reports' boleh lihat daftar (index)
        $this->middleware('can:view-reports')->only(['index']);
        
        // (Opsional) Jika Anda ingin permission terpisah untuk mengelola beban
        // $this->middleware('can:manage-expenses')->except(['index']);
    }
    
    /**
     * Menampilkan daftar pengeluaran.
     */
    public function index(Request $request): View
    {
        // $this->authorize('viewAny', Expense::class); 
        
        // ✅ Perbarui query untuk load relasi baru
        $query = Expense::with(['user', 'expenseAccount', 'cashBankAccount']); 

        // Filter berdasarkan pencarian (deskripsi / kategori string)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%"); // Masih cari di kolom category
            });
        }
        
        // Filter tanggal (Sama)
        if ($request->filled('start_date')) {
            $query->whereDate('expense_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('expense_date', '<=', $request->end_date);
        }

        $expenses = $query->latest('expense_date')->paginate(15)->appends($request->query());
        $totalExpenses = $query->sum('amount'); 
        
        return view('expenses.index', compact('expenses', 'totalExpenses'));
    }

    /**
     * Menampilkan form untuk membuat pengeluaran baru.
     */
    public function create(): View
    {
        // $this->authorize('create', Expense::class);
        
        // ✅ Ambil akun Kategori Beban dari COA
        $expenseAccounts = ChartOfAccount::where('account_type', 'Beban')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
        
        // ✅ Ambil akun Sumber Dana (Kas/Bank) dari COA
        // Asumsi akun Kas/Bank adalah 'Aset'
        $cashAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();

        return view('expenses.create', compact('expenseAccounts', 'cashAccounts'));
    }

    /**
     * Menyimpan pengeluaran baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        // $this->authorize('create', Expense::class);
        
        $validated = $request->validate([
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            // ✅ Validasi kolom baru
            'chart_of_account_id' => 'required|exists:chart_of_accounts,account_id',
            'cash_bank_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);

        // Ambil nama kategori dari COA untuk disimpan di kolom 'category' (legacy)
        $expenseAccount = ChartOfAccount::find($validated['chart_of_account_id']);
        
        DB::beginTransaction();
        try {
            // 1. Simpan data pengeluaran
            $expense = Expense::create([
                'expense_date' => $validated['expense_date'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'user_id' => Auth::id(),
                'chart_of_account_id' => $validated['chart_of_account_id'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
                'category' => $expenseAccount->account_name, // Simpan nama akun
            ]);

            // 2. Post Jurnal Akuntansi
            $journalGroupId = "EXP-" . $expense->expense_id;
            $description = "Beban: " . $expense->description;

            $debitEntries = [
                // [Akun Beban, Jumlah]
                [$validated['chart_of_account_id'], $validated['amount']]
            ];
            $creditEntries = [
                // [Akun Kas/Bank, Jumlah]
                [$validated['cash_bank_account_id'], $validated['amount']]
            ];

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['expense_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $expense, // Model referensi
                Auth::id()
            );

            DB::commit();
            return redirect()->route('expenses.index')->with('success', 'Data pengeluaran berhasil disimpan dan dijurnal.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menampilkan form untuk mengedit pengeluaran.
     */
    public function edit(Expense $expense): View
    {
        // $this->authorize('update', $expense);
        
        // ✅ Ambil akun Kategori Beban dari COA
        $expenseAccounts = ChartOfAccount::where('account_type', 'Beban')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
        
        // ✅ Ambil akun Sumber Dana (Kas/Bank) dari COA
        $cashAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();

        return view('expenses.edit', compact('expense', 'expenseAccounts', 'cashAccounts'));
    }
    /**
     * Mengupdate data pengeluaran di database.
     */
    public function update(Request $request, Expense $expense): RedirectResponse
    {   
        // 1. Cek Data Lama (Apakah terkunci?)
        $journalGroupId = "EXP-" . $expense->expense_id;
        if ($error = $this->checkTransactionLock($expense->expense_date, $journalGroupId)) {
            return back()->with('error', "Gagal Update: " . $error);
        }
        
        // 2. Cek Tanggal Baru (Apakah masuk tahun yang ditutup?)
        if ($request->filled('expense_date') && $this->isDateClosed($request->expense_date)) {
            return back()->with('error', "Gagal Update: Tanggal baru masuk periode tutup buku.");
        }

        // $this->authorize('update', $expense);
        
        $validated = $request->validate([
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            // ✅ Validasi kolom baru
            'chart_of_account_id' => 'required|exists:chart_of_accounts,account_id',
            'cash_bank_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);

        $expenseAccount = ChartOfAccount::find($validated['chart_of_account_id']);

        DB::beginTransaction();
        try {
            // 1. Update data pengeluaran
            $expense->update([
                'expense_date' => $validated['expense_date'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'chart_of_account_id' => $validated['chart_of_account_id'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
                'category' => $expenseAccount->account_name, // Simpan nama akun
            ]);

            // 2. Post ulang Jurnal Akuntansi (Service kita akan hapus yg lama)
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
                $expense, // Model referensi
                Auth::id()
            );

            DB::commit();
            return redirect()->route('expenses.index')->with('success', 'Data pengeluaran berhasil diupdate.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate data: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menghapus data pengeluaran dari database.
     */
    public function destroy(Expense $expense): RedirectResponse
    {   
        $journalGroupId = "EXP-" . $expense->expense_id;
        if ($error = $this->checkTransactionLock($expense->expense_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus: " . $error);
        }
        
        // $this->authorize('delete', $expense);
        
        DB::beginTransaction();
        try {
            // 1. Hapus Jurnal terkait
            // Kita jurnal balik (reversal)
            $journalGroupId = "EXP-REVERSAL-" . $expense->expense_id;
            $description = "Reversal Beban: " . $expense->description;

            $debitEntries = [
                // [Akun Kas/Bank, Jumlah]
                [$expense->cash_bank_account_id, $expense->amount]
            ];
            $creditEntries = [
                // [Akun Beban, Jumlah]
                [$expense->chart_of_account_id, $expense->amount]
            ];
            
            $this->accountingService->postJournal(
                $journalGroupId,
                now(), // Tanggal reversal adalah hari ini
                $description,
                $debitEntries,
                $creditEntries,
                $expense,
                Auth::id()
            );
            
            // 2. Hapus Jurnal Asli (EXP-...)
            DB::table('general_ledgers')->where('journal_group_id', "EXP-" . $expense->expense_id)->delete();

            // 3. Hapus data pengeluaran
            $expense->delete();
            
            DB::commit();
            return redirect()->route('expenses.index')->with('success', 'Data pengeluaran berhasil dihapus.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}