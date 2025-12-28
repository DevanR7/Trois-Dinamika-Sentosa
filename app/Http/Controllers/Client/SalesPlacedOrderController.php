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
    public function index(Request $request): View
    {
        $client = Auth::guard('client')->user();
        $query = $client->orders()->where('order_source', 'sales');

        $uniqueDates = $client->orders()
            ->where('order_source', 'sales')
            ->select(DB::raw("DATE_FORMAT(order_date, '%Y-%m') as ym"))
            ->distinct()
            ->orderBy('ym', 'desc')
            ->get()
            ->mapWithKeys(fn($item) => [$item->ym => Carbon::createFromFormat('Y-m', $item->ym)->isoFormat('MMMM YYYY')]);

        if ($request->filled('search')) {
            $query->where('order_number', 'like', "%{$request->search}%");
        }
        if ($request->filled('date_filter')) {
            try {
                $date = Carbon::createFromFormat('Y-m', $request->date_filter);
                $query->whereYear('order_date', $date->year)->whereMonth('order_date', $date->month);
            } catch (\Exception $e) {}
        }
        if ($request->filled('status_filter')) {
             $query->where('status', $request->status_filter);
        }

        $sort = $request->get('sort', 'terbaru');
        $query->orderBy('order_date', $sort === 'terlama' ? 'asc' : 'desc');

        $salesOrders = $query->paginate(15)->appends($request->query());

        return view('client.sales_orders.index', compact('salesOrders', 'uniqueDates'));
    }

    public function show(Order $order): View|RedirectResponse
    {
        if ($order->client_id !== Auth::guard('client')->id() || $order->order_source !== 'sales') {
            return redirect()->route('client.sales-orders.index')->with('error', 'Pesanan tidak ditemukan.');
        }

        $order->load(['items.product', 'sales', 'changeRequests']);
        return view('client.sales_orders.show', compact('order'));
    }
}