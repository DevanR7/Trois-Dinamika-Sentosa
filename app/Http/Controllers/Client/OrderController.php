<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $client = Auth::guard('client')->user();
        $orders = $client->orders()
                        ->latest('order_date')
                        ->paginate(15);

        // ✅ BERUBAH: Gunakan path view yang benar
        return view('client.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        if ($order->client_id !== Auth::guard('client')->id()) {
            abort(403, 'Akses Ditolak');
        }
        $order->load(['items.product', 'sales']);

        // ✅ BERUBAH: Gunakan path view yang benar
        return view('client.orders.show', compact('order'));
    }
}