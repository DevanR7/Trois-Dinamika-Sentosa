<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;
use App\Models\Payment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard dengan data ringkasan dan visualisasi.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $selectedYear = $request->input('year', date('Y'));
        $selectedSalesId = $request->input('sales_id');

        // --- 1. DATA TAHUN (Sama) ---
        $invoiceYears = SalesInvoice::select(DB::raw('YEAR(order_date) as year'))->distinct()->pluck('year');
        $poYears = PurchaseOrder::select(DB::raw('YEAR(order_date) as year'))->distinct()->pluck('year');
        $availableYears = $invoiceYears->merge($poYears)->unique()->sortDesc();

        // Inisialisasi Variabel
        $stats = [];
        $mainChartData = null;
        $financialHealth = []; // ✅ BARU: Kesehatan Keuangan
        $pendingActions = [];  // ✅ BARU: Daftar Tugas
        
        $canViewFinancials = $user->can('view-dashboard-financials');

        // =====================================================================
        // 2. LOGIKA KEUANGAN (PETINGGI)
        // =====================================================================
        if ($canViewFinancials) {
            // A. Statistik Ringkasan (Sama seperti sebelumnya)
            $stats['revenue'] = Payment::whereYear('payment_date', $selectedYear)->where('status', 'completed')->sum('amount');
            
            // Optimasi query hutang/piutang (hitung manual via DB agar cepat)
            // Note: Accessor remaining_balance lambat untuk banyak data, kita pakai raw query sederhana untuk dashboard
            $stats['hutang'] = PurchaseOrder::whereIn('payment_status', ['unpaid', 'partially_paid'])->whereYear('order_date', $selectedYear)
                ->sum(DB::raw('total_amount - amount_paid - total_returned'));
            
            $stats['piutang'] = SalesInvoice::whereIn('status', ['unpaid', 'partially_paid'])->whereYear('order_date', $selectedYear)
                ->sum(DB::raw('total_amount - amount_paid')); // Asumsi retur sudah memotong amount di logika invoice

            $stats['sales_return'] = SalesReturn::whereYear('return_date', $selectedYear)->sum('total_amount');
            $stats['purchase_return'] = PurchaseReturn::whereYear('return_date', $selectedYear)->sum('total_amount');
            
            $stats['labels'] = [
                'revenue' => 'Total Pendapatan (Rp)',
                'hutang' => 'Total Hutang (Rp)',
                'piutang' => 'Total Piutang (Rp)',
                'sales_return' => 'Nilai Retur Penjualan',
                'purchase_return' => 'Nilai Retur Pembelian'
            ];

            // B. Grafik (Sama)
            $months = collect(range(1, 12))->map(fn ($month) => Carbon::create()->month($month)->format('M'));
            $getDataForChart = function ($model, $dateColumn, $amountColumn = 'total_amount') use ($selectedYear) {
                return $model::select(DB::raw("MONTH({$dateColumn}) as month"), DB::raw("SUM({$amountColumn}) as total"))
                    ->whereYear($dateColumn, $selectedYear)->groupBy('month')->pluck('total', 'month')->all();
            };
            $mainChartData = [
                'labels' => $months,
                'penjualan' => array_values(array_replace(array_fill(1, 12, 0), $getDataForChart(SalesInvoice::class, 'order_date'))),
                'pembelian' => array_values(array_replace(array_fill(1, 12, 0), $getDataForChart(PurchaseOrder::class, 'order_date'))),
                'pendapatan' => array_values(array_replace(array_fill(1, 12, 0), $getDataForChart(Payment::class, 'payment_date', 'amount'))),
            ];

            // ✅ C. FINANCIAL HEALTH (BARU)
            // 1. Saldo Kas Saat Ini (Real-time)
            // Ambil akun aset yang namanya mengandung 'Kas' atau 'Bank'
            $cashAccountIds = \App\Models\ChartOfAccount::where('account_type', 'Aset')
                ->where(function($q) {
                    $q->where('account_name', 'like', '%Kas%')->orWhere('account_name', 'like', '%Bank%');
                })->pluck('account_id');
            
            $currentCashBalance = \App\Models\GeneralLedger::whereIn('chart_of_account_id', $cashAccountIds)
                ->sum(DB::raw('debit - credit'));

            // 2. Estimasi Laba Bersih Bulan Ini
            $startMonth = now()->startOfMonth();
            $endMonth = now()->endOfMonth();
            
            // Ambil saldo akun Laba Rugi (Pendapatan(K) - Beban(D) - HPP(D))
            $plEntries = \App\Models\GeneralLedger::join('chart_of_accounts', 'general_ledgers.chart_of_account_id', '=', 'chart_of_accounts.account_id')
                ->whereBetween('entry_date', [$startMonth, $endMonth])
                ->whereIn('chart_of_accounts.account_type', ['Pendapatan', 'HPP', 'Beban'])
                ->select(
                    'chart_of_accounts.account_type',
                    'chart_of_accounts.normal_balance',
                    DB::raw('SUM(general_ledgers.debit) as total_debit'),
                    DB::raw('SUM(general_ledgers.credit) as total_credit')
                )
                ->groupBy('chart_of_accounts.account_type', 'chart_of_accounts.normal_balance')
                ->get();

            $netProfitThisMonth = 0;
            foreach($plEntries as $entry) {
                $balance = ($entry->normal_balance == 'Kredit') 
                    ? ($entry->total_credit - $entry->total_debit) 
                    : ($entry->total_debit - $entry->total_credit);
                
                // Jika Pendapatan (+), Jika Beban/HPP (-)
                if ($entry->account_type == 'Pendapatan') $netProfitThisMonth += $balance;
                else $netProfitThisMonth -= $balance;
            }

            $financialHealth = [
                'cash_balance' => $currentCashBalance,
                'monthly_profit' => $netProfitThisMonth
            ];
        } 
        else {
            // MODE STAF (Sama seperti sebelumnya)
            $stats['revenue'] = Payment::whereYear('payment_date', $selectedYear)->where('status', 'completed')->count();
            $stats['hutang'] = PurchaseOrder::whereNotIn('payment_status', ['paid'])->whereYear('order_date', $selectedYear)->count();
            $stats['piutang'] = SalesInvoice::whereNotIn('status', ['paid', 'cancelled'])->whereYear('order_date', $selectedYear)->count();
            $stats['sales_return'] = SalesReturn::whereYear('return_date', $selectedYear)->count();
            $stats['purchase_return'] = PurchaseReturn::whereYear('return_date', $selectedYear)->count();
            $stats['labels'] = [
                'revenue' => 'Transaksi Pembayaran Masuk',
                'hutang' => 'Jumlah PO Belum Lunas',
                'piutang' => 'Jumlah Invoice Belum Lunas',
                'sales_return' => 'Jumlah Nota Retur Jual',
                'purchase_return' => 'Jumlah Nota Retur Beli'
            ];
        }

        // ✅ 3. LOGIKA PENDING ACTIONS (BARU - UNTUK SEMUA ROLE)
        // Menghitung tugas yang "gantung"
        $pendingActions = [
            'po_draft' => PurchaseOrder::where('status', 'draft')->count(),
            'invoice_draft' => SalesInvoice::where('status', 'draft')->count(),
            'payment_clearance' => Payment::where('status', 'pending_clearance')->count() + PurchaseOrder::where('status', 'pending_clearance')->count(), // Asumsi PO juga punya status ini atau ambil dari payments PO
            // Tambahkan PO Payment pending
            'po_payment_pending' => \App\Models\PurchaseOrderPayment::where('status', 'pending_clearance')->count(),
        ];
        $pendingActions['total_clearance'] = $pendingActions['payment_clearance'] + $pendingActions['po_payment_pending'];


        // =====================================================================
        // 4. DATA UMUM LAINNYA (Sama)
        // =====================================================================
        $topProducts = DB::table('invoice_items')
            ->join('sales_invoices', 'invoice_items.invoice_id', '=', 'sales_invoices.invoice_id')
            ->join('products', 'invoice_items.product_id', '=', 'products.product_id')
            ->whereYear('sales_invoices.order_date', $selectedYear)
            ->select('products.product_name', DB::raw('SUM(invoice_items.quantity) as total_quantity'))
            ->groupBy('products.product_name')->orderBy('total_quantity', 'desc')->limit(5)->get();
        
        $topProductsChartData = ['labels' => $topProducts->pluck('product_name'), 'data' => $topProducts->pluck('total_quantity')];

        $filterableUsers = [];
        if ($canViewFinancials) {
            $filterableUsers = User::whereHas('roles')->orderBy('full_name')->get()->groupBy(fn ($u) => Str::title($u->getRoleNames()->first() ?? 'Lainnya'));
        }

        $ordersQuery = Order::with(['client', 'sales'])->whereYear('order_date', $selectedYear)->where('order_source', 'sales');
        $runningInvoicesQuery = SalesInvoice::with(['client', 'sales'])->whereIn('status', ['unpaid', 'partially_paid'])->whereYear('order_date', $selectedYear);

        if ($user->hasRole('sales')) {
            $ordersQuery->where('user_id_sales', $user->user_id);
            $runningInvoicesQuery->where('user_id_sales', $user->user_id);
        } elseif ($selectedSalesId && $canViewFinancials) {
            $ordersQuery->where('user_id_sales', $selectedSalesId);
            $runningInvoicesQuery->where('user_id_sales', $selectedSalesId);
        }

        $latestOrders = $ordersQuery->latest('order_date')->take(5)->get();
        $latestRunningInvoices = $runningInvoicesQuery->latest('order_date')->take(5)->get();

        $lowStockProducts = [];
        if ($user->can('view-dashboard-inventory')) {
            $lowStockProducts = Product::where('stock_quantity', '<=', 10)->orderBy('stock_quantity', 'asc')->take(5)->get();
        }

        return view('dashboard', compact(
            'stats', 'canViewFinancials', 'mainChartData', 'topProductsChartData', 'lowStockProducts',
            'filterableUsers', 'latestOrders', 'selectedSalesId', 'availableYears', 'selectedYear', 'latestRunningInvoices',
            'user', 'financialHealth', 'pendingActions' // ✅ Kirim variabel baru
        ));
    }
}