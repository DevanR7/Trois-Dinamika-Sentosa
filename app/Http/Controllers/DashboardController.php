<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Data untuk kartu statistik
        $totalRevenue = Payment::sum('amount');
        $unpaidInvoicesCount = SalesInvoice::whereIn('status', ['unpaid'])->count();
        $productCount = Product::count();

        // Data untuk tabel "Invoice Terbaru"
        $latestInvoices = SalesInvoice::with('client')
            ->latest('invoice_date')
            ->take(5)
            ->get();

        // Data untuk tabel "Stok Produk Menipis"
        $lowStockProducts = Product::where('stock_quantity', '<', 10)
            ->orderBy('stock_quantity', 'asc')
            ->take(5)
            ->get();

        // Data untuk Grafik Penjualan Bulanan
        $salesData = Payment::select(
                DB::raw("DATE_FORMAT(payment_date, '%b') as month"), // Ambil nama bulan saja, misal: Jan, Feb
                DB::raw("SUM(amount) as total_sales")
            )
            ->whereYear('payment_date', date('Y')) // Hanya untuk tahun ini
            ->groupBy('month')
            ->orderByRaw('MIN(payment_date) asc')
            ->get();
        
        return view('dashboard', compact(
            'totalRevenue', 
            'unpaidInvoicesCount', 
            'productCount',
            'latestInvoices',
            'lowStockProducts',
            'salesData'
        ));
    }
}