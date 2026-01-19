<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Models\Payment;
use App\Models\PurchaseOrderPayment;
use App\Models\Expense;
use App\Models\LoanPayment;
use App\Models\FixedAsset;
use App\Models\EquityTransaction;
use App\Models\Loan;
use App\Models\Depreciation;
use App\Models\GeneralLedger;
use App\Models\ChartOfAccount;
use App\Models\CompanyBankAccount;
use App\Services\AccountingSettingService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    protected $accountingSettings;

    public function __construct(AccountingSettingService $accountingSettingService)
    {
        $this->accountingSettings = $accountingSettingService;
        $this->middleware('can:view-reports');
    }

    /**
     * Logic inti perhitungan keuangan dipisah agar bisa dipakai oleh Index (Web) dan PrintPDF (Cetak).
     */
    private function getFinancialData($startDate, $endDate)
    {
        $startDateCarbon = Carbon::parse($startDate)->startOfDay();
        $endDateCarbon = Carbon::parse($endDate)->endOfDay();
        $previousDateCarbon = Carbon::parse($startDate)->subDay()->endOfDay();

        // --- 1. LABA RUGI ---
        $plAccounts = GeneralLedger::join('chart_of_accounts as coa', 'general_ledgers.chart_of_account_id', '=', 'coa.account_id')
            ->whereIn('coa.account_type', ['Pendapatan', 'HPP', 'Beban'])
            ->whereBetween('general_ledgers.entry_date', [$startDate, $endDate])
            ->select(
                'coa.account_id', 'coa.account_name', 'coa.account_type', 'coa.normal_balance', 'coa.account_number',
                DB::raw('SUM(general_ledgers.debit) as total_debit'),
                DB::raw('SUM(general_ledgers.credit) as total_credit')
            )
            ->groupBy('coa.account_id', 'coa.account_name', 'coa.account_type', 'coa.normal_balance', 'coa.account_number')
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

        // --- 2. NERACA ---
        $bsAccounts = DB::table('general_ledgers')
            ->join('chart_of_accounts as coa', 'general_ledgers.chart_of_account_id', '=', 'coa.account_id')
            ->whereIn('coa.account_type', ['Aset', 'Liabilitas', 'Ekuitas'])
            ->where('general_ledgers.entry_date', '<=', $endDateCarbon)
            ->select(
                'coa.account_id', 'coa.account_name', 'coa.account_type', 'coa.normal_balance', 'coa.account_number',
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

        $ekuitas_labaRugiAkumulasi = ($plAccumulated['Pendapatan'] ?? 0) - (($plAccumulated['HPP'] ?? 0) + ($plAccumulated['Beban'] ?? 0));

        $totalAset = $neraca_aset->sum('balance');
        $totalLiabilitas = $neraca_liabilitas->sum('balance');
        $totalEkuitas = $neraca_ekuitas_non_pl->sum('balance') + $ekuitas_labaRugiAkumulasi;
        $totalLiabilitasDanEkuitas = $totalLiabilitas + $totalEkuitas;

        // --- 3. ARUS KAS ---
        $cf_operating_net_income = $labaBersih;
        $cf_operating_depreciation = Depreciation::whereBetween('depreciation_date', [$startDate, $endDate])->sum('amount');

        $arId = $this->accountingSettings->getAccountsReceivableId();
        $invId = $this->accountingSettings->getInventoryId();
        $apId = $this->accountingSettings->getAccountsPayableId();
        $clientDepositId = $this->accountingSettings->getClientDepositId();
        $supplierDepositId = $this->accountingSettings->getSupplierDepositId();

        $getChange = function($accountId, $isAsset) use ($previousDateCarbon, $endDateCarbon) {
            if (!$accountId) return 0;
            $startBal = GeneralLedger::where('chart_of_account_id', $accountId)
                ->where('entry_date', '<=', $previousDateCarbon)
                ->sum(DB::raw($isAsset ? 'debit - credit' : 'credit - debit'));
            $endBal = GeneralLedger::where('chart_of_account_id', $accountId)
                ->where('entry_date', '<=', $endDateCarbon)
                ->sum(DB::raw($isAsset ? 'debit - credit' : 'credit - debit'));
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

        $cashAccountIds = CompanyBankAccount::whereNotNull('chart_of_account_id')->pluck('chart_of_account_id')->toArray();
        if (empty($cashAccountIds)) {
            $cashAccountIds = ChartOfAccount::where('account_type', 'Aset')->where(function($q) {
                $q->where('account_name', 'like', '%Kas%')->orWhere('account_name', 'like', '%Bank%');
            })->pluck('account_id')->toArray();
        }

        $cash_beginning = GeneralLedger::whereIn('chart_of_account_id', $cashAccountIds)
            ->where('entry_date', '<', $startDate)
            ->sum(DB::raw('debit - credit'));
        $cash_ending = $cash_beginning + $net_increase_cash;

        // --- 4. DETAILS ---
        $laporanPiutang = SalesInvoice::with('client')->whereIn('status', ['unpaid', 'partially_paid'])->orderBy('due_date', 'asc')->get();
        $totalPiutang_SL = $laporanPiutang->sum(fn($inv) => $inv->remaining_balance);

        $laporanUtang = PurchaseOrder::with('supplier')->whereIn('payment_status', ['unpaid', 'partially_paid'])->orderBy('due_date', 'asc')->get();
        $totalUtang_SL = $laporanUtang->sum(fn($po) => $po->remaining_balance);

        return compact(
            'startDate', 'endDate', 'endDateCarbon',
            'labaRugi_pendapatan', 'totalPendapatan', 'labaRugi_hpp', 'totalHPP', 'labaKotor', 'labaRugi_beban', 'totalBeban', 'labaBersih',
            'neraca_aset', 'totalAset', 'neraca_liabilitas', 'totalLiabilitas', 'neraca_ekuitas_non_pl', 'ekuitas_labaRugiAkumulasi', 'totalEkuitas', 'totalLiabilitasDanEkuitas',
            'cf_operating_net_income', 'cf_operating_depreciation', 'cf_change_ar', 'cf_change_inventory', 'cf_change_ap', 'cf_change_supplier_deposit', 'cf_change_client_deposit',
            'total_cash_from_operations', 'cf_investing_purchase_asset', 'total_cash_from_investing', 'cf_financing_capital_in', 'cf_financing_drawing', 'cf_financing_loan_in', 'cf_financing_loan_pay', 'total_cash_from_financing',
            'net_increase_cash', 'cash_beginning', 'cash_ending',
            'laporanPiutang', 'totalPiutang_SL', 'laporanUtang', 'totalUtang_SL'
        );
    }

    /**
     * Halaman Web (HTML)
     */
    public function index(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Ambil data dari helper
        $data = $this->getFinancialData($startDate, $endDate);

        // Tambahan data spesifik untuk tampilan web (opsional, jika tidak dibutuhkan di PDF)
        return view('admin.reports.index', $data);
    }

    /**
     * Download PDF
     * Method ini yang sebelumnya hilang/error.
     */
    public function printPDF(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Ambil data yang sama persis dengan index
        $data = $this->getFinancialData($startDate, $endDate);

        // Load view PDF
        $pdf = Pdf::loadView('admin.reports.pdf', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'Laporan_Keuangan_' . Carbon::parse($startDate)->format('Ymd') . '-' . Carbon::parse($endDate)->format('Ymd') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Aging Schedule (Halaman Terpisah)
     */
    public function agingSchedule(): View
    {
        $arAging = ['0_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0, 'total' => 0, 'details' => []];
        $unpaidInvoices = SalesInvoice::with('client')->whereIn('status', ['unpaid', 'partially_paid'])->get();

        foreach ($unpaidInvoices as $inv) {
            $balance = $inv->remaining_balance;
            if ($balance <= 0.01) continue;
            $daysOverdue = Carbon::now()->diffInDays(Carbon::parse($inv->due_date), false);
            $daysOverdue = $daysOverdue > 0 ? $daysOverdue : 0; 

            if ($daysOverdue <= 30) $arAging['0_30'] += $balance;
            elseif ($daysOverdue <= 60) $arAging['31_60'] += $balance;
            elseif ($daysOverdue <= 90) $arAging['61_90'] += $balance;
            else $arAging['90_plus'] += $balance;

            $arAging['total'] += $balance;
            $arAging['details'][] = [
                'type' => 'invoice',
                'number' => $inv->invoice_number,
                'party' => $inv->client->client_name ?? 'Unknown Client',
                'date' => $inv->order_date->format('Y-m-d'),
                'due_date' => $inv->due_date->format('Y-m-d'),
                'days_overdue' => $daysOverdue,
                'amount' => $balance
            ];
        }

        $apAging = ['0_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0, 'total' => 0, 'details' => []];
        $unpaidPOs = PurchaseOrder::with('supplier')->whereIn('payment_status', ['unpaid', 'partially_paid'])->get();

        foreach ($unpaidPOs as $po) {
            $balance = $po->remaining_balance;
            if ($balance <= 0.01) continue;
            $dueDate = $po->due_date ? Carbon::parse($po->due_date) : Carbon::parse($po->order_date)->addDays(30);
            $daysOverdue = Carbon::now()->diffInDays($dueDate, false);
            $daysOverdue = $daysOverdue > 0 ? $daysOverdue : 0; 

            if ($daysOverdue <= 30) $apAging['0_30'] += $balance;
            elseif ($daysOverdue <= 60) $apAging['31_60'] += $balance;
            elseif ($daysOverdue <= 90) $apAging['61_90'] += $balance;
            else $apAging['90_plus'] += $balance;

            $apAging['total'] += $balance;
            $apAging['details'][] = [
                'type' => 'po',
                'number' => $po->po_number,
                'party' => $po->supplier->supplier_name ?? 'Unknown Supplier',
                'date' => $po->order_date->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'days_overdue' => $daysOverdue,
                'amount' => $balance
            ];
        }

        usort($arAging['details'], fn($a, $b) => $b['days_overdue'] <=> $a['days_overdue']);
        usort($apAging['details'], fn($a, $b) => $b['days_overdue'] <=> $a['days_overdue']);

        return view('admin.reports.aging_schedule', compact('arAging', 'apAging'));
    }
}