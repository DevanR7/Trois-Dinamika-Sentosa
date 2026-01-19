<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualJournal;
use App\Models\ChartOfAccount;
use App\Services\AccountingService;
use App\Traits\ValidatesAccountingPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class ManualJournalController extends Controller
{
    use ValidatesAccountingPeriod; 
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
        $this->middleware('can:manage-manual-journals');
    }

    public function index(Request $request): View
    {
        $query = ManualJournal::with('user')->orderBy('entry_date', 'desc');

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('journal_number', 'like', '%' . $request->search . '%');
            });
        }

        $manualJournals = $query->paginate(20)->appends($request->query());
        return view('admin.manual_journals.index', compact('manualJournals'));
    }

    public function create(): View
    {
        $accounts = ChartOfAccount::where('is_active', true)
            ->orderBy('account_number')
            ->get();
                        
        return view('admin.manual_journals.create', compact('accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->isDateClosed($request->entry_date)) {
            $year = \Carbon\Carbon::parse($request->entry_date)->year;
            return back()->with('error', "Gagal: Tahun buku $year sudah ditutup. Tidak bisa menambah transaksi mundur.")->withInput();
        }

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:1000',
            'entries' => 'required|array|min:2',
            'entries.*.account_id' => 'required|exists:chart_of_accounts,account_id',
            'entries.*.debit' => 'nullable|numeric|min:0',
            'entries.*.credit' => 'nullable|numeric|min:0',
            'entries.*.description' => 'nullable|string|max:255',
        ]);

        $totalDebit = 0; $totalCredit = 0; $journalEntries = [];

        foreach ($validated['entries'] as $entry) {
            $debit = (float)($entry['debit'] ?? 0);
            $credit = (float)($entry['credit'] ?? 0);

            if ($debit > 0 && $credit > 0) return back()->with('error', 'Satu baris tidak boleh Debit & Kredit.')->withInput();
            if ($debit == 0 && $credit == 0) continue;
            
            $totalDebit += $debit;
            $totalCredit += $credit;
            $journalEntries[] = $entry;
        }

        if (round($totalDebit, 2) != round($totalCredit, 2)) {
            return back()->with('error', 'Jurnal tidak seimbang!')->withInput();
        }
        
        DB::beginTransaction();
        try {
            $manualJournal = ManualJournal::create([
                'journal_number' => ManualJournal::generateJournalNumber(),
                'entry_date' => $validated['entry_date'],
                'description' => $validated['description'],
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'user_id' => Auth::id(),
            ]);

            $debitEntriesForGL = [];
            $creditEntriesForGL = [];

            foreach ($journalEntries as $entry) {
                $debit = (float)($entry['debit'] ?? 0);
                $credit = (float)($entry['credit'] ?? 0);
                $desc = $entry['description'] ?? $validated['description'];

                $manualJournal->entries()->create([
                    'chart_of_account_id' => $entry['account_id'],
                    'debit' => $debit, 'credit' => $credit, 'description' => $desc,
                ]);

                if ($debit > 0) $debitEntriesForGL[] = [$entry['account_id'], $debit, $desc];
                if ($credit > 0) $creditEntriesForGL[] = [$entry['account_id'], $credit, $desc];
            }

            $this->accountingService->postJournal(
                $manualJournal->journal_number, $manualJournal->entry_date, $manualJournal->description,
                $debitEntriesForGL, $creditEntriesForGL, $manualJournal
            );

            DB::commit();
            return redirect()->route('admin.manual-journals.index')->with('success', 'Jurnal berhasil diposting.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Manual Journal Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage())->withInput();
        }
    }

    public function show(ManualJournal $manualJournal): View
    {
        $manualJournal->load('entries.account', 'user');
        return view('admin.manual_journals.show', compact('manualJournal'));
    }

    public function edit(ManualJournal $manualJournal): View
    {
        $manualJournal->load('entries');
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('account_number')->get();
        return view('admin.manual_journals.edit', compact('manualJournal', 'accounts'));
    }

    public function update(Request $request, ManualJournal $manualJournal): RedirectResponse
    {
        if ($error = $this->checkTransactionLock($manualJournal->entry_date, $manualJournal->journal_number, $manualJournal->description)) {
            return back()->with('error', $error);
        }

        if ($request->filled('entry_date') && $this->isDateClosed($request->entry_date)) {
            $year = \Carbon\Carbon::parse($request->entry_date)->year;
            return back()->with('error', "Gagal Update: Tanggal baru berada di tahun $year yang sudah ditutup.")->withInput();
        }

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:1000',
            'entries' => 'required|array|min:2',
            'entries.*.account_id' => 'required|exists:chart_of_accounts,account_id',
            'entries.*.debit' => 'nullable|numeric|min:0',
            'entries.*.credit' => 'nullable|numeric|min:0',
            'entries.*.description' => 'nullable|string|max:255',
        ]);
        
        $totalDebit = 0; $totalCredit = 0; $journalEntries = [];
        foreach ($validated['entries'] as $entry) {
            $debit = (float)($entry['debit'] ?? 0);
            $credit = (float)($entry['credit'] ?? 0);
            if ($debit > 0 && $credit > 0) return back()->with('error', 'Error: Satu baris ada Debit & Kredit.')->withInput();
            if ($debit == 0 && $credit == 0) continue;
            $totalDebit += $debit; $totalCredit += $credit;
            $journalEntries[] = $entry;
        }

        if (round($totalDebit, 2) != round($totalCredit, 2)) return back()->with('error', 'Jurnal tidak seimbang!')->withInput();

        DB::beginTransaction();
        try {
            $manualJournal->update([
                'entry_date' => $validated['entry_date'],
                'description' => $validated['description'],
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'user_id' => Auth::id(),
            ]);

            $manualJournal->entries()->delete();
            $debitEntriesForGL = [];
            $creditEntriesForGL = [];

            foreach ($journalEntries as $entry) {
                $debit = (float)($entry['debit'] ?? 0);
                $credit = (float)($entry['credit'] ?? 0);
                $desc = $entry['description'] ?? $validated['description'];

                $manualJournal->entries()->create([
                    'chart_of_account_id' => $entry['account_id'],
                    'debit' => $debit, 'credit' => $credit, 'description' => $desc,
                ]);

                if ($debit > 0) $debitEntriesForGL[] = [$entry['account_id'], $debit, $desc];
                if ($credit > 0) $creditEntriesForGL[] = [$entry['account_id'], $credit, $desc];
            }

            $this->accountingService->postJournal(
                $manualJournal->journal_number, $manualJournal->entry_date, $manualJournal->description,
                $debitEntriesForGL, $creditEntriesForGL, $manualJournal
            );

            DB::commit();
            return redirect()->route('admin.manual-journals.index')->with('success', 'Jurnal berhasil diperbarui.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal update: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(ManualJournal $manualJournal): RedirectResponse
    {
        if ($error = $this->checkTransactionLock($manualJournal->entry_date, $manualJournal->journal_number, $manualJournal->description)) {
            return back()->with('error', "Gagal Hapus: " . $error);
        }

        DB::beginTransaction();
        try {
            
            DB::table('general_ledgers')->where('journal_group_id', $manualJournal->journal_number)->delete();

            $manualJournal->delete();
            
            DB::commit();
            return redirect()->route('admin.manual-journals.index')->with('success', 'Jurnal berhasil dihapus.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }
}