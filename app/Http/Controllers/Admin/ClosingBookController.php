<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\ManualJournal;
use App\Models\GeneralLedger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;

class ClosingBookController extends Controller
{
    protected $accountingService;
    protected $accountingSettings;

    public function __construct(
        AccountingService $accountingService,
        AccountingSettingService $accountingSettings
    ) {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettings;
        $this->middleware('can:manage-settings');
    }

    public function index(): View
    {
        $firstEntryYear = GeneralLedger::min(DB::raw('YEAR(entry_date)'));
        $currentYear = now()->year;
        
        $availableYears = [];
        if ($firstEntryYear) {
            for ($year = $currentYear; $year >= $firstEntryYear; $year--) {
                $availableYears[] = $year;
            }
        } else {
            $availableYears[] = $currentYear;
        }

        $closedYears = ManualJournal::where('description', 'LIKE', 'Jurnal Penutup Tahun%')
                        ->select(DB::raw('YEAR(entry_date) as year'))
                        ->distinct()
                        ->pluck('year')
                        ->toArray();

        return view('admin.closing_book.index', compact('availableYears', 'closedYears'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => 'required|numeric|min:2020|max:' . now()->year,
        ]);

        $year = $validated['year'];
        $startDate = Carbon::create($year, 1, 1)->startOfDay();
        $endDate = Carbon::create($year, 12, 31)->endOfDay();
        $retainedEarningsAccount = $this->accountingSettings->getRetainedEarningsId();

        if (!$retainedEarningsAccount) {
            return back()->with('error', 'Gagal: Akun "Laba Ditahan" belum diatur di Pengaturan -> Akun Default.');
        }

        $isClosed = ManualJournal::where('description', 'LIKE', 'Jurnal Penutup Tahun ' . $year)->exists();
        if ($isClosed) {
            return back()->with('error', "Gagal: Tahun $year sudah ditutup sebelumnya.");
        }

        $plAccounts = GeneralLedger::join('chart_of_accounts as coa', 'general_ledgers.chart_of_account_id', '=', 'coa.account_id')
            ->whereIn('coa.account_type', ['Pendapatan', 'HPP', 'Beban'])
            ->whereBetween('general_ledgers.entry_date', [$startDate, $endDate])
            ->select(
                'coa.account_id', 
                'coa.account_name', 
                'coa.normal_balance',
                DB::raw('SUM(general_ledgers.debit) as total_debit'), 
                DB::raw('SUM(general_ledgers.credit) as total_credit')
            )
            ->groupBy('coa.account_id', 'coa.account_name', 'coa.normal_balance')
            ->get();

        $debitEntriesForGL = [];
        $creditEntriesForGL = [];
        $totalNetIncome = 0;

        foreach ($plAccounts as $account) {
            if ($account->normal_balance == 'Debit') {
                $balance = $account->total_debit - $account->total_credit;
                if ($balance > 0) {
                    $creditEntriesForGL[] = [$account->account_id, $balance, "Tutup Buku: " . $account->account_name];
                    $totalNetIncome -= $balance; 
                }
            } else { 
                $balance = $account->total_credit - $account->total_debit;
                if ($balance > 0) {
                    $debitEntriesForGL[] = [$account->account_id, $balance, "Tutup Buku: " . $account->account_name];
                    $totalNetIncome += $balance; 
                }
            }
        }

        if (empty($debitEntriesForGL) && empty($creditEntriesForGL)) {
             return back()->with('info', "Tidak ada data Laba Rugi yang bisa ditutup untuk tahun $year.");
        }

        if ($totalNetIncome > 0) {
            $creditEntriesForGL[] = [$retainedEarningsAccount, $totalNetIncome, "Tutup Buku: Laba Bersih Tahun " . $year];
        } elseif ($totalNetIncome < 0) {
            $debitEntriesForGL[] = [$retainedEarningsAccount, abs($totalNetIncome), "Tutup Buku: Rugi Bersih Tahun " . $year];
        }
        
        $totalDebit = array_sum(array_column($debitEntriesForGL, 1));
        $totalCredit = array_sum(array_column($creditEntriesForGL, 1));

        DB::beginTransaction();
        try {
            $manualJournal = ManualJournal::create([
                'journal_number' => ManualJournal::generateJournalNumber(),
                'entry_date' => $endDate,
                'description' => "Jurnal Penutup Tahun " . $year,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'user_id' => Auth::id(),
            ]);

            $this->accountingService->postJournal(
                $manualJournal->journal_number,
                $manualJournal->entry_date,
                $manualJournal->description,
                $debitEntriesForGL,
                $creditEntriesForGL,
                $manualJournal,
                Auth::id()
            );
            
            foreach ($debitEntriesForGL as $entry) {
                $manualJournal->entries()->create(['chart_of_account_id' => $entry[0], 'debit' => $entry[1], 'description' => $entry[2]]);
            }
            foreach ($creditEntriesForGL as $entry) {
                $manualJournal->entries()->create(['chart_of_account_id' => $entry[0], 'credit' => $entry[1], 'description' => $entry[2]]);
            }

            DB::commit();
            return redirect()->route('admin.closing-book.index')->with('success', "Tutup Buku Tahun $year berhasil. Laba/Rugi telah dipindahkan ke Laba Ditahan.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal Proses Tutup Buku: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses Tutup Buku: ' . $e->getMessage());
        }
    }
}