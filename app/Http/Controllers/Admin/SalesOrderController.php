<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SalesOrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);
        $user = Auth::user();

        $query = Order::with(['client', 'sales'])
            ->where('order_source', 'sales');

        if ($user->hasRole('sales')) {
            $query->where('user_id_sales', $user->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($q) use ($search) {
                      $q->where('client_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('order_date', $request->date);
        }

        $sort = $request->get('sort', 'terbaru');
        switch ($sort) {
            case 'terlama':
                $query->orderBy('order_date', 'asc')->orderBy('order_id', 'asc');
                break;
            case 'klien_az':
                $query->join('clients', 'orders.client_id', '=', 'clients.client_id')
                      ->orderBy('clients.client_name', 'asc')
                      ->select('orders.*');
                break;
            case 'klien_za':
                $query->join('clients', 'orders.client_id', '=', 'clients.client_id')
                      ->orderBy('clients.client_name', 'desc')
                      ->select('orders.*');
                break;
            default:
                $query->orderBy('order_date', 'desc')->orderBy('order_id', 'desc');
        }

        $orders = $query->paginate(15)->appends($request->query());

        return view('admin.sales_orders.index', compact('orders'));
    }

    public function create(): View
    {
        $this->authorize('create', Order::class);
        
        $clients = Client::all();
        $products = Product::where('stock_quantity', '>', 0)->get();
        $salesUsers = User::role('sales')->get(); 

        return view('admin.sales_orders.create', compact('clients', 'products', 'salesUsers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Order::class);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'order_date' => 'required|date',
            'sales_id' => 'nullable|exists:users,user_id',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1', 
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|numeric|min:0.01', 
        ]);

        try {
            DB::beginTransaction();

            $order = new Order();
            $order->client_id = $validated['client_id'];
            $order->order_date = $validated['order_date'];
            $order->notes = $validated['notes'] ?? null;
            
            if ($request->filled('sales_id')) {
                 $order->user_id_sales = $validated['sales_id'];
            } else {
                 $order->user_id_sales = Auth::id();
            }
            
            $order->order_number = Order::generateOrderNumber(Auth::id());
            $order->status = 'pending';
            $order->order_source = 'sales';
            $order->total_amount = 0; 
            $order->save();

            $totalAmount = 0;

            foreach ($validated['products'] as $itemData) {
                $product = Product::find($itemData['product_id']);
                $price = $product->selling_price; 
                $subtotal = $itemData['quantity'] * $price;
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'price_per_unit' => $price,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_amount' => $totalAmount]);

            DB::commit();

            return redirect()
                ->route('admin.sales-orders.show', $order->order_id)
                ->with('success', 'Pesanan Penjualan berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Gagal membuat pesanan: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load(['client', 'sales', 'items.product' => function ($query) {
            $query->withTrashed();
        }]);

        return view('admin.sales_orders.show', compact('order'));
    }

    public function edit(Order $order): View
    {
        $this->authorize('update', $order);

        if ($order->status !== 'pending') {
            abort(403, 'Pesanan yang sudah diproses tidak dapat diedit.');
        }

        $order->load(['items.product' => function ($query) {
            $query->withTrashed();
        }]);
        
        $clients = Client::all();
        $products = Product::all();
        $salesUsers = User::role('sales')->get();

        return view('admin.sales_orders.edit', compact('order', 'clients', 'products', 'salesUsers'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        if ($order->status !== 'pending') {
            return back()->with('error', 'Pesanan yang sudah diproses tidak dapat diedit.');
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'order_date' => 'required|date',
            'sales_id' => 'nullable|exists:users,user_id',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1', 
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'client_id' => $validated['client_id'],
                'order_date' => $validated['order_date'],
                'notes' => $validated['notes'] ?? null,
            ];
            
            if ($request->filled('sales_id')) {
                $updateData['user_id_sales'] = $validated['sales_id'];
            }

            $order->update($updateData);
            $order->items()->delete();
            $totalAmount = 0;
            
            foreach ($validated['products'] as $itemData) {
                $product = Product::find($itemData['product_id']);
                $price = $product->selling_price; 
                $subtotal = $itemData['quantity'] * $price;
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'price_per_unit' => $price,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_amount' => $totalAmount]);
            
            DB::commit();

            return redirect()
                ->route('admin.sales-orders.index')
                ->with('success', 'Pesanan Penjualan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Gagal mengupdate pesanan: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);

        if ($order->status !== 'pending') {
             return back()->with('error', 'Hanya pesanan pending yang dapat dihapus.');
        }

        $order->delete();

        return redirect()
            ->route('admin.sales-orders.index')
            ->with('success', 'Pesanan Penjualan berhasil dihapus.');
    }
}