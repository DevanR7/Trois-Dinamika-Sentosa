<?php

namespace App\Http\Controllers;

use App\Models\BankReconciliation;
use App\Models\CompanyBankAccount;
use App\Models\ChartOfAccount;
use App\Models\GeneralLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class BankReconciliationController extends Controller
{
    public function __construct()
    {
        // (Opsional) Ganti 'manage-settings' dengan permission baru jika Anda mau
        $this->middleware('can:manage-settings');
    }

    /**
     * Menampilkan daftar rekonsiliasi yang sudah selesai.
     */
    public function index(): View
    {
        $reconciliations = BankReconciliation::with('account', 'user')
            ->orderBy('statement_date', 'desc')
            ->paginate(15);
            
        return view('bank_reconciliations.index', compact('reconciliations'));
    }

    /**
     * Menampilkan form untuk memulai rekonsiliasi baru.
     */
    public function create(): View
    {
        // Ambil hanya Akun Bank Perusahaan yang terhubung ke COA
        $bankAccounts = CompanyBankAccount::whereNotNull('chart_of_account_id')
            ->with('account') // Load relasi COA
            ->get();
            
        return view('bank_reconciliations.create', compact('bankAccounts'));
    }

    /**
     * Menyimpan header rekonsiliasi (draft) dan redirect ke halaman kerja.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_bank_account_id' => 'required|exists:company_bank_accounts,company_bank_account_id',
            'statement_date' => 'required|date',
            'statement_balance' => 'required|numeric',
        ]);

        $bankAccount = CompanyBankAccount::find($validated['company_bank_account_id']);
        $chartOfAccountId = $bankAccount->chart_of_account_id;

        // Cek apakah sudah ada draft untuk akun & tanggal ini
        $existing = BankReconciliation::where('chart_of_account_id', $chartOfAccountId)
            ->where('status', 'draft')
            ->first();
        
        if ($existing) {
            return redirect()->route('bank-reconciliations.show', $existing)
                ->with('info', 'Melanjutkan draft rekonsiliasi yang sudah ada.');
        }

        // Hitung saldo akhir di Jurnal Umum
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

        return redirect()->route('bank-reconciliations.show', $reconciliation);
    }

    /**
     * Menampilkan Halaman Kerja Rekonsiliasi (Halaman Utama).
     */
    public function show(BankReconciliation $bankReconciliation): View
    {
        $bankReconciliation->load('account');
        $accountId = $bankReconciliation->chart_of_account_id;
        $statementDate = $bankReconciliation->statement_date;

        // 1. Ambil semua "Centang" yang sudah ada di rekonsiliasi INI
        $clearedEntries = GeneralLedger::where('bank_reconciliation_id', $bankReconciliation->reconciliation_id)
            ->orderBy('entry_date')
            ->get();
            
        // 2. Ambil semua "Belum Centang" (Unreconciled) SEBELUM tanggal statement
        $unreconciledEntries = GeneralLedger::where('chart_of_account_id', $accountId)
            ->whereNull('bank_reconciliation_id')
            ->where('entry_date', '<=', $statementDate)
            ->orderBy('entry_date')
            ->get();
            
        // Pisahkan Debit (Setoran/Pemasukan) dan Kredit (Cek/Pembayaran)
        $cleared_deposits = $clearedEntries->where('debit', '>', 0);
        $cleared_payments = $clearedEntries->where('credit', '>', 0);
        
        $unreconciled_deposits = $unreconciledEntries->where('debit', '>', 0);
        $unreconciled_payments = $unreconciledEntries->where('credit', '>', 0);

        // 3. Hitung Kalkulasi
        $totalCleared = $clearedEntries->sum(DB::raw('debit - credit'));
        $totalUnreconciled = $unreconciledEntries->sum(DB::raw('debit - credit'));
        
        // Saldo Jurnal Umum (GL) pada tanggal statement
        $closingBalance = $bankReconciliation->closing_balance;
        // Saldo Bank (Rekening Koran)
        $statementBalance = $bankReconciliation->statement_balance;

        // Saldo GL - item yg sdh dicentang + item yg blm dicentang = Saldo GL
        // Kita hitung: Saldo GL - item yg blm dicentang = Saldo yg sudah bersih
        $reconciledBalance = $closingBalance - $totalUnreconciled;
        $difference = $reconciledBalance - $statementBalance;
        
        // Update selisih di DB
        $bankReconciliation->update(['difference' => $difference]);

        return view('bank_reconciliations.show', compact(
            'bankReconciliation',
            'cleared_deposits', 'cleared_payments',
            'unreconciled_deposits', 'unreconciled_payments',
            'closingBalance', 'statementBalance', 'difference'
        ));
    }

    /**
     * (Tidak dipakai, kita gunakan update)
     */
    public function edit(BankReconciliation $bankReconciliation)
    {
        return redirect()->route('bank-reconciliations.show', $bankReconciliation);
    }

    /**
     * Menyimpan perubahan (centang) dari Halaman Kerja.
     */
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
            // 1. Lepaskan semua centang LAMA yang terkait dengan draft INI
            GeneralLedger::where('bank_reconciliation_id', $reconciliationId)
                ->update(['bank_reconciliation_id' => null]);
            
            // 2. Terapkan centang BARU
            if (!empty($clearedEntryIds)) {
                GeneralLedger::whereIn('ledger_id', $clearedEntryIds)
                    ->where('chart_of_account_id', $accountId) // Keamanan
                    ->update(['bank_reconciliation_id' => $reconciliationId]);
            }

            // 3. Hitung ulang selisih
            $closingBalance = $bankReconciliation->closing_balance;
            $statementBalance = $bankReconciliation->statement_balance;

            // Ambil semua yg BELUM tercentang (unreconciled)
            $totalUnreconciled = GeneralLedger::where('chart_of_account_id', $accountId)
                ->whereNull('bank_reconciliation_id')
                ->where('entry_date', '<=', $bankReconciliation->statement_date)
                ->sum(DB::raw('debit - credit'));
            
            $reconciledBalance = $closingBalance - $totalUnreconciled;
            $difference = $reconciledBalance - $statementBalance;

            // 4. Update Header
            $bankReconciliation->update([
                'difference' => $difference,
                'user_id' => Auth::id(),
            ]);

            // 5. Jika user klik "Reconcile"
            if ($request->action == 'reconcile') {
                if (round($difference, 2) != 0) {
                    throw new \Exception('Selisih belum nol (Rp ' . number_format($difference) . '). Jurnal tidak dapat direkonsiliasi.');
                }
                // Kunci rekonsiliasi ini
                $bankReconciliation->update(['status' => 'reconciled']);
            }
            
            DB::commit();

            if ($request->action == 'reconcile') {
                return redirect()->route('bank-reconciliations.index')->with('success', 'Rekonsiliasi berhasil diselesaikan dan dikunci.');
            }
            return back()->with('success', 'Draft rekonsiliasi berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus draft rekonsiliasi.
     */
    public function destroy(BankReconciliation $bankReconciliation): RedirectResponse
    {
        if ($bankReconciliation->status == 'reconciled') {
            return back()->with('error', 'Rekonsiliasi yang sudah selesai tidak dapat dihapus.');
        }

        DB::beginTransaction();
        try {
            // Lepaskan semua centang
            GeneralLedger::where('bank_reconciliation_id', $bankReconciliation->reconciliation_id)
                ->update(['bank_reconciliation_id' => null]);
            
            // Hapus header
            $bankReconciliation->delete();
            
            DB::commit();
            return redirect()->route('bank-reconciliations.index')->with('success', 'Draft rekonsiliasi berhasil dihapus.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus draft: ' . $e->getMessage());
        }
    }
}