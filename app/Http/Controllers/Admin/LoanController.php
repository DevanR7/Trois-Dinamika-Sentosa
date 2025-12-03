<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\ChartOfAccount;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use App\Traits\ValidatesAccountingPeriod;

class LoanController extends Controller
{   
    use ValidatesAccountingPeriod;
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
        
        // ✅ TAMBAHKAN BLOK INI
        // Hanya yang bisa 'view-reports' boleh lihat daftar (index dan show)
        $this->middleware('can:view-reports')->only(['index', 'show']);
        
        // (Opsional) Jika Anda ingin permission terpisah
        // $this->middleware('can:manage-loans')->except(['index', 'show']);
    }

    /**
     * Menampilkan daftar pinjaman.
     */
    public function index(Request $request): View
    {
        // $this->authorize('viewAny', Loan::class);
        
        // ✅ Perbarui query untuk load relasi baru
        $query = Loan::with(['loanAccount', 'cashBankAccount']);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $loans = $query->latest('loan_date')->paginate(15)->appends($request->query());
        return view('admin.loans.index', compact('loans'));
    }

    /**
     * Menampilkan form untuk membuat pinjaman baru.
     */
    public function create(): View
    {
        // $this->authorize('create', Loan::class);
        
        // ✅ Ambil akun Liabilitas (Utang) dari COA
        $loanAccounts = ChartOfAccount::where('account_type', 'Liabilitas')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
        
        // ✅ Ambil akun Aset (Kas/Bank) dari COA
        $cashAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();

        return view('admin.loans.create', compact('loanAccounts', 'cashAccounts'));
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
            // ✅ Validasi kolom baru
            'loan_account_id' => 'required|exists:chart_of_accounts,account_id',
            'cash_bank_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);
        
        DB::beginTransaction();
        try {
            // 1. Simpan data pinjaman
            $loan = Loan::create([
                'lender_name' => $validated['lender_name'],
                'loan_date' => $validated['loan_date'],
                'principal_amount' => $validated['principal_amount'],
                'remaining_balance' => $validated['principal_amount'], 
                'description' => $validated['description'],
                'status' => 'active',
                'user_id' => Auth::id(),
                'loan_account_id' => $validated['loan_account_id'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
            ]);

            // 2. Post Jurnal Akuntansi (Penerimaan Pinjaman)
            $journalGroupId = "LOAN-" . $loan->loan_id;
            $description = "Penerimaan Pinjaman dari " . $loan->lender_name;

            $debitEntries = [
                // [Akun Kas/Bank, Jumlah] (Kas bertambah)
                [$validated['cash_bank_account_id'], $validated['principal_amount']]
            ];
            $creditEntries = [
                // [Akun Utang Pinjaman, Jumlah] (Utang bertambah)
                [$validated['loan_account_id'], $validated['principal_amount']]
            ];

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['loan_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $loan,
                Auth::id() // Model referensi
            );

            DB::commit();
            return redirect()->route('admin.loans.index')->with('success', 'Data pinjaman baru berhasil disimpan dan dijurnal.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menampilkan detail pinjaman (DAN riwayat pembayarannya).
     */
    public function show(Loan $loan): View
    {
        // $this->authorize('view', $loan);
        
        // ✅ Load relasi akun di payments
        $loan->load(['payments.user', 'payments.cashBankAccount', 'payments.interestExpenseAccount']); 
        
        return view('admin.loans.show', compact('loan'));
    }

    /**
     * Menampilkan form untuk mengedit pinjaman.
     */
    public function edit(Loan $loan): View
    {
        // $this->authorize('update', $loan);
        
        $loanAccounts = ChartOfAccount::where('account_type', 'Liabilitas')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
        $cashAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();

        return view('admin.loans.edit', compact('loan', 'loanAccounts', 'cashAccounts'));
    }

    /**
     * Mengupdate data pinjaman di database.
     */
    public function update(Request $request, Loan $loan): RedirectResponse
    {   
        $journalGroupId = "LOAN-" . $loan->loan_id;
        if ($error = $this->checkTransactionLock($loan->loan_date, $journalGroupId)) {
            return back()->with('error', "Gagal Update: " . $error);
        }

        // $this->authorize('update', $loan);
        
        if ($loan->payments()->exists()) {
            return back()->with('error', 'Pinjaman ini tidak bisa diedit karena sudah memiliki riwayat pembayaran.');
        }

        $validated = $request->validate([
            'lender_name' => 'required|string|max:255',
            'loan_date' => 'required|date',
            'principal_amount' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            // ✅ Validasi kolom baru
            'loan_account_id' => 'required|exists:chart_of_accounts,account_id',
            'cash_bank_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);
        
        DB::beginTransaction();
        try {
            // 1. Update data pinjaman
            $loan->update([
                'lender_name' => $validated['lender_name'],
                'loan_date' => $validated['loan_date'],
                'principal_amount' => $validated['principal_amount'],
                'remaining_balance' => $validated['principal_amount'],
                'description' => $validated['description'],
                'loan_account_id' => $validated['loan_account_id'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
            ]);

            // 2. Post ulang Jurnal Akuntansi (Service akan hapus yg lama)
            $journalGroupId = "LOAN-" . $loan->loan_id;
            $description = "Penerimaan Pinjaman (Update): " . $loan->lender_name;

            $debitEntries = [
                [$validated['cash_bank_account_id'], $validated['principal_amount']]
            ];
            $creditEntries = [
                [$validated['loan_account_id'], $validated['principal_amount']]
            ];

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['loan_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $loan,
                Auth::id() // Model referensi
            );

            DB::commit();
            return redirect()->route('admin.loans.index')->with('success', 'Data pinjaman berhasil diupdate.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate data: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menghapus pinjaman (hanya jika belum ada pembayaran).
     */
    public function destroy(Loan $loan): RedirectResponse
    {   
        $journalGroupId = "LOAN-" . $loan->loan_id;
        if ($error = $this->checkTransactionLock($loan->loan_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus: " . $error);
        }
        
        // $this->authorize('delete', $loan);
        
        if ($loan->payments()->exists()) {
            return back()->with('error', 'Pinjaman ini tidak bisa dihapus karena sudah memiliki riwayat pembayaran.');
        }
        
        DB::beginTransaction();
        try {
            // 1. Post Jurnal Reversal (Pembalikan)
            $journalGroupId = "LOAN-REVERSAL-" . $loan->loan_id;
            $description = "Reversal Penerimaan Pinjaman: " . $loan->lender_name;

            $debitEntries = [
                // [Akun Utang, Jumlah] (Utang lunas)
                [$loan->loan_account_id, $loan->principal_amount]
            ];
            $creditEntries = [
                // [Akun Kas/Bank, Jumlah] (Kas kembali)
                [$loan->cash_bank_account_id, $loan->principal_amount]
            ];
            
            $this->accountingService->postJournal(
                $journalGroupId,
                now(), // Tanggal reversal
                $description,
                $debitEntries,
                $creditEntries,
                $loan,
                Auth::id()
            );

            // 2. Hapus Jurnal Asli (LOAN-...)
            DB::table('general_ledgers')->where('journal_group_id', "LOAN-" . $loan->loan_id)->delete();

            // 3. Hapus data pinjaman
            $loan->delete();
            
            DB::commit();
            return redirect()->route('admin.loans.index')->with('success', 'Data pinjaman berhasil dihapus.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}