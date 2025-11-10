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
        // --- Filter input pengguna ---
        $selectedYear = $request->input('year', date('Y'));
        $selectedSalesId = $request->input('sales_id');
        $user = Auth::user();

        // Ambil daftar tahun unik dari data penjualan dan pembelian
        $invoiceYears = SalesInvoice::select(DB::raw('YEAR(order_date) as year'))->distinct()->pluck('year');
        $poYears = PurchaseOrder::select(DB::raw('YEAR(order_date) as year'))->distinct()->pluck('year');
        $availableYears = $invoiceYears->merge($poYears)->unique()->sortDesc();

        // === Statistik ringkasan utama ===
        $totalRevenue = Payment::whereYear('payment_date', $selectedYear)->sum('amount');
        $totalHutang = PurchaseOrder::whereNotIn('payment_status', ['paid'])
            ->whereYear('order_date', $selectedYear)
            ->sum(DB::raw('total_amount - amount_paid - total_returned'));
        $totalPiutang = SalesInvoice::whereNotIn('status', ['paid', 'cancelled'])
            ->whereYear('order_date', $selectedYear)
            ->sum(DB::raw('total_amount - amount_paid'));
        $totalSalesReturn = SalesReturn::whereYear('return_date', $selectedYear)->sum('total_amount');
        $totalPurchaseReturn = PurchaseReturn::whereYear('return_date', $selectedYear)->sum('total_amount');

        // === Data untuk grafik bulanan (penjualan, pembelian, pendapatan) ===
        $months = collect(range(1, 12))->map(fn ($month) => Carbon::create()->month($month)->format('M'));

        $getDataForChart = function ($model, $dateColumn, $amountColumn = 'total_amount') use ($selectedYear) {
            return $model::select(
                DB::raw("MONTH({$dateColumn}) as month"),
                DB::raw("SUM({$amountColumn}) as total")
            )
                ->whereYear($dateColumn, $selectedYear)
                ->groupBy('month')
                ->pluck('total', 'month')
                ->all();
        };

        $penjualan = $getDataForChart(SalesInvoice::class, 'order_date');
        $pembelian = $getDataForChart(PurchaseOrder::class, 'order_date');
        $pendapatan = $getDataForChart(Payment::class, 'payment_date', 'amount');

        // Pastikan semua bulan (1–12) muncul di grafik, isi 0 jika tidak ada data
        $mainChartData = [
            'labels' => $months,
            'penjualan' => array_values(array_replace(array_fill(1, 12, 0), $penjualan)),
            'pembelian' => array_values(array_replace(array_fill(1, 12, 0), $pembelian)),
            'pendapatan' => array_values(array_replace(array_fill(1, 12, 0), $pendapatan)),
        ];

        // === Produk terlaris (berdasarkan kuantitas terjual) ===
        $topProducts = DB::table('invoice_items')
            ->join('sales_invoices', 'invoice_items.invoice_id', '=', 'sales_invoices.invoice_id')
            ->join('products', 'invoice_items.product_id', '=', 'products.product_id')
            ->whereYear('sales_invoices.order_date', $selectedYear)
            ->select('products.product_name', DB::raw('SUM(invoice_items.quantity) as total_quantity'))
            ->groupBy('products.product_name')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get();

        $topProductsChartData = [
            'labels' => $topProducts->pluck('product_name'),
            'data' => $topProducts->pluck('total_quantity'),
        ];

        // === Kinerja penjualan: daftar order terbaru dari sales ===
        $filterableUsers = User::whereHas('roles')
            ->orderBy('full_name')
            ->get()
            ->groupBy(fn ($user) => Str::title($user->getRoleNames()->first() ?? 'Lainnya'));

        $ordersQuery = Order::with(['client', 'sales'])
            ->whereYear('order_date', $selectedYear)
            ->where('order_source', 'sales');

        if ($selectedSalesId) {
            $ordersQuery->where('user_id_sales', $selectedSalesId);
        }

        $latestOrders = $ordersQuery->latest('order_date')->take(5)->get();

        // === Invoice berjalan (belum lunas) ===
        $runningInvoicesQuery = SalesInvoice::with(['client', 'sales'])
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->whereYear('order_date', $selectedYear);

        if ($user->hasRole('sales')) {
            $runningInvoicesQuery->where('user_id_sales', $user->user_id);
        }

        $latestRunningInvoices = $runningInvoicesQuery->latest('order_date')->take(5)->get();

        // === Produk dengan stok menipis ===
        $lowStockProducts = Product::where('stock_quantity', '<=', 10)
            ->orderBy('stock_quantity', 'asc')
            ->take(5)
            ->get();

        // === Kirim semua data ke view ===
        return view('dashboard', compact(
            'totalRevenue',
            'totalHutang',
            'totalPiutang',
            'totalSalesReturn',
            'totalPurchaseReturn',
            'mainChartData',
            'topProductsChartData',
            'lowStockProducts',
            'filterableUsers',
            'latestOrders',
            'selectedSalesId',
            'availableYears',
            'selectedYear',
            'latestRunningInvoices'
        ));
    }
}