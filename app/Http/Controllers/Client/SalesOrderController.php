<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SalesOrderController extends Controller
{
    public function index(): View
    {
        $client = Auth::guard('client')->user();
        $salesOrders = $client->salesOrders()
                               ->latest('order_date')
                               ->paginate(15);

        return view('client.sales_orders.index', compact('salesOrders'));
    }

    public function show(SalesOrder $salesOrder): View
    {
        // Keamanan: Pastikan pesanan ini milik klien yang sedang login
        if ($salesOrder->client_id !== Auth::guard('client')->id()) {
            abort(403, 'Akses Ditolak');
        }

        $salesOrder->load(['items.product', 'sales']);

        return view('client.sales_orders.show', compact('salesOrder'));
    }
}