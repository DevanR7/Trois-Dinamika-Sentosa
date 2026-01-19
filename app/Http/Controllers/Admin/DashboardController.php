<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Models\Payment;
use App\Models\PurchaseOrderPayment;
use App\Models\Expense;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\FixedAsset;
use App\Models\EquityTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\StockOpname;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $selectedYear = $request->input('year', date('Y'));
        $selectedUserId = $request->input('filter_user_id'); 
        
        $isAdmin = $user->hasRole(['admin', 'superadmin']);
        $canViewFinancials = $user->can('view-reports'); 
        $canViewInventory = $user->can('view-products');

        if (!$isAdmin) {
            $selectedUserId = $user->user_id;
        }

        // Optimasi: Gunakan Raw Query untuk tahun agar lebih cepat daripada pluck semua data
        $availableYears = DB::table('sales_invoices')
            ->selectRaw('YEAR(order_date) as year')
            ->union(DB::table('purchase_orders')->selectRaw('YEAR(order_date) as year'))
            ->distinct()->orderByDesc('year')->pluck('year');
        
        if ($availableYears->isEmpty()) $availableYears = collect([date('Y')]);
        
        $allUsers = $isAdmin ? User::orderBy('full_name')->get() : collect([]);

        // --- INIT VARIABLES ---
        $stats = [
            'revenue' => 0, 'expense' => 0, 'net_profit' => 0,
            'growth_revenue' => 0, 'growth_profit' => 0, 'prev_year_label' => $selectedYear - 1,
            'payables' => 0, 'loans' => 0, 'assets' => 0, 'receivables_global' => 0,
            'total_invoices' => 0, 'paid_invoices' => 0, 'total_sales_value' => 0,
            'items_sold' => 0, 'active_clients' => 0, 'success_rate' => 0, 'receivables_filtered' => 0,
            'sales_breakdown' => [], 
            'purchase_breakdown' => []
        ];

        $forecast = [
            'incoming_30_days' => 0, 'outgoing_30_days' => 0, 'net_forecast' => 0,
            'monthly_target' => 500000000, 'current_monthly_sales' => 0, 'target_percentage' => 0
        ];

        $trendDataIncome = array_fill(0, 12, 0);
        $trendDataExpense = array_fill(0, 12, 0);
        $expenseCompositionData = [0, 0, 0, 0, 0];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $topClients = collect([]);

        // =========================================================================
        // BAGIAN 1: FINANCIALS (Database Aggregate - Efficient)
        // =========================================================================
        if ($canViewFinancials) {
            // 1. DATA TAHUN INI
            $inc_sales = Payment::whereYear('payment_date', $selectedYear)->where('status', 'completed')->sum('amount');
            $inc_equity = EquityTransaction::whereYear('transaction_date', $selectedYear)->where('type', 'investment')->sum('amount');
            $inc_loan = Loan::whereYear('loan_date', $selectedYear)->sum('principal_amount');
            $stats['revenue'] = $inc_sales + $inc_equity + $inc_loan;

            $val_exp_ops = Expense::whereYear('expense_date', $selectedYear)->sum('amount');
            $val_exp_po = PurchaseOrderPayment::whereYear('payment_date', $selectedYear)->where('status', 'completed')->sum('amount');
            $val_exp_asset = FixedAsset::whereYear('purchase_date', $selectedYear)->sum('purchase_cost');
            $val_exp_loan = LoanPayment::whereYear('payment_date', $selectedYear)->sum('total_paid');
            $val_exp_equity = EquityTransaction::whereYear('transaction_date', $selectedYear)->where('type', 'drawing')->sum('amount');

            $stats['expense'] = $val_exp_ops + $val_exp_po + $val_exp_asset + $val_exp_loan + $val_exp_equity;
            $stats['net_profit'] = $stats['revenue'] - $stats['expense'];

            // 2. DATA TAHUN SEBELUMNYA
            $prevYear = $selectedYear - 1;
            $prev_sales = Payment::whereYear('payment_date', $prevYear)->where('status', 'completed')->sum('amount');
            $prev_equity = EquityTransaction::whereYear('transaction_date', $prevYear)->where('type', 'investment')->sum('amount');
            $prev_loan = Loan::whereYear('loan_date', $prevYear)->sum('principal_amount');
            $prevRevenue = $prev_sales + $prev_equity + $prev_loan;

            $prevExpense = Expense::whereYear('expense_date', $prevYear)->sum('amount') + 
                           PurchaseOrderPayment::whereYear('payment_date', $prevYear)->where('status', 'completed')->sum('amount') +
                           FixedAsset::whereYear('purchase_date', $prevYear)->sum('purchase_cost') +
                           LoanPayment::whereYear('payment_date', $prevYear)->sum('total_paid') +
                           EquityTransaction::whereYear('transaction_date', $prevYear)->where('type', 'drawing')->sum('amount');

            $prevProfit = $prevRevenue - $prevExpense;

            $calculateGrowth = function($current, $previous) {
                if ($previous == 0) return $current > 0 ? 100 : 0;
                return round((($current - $previous) / $previous) * 100, 1);
            };

            $stats['growth_revenue'] = $calculateGrowth($stats['revenue'], $prevRevenue);
            $stats['growth_profit'] = $calculateGrowth($stats['net_profit'], $prevProfit);
            $expenseCompositionData = [$val_exp_ops, $val_exp_po, $val_exp_asset, $val_exp_loan, $val_exp_equity];

            // Global Stats (Optimized SUM DB)
            $stats['payables'] = PurchaseOrder::whereIn('payment_status', ['unpaid', 'partially_paid'])
                ->where('status', '!=', 'cancelled')->sum(DB::raw('grand_total - amount_paid - total_returned'));
                
            $stats['loans'] = Loan::where('status', 'active')->sum('remaining_balance');
            $stats['assets'] = FixedAsset::sum('current_book_value');
            
            $stats['receivables_global'] = SalesInvoice::whereIn('status', ['unpaid', 'partially_paid'])
                ->where('status', '!=', 'cancelled')->sum(DB::raw('total_amount - amount_paid'));

            // Chart Data
            $m_sales = Payment::whereYear('payment_date', $selectedYear)->where('status', 'completed')
                ->selectRaw("MONTH(payment_date) as month, SUM(amount) as total")
                ->groupBy('month')->pluck('total', 'month')->toArray();
     
            $m_exp = Expense::whereYear('expense_date', $selectedYear)
                ->selectRaw("MONTH(expense_date) as month, SUM(amount) as total")->groupBy('month')->pluck('total', 'month')->toArray();
            
            $m_po = PurchaseOrderPayment::whereYear('payment_date', $selectedYear)->where('status', 'completed')
                ->selectRaw("MONTH(payment_date) as month, SUM(amount) as total")->groupBy('month')->pluck('total', 'month')->toArray();

            for ($i = 1; $i <= 12; $i++) {
                $trendDataIncome[$i-1] = $m_sales[$i] ?? 0;
                $trendDataExpense[$i-1] = ($m_exp[$i] ?? 0) + ($m_po[$i] ?? 0);
            }

            $topClients = SalesInvoice::whereYear('order_date', $selectedYear)
                ->where('status', '!=', 'cancelled')
                ->select('client_id', DB::raw('SUM(total_amount) as total_spent'))
                ->with('client:client_id,client_name') // Eager load specific columns
                ->groupBy('client_id')
                ->orderByDesc('total_spent')
                ->take(5)->get();

            // Forecasting (Optimized)
            $forecast['incoming_30_days'] = SalesInvoice::whereIn('status', ['unpaid', 'partially_paid'])
                ->where('status', '!=', 'cancelled')
                ->whereBetween('due_date', [Carbon::now(), Carbon::now()->addDays(30)])
                ->sum(DB::raw('total_amount - amount_paid'));

            $forecast['outgoing_30_days'] = PurchaseOrder::whereIn('payment_status', ['unpaid', 'partially_paid'])
                ->where('status', '!=', 'cancelled')
                ->whereBetween('due_date', [Carbon::now(), Carbon::now()->addDays(30)])
                ->sum(DB::raw('grand_total - amount_paid - total_returned'));

            $forecast['net_forecast'] = $forecast['incoming_30_days'] - $forecast['outgoing_30_days'];
            $forecast['current_monthly_sales'] = Payment::whereYear('payment_date', date('Y'))
                ->whereMonth('payment_date', date('m'))->where('status', 'completed')->sum('amount');

            if ($forecast['monthly_target'] > 0) {
                $forecast['target_percentage'] = min(100, round(($forecast['current_monthly_sales'] / $forecast['monthly_target']) * 100));
            }
        }

        // =========================================================================
        // BAGIAN 2: OPERATIONAL & BREAKDOWN (Optimized Counts)
        // =========================================================================
        
        $baseSalesQuery = SalesInvoice::whereYear('order_date', $selectedYear)->where('status', '!=', 'cancelled');
        if ($selectedUserId) $baseSalesQuery->where('user_id_sales', $selectedUserId);

        // Breakdown menggunakan conditional count untuk menghindari multiple query berat
        $salesStatsRaw = (clone $baseSalesQuery)
            ->selectRaw("
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid,
                COUNT(CASE WHEN status = 'partially_paid' THEN 1 END) as partial,
                COUNT(CASE WHEN status = 'unpaid' AND due_date >= CURRENT_DATE THEN 1 END) as unpaid_current,
                COUNT(CASE WHEN status IN ('unpaid', 'partially_paid') AND due_date < CURRENT_DATE THEN 1 END) as overdue,
                COUNT(*) as total,
                SUM(total_amount) as total_value,
                COUNT(DISTINCT client_id) as distinct_clients
            ")->first();

        $stats['sales_breakdown'] = [
            'paid' => $salesStatsRaw->paid,
            'partial' => $salesStatsRaw->partial,
            'unpaid_current' => $salesStatsRaw->unpaid_current,
            'overdue' => $salesStatsRaw->overdue,
        ];

        $stats['total_invoices'] = $salesStatsRaw->total;
        $stats['paid_invoices'] = $salesStatsRaw->paid;
        $stats['total_sales_value'] = $salesStatsRaw->total_value ?? 0;
        $stats['active_clients'] = $salesStatsRaw->distinct_clients;
        $stats['success_rate'] = $stats['total_invoices'] > 0 ? round(($stats['paid_invoices'] / $stats['total_invoices']) * 100) : 0;

        // Hitung piutang filtered
        $stats['receivables_filtered'] = (clone $baseSalesQuery)
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->sum(DB::raw('total_amount - amount_paid'));

        // Purchase Breakdown
        if ($isAdmin || $canViewFinancials) {
            $poStatsRaw = PurchaseOrder::whereYear('order_date', $selectedYear)
                ->where('status', '!=', 'cancelled')
                ->selectRaw("
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid,
                    COUNT(CASE WHEN payment_status = 'partially_paid' THEN 1 END) as partial,
                    COUNT(CASE WHEN payment_status = 'unpaid' AND due_date >= CURRENT_DATE THEN 1 END) as unpaid_current,
                    COUNT(CASE WHEN payment_status IN ('unpaid', 'partially_paid') AND due_date < CURRENT_DATE THEN 1 END) as overdue
                ")->first();

            $stats['purchase_breakdown'] = [
                'paid' => $poStatsRaw->paid,
                'partial' => $poStatsRaw->partial,
                'unpaid_current' => $poStatsRaw->unpaid_current,
                'overdue' => $poStatsRaw->overdue,
            ];
        }

        // Items Sold - Optimized with Join
        $stats['items_sold'] = DB::table('invoice_items')
            ->join('sales_invoices', 'invoice_items.invoice_id', '=', 'sales_invoices.invoice_id')
            ->whereYear('sales_invoices.order_date', $selectedYear)
            ->where('sales_invoices.status', '!=', 'cancelled')
            ->when($selectedUserId, fn($q) => $q->where('sales_invoices.user_id_sales', $selectedUserId))
            ->sum('invoice_items.quantity');

        // Recent Invoices
        $recentInvoices = (clone $baseSalesQuery)->with(['client', 'sales'])->latest('order_date')->take(5)->get();

        // Top Products - Optimized
        $topProducts = DB::table('invoice_items')
            ->join('sales_invoices', 'invoice_items.invoice_id', '=', 'sales_invoices.invoice_id')
            ->join('products', 'invoice_items.product_id', '=', 'products.product_id')
            ->whereYear('sales_invoices.order_date', $selectedYear)
            ->where('sales_invoices.status', '!=', 'cancelled')
            ->when($selectedUserId, fn($q) => $q->where('sales_invoices.user_id_sales', $selectedUserId))
            ->select('products.product_name', DB::raw('SUM(invoice_items.quantity) as total_qty'))
            ->groupBy('products.product_id', 'products.product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $charts = [
            'months' => $months, 
            'trend_data_income' => $trendDataIncome, 
            'trend_data_expense' => $trendDataExpense,
            'expense_composition' => $expenseCompositionData, 
            'top_products_labels' => $topProducts->pluck('product_name')->toArray(), 
            'top_products_data' => $topProducts->pluck('total_qty')->toArray(),
        ];

        // Pending Actions
        $pendingActions = [
            'invoice_draft' => SalesInvoice::where('status', 'draft')->when($selectedUserId, fn($q) => $q->where('user_id_sales', $selectedUserId))->count(),
            'po_draft' => ($isAdmin) ? PurchaseOrder::where('status', 'draft')->count() : 0,
            'pending_payments' => ($user->can('manage-payment-clearance')) ? Payment::where('status', 'pending_verification')->count() : 0,
            'stock_opname_draft' => ($canViewInventory) ? StockOpname::where('status', 'draft')->count() : 0,
            'stock_alert' => ($canViewInventory) ? Product::where('stock_quantity', '<=', 5)->count() : 0,
        ];
        
        $lowStockProducts = Product::where('stock_quantity', '<=', 10)->orderBy('stock_quantity', 'asc')->take(5)->get();

        return view('admin.dashboard', compact(
            'user', 'availableYears', 'allUsers', 'selectedYear', 'selectedUserId', 'isAdmin',
            'stats', 'charts', 'pendingActions', 'recentInvoices', 'lowStockProducts', 'canViewFinancials',
            'topClients', 'forecast'
        ));
    }
}