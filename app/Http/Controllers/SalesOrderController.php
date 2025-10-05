<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
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
        $this->authorize('viewAny', SalesOrder::class);
        $user = Auth::user();
        
        $query = SalesOrder::with(['client', 'sales']);

        // Filter data jika role adalah 'sales'
        if ($user->role === 'sales') {
            $query->where('user_id_sales', $user->user_id);
        }
        
        // Logika untuk Pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function($q) use ($search) {
                      $q->where('client_name', 'like', "%{$search}%");
                  });
            });
        }

        // Logika untuk Filter Tanggal (satu tanggal)
        if ($request->filled('date')) {
            $query->whereDate('order_date', $request->date);
        }

        // Logika untuk Pengurutan
        $sort = $request->get('sort', 'terbaru');
        switch ($sort) {
            case 'terlama':
                $query->orderBy('order_date', 'asc')->orderBy('order_id', 'asc');
                break;
            case 'klien_az':
                $query->join('clients', 'sales_orders.client_id', '=', 'clients.client_id')
                      ->orderBy('clients.client_name', 'asc');
                break;
            case 'klien_za':
                $query->join('clients', 'sales_orders.client_id', '=', 'clients.client_id')
                      ->orderBy('clients.client_name', 'desc');
                break;
            default: // 'terbaru'
                $query->orderBy('order_date', 'desc')->orderBy('order_id', 'desc');
                break;
        }

        $salesOrders = $query->paginate(15)->appends($request->query());

        return view('sales_orders.index', compact('salesOrders'));
    }

    /**
     * Menampilkan form untuk membuat pesanan baru.
     */
    public function create(): View
    {
        $this->authorize('create', SalesOrder::class);

        $clients = Client::all();
        $products = Product::where('stock_quantity', '>', 0)->get();
        return view('sales_orders.create', compact('clients', 'products'));
    }

    /**
     * Menyimpan pesanan baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
         $this->authorize('create', SalesOrder::class);

    $validated = $request->validate([
        'client_id' => 'required|exists:clients,client_id',
        'order_date' => 'required|date',
        'notes' => 'nullable|string',
        'products' => 'required|array|min:1',
        'products.*.product_id' => 'required|exists:products,product_id',
        'products.*.quantity' => 'required|integer|min:1',
    ]);

        /* foreach ($validated['products'] as $productData) {
        $product = Product::find($productData['product_id']);
        if ($product->stock_quantity < $productData['quantity']) {
            // Jika stok tidak cukup, kembalikan dengan pesan error
            return back()
                ->with('error', "Stok untuk produk '{$product->product_name}' tidak mencukupi. Sisa stok: {$product->stock_quantity}.")
                ->withInput();
        }
    }*/

        try {
        DB::beginTransaction();

        $salesOrder = new SalesOrder();
        $salesOrder->client_id = $validated['client_id'];
        $salesOrder->order_date = $validated['order_date'];
        $salesOrder->notes = $validated['notes'];
        $salesOrder->user_id_sales = Auth::id();
        $salesOrder->order_number = SalesOrder::generateOrderNumber(); // Anda mungkin ingin menggunakan generator nomor seperti di Invoice
        
        $totalAmount = 0;
        foreach ($validated['products'] as $productData) {
            $product = Product::find($productData['product_id']);
            $totalAmount += $productData['quantity'] * $product->purchase_price;
        }
        $salesOrder->total_amount = $totalAmount;
        $salesOrder->save();

        foreach ($validated['products'] as $productData) {
            $product = Product::find($productData['product_id']);
            SalesOrderItem::create([
                'order_id' => $salesOrder->order_id,
                'product_id' => $productData['product_id'],
                'quantity' => $productData['quantity'],
                'price_per_unit' => $product->purchase_price,
                'subtotal' => $productData['quantity'] * $product->purchase_price,
            ]);
        }

        DB::commit();
        
        // ✅ PERUBAHAN DI SINI
        return redirect()->route('sales-orders.show', $salesOrder->order_id)->with('success', 'Pesanan Penjualan berhasil dibuat!');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal membuat pesanan: ' . $e->getMessage())->withInput();
    }
    }

    /**
     * Menampilkan detail satu pesanan.
     */
    public function show(SalesOrder $salesOrder): View
{
    $this->authorize('view', $salesOrder);

    // Eager load relasi, termasuk produk yang sudah di-soft delete
    $salesOrder->load(['client', 'sales', 'items.product' => function ($query) {
        $query->withTrashed();
    }]);
    
    return view('sales_orders.show', compact('salesOrder'));
}

    /**
     * Menampilkan form untuk mengedit pesanan.
     */
    public function edit(SalesOrder $salesOrder): View
    {
        $this->authorize('update', $salesOrder);

        $salesOrder->load('items.product');
        $clients = Client::all();
        $products = Product::all();
        return view('sales_orders.edit', compact('salesOrder', 'clients', 'products'));
    }

    /**
     * Mengupdate pesanan di database.
     */
    public function update(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('update', $salesOrder);

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

            $salesOrder->client_id = $validated['client_id'];
            $salesOrder->order_date = $validated['order_date'];
            $salesOrder->notes = $validated['notes'];
            
             $salesOrder->items()->delete();

        $totalAmount = 0;
        foreach ($validated['products'] as $productData) {
            $product = Product::find($productData['product_id']);
            // PERUBAHAN DI SINI
            $subtotal = $productData['quantity'] * $product->purchase_price; 
            $totalAmount += $subtotal;

            SalesOrderItem::create([
                'order_id' => $salesOrder->order_id,
                'product_id' => $productData['product_id'],
                'quantity' => $productData['quantity'],
                // PERUBAHAN DI SINI
                'price_per_unit' => $product->purchase_price,
                'subtotal' => $subtotal,
            ]);
        }

        $salesOrder->total_amount = $totalAmount;
        $salesOrder->save();

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
    public function destroy(SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('delete', $salesOrder);
        
        $salesOrder->delete();
        return redirect()->route('sales-orders.index')->with('success', 'Pesanan Penjualan berhasil dihapus.');
    }
}