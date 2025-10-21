<?php

namespace App\Http\Controllers\Client; // Pastikan namespace benar

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse; // Tambahkan ini

// Nama class diubah
class SalesPlacedOrderController extends Controller
{
    /**
     * Menampilkan daftar pesanan yang dibuat oleh Sales untuk klien.
     */
    public function index(): View
    {
        $client = Auth::guard('client')->user();
        $salesOrders = $client->orders()
                           ->where('order_source', 'sales') // Filter hanya order dari sales
                           ->latest('order_date')
                           ->paginate(15);

        // View baru: client.sales_orders.index
        return view('client.sales_orders.index', compact('salesOrders'));
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