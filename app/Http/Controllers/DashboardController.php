<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
     public function index(): View
    {
        $user = Auth::user(); // Ambil user yang sedang login

        // --- Mulai query ---
        $invoiceQuery = SalesInvoice::query();
        $paymentQuery = Payment::query();

        // Jika user yang login adalah 'sales', filter semua query hanya untuk invoice miliknya
        if ($user->role === 'sales') {
            $invoiceIds = SalesInvoice::where('user_id_sales', $user->user_id)->pluck('invoice_id');
            
            $invoiceQuery->where('user_id_sales', $user->user_id);
            $paymentQuery->whereIn('invoice_id', $invoiceIds);
        }

        // Data untuk kartu statistik
        $totalRevenue = $paymentQuery->sum('amount');
        $unpaidInvoicesCount = (clone $invoiceQuery)->whereIn('status', ['unpaid', 'partially_paid'])->count();
        $productCount = Product::count(); // Jumlah produk biasanya tidak perlu difilter per sales

        // Data untuk tabel "Invoice Terbaru"
        // [FIX] Menggunakan 'order_date' dan query yang sudah difilter
        $latestInvoices = (clone $invoiceQuery)->with('client')->latest('order_date')->take(5)->get();

        // Data untuk tabel "Stok Produk Menipis"
        $lowStockProducts = Product::where('stock_quantity', '<', 10)
            ->orderBy('stock_quantity', 'asc')
            ->take(5)
            ->get();

        // Data untuk Grafik Penjualan Bulanan
        $salesData = (clone $paymentQuery)->select(
                DB::raw("DATE_FORMAT(payment_date, '%b') as month"),
                DB::raw("SUM(amount) as total_sales")
            )
            ->whereYear('payment_date', date('Y'))
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