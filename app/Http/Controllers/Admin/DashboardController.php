<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
        
        // 1. Parameter Filter
        $selectedYear = $request->input('year', date('Y'));
        $selectedUserId = $request->input('filter_user_id'); // Bisa null jika Admin pilih "Semua"
        
        // 2. Permission Checks
        $isAdmin = $user->hasRole(['admin', 'superadmin']);
        $canViewFinancials = $user->can('view-dashboard-financials'); // Flexibel via Permission
        $canViewInventory = $user->can('view-dashboard-inventory');

        // Jika user biasa (bukan admin) login, paksa filter ke diri sendiri
        if (!$isAdmin) {
            $selectedUserId = $user->user_id;
        }

        // 3. Tahun Tersedia
        $availableYears = DB::table('sales_invoices')
            ->selectRaw('YEAR(order_date) as year')
            ->union(DB::table('purchase_orders')->selectRaw('YEAR(order_date) as year'))
            ->distinct()->orderByDesc('year')->pluck('year');
        if ($availableYears->isEmpty()) $availableYears = collect([date('Y')]);
        
        $allUsers = $isAdmin ? User::orderBy('full_name')->get() : collect([]);

        // =========================================================================
        // BAGIAN 1: FINANCIAL STATS (GLOBAL PERUSAHAAN)
        // Data ini TIDAK TERPENGARUH filter user. Selalu menampilkan kondisi PT.
        // Hanya dihitung jika user punya izin view-dashboard-financials
        // =========================================================================
        
        $totalRevenue = 0;
        $totalExpenses = 0;
        $trendDataIncome = [];
        $trendDataExpense = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        // Stat Sekunder Global (Hutang, Aset, Sisa Pinjaman)
        // Ini selalu global
        $totalPayables = 0; 
        $totalActiveLoans = 0; 
        $totalAssetsValue = 0;
        $receivablesGlobal = 0;

        if ($canViewFinancials) {
            // A. PEMASUKAN GLOBAL (Cash In)
            $q_inc_sales_global = Payment::whereYear('payment_date', $selectedYear)->where('status', 'completed');
            $q_inc_equity = EquityTransaction::whereYear('transaction_date', $selectedYear)->where('type', 'investment');
            $q_inc_loan = Loan::whereYear('loan_date', $selectedYear);

            // B. PENGELUARAN GLOBAL (Cash Out)
            $q_exp_ops = Expense::whereYear('expense_date', $selectedYear);
            $q_exp_po = PurchaseOrderPayment::whereYear('payment_date', $selectedYear)->where('status', 'completed');
            $q_exp_asset = FixedAsset::whereYear('purchase_date', $selectedYear);
            $q_exp_loan = LoanPayment::whereYear('payment_date', $selectedYear);
            $q_exp_equity = EquityTransaction::whereYear('transaction_date', $selectedYear)->where('type', 'drawing');

            // Hitung Total Utama
            $totalRevenue = $q_inc_sales_global->sum('amount') + $q_inc_equity->sum('amount') + $q_inc_loan->sum('principal_amount');
            $totalExpenses = $q_exp_ops->sum('amount') + $q_exp_po->sum('amount') + $q_exp_asset->sum('purchase_cost') + $q_exp_loan->sum('total_paid') + $q_exp_equity->sum('amount');

            // Hitung Stat Sekunder
            $totalPayables = PurchaseOrder::whereIn('payment_status', ['unpaid', 'partially_paid'])
                ->where('status', '!=', 'cancelled')->sum(DB::raw('grand_total - amount_paid - total_returned'));
            $totalActiveLoans = Loan::where('status', 'active')->sum('remaining_balance');
            $totalAssetsValue = FixedAsset::sum('current_book_value');
            $receivablesGlobal = SalesInvoice::whereIn('status', ['unpaid', 'partially_paid'])
                ->where('status', '!=', 'cancelled')->sum(DB::raw('total_amount - amount_paid'));

            // Data Grafik Tren (Bulanan Global)
            $groupByMonth = function($query, $dateCol, $sumCol) {
                return $query->selectRaw("MONTH($dateCol) as month, SUM($sumCol) as total")
                    ->groupBy(DB::raw("MONTH($dateCol)"))
                    ->pluck('total', 'month')->toArray();
            };

            $monthly_sales = $groupByMonth(clone $q_inc_sales_global, 'payment_date', 'amount');
            $monthly_equity_in = $groupByMonth(clone $q_inc_equity, 'transaction_date', 'amount');
            $monthly_loan_in = $groupByMonth(clone $q_inc_loan, 'loan_date', 'principal_amount');

            $monthly_expense_ops = $groupByMonth(clone $q_exp_ops, 'expense_date', 'amount');
            $monthly_po_pay = $groupByMonth(clone $q_exp_po, 'payment_date', 'amount');
            $monthly_asset_buy = $groupByMonth(clone $q_exp_asset, 'purchase_date', 'purchase_cost');
            $monthly_loan_pay = $groupByMonth(clone $q_exp_loan, 'payment_date', 'total_paid');
            $monthly_equity_out = $groupByMonth(clone $q_exp_equity, 'transaction_date', 'amount');

            for ($i = 1; $i <= 12; $i++) {
                $trendDataIncome[] = ($monthly_sales[$i] ?? 0) + ($monthly_equity_in[$i] ?? 0) + ($monthly_loan_in[$i] ?? 0);
                $trendDataExpense[] = ($monthly_expense_ops[$i] ?? 0) + ($monthly_po_pay[$i] ?? 0) + ($monthly_asset_buy[$i] ?? 0) + ($monthly_loan_pay[$i] ?? 0) + ($monthly_equity_out[$i] ?? 0);
            }
        }

        // =========================================================================
        // BAGIAN 2: OPERATIONAL STATS & SALES DATA (FILTERABLE)
        // Data ini DIPENGARUHI oleh Filter User.
        // Jika Admin pilih User A -> Muncul data User A.
        // Jika Staff login -> Muncul data Staff itu sendiri.
        // =========================================================================

        $salesQuery = SalesInvoice::whereYear('order_date', $selectedYear)
            ->where('status', '!=', 'cancelled');

        if ($selectedUserId) {
            $salesQuery->where('user_id_sales', $selectedUserId);
        }

        // Statistik Operasional (Personal/Filtered)
        $countInvoices = (clone $salesQuery)->count();
        $countPaidInvoices = (clone $salesQuery)->where('status', 'paid')->count();
        $totalSalesValue = (clone $salesQuery)->sum('total_amount'); 
        
        $itemsSoldQuery = DB::table('invoice_items')
            ->join('sales_invoices', 'invoice_items.invoice_id', '=', 'sales_invoices.invoice_id')
            ->whereYear('sales_invoices.order_date', $selectedYear)
            ->where('sales_invoices.status', '!=', 'cancelled');
            
        if ($selectedUserId) {
            $itemsSoldQuery->where('sales_invoices.user_id_sales', $selectedUserId);
        }
        $itemsSold = $itemsSoldQuery->sum('quantity');

        $activeClients = (clone $salesQuery)->distinct('client_id')->count('client_id');
        $successRate = $countInvoices > 0 ? round(($countPaidInvoices / $countInvoices) * 100) : 0;

        // Piutang (Bisa difilter per user jika perlu, atau global)
        // Di sini kita buat Piutang mengikuti filter user untuk Operasional View
        $receivablesFiltered = (clone $salesQuery)->whereIn('status', ['unpaid', 'partially_paid'])
            ->sum(DB::raw('total_amount - amount_paid'));

        // =========================================================================
        // BAGIAN 3: TRANSAKSI TERAKHIR & PRODUK
        // =========================================================================

        // Recent Invoices (Tabel Bawah) - Mengikuti Filter User
        $recentInvoices = (clone $salesQuery)->with(['client', 'sales'])
            ->latest('order_date')
            ->take(10)
            ->get();

        // Top Products (Donut Chart) - Mengikuti Filter User
        $topProductsQuery = DB::table('invoice_items')
            ->join('sales_invoices', 'invoice_items.invoice_id', '=', 'sales_invoices.invoice_id')
            ->join('products', 'invoice_items.product_id', '=', 'products.product_id')
            ->whereYear('sales_invoices.order_date', $selectedYear)
            ->where('sales_invoices.status', '!=', 'cancelled');
            
        if ($selectedUserId) {
            $topProductsQuery->where('sales_invoices.user_id_sales', $selectedUserId);
        }

        $topProducts = $topProductsQuery
            ->select('products.product_name', DB::raw('SUM(invoice_items.quantity) as total_qty'))
            ->groupBy('products.product_id', 'products.product_name')
            ->orderByDesc('total_qty')
            ->get();

        // =========================================================================
        // COMPILE DATA
        // =========================================================================

        $stats = [
            // Financials (Global) - Hanya diisi jika canViewFinancials
            'revenue' => $totalRevenue,
            'expense' => $totalExpenses,
            'net_profit' => $totalRevenue - $totalExpenses,
            'payables' => $totalPayables,
            'loans' => $totalActiveLoans,
            'assets' => $totalAssetsValue,
            'receivables_global' => $receivablesGlobal,

            // Operational (Filtered)
            'total_invoices' => $countInvoices,
            'paid_invoices' => $countPaidInvoices,
            'total_sales_value' => $totalSalesValue,
            'items_sold' => $itemsSold,
            'active_clients' => $activeClients,
            'success_rate' => $successRate,
            'receivables_filtered' => $receivablesFiltered,
        ];

        $charts = [
            'months' => $months,
            'trend_data_income' => $trendDataIncome,
            'trend_data_expense' => $trendDataExpense,
            'trend_label_1' => 'Pemasukan',
            'trend_label_2' => 'Pengeluaran',
            'top_products_labels' => $topProducts->pluck('product_name')->toArray(),
            'top_products_data' => $topProducts->pluck('total_qty')->toArray(),
        ];

        // Pending Actions Widget
        $pendingActions = [
            'invoice_draft' => SalesInvoice::where('status', 'draft')
                ->when($selectedUserId, fn($q) => $q->where('user_id_sales', $selectedUserId))
                ->count(),
            'po_draft' => ($isAdmin) ? PurchaseOrder::where('status', 'draft')->count() : 0,
            'pending_payments' => ($user->can('manage-payment-clearance')) ? Payment::where('status', 'pending_verification')->count() : 0,
            'stock_opname_draft' => ($canViewInventory) ? StockOpname::where('status', 'draft')->count() : 0,
            'stock_alert' => ($canViewInventory) ? Product::where('stock_quantity', '<=', 5)->count() : 0,
        ];

        $lowStockProducts = Product::where('stock_quantity', '<', 10)
            ->orderBy('stock_quantity', 'asc')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'user', 'availableYears', 'allUsers', 
            'selectedYear', 'selectedUserId', 'isAdmin',
            'stats', 'charts', 'pendingActions', 'recentInvoices', 'lowStockProducts',
            'canViewFinancials'
        ));
    }
}