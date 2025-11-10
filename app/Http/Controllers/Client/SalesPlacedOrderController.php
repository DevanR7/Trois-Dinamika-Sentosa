<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB; 
use Carbon\Carbon;

class SalesPlacedOrderController extends Controller
{
    /**
     * ==============================================
     * BAGIAN: Menampilkan daftar pesanan dari Sales
     * ==============================================
     */
    public function index(Request $request): View
    {
        // Ambil data klien yang sedang login
        $client = Auth::guard('client')->user();
        
        // Query dasar: hanya pesanan yang dibuat oleh Sales
        $query = $client->orders()
                        ->where('order_source', 'sales');

        // ============================================
        // Ambil daftar bulan unik untuk dropdown filter
        // ============================================
        $uniqueDates = $client->orders()
            ->where('order_source', 'sales')
            ->select(DB::raw("DATE_FORMAT(order_date, '%Y-%m') as ym"))
            ->distinct()
            ->orderBy('ym', 'desc')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->ym => Carbon::createFromFormat('Y-m', $item->ym)->isoFormat('MMMM YYYY')];
            });

        // =============================
        // Terapkan berbagai jenis filter
        // =============================

        // 1. Filter berdasarkan nomor pesanan
        if ($request->filled('search')) {
            $query->where('order_number', 'like', "%{$request->search}%");
        }

        // 2. Filter berdasarkan bulan dan tahun pesanan
        if ($request->filled('date_filter')) {
            $yearMonth = $request->date_filter; // Format YYYY-MM
            try {
                $date = Carbon::createFromFormat('Y-m', $yearMonth);
                $query->whereYear('order_date', $date->year)
                      ->whereMonth('order_date', $date->month);
            } catch (\Exception $e) {
                // Abaikan jika format tanggal tidak valid
            }
        }
        
        // 3. Filter berdasarkan status pesanan
        if ($request->filled('status_filter')) {
             $query->where('status', $request->status_filter);
        }

        // 4. Pengurutan data (default: terbaru)
        $sort = $request->get('sort', 'terbaru');
        if ($sort === 'terlama') {
            $query->orderBy('order_date', 'asc');
        } else {
            $query->orderBy('order_date', 'desc');
        }

        // Ambil hasil akhir dengan paginasi
        $salesOrders = $query->paginate(15)->appends($request->query());

        // Tampilkan halaman daftar pesanan Sales
        return view('client.sales_orders.index', compact('salesOrders', 'uniqueDates'));
    }

    /**
     * ====================================================
     * BAGIAN: Menampilkan detail pesanan dari Sales
     * ====================================================
     */
    public function show(Order $order): View|RedirectResponse
    {
        // Keamanan: pastikan pesanan milik klien yang login dan dibuat oleh Sales
        if ($order->client_id !== Auth::guard('client')->id() || $order->order_source !== 'sales') {
            return redirect()->route('client.sales-orders.index')
                ->with('error', 'Pesanan tidak ditemukan.');
        }

        // Muat relasi penting untuk tampilan detail
        $order->load(['items.product', 'sales', 'changeRequests']);

        // Tampilkan halaman detail pesanan Sales
        return view('client.sales_orders.show', compact('order'));
    }
}
