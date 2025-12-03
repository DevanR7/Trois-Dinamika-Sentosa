<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
// Model Operasional
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Models\Payment;
use App\Models\PurchaseOrderPayment;
use App\Models\Expense;
use App\Models\LoanPayment;
use App\Models\FixedAsset;
use App\Models\EquityTransaction;
use App\Models\Loan; // Tambahkan ini
use App\Models\Depreciation;

// Model Akuntansi
use App\Models\GeneralLedger;
use App\Models\ChartOfAccount;

// Service
use App\Services\AccountingSettingService;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        
        $startDateCarbon = Carbon::parse($startDate)->startOfDay();
        $endDateCarbon = Carbon::parse($endDate)->endOfDay();
        $previousDateCarbon = Carbon::parse($startDate)->subDay()->endOfDay();

        // =================================================================
        // A. LAPORAN LABA RUGI (GL BASED)
        // =================================================================
        $plAccounts = GeneralLedger::join('chart_of_accounts as coa', 'general_ledgers.chart_of_account_id', '=', 'coa.account_id')
            ->whereIn('coa.account_type', ['Pendapatan', 'HPP', 'Beban'])
            ->whereBetween('general_ledgers.entry_date', [$startDate, $endDate])
            ->select(
                'coa.account_id', 'coa.account_name', 'coa.account_type', 'coa.normal_balance',
                DB::raw('SUM(general_ledgers.debit) as total_debit'), 
                DB::raw('SUM(general_ledgers.credit) as total_credit')
            )
            ->groupBy('coa.account_id', 'coa.account_name', 'coa.account_type', 'coa.normal_balance')
            ->orderBy('coa.account_number')
            ->get();

        $labaRugi_pendapatan = $plAccounts->where('account_type', 'Pendapatan');
        $labaRugi_hpp = $plAccounts->where('account_type', 'HPP');
        $labaRugi_beban = $plAccounts->where('account_type', 'Beban');

        $totalPendapatan = $labaRugi_pendapatan->sum(fn($acc) => $acc->total_credit - $acc->total_debit);
        $totalHPP = $labaRugi_hpp->sum(fn($acc) => $acc->total_debit - $acc->total_credit);
        $totalBeban = $labaRugi_beban->sum(fn($acc) => $acc->total_debit - $acc->total_credit);
        
        $labaKotor = $totalPendapatan - $totalHPP;
        $labaBersih = $labaKotor - $totalBeban;

        // =================================================================
        // B. LAPORAN NERACA (GL BASED)
        // =================================================================
        $bsAccounts = DB::table('general_ledgers')
    ->join('chart_of_accounts as coa', 'general_ledgers.chart_of_account_id', '=', 'coa.account_id')
    ->whereIn('coa.account_type', ['Aset', 'Liabilitas', 'Ekuitas'])
    ->where('general_ledgers.entry_date', '<=', $endDateCarbon)
    ->select(
        'coa.account_id', 
        'coa.account_name', 
        'coa.account_type', 
        'coa.normal_balance',
        'coa.account_number',
        DB::raw('SUM(CASE 
            WHEN coa.normal_balance = "Debit" THEN general_ledgers.debit - general_ledgers.credit 
            ELSE general_ledgers.credit - general_ledgers.debit 
        END) as balance')
    )
    ->groupBy('coa.account_id', 'coa.account_name', 'coa.account_type', 'coa.normal_balance', 'coa.account_number')
    ->orderBy('coa.account_number')
    ->get()
    ->filter(fn($acc) => round($acc->balance, 2) != 0);

        $neraca_aset = $bsAccounts->where('account_type', 'Aset');
        $neraca_liabilitas = $bsAccounts->where('account_type', 'Liabilitas');
        $neraca_ekuitas_non_pl = $bsAccounts->where('account_type', 'Ekuitas');
        
        $plAccumulated = GeneralLedger::join('chart_of_accounts as coa', 'general_ledgers.chart_of_account_id', '=', 'coa.account_id')
            ->whereIn('coa.account_type', ['Pendapatan', 'HPP', 'Beban'])
            ->where('general_ledgers.entry_date', '<=', $endDateCarbon)
            ->select(DB::raw('SUM(CASE WHEN coa.normal_balance = "Kredit" THEN credit - debit ELSE debit - credit END) as net_balance'), 'coa.account_type')
            ->groupBy('coa.account_type')
            ->pluck('net_balance', 'account_type');
            
        $totalPendapatanAkum = $plAccumulated['Pendapatan'] ?? 0;
        $totalHppAkum = $plAccumulated['HPP'] ?? 0;
        $totalBebanAkum = $plAccumulated['Beban'] ?? 0;
        
        $ekuitas_labaRugiAkumulasi = $totalPendapatanAkum - ($totalHppAkum + $totalBebanAkum);

        $totalAset = $neraca_aset->sum('balance');
        $totalLiabilitas = $neraca_liabilitas->sum('balance');
        $totalEkuitas = $neraca_ekuitas_non_pl->sum('balance') + $ekuitas_labaRugiAkumulasi;
        $totalLiabilitasDanEkuitas = $totalLiabilitas + $totalEkuitas;

        // =================================================================
        // C. LAPORAN ARUS KAS (INDIRECT METHOD)
        // =================================================================
        $cf_operating_net_income = $labaBersih;
        $cf_operating_depreciation = Depreciation::whereBetween('depreciation_date', [$startDate, $endDate])->sum('amount');

        $arId = $this->accountingSettings->getAccountsReceivableId();
        $invId = $this->accountingSettings->getInventoryId();
        $apId = $this->accountingSettings->getAccountsPayableId();
        $clientDepositId = $this->accountingSettings->getClientDepositId();
        $supplierDepositId = $this->accountingSettings->getSupplierDepositId();

        $getChange = function($accountId, $isAsset) use ($previousDateCarbon, $endDateCarbon) {
            if (!$accountId) return 0;
            $startBal = GeneralLedger::where('chart_of_account_id', $accountId)->where('entry_date', '<=', $previousDateCarbon)->sum(DB::raw($isAsset ? 'debit - credit' : 'credit - debit'));
            $endBal = GeneralLedger::where('chart_of_account_id', $accountId)->where('entry_date', '<=', $endDateCarbon)->sum(DB::raw($isAsset ? 'debit - credit' : 'credit - debit'));
            return $isAsset ? ($startBal - $endBal) : ($endBal - $startBal);
        };

        $cf_change_ar = $getChange($arId, true);
        $cf_change_inventory = $getChange($invId, true);
        $cf_change_supplier_deposit = $getChange($supplierDepositId, true);
        $cf_change_ap = $getChange($apId, false);
        $cf_change_client_deposit = $getChange($clientDepositId, false);

        $total_cash_from_operations = $cf_operating_net_income + $cf_operating_depreciation + $cf_change_ar + $cf_change_inventory + $cf_change_supplier_deposit + $cf_change_ap + $cf_change_client_deposit;

        $cf_investing_purchase_asset = FixedAsset::whereBetween('purchase_date', [$startDate, $endDate])->sum('purchase_cost');
        $total_cash_from_investing = -($cf_investing_purchase_asset);

        $cf_financing_capital_in = EquityTransaction::where('type', 'investment')->whereBetween('transaction_date', [$startDate, $endDate])->sum('amount');
        $cf_financing_drawing = EquityTransaction::where('type', 'drawing')->whereBetween('transaction_date', [$startDate, $endDate])->sum('amount');
        $cf_financing_loan_in = Loan::whereBetween('loan_date', [$startDate, $endDate])->sum('principal_amount');
        $cf_financing_loan_pay = LoanPayment::whereBetween('payment_date', [$startDate, $endDate])->sum('principal_paid');

        $total_cash_from_financing = $cf_financing_capital_in - $cf_financing_drawing + $cf_financing_loan_in - $cf_financing_loan_pay;
        $net_increase_cash = $total_cash_from_operations + $total_cash_from_investing + $total_cash_from_financing;

        $cashAccountIds = \App\Models\CompanyBankAccount::whereNotNull('chart_of_account_id')->pluck('chart_of_account_id')->toArray();
        if(empty($cashAccountIds)) {
            $cashAccountIds = ChartOfAccount::where('account_type', 'Aset')->where(function($q) { $q->where('account_name', 'like', '%Kas%')->orWhere('account_name', 'like', '%Bank%'); })->pluck('account_id')->toArray();
        }
        $cash_beginning = GeneralLedger::whereIn('chart_of_account_id', $cashAccountIds)->where('entry_date', '<', $startDate)->sum(DB::raw('debit - credit'));
        $cash_ending = $cash_beginning + $net_increase_cash;

        // =================================================================
        // D. LAPORAN PENDUKUNG (SUB-LEDGER) & ARUS KAS DIRECT (UNTUK VIEW)
        // ✅ PERBAIKAN: Pastikan semua variabel ini ada
        // =================================================================
        
        // 1. Variabel Arus Kas Sederhana (Direct)
        $pemasukan_invoice = Payment::whereBetween('payment_date', [$startDate, $endDate])->where('status', 'completed')->get();
        $pemasukan_modal = EquityTransaction::where('type', 'investment')->whereBetween('transaction_date', [$startDate, $endDate])->get();
        
        // ✅ INI YANG HILANG SEBELUMNYA:
        $totalPemasukan = $pemasukan_invoice->sum('amount') + $pemasukan_modal->sum('amount');

        $pengeluaran_po = PurchaseOrderPayment::whereBetween('payment_date', [$startDate, $endDate])->where('status', 'completed')->get();
        $pengeluaran_beban = Expense::whereBetween('expense_date', [$startDate, $endDate])->get();
        $pengeluaran_pinjaman = LoanPayment::whereBetween('payment_date', [$startDate, $endDate])->get();
        $pengeluaran_aset = FixedAsset::whereBetween('purchase_date', [$startDate, $endDate])->get();
        $pengeluaran_modal = EquityTransaction::where('type', 'drawing')->whereBetween('transaction_date', [$startDate, $endDate])->get();

        // ✅ Hitung Total Pengeluaran untuk View
        $totalPengeluaranPO = $pengeluaran_po->sum('amount');
        $totalPengeluaranBeban = $pengeluaran_beban->sum('amount');
        $totalPengeluaranPinjaman = $pengeluaran_pinjaman->sum('total_paid');
        $totalPengeluaranAset = $pengeluaran_aset->sum('purchase_cost');
        $totalPengeluaranModal = $pengeluaran_modal->sum('amount');
        
        $totalPengeluaran = $totalPengeluaranPO + $totalPengeluaranBeban + $totalPengeluaranPinjaman + $totalPengeluaranAset + $totalPengeluaranModal;

        // 2. Variabel Rincian Piutang & Utang
        $laporanPiutang = SalesInvoice::with('client')->whereIn('status', ['unpaid', 'partially_paid'])->orderBy('due_date', 'asc')->get();
        $totalPiutang_SL = $laporanPiutang->sum(fn($inv) => $inv->remaining_balance);

        $laporanUtang = PurchaseOrder::with('supplier')->whereIn('payment_status', ['unpaid', 'partially_paid'])->orderBy('due_date', 'asc')->get();
        $totalUtang_SL = $laporanUtang->sum(fn($po) => $po->remaining_balance);

        return view('admin.reports.index', compact(
            'startDate', 'endDate', 'endDateCarbon',
            // Laba Rugi
            'labaRugi_pendapatan', 'totalPendapatan',
            'labaRugi_hpp', 'totalHPP', 'labaKotor',
            'labaRugi_beban', 'totalBeban', 'labaBersih',
            // Neraca
            'neraca_aset', 'totalAset',
            'neraca_liabilitas', 'totalLiabilitas',
            'neraca_ekuitas_non_pl', 'ekuitas_labaRugiAkumulasi', 'totalEkuitas', 'totalLiabilitasDanEkuitas',
            // Arus Kas Indirect
            'cf_operating_net_income', 'cf_operating_depreciation',
            'cf_change_ar', 'cf_change_inventory', 'cf_change_ap', 'cf_change_supplier_deposit', 'cf_change_client_deposit',
            'total_cash_from_operations', 'cf_investing_purchase_asset', 'total_cash_from_investing',
            'cf_financing_capital_in', 'cf_financing_drawing', 'cf_financing_loan_in', 'cf_financing_loan_pay', 'total_cash_from_financing',
            'net_increase_cash', 'cash_beginning', 'cash_ending',
            // Pendukung & Arus Kas Direct (✅ SUDAH LENGKAP)
            'laporanPiutang', 'totalPiutang_SL', 
            'laporanUtang', 'totalUtang_SL',
            'pemasukan_invoice', 'pemasukan_modal', 'totalPemasukan',
            'pengeluaran_po', 'pengeluaran_beban', 'pengeluaran_pinjaman', 'pengeluaran_aset', 'pengeluaran_modal',
            'totalPengeluaranPO', 'totalPengeluaranBeban', 'totalPengeluaranPinjaman', 'totalPengeluaranAset', 'totalPengeluaranModal',
            'totalPengeluaran'
        ));
    }
}