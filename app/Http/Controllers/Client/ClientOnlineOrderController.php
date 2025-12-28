<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class ClientOnlineOrderController extends Controller
{
    public function index(Request $request): View
    {
        $client = Auth::guard('client')->user();
        $query = $client->orders()->where('order_source', 'client');

        $uniqueDates = $client->orders()
            ->where('order_source', 'client')
            ->select(DB::raw("DATE_FORMAT(order_date, '%Y-%m') as ym"))
            ->distinct()
            ->orderBy('ym', 'desc')
            ->get()
            ->mapWithKeys(fn($item) => [
                $item->ym => Carbon::createFromFormat('Y-m', $item->ym)->isoFormat('MMMM YYYY'),
            ]);

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

        $myOrders = $query->paginate(15)->appends($request->query());

        return view('client.client_online_orders.index', compact('myOrders', 'uniqueDates'));
    }

    public function show(Order $order): View|RedirectResponse
    {
        if ($order->client_id !== Auth::guard('client')->id() || $order->order_source !== 'client') {
            return redirect()->route('client.client-orders.index')->with('error', 'Pesanan tidak ditemukan.');
        }
        $order->load(['items.product']);
        return view('client.client_online_orders.show', compact('order'));
    }

    public function create(): View
    {
        $products = Product::where('stock_quantity', '>', 0)
            ->whereNotNull('selling_price')
            ->orderBy('product_name')
            ->get();
        return view('client.client_online_orders.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        $client = Auth::guard('client')->user();
        $itemsDataForOrder = [];

        try {
            DB::beginTransaction();
            $totalAmount = 0;

            foreach ($validated['products'] as $productData) {
                $product = Product::where('product_id', $productData['product_id'])->lockForUpdate()->first();

                if (!$product) throw new \Exception("Produk tidak valid.");

                if ($product->stock_quantity < $productData['quantity']) {
                    throw new \Exception("Stok produk '{$product->product_name}' tidak mencukupi (sisa: {$product->stock_quantity}).");
                }

                $quantity = $productData['quantity'];
                $price = $product->selling_price ?? 0;
                $subtotal = $quantity * $price;
                $totalAmount += $subtotal;

                $itemsDataForOrder[] = [
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'price_per_unit' => $price,
                    'subtotal' => $subtotal,
                ];

                $product->decrement('stock_quantity', $quantity);
            }

            $order = $client->orders()->create([
                'order_number' => Order::generateOrderNumber(null),
                'user_id_sales' => null,
                'order_date' => $validated['order_date'],
                'notes' => $validated['notes'],
                'total_amount' => $totalAmount,
                'status' => 'pending_review',
                'order_source' => 'client',
            ]);

            $order->items()->createMany($itemsDataForOrder);

            DB::commit();

            return redirect()->route('client.client-orders.index')
                ->with('success', 'Pesanan Anda berhasil dikirim dan stok telah diamankan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat pesanan: ' . $e->getMessage())->withInput();
        }
    }
}