<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;
use App\Models\Payment;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        // --- PENGATURAN FILTER ---
        $selectedYear = $request->input('year', date('Y'));
        $selectedSalesId = $request->input('sales_id');
        $user = Auth::user();

        // Ambil daftar tahun yang ada transaksinya untuk dropdown filter
        $invoiceYears = SalesInvoice::select(DB::raw('YEAR(order_date) as year'))->distinct()->pluck('year');
        $poYears = PurchaseOrder::select(DB::raw('YEAR(order_date) as year'))->distinct()->pluck('year');
        $availableYears = $invoiceYears->merge($poYears)->unique()->sortDesc();


        // === DATA UNTUK KARTU STATISTIK (DENGAN FILTER TAHUN) ===
        $totalRevenue = Payment::whereYear('payment_date', $selectedYear)->sum('amount');
        $totalHutang = PurchaseOrder::whereNotIn('payment_status', ['paid'])->whereYear('order_date', $selectedYear)->sum(DB::raw('total_amount - amount_paid - total_returned'));
        $totalPiutang = SalesInvoice::whereNotIn('status', ['paid', 'cancelled'])->whereYear('order_date', $selectedYear)->sum(DB::raw('total_amount - amount_paid'));
        $totalSalesReturn = SalesReturn::whereYear('return_date', $selectedYear)->sum('total_amount');
        $totalPurchaseReturn = PurchaseReturn::whereYear('return_date', $selectedYear)->sum('total_amount');


        // === DATA UNTUK GRAFIK UTAMA (PENJUALAN, PEMBELIAN, PENDAPATAN) ===
        $months = collect(range(1, 12))->map(fn ($month) => Carbon::create()->month($month)->format('M'));
        
        $getDataForChart = function ($model, $dateColumn, $amountColumn = 'total_amount') use ($selectedYear) {
            return $model::select(DB::raw("MONTH({$dateColumn}) as month"), DB::raw("SUM({$amountColumn}) as total"))
                ->whereYear($dateColumn, $selectedYear)
                ->groupBy('month')->pluck('total', 'month')->all();
        };
        
        $penjualan = $getDataForChart(new SalesInvoice, 'order_date');
        $pembelian = $getDataForChart(new PurchaseOrder, 'order_date');
        $pendapatan = $getDataForChart(new Payment, 'payment_date', 'amount');

        $mainChartData = [
            'labels' => $months,
            'penjualan' => array_values(array_replace(array_fill(1, 12, 0), $penjualan)),
            'pembelian' => array_values(array_replace(array_fill(1, 12, 0), $pembelian)),
            'pendapatan' => array_values(array_replace(array_fill(1, 12, 0), $pendapatan)),
        ];

        // === DATA PRODUK TERLARIS (PIE CHART) (DENGAN FILTER TAHUN) ===
        $topProducts = DB::table('invoice_items')
            ->join('sales_invoices', 'invoice_items.invoice_id', '=', 'sales_invoices.invoice_id')
            ->join('products', 'invoice_items.product_id', '=', 'products.product_id')
            ->whereYear('sales_invoices.order_date', $selectedYear)
            ->select('products.product_name', DB::raw('SUM(invoice_items.quantity) as total_quantity'))
            ->groupBy('products.product_name')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)->get();
        
        $topProductsChartData = ['labels' => $topProducts->pluck('product_name'), 'data' => $topProducts->pluck('total_quantity')];

        // === DATA KINERJA PENJUALAN (DENGAN FILTER USER UNIVERSAL) ===
        // Ambil semua user yang punya role untuk ditampilkan di filter
        $filterableUsers = User::whereHas('roles')->orderBy('full_name')->get()->groupBy(function($user) {
            return Str::title($user->getRoleNames()->first() ?? 'Lainnya');
        });

        $salesOrdersQuery = SalesOrder::with(['client', 'sales'])->whereYear('order_date', $selectedYear);
        if ($selectedSalesId) {
            $salesOrdersQuery->where('user_id_sales', $selectedSalesId);
        }
        $latestSalesOrders = $salesOrdersQuery->latest('order_date')->take(5)->get();

        // ==========================================================
    // [DATA BARU] INVOICE YANG SEDANG BERJALAN
    // ==========================================================
        $runningInvoicesQuery = SalesInvoice::with(['client', 'sales'])
        ->whereIn('status', ['unpaid', 'partially_paid'])
        ->whereYear('order_date', $selectedYear);

    // Filter per sales jika user adalah sales
    if ($user->hasRole('sales')) {
        $runningInvoicesQuery->where('user_id_sales', $user->id);
    }
    
    $latestRunningInvoices = $runningInvoicesQuery->latest('order_date')->take(5)->get();

        // === DATA STOK MENIPIS (TIDAK BERUBAH) ===
        $lowStockProducts = Product::where('stock_quantity', '<=', 10)->orderBy('stock_quantity', 'asc')->take(5)->get();

        return view('dashboard', compact(
            'totalRevenue', 'totalHutang', 'totalPiutang', 'totalSalesReturn', 'totalPurchaseReturn',
            'mainChartData', 'topProductsChartData', 'lowStockProducts',
            'filterableUsers', 'latestSalesOrders', 'selectedSalesId',
            'availableYears', 'selectedYear',
            'latestRunningInvoices',
        ));
    }
}