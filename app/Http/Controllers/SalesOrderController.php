<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Order; // ✅ BERUBAH: Menggunakan model Order
use App\Models\OrderItem; // ✅ BERUBAH: Menggunakan model OrderItem
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SalesOrderController extends Controller
{
    /**
     * Menampilkan daftar pesanan penjualan, difilter berdasarkan role.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class); 
        $user = Auth::user();
        
        $query = Order::with(['client', 'sales']);

        // ✅ BERUBAH: Filter data berdasarkan role Spatie
        if ($user->hasRole('sales')) { 
            $query->where('user_id_sales', $user->user_id);
        }

        // ✅ BERUBAH: Hanya tampilkan pesanan dari 'sales'
        $query->where('order_source', 'sales');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function($q) use ($search) {
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
                // ✅ BERUBAH: Menggunakan nama tabel 'orders'
                $query->join('clients', 'orders.client_id', '=', 'clients.client_id') 
                      ->orderBy('clients.client_name', 'asc');
                break;
            case 'klien_za':
                // ✅ BERUBAH: Menggunakan nama tabel 'orders'
                $query->join('clients', 'orders.client_id', '=', 'clients.client_id')
                      ->orderBy('clients.client_name', 'desc');
                break;
            default: // 'terbaru'
                $query->orderBy('order_date', 'desc')->orderBy('order_id', 'desc');
                break;
        }

        // ✅ BERUBAH: Variabel diganti nama jadi $orders
        $orders = $query->paginate(15)->appends($request->query());

        // Variabel $orders dikirim ke view 'sales_orders.index'
        return view('sales_orders.index', compact('orders')); 
    }

    /**
     * Menampilkan form untuk membuat pesanan baru.
     */
    public function create(): View
    {
        $this->authorize('create', Order::class);
        $clients = Client::where('is_approved', true)->get();
        $products = Product::where('stock_quantity', '>', 0)->get();
        
        return view('sales_orders.create', compact('clients', 'products'));
    }

    /**
     * Menyimpan pesanan baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Order::class);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'order_date' => 'required|date',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // ✅ BERUBAH: Membuat instance dari model Order
            $order = new Order();
            $order->client_id = $validated['client_id'];
            $order->order_date = $validated['order_date'];
            $order->notes = $validated['notes'];
            $order->user_id_sales = Auth::id();
            $order->order_number = Order::generateOrderNumber(Auth::id());
            $order->status = 'pending'; 
            $order->order_source = 'sales'; // ✅ PENTING

            $totalAmount = 0;
            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']);
                
                // ✅ Sesuai permintaan Anda: menggunakan 'purchase_price'
                $subtotal = $productData['quantity'] * $product->purchase_price; 
                $totalAmount += $subtotal;
            }
            $order->total_amount = $totalAmount;
            $order->save();

            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']);
                
                // ✅ BERUBAH: Membuat instance dari OrderItem
                OrderItem::create([ 
                    'order_id' => $order->order_id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    // ✅ Sesuai permintaan Anda: menggunakan 'purchase_price'
                    'price_per_unit' => $product->purchase_price, 
                    'subtotal' => $productData['quantity'] * $product->purchase_price,
                ]);
            }

            DB::commit();
            
            return redirect()->route('sales-orders.show', $order->order_id)->with('success', 'Pesanan Penjualan berhasil dibuat!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat pesanan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menampilkan detail satu pesanan.
     */
    // ✅ BERUBAH: Menggunakan Route Model Binding 'Order'
    public function show(Order $order): View
{
    $this->authorize('view', $order);

    $order->load(['client', 'sales', 'items.product' => function ($query) {
        $query->withTrashed();
    }]);

    // Make sure 'order' is the variable name being passed
    return view('sales_orders.show', compact('order'));
}

    /**
     * Menampilkan form untuk mengedit pesanan.
     */
    // ✅ BERUBAH: Menggunakan Route Model Binding 'Order'
    public function edit(Order $order): View
    {
        $this->authorize('update', $order);
        if ($order->status !== 'pending') {
            abort(403, 'Pesanan yang sudah diproses tidak dapat diedit.');
        }

        $order->load('items.product');
        $clients = Client::all();
        $products = Product::all();
        
        return view('sales_orders.edit', compact('order', 'clients', 'products'));
    }

    /**
     * Mengupdate pesanan di database.
     */
    // ✅ BERUBAH: Menggunakan Route Model Binding 'Order'
    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);
        if ($order->status !== 'pending') {
            return back()->with('error', 'Pesanan yang sudah diproses tidak dapat diedit.')->withInput();
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'order_date' => 'required|date',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $order->client_id = $validated['client_id'];
            $order->order_date = $validated['order_date'];
            $order->notes = $validated['notes'];
            
            $order->items()->delete();

            $totalAmount = 0;
            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']);
                
                // ✅ Sesuai permintaan Anda: menggunakan 'purchase_price'
                $subtotal = $productData['quantity'] * $product->purchase_price; 
                $totalAmount += $subtotal;

                // ✅ BERUBAH: Membuat instance dari OrderItem
                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'price_per_unit' => $product->purchase_price,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->total_amount = $totalAmount;
            $order->save();

            DB::commit();
            
            return redirect()->route('sales-orders.index')->with('success', 'Pesanan Penjualan berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate pesanan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menghapus pesanan dari database.
     */
    // ✅ BERUBAH: Menggunakan Route Model Binding 'Order'
    public function destroy(Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);
        $order->delete();
        
        return redirect()->route('sales-orders.index')->with('success', 'Pesanan Penjualan berhasil dihapus.');
    }
}