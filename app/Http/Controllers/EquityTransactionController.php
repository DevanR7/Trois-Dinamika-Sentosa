<?php

namespace App\Http\Controllers;

use App\Models\EquityTransaction; // Pastikan Model di-import
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
        
        // ✅ TAMBAHKAN BLOK INI
        // Hanya yang bisa 'view-reports' boleh lihat daftar (index)
        $this->middleware('can:view-reports')->only(['index']);
        
        // (Opsional) Jika Anda ingin permission terpisah
        // $this->middleware('can:manage-equity')->except(['index']);
    }

    /**
     * Menampilkan daftar semua transaksi modal.
     */
    public function index(Request $request): View
    {
        // ✅ Perbarui query untuk load relasi baru
        $query = EquityTransaction::with(['user', 'equityAccount', 'cashBankAccount']);
        
        // Filter (Sama)
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
        
        // Kalkulasi (Sama)
        $queryTotals = clone $query; 
        $totalInvestment = $queryTotals->where('type', 'investment')->sum('amount');
        
        $queryTotals = clone $query; 
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
        
        // ✅ Ambil akun Ekuitas dari COA
        $equityAccounts = ChartOfAccount::where('account_type', 'Ekuitas')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
        
        // ✅ Ambil akun Sumber Dana (Kas/Bank) dari COA
        $cashAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();

        return view('equity_transactions.create', compact('equityAccounts', 'cashAccounts'));
    }

    /**
     * Menyimpan transaksi baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        // $this->authorize('create', EquityTransaction::class);
        
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            // ✅ Validasi kolom baru
            'equity_account_id' => 'required|exists:chart_of_accounts,account_id',
            'cash_bank_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);

        // Tentukan 'type' (investment/drawing) berdasarkan Saldo Normal Akun Ekuitas
        $equityAccount = ChartOfAccount::find($validated['equity_account_id']);
        // Akun Modal (Saldo Normal Kredit) = 'investment'
        // Akun Prive (Saldo Normal Debit) = 'drawing'
        $type = ($equityAccount->normal_balance == 'Kredit') ? 'investment' : 'drawing';

        DB::beginTransaction();
        try {
            // 1. Simpan data transaksi
            $transaction = EquityTransaction::create([
                'transaction_date' => $validated['transaction_date'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'user_id' => Auth::id(),
                'equity_account_id' => $validated['equity_account_id'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
                'type' => $type, // Simpan tipe yg ditentukan otomatis
            ]);

            // 2. Post Jurnal Akuntansi
            $journalGroupId = "EQ-" . $transaction->transaction_id;
            $description = "Modal: " . $transaction->description;
            
            $debitEntries = [];
            $creditEntries = [];

            if ($type == 'investment') {
                // Setoran Modal: Kas (Debit), Modal (Kredit)
                $debitEntries[] = [$validated['cash_bank_account_id'], $validated['amount']];
                $creditEntries[] = [$validated['equity_account_id'], $validated['amount']];
            } else {
                // Penarikan Modal: Prive (Debit), Kas (Kredit)
                $debitEntries[] = [$validated['equity_account_id'], $validated['amount']];
                $creditEntries[] = [$validated['cash_bank_account_id'], $validated['amount']];
            }

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['transaction_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $transaction, // Model referensi
                Auth::id()
            );

            DB::commit();
            return redirect()->route('equity-transactions.index')->with('success', 'Transaksi modal berhasil dicatat dan dijurnal.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menampilkan form untuk mengedit transaksi.
     */
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
        
        return view('equity_transactions.edit', compact('transaction', 'equityAccounts', 'cashAccounts'));
    }

    /**
     * Mengupdate data transaksi di database.
     */
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
            // ✅ Validasi kolom baru
            'equity_account_id' => 'required|exists:chart_of_accounts,account_id',
            'cash_bank_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);

        $equityAccount = ChartOfAccount::find($validated['equity_account_id']);
        $type = ($equityAccount->normal_balance == 'Kredit') ? 'investment' : 'drawing';

        DB::beginTransaction();
        try {
            // 1. Update data transaksi
            $equityTransaction->update([
                'transaction_date' => $validated['transaction_date'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'equity_account_id' => $validated['equity_account_id'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
                'type' => $type,
            ]);

            // 2. Post ulang Jurnal Akuntansi
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
                $equityTransaction, // Model referensi
                Auth::id()
            );

            DB::commit();
            return redirect()->route('equity-transactions.index')->with('success', 'Transaksi modal berhasil diupdate.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate data: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menghapus data transaksi dari database.
     */
    public function destroy(EquityTransaction $equityTransaction): RedirectResponse
    {   
        $journalGroupId = "EQ-" . $equityTransaction->transaction_id;
        if ($error = $this->checkTransactionLock($equityTransaction->transaction_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus: " . $error);
        }
        
        // $this->authorize('delete', $equityTransaction);
        
        DB::beginTransaction();
        try {
            // 1. Hapus Jurnal terkait (Reversal)
            $journalGroupId = "EQ-REVERSAL-" . $equityTransaction->transaction_id;
            $description = "Reversal Modal: " . $equityTransaction->description;
            
            $debitEntries = [];
            $creditEntries = [];

            // Jurnalnya dibalik
            if ($equityTransaction->type == 'investment') {
                // Asli: (D) Kas, (K) Modal
                // Balik: (D) Modal, (K) Kas
                $debitEntries[] = [$equityTransaction->equity_account_id, $equityTransaction->amount];
                $creditEntries[] = [$equityTransaction->cash_bank_account_id, $equityTransaction->amount];
            } else {
                // Asli: (D) Prive, (K) Kas
                // Balik: (D) Kas, (K) Prive
                $debitEntries[] = [$equityTransaction->cash_bank_account_id, $equityTransaction->amount];
                $creditEntries[] = [$equityTransaction->equity_account_id, $equityTransaction->amount];
            }
            
            $this->accountingService->postJournal(
                $journalGroupId,
                now(), // Tanggal reversal
                $description,
                $debitEntries,
                $creditEntries,
                $equityTransaction,
                Auth::id()
            );
            
            // 2. Hapus Jurnal Asli (EQ-...)
            DB::table('general_ledgers')->where('journal_group_id', "EQ-" . $equityTransaction->transaction_id)->delete();
            
            // 3. Hapus data transaksi
            $equityTransaction->delete();
            
            DB::commit();
            return redirect()->route('equity-transactions.index')->with('success', 'Transaksi modal berhasil dihapus.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}