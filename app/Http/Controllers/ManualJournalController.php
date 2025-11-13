<?php

namespace App\Http\Controllers;

use App\Models\ManualJournal;
use App\Models\ManualJournalEntry;
use App\Models\ChartOfAccount;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class ManualJournalController extends Controller
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
        
        // (Opsional) Buat permission baru 'manage-manual-journals'
        // $this->middleware('can:manage-manual-journals'); 
        
        // Untuk saat ini, kita gunakan 'manage-settings' agar Admin bisa akses
        $this->middleware('can:manage-settings'); 
    }

    /**
     * Menampilkan daftar Jurnal Umum Manual.
     */
    public function index(Request $request): View
    {
        $query = ManualJournal::with('user')->orderBy('entry_date', 'desc');

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('journal_number', 'like', '%' . $request->search . '%');
        }

        $manualJournals = $query->paginate(20)->appends($request->query());
        
        return view('manual_journals.index', compact('manualJournals'));
    }

    /**
     * Menampilkan form untuk membuat Jurnal Manual baru.
     */
    public function create(): View
    {
        // Ambil semua akun aktif untuk dropdown
        $accounts = ChartOfAccount::where('is_active', true)
                        ->orderBy('account_number')
                        ->get();
                        
        return view('manual_journals.create', compact('accounts'));
    }

    /**
     * Menyimpan Jurnal Manual baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:1000',
            'entries' => 'required|array|min:2', // Minimal 1 Debit dan 1 Kredit
            'entries.*.account_id' => 'required|exists:chart_of_accounts,account_id',
            'entries.*.debit' => 'nullable|numeric|min:0',
            'entries.*.credit' => 'nullable|numeric|min:0',
            'entries.*.description' => 'nullable|string|max:255',
        ]);

        $totalDebit = 0;
        $totalCredit = 0;
        $journalEntries = [];

        foreach ($validated['entries'] as $entry) {
            $debit = (float)($entry['debit'] ?? 0);
            $credit = (float)($entry['credit'] ?? 0);

            // Pastikan tidak ada baris yg debit & kreditnya diisi
            if ($debit > 0 && $credit > 0) {
                return back()->with('error', 'Satu baris tidak boleh memiliki Debit dan Kredit sekaligus.')->withInput();
            }
            // Pastikan baris diisi
            if ($debit == 0 && $credit == 0) {
                continue; // Abaikan baris kosong
            }
            
            $totalDebit += $debit;
            $totalCredit += $credit;
            $journalEntries[] = $entry; // Simpan entri yang valid
        }

        // Validasi Keseimbangan
        if (round($totalDebit, 2) != round($totalCredit, 2)) {
            return back()->with('error', 'Jurnal tidak seimbang! Total Debit (Rp ' . number_format($totalDebit) . ') harus sama dengan Total Kredit (Rp ' . number_format($totalCredit) . ').')->withInput();
        }
        
        if ($totalDebit == 0) {
            return back()->with('error', 'Total Debit/Kredit tidak boleh nol.')->withInput();
        }

        DB::beginTransaction();
        try {
            // 1. Buat Header Jurnal
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

            // 2. Buat Detail/Baris Jurnal
            foreach ($journalEntries as $entry) {
                $debit = (float)($entry['debit'] ?? 0);
                $credit = (float)($entry['credit'] ?? 0);
                $accountId = $entry['account_id'];
                $lineDescription = $entry['description'] ?? $validated['description'];

                $manualJournal->entries()->create([
                    'chart_of_account_id' => $accountId,
                    'debit' => $debit,
                    'credit' => $credit,
                    'description' => $lineDescription,
                ]);

                // 3. Siapkan data untuk Jurnal Umum (General Ledger)
                if ($debit > 0) {
                    $debitEntriesForGL[] = [$accountId, $debit, $lineDescription];
                }
                if ($credit > 0) {
                    $creditEntriesForGL[] = [$accountId, $credit, $lineDescription];
                }
            }

            // 4. Post ke Jurnal Umum (General Ledger)
            $this->accountingService->postJournal(
                $manualJournal->journal_number, // Gunakan nomor jurnal sebagai ID Grup
                $manualJournal->entry_date,
                $manualJournal->description,
                $debitEntriesForGL,
                $creditEntriesForGL,
                $manualJournal // Referensi ke model ManualJournal
            );

            DB::commit();
            return redirect()->route('manual-journals.index')->with('success', 'Jurnal Umum Manual (' . $manualJournal->journal_number . ') berhasil diposting.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan Jurnal Manual: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan Jurnal Manual: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menampilkan detail Jurnal Manual.
     */
    public function show(ManualJournal $manualJournal): View
    {
        $manualJournal->load('entries.account', 'user');
        return view('manual_journals.show', compact('manualJournal'));
    }

    /**
     * Menampilkan form untuk mengedit Jurnal Manual.
     * (Catatan: Ini akan mem-posting ulang jurnal)
     */
    public function edit(ManualJournal $manualJournal): View
    {
        $manualJournal->load('entries');
        $accounts = ChartOfAccount::where('is_active', true)
                        ->orderBy('account_number')
                        ->get();
                        
        return view('manual_journals.edit', compact('manualJournal', 'accounts'));
    }

    /**
     * Mengupdate Jurnal Manual.
     */
    public function update(Request $request, ManualJournal $manualJournal): RedirectResponse
    {
        // (Logika validasi sama persis seperti store)
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:1000',
            'entries' => 'required|array|min:2',
            'entries.*.account_id' => 'required|exists:chart_of_accounts,account_id',
            'entries.*.debit' => 'nullable|numeric|min:0',
            'entries.*.credit' => 'nullable|numeric|min:0',
            'entries.*.description' => 'nullable|string|max:255',
        ]);
        
        $totalDebit = 0;
        $totalCredit = 0;
        $journalEntries = [];
        // ... (Loop validasi debit/kredit & keseimbangan, sama seperti store) ...
        foreach ($validated['entries'] as $entry) {
            $debit = (float)($entry['debit'] ?? 0);
            $credit = (float)($entry['credit'] ?? 0);
            if ($debit > 0 && $credit > 0) {
                return back()->with('error', 'Satu baris tidak boleh memiliki Debit dan Kredit sekaligus.')->withInput();
            }
            if ($debit == 0 && $credit == 0) continue;
            $totalDebit += $debit;
            $totalCredit += $credit;
            $journalEntries[] = $entry;
        }
        if (round($totalDebit, 2) != round($totalCredit, 2)) {
            return back()->with('error', 'Jurnal tidak seimbang! Total Debit (Rp ' . number_format($totalDebit) . ') harus sama dengan Total Kredit (Rp ' . number_format($totalCredit) . ').')->withInput();
        }
        if ($totalDebit == 0) {
            return back()->with('error', 'Total Debit/Kredit tidak boleh nol.')->withInput();
        }

        DB::beginTransaction();
        try {
            // 1. Update Header Jurnal
            $manualJournal->update([
                'entry_date' => $validated['entry_date'],
                'description' => $validated['description'],
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'user_id' => Auth::id(),
            ]);

            // Hapus entri lama
            $manualJournal->entries()->delete();

            $debitEntriesForGL = [];
            $creditEntriesForGL = [];

            // 2. Buat ulang Detail/Baris Jurnal
            foreach ($journalEntries as $entry) {
                $debit = (float)($entry['debit'] ?? 0);
                $credit = (float)($entry['credit'] ?? 0);
                $accountId = $entry['account_id'];
                $lineDescription = $entry['description'] ?? $validated['description'];

                $manualJournal->entries()->create([
                    'chart_of_account_id' => $accountId,
                    'debit' => $debit,
                    'credit' => $credit,
                    'description' => $lineDescription,
                ]);

                // 3. Siapkan data untuk Jurnal Umum (General Ledger)
                if ($debit > 0) {
                    $debitEntriesForGL[] = [$accountId, $debit, $lineDescription];
                }
                if ($credit > 0) {
                    $creditEntriesForGL[] = [$accountId, $credit, $lineDescription];
                }
            }

            // 4. Post ulang ke Jurnal Umum (General Ledger)
            // (Service akan otomatis menghapus entri lama berdasarkan journal_number)
            $this->accountingService->postJournal(
                $manualJournal->journal_number, // ID Grup Jurnal tetap sama
                $manualJournal->entry_date,
                $manualJournal->description,
                $debitEntriesForGL,
                $creditEntriesForGL,
                $manualJournal // Referensi ke model ManualJournal
            );

            DB::commit();
            return redirect()->route('manual-journals.index')->with('success', 'Jurnal Manual (' . $manualJournal->journal_number . ') berhasil diperbarui.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal update Jurnal Manual: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengupdate Jurnal Manual: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menghapus Jurnal Manual (Membuat Jurnal Balik/Reversal).
     */
    public function destroy(ManualJournal $manualJournal): RedirectResponse
    {
        DB::beginTransaction();
        try {
            // 1. Post Jurnal Reversal (Pembalikan)
            $reversalGroupId = "JUM-REV-" . $manualJournal->id;
            $description = "Reversal: " . $manualJournal->description;

            $debitEntries = [];
            $creditEntries = [];
            
            // Ambil entri asli dan balikkan
            foreach ($manualJournal->entries as $entry) {
                if ($entry->debit > 0) {
                    $creditEntries[] = [$entry->chart_of_account_id, $entry->debit, "Reversal: " . $entry->description];
                }
                if ($entry->credit > 0) {
                    $debitEntries[] = [$entry->chart_of_account_id, $entry->credit, "Reversal: " . $entry->description];
                }
            }

            $this->accountingService->postJournal(
                $reversalGroupId,
                now(), // Tanggal reversal
                $description,
                $debitEntries,
                $creditEntries,
                $manualJournal
            );
            
            // 2. Hapus Jurnal Asli dari General Ledger
            DB::table('general_ledgers')->where('journal_group_id', $manualJournal->journal_number)->delete();

            // 3. Hapus Jurnal Manual (Header & Lines)
            // (Entries akan terhapus otomatis karena 'onDelete('cascade')')
            $manualJournal->delete();
            
            DB::commit();
            return redirect()->route('manual-journals.index')->with('success', 'Jurnal Manual berhasil dihapus (dibalik).');
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menghapus Jurnal Manual: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus Jurnal Manual: ' . $e->getMessage());
        }
    }
}