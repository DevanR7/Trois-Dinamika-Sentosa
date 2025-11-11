<?php

namespace App\Http\Controllers;

// Model-model untuk Arus Kas (Lama) & Laporan Utang/Piutang (Lama)
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Models\Payment;
use App\Models\PurchaseOrderPayment;
use App\Models\Expense;
use App\Models\LoanPayment;
use App\Models\FixedAsset; // <-- Ditambahkan untuk Arus Kas
use App\Models\EquityTransaction; // <-- Ditambahkan untuk Arus Kas

// ✅ Model BARU untuk Laporan Inti
use App\Models\GeneralLedger;
use App\Models\ChartOfAccount;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingSettingService;

class ReportController extends Controller
{
    protected $accountingSettings;

    public function __construct(AccountingSettingService $accountingSettingService)
    {
        $this->middleware('can:view-reports');
        $this->accountingSettings = $accountingSettingService;
    }
    
    public function index(Request $request): View
    {
        // --- 1. PENGATURAN TANGGAL ---
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $endDateCarbon = Carbon::parse($endDate)->endOfDay();

        // =================================================================
        // LAPORAN LABA RUGI (PROFIT & LOSS) - PERIODE ($startDate s/d $endDate)
        // Dihitung murni dari Jurnal Umum (General Ledger)
        // =================================================================
        
        $plAccounts = GeneralLedger::join('chart_of_accounts as coa', 'general_ledgers.chart_of_account_id', '=', 'coa.account_id')
            ->whereIn('coa.account_type', ['Pendapatan', 'HPP', 'Beban'])
            ->whereBetween('general_ledgers.entry_date', [$startDate, $endDate])
            ->select(
                'coa.account_id', 
                'coa.account_name', 
                'coa.account_type', 
                'coa.normal_balance',
                DB::raw('SUM(general_ledgers.debit) as total_debit'), 
                DB::raw('SUM(general_ledgers.credit) as total_credit')
            )
            ->groupBy('coa.account_id', 'coa.account_name', 'coa.account_type', 'coa.normal_balance')
            ->orderBy('coa.account_number')
            ->get();

        // Pisahkan berdasarkan Tipe Akun
        $labaRugi_pendapatan = $plAccounts->where('account_type', 'Pendapatan');
        $labaRugi_hpp = $plAccounts->where('account_type', 'HPP');
        $labaRugi_beban = $plAccounts->where('account_type', 'Beban');

        // Hitung Total (Saldo Normal Kredit - Saldo Normal Debit)
        $totalPendapatan = $labaRugi_pendapatan->sum(fn($acc) => $acc->total_credit - $acc->total_debit);
        $totalHPP = $labaRugi_hpp->sum(fn($acc) => $acc->total_debit - $acc->total_credit);
        $totalBeban = $labaRugi_beban->sum(fn($acc) => $acc->total_debit - $acc->total_credit);
        
        $labaKotor = $totalPendapatan - $totalHPP;
        $labaBersih = $labaKotor - $totalBeban;

        // =================================================================
        // LAPORAN NERACA (BALANCE SHEET) - SNAPSHOT PADA $endDate
        // Dihitung murni dari Jurnal Umum (General Ledger) s/d $endDate
        // =================================================================
        
        // 1. Ambil Saldo Akun Aset, Liabilitas, Ekuitas
        $bsAccounts = GeneralLedger::join('chart_of_accounts as coa', 'general_ledgers.chart_of_account_id', '=', 'coa.account_id')
            ->whereIn('coa.account_type', ['Aset', 'Liabilitas', 'Ekuitas'])
            ->where('general_ledgers.entry_date', '<=', $endDateCarbon)
            ->select(
                'coa.account_id', 
                'coa.account_name', 
                'coa.account_type', 
                'coa.normal_balance',
                DB::raw('SUM(general_ledgers.debit) as total_debit'), 
                DB::raw('SUM(general_ledgers.credit) as total_credit')
            )
            ->groupBy('coa.account_id', 'coa.account_name', 'coa.account_type', 'coa.normal_balance')
            ->orderBy('coa.account_number')
            ->get()
            ->map(function ($acc) {
                // Hitung saldo akhir berdasarkan saldo normal
                if ($acc->normal_balance == 'Debit') {
                    $acc->balance = $acc->total_debit - $acc->total_credit;
                } else {
                    $acc->balance = $acc->total_credit - $acc->total_debit;
                }
                return $acc;
            })
            ->filter(function ($acc) {
                // Sembunyikan akun dengan saldo 0
                return round($acc->balance, 2) != 0;
            });

        // Pisahkan Aset, Liabilitas, Ekuitas
        $neraca_aset = $bsAccounts->where('account_type', 'Aset');
        $neraca_liabilitas = $bsAccounts->where('account_type', 'Liabilitas');
        $neraca_ekuitas_non_pl = $bsAccounts->where('account_type', 'Ekuitas'); // Hanya Modal Disetor, Prive, dll.
        
        // 2. Hitung Laba/Rugi Akumulasi (dari Awal s/d $endDate)
        $plAccumulated = GeneralLedger::join('chart_of_accounts as coa', 'general_ledgers.chart_of_account_id', '=', 'coa.account_id')
            ->whereIn('coa.account_type', ['Pendapatan', 'HPP', 'Beban'])
            ->where('general_ledgers.entry_date', '<=', $endDateCarbon)
            ->select(
                'coa.account_type',
                DB::raw('SUM(general_ledgers.debit) as total_debit'), 
                DB::raw('SUM(general_ledgers.credit) as total_credit')
            )
            ->groupBy('coa.account_type')
            ->get()
            ->keyBy('account_type');

        $totalPendapatanAkumulasi = ($plAccumulated['Pendapatan']->total_credit ?? 0) - ($plAccumulated['Pendapatan']->total_debit ?? 0);
        $totalHppAkumulasi = ($plAccumulated['HPP']->total_debit ?? 0) - ($plAccumulated['HPP']->total_credit ?? 0);
        $totalBebanAkumulasi = ($plAccumulated['Beban']->total_debit ?? 0) - ($plAccumulated['Beban']->total_credit ?? 0);
        
        $ekuitas_labaRugiAkumulasi = $totalPendapatanAkumulasi - $totalHppAkumulasi - $totalBebanAkumulasi;

        // 3. Hitung Total Neraca
        $totalAset = $neraca_aset->sum('balance');
        $totalLiabilitas = $neraca_liabilitas->sum('balance');
        $totalEkuitasNonPl = $neraca_ekuitas_non_pl->sum('balance');
        $totalEkuitas = $totalEkuitasNonPl + $ekuitas_labaRugiAkumulasi;
        $totalLiabilitasDanEkuitas = $totalLiabilitas + $totalEkuitas;

        // =================================================================
        // LAPORAN PENDUKUNG (SUB-LEDGER & ARUS KAS LAMA)
        // =================================================================
        
        // 1. Arus Kas (Sama seperti Controller Lama, namun lebih lengkap)
        $pemasukan_invoice = Payment::whereBetween('payment_date', [$startDate, $endDate])->where('status', 'completed')->get();
        $pemasukan_modal = EquityTransaction::where('type', 'investment')->whereBetween('transaction_date', [$startDate, $endDate])->get();
        // (Anda bisa tambahkan pemasukan Pinjaman jika mau)
        $totalPemasukan = $pemasukan_invoice->sum('amount') + $pemasukan_modal->sum('amount');

        $pengeluaran_po = PurchaseOrderPayment::whereBetween('payment_date', [$startDate, $endDate])->where('status', 'completed')->get();
        $pengeluaran_beban = Expense::whereBetween('expense_date', [$startDate, $endDate])->get();
        $pengeluaran_pinjaman = LoanPayment::whereBetween('payment_date', [$startDate, $endDate])->get();
        $pengeluaran_aset = FixedAsset::whereBetween('purchase_date', [$startDate, $endDate])->get();
        $pengeluaran_modal = EquityTransaction::where('type', 'drawing')->whereBetween('transaction_date', [$startDate, $endDate])->get();

        $totalPengeluaranPO = $pengeluaran_po->sum('amount');
        $totalPengeluaranBeban = $pengeluaran_beban->sum('amount');
        $totalPengeluaranPinjaman = $pengeluaran_pinjaman->sum('total_paid');
        $totalPengeluaranAset = $pengeluaran_aset->sum('purchase_cost');
        $totalPengeluaranModal = $pengeluaran_modal->sum('amount');
        
        $totalPengeluaran = $totalPengeluaranPO + $totalPengeluaranBeban + $totalPengeluaranPinjaman + $totalPengeluaranAset + $totalPengeluaranModal;

        // 2. Laporan Rincian Utang & Piutang (Subsidiary Ledger)
        $laporanPiutang = SalesInvoice::with('client')
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->orderBy('due_date', 'asc')
            ->get();
        $totalPiutang_SL = $laporanPiutang->sum(fn($inv) => $inv->remaining_balance); // Gunakan accessor yg sudah diperbaiki

        $laporanUtang = PurchaseOrder::with('supplier')
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->orderBy('due_date', 'asc')
            ->get();
        $totalUtang_SL = $laporanUtang->sum(fn($po) => $po->remaining_balance); // Gunakan accessor yg sudah ada

        // =================================================================
        // MENGIRIM DATA KE VIEW
        // =================================================================
        
        return view('reports.index', compact(
            'startDate', 'endDate', 'endDateCarbon',
            
            // Laba Rugi (BARU - berbasis GL)
            'labaRugi_pendapatan', 'totalPendapatan',
            'labaRugi_hpp', 'totalHPP', 'labaKotor',
            'labaRugi_beban', 'totalBeban',
            'labaBersih',
            
            // Neraca (BARU - berbasis GL)
            'neraca_aset', 'totalAset',
            'neraca_liabilitas', 'totalLiabilitas',
            'neraca_ekuitas_non_pl',
            'ekuitas_labaRugiAkumulasi', 'totalEkuitas',
            'totalLiabilitasDanEkuitas',

            // Laporan Rincian (LAMA - untuk pendukung)
            'laporanPiutang', 'totalPiutang_SL',
            'laporanUtang', 'totalUtang_SL',
            
            // Arus Kas (LAMA - untuk pendukung)
            'pemasukan_invoice', 'pemasukan_modal', 'totalPemasukan',
            'pengeluaran_po', 'pengeluaran_beban', 'pengeluaran_pinjaman', 'pengeluaran_aset', 'pengeluaran_modal',
            'totalPengeluaranPO', 'totalPengeluaranBeban', 'totalPengeluaranPinjaman', 'totalPengeluaranAset', 'totalPengeluaranModal',
            'totalPengeluaran'
        ));
    }
}