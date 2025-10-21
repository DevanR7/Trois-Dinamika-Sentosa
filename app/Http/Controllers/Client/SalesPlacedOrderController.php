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

// Nama class diubah
class SalesPlacedOrderController extends Controller
{
    /**
     * Menampilkan daftar pesanan yang dibuat oleh Sales untuk klien.
     */
    public function index(Request $request): View // <-- Tambahkan Request
    {
        $client = Auth::guard('client')->user();
        
        $query = $client->orders()
                       ->where('order_source', 'sales'); // Filter hanya order dari sales

        // --- Ambil Data untuk Dropdown Filter Tanggal ---
        $uniqueDates = $client->orders()
            ->where('order_source', 'sales')
            ->select(DB::raw("DATE_FORMAT(order_date, '%Y-%m') as ym"))
            ->distinct()
            ->orderBy('ym', 'desc')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->ym => Carbon::createFromFormat('Y-m', $item->ym)->isoFormat('MMMM YYYY')];
            });

        // --- Terapkan Filter ---
        // 1. Filter Search (Nomor Pesanan)
        if ($request->filled('search')) {
            $query->where('order_number', 'like', "%{$request->search}%");
        }

        // 2. Filter Tanggal (Bulan & Tahun)
        if ($request->filled('date_filter')) {
            $yearMonth = $request->date_filter; // Format YYYY-MM
            try {
                $date = Carbon::createFromFormat('Y-m', $yearMonth);
                $query->whereYear('order_date', $date->year)
                      ->whereMonth('order_date', $date->month);
            } catch (\Exception $e) { /* Abaikan format tanggal salah */ }
        }
        
        // 3. Filter Status (jika perlu)
        if ($request->filled('status_filter')) {
             $query->where('status', $request->status_filter);
        }

        // 4. Pengurutan
        $sort = $request->get('sort', 'terbaru'); // Default terbaru
        if ($sort === 'terlama') {
            $query->orderBy('order_date', 'asc');
        } else {
            $query->orderBy('order_date', 'desc');
        }

        $salesOrders = $query->paginate(15)->appends($request->query());

        // View baru: client.sales_orders.index
        return view('client.sales_orders.index', compact('salesOrders', 'uniqueDates'));
    }

     /**
     * Menampilkan detail pesanan yang dibuat oleh Sales untuk klien.
     */
    public function show(Order $order): View | RedirectResponse // Tambah RedirectResponse
    {
        // Keamanan: Pastikan order milik klien DAN dibuat oleh sales
        if ($order->client_id !== Auth::guard('client')->id() || $order->order_source !== 'sales') {
            // Redirect ke index jika mencoba akses order klien di sini
            return redirect()->route('client.sales-orders.index')->with('error', 'Pesanan tidak ditemukan.');
            // abort(403, 'Akses Ditolak');
        }

        // Load relasi termasuk change requests
        $order->load(['items.product', 'sales', 'changeRequests']);

        // View baru: client.sales_orders.show
        return view('client.sales_orders.show', compact('order'));
    }
}