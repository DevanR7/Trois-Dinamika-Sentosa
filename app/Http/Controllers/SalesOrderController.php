<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SalesOrderController extends Controller
{
    /**
     * ===========================================================
     *  MENAMPILKAN DAFTAR PESANAN PENJUALAN
     * ===========================================================
     * - Data difilter berdasarkan peran pengguna (sales/admin)
     * - Dapat difilter berdasarkan pencarian dan tanggal
     * - Dapat diurutkan dengan beberapa opsi
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);
        $user = Auth::user();

        $query = Order::with(['client', 'sales'])
            ->where('order_source', 'sales');

        // Filter khusus untuk pengguna dengan peran "sales"
        if ($user->hasRole('sales')) {
            $query->where('user_id_sales', $user->user_id);
        }

        // Filter pencarian berdasarkan nomor order atau nama klien
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($q) use ($search) {
                      $q->where('client_name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter berdasarkan tanggal order
        if ($request->filled('date')) {
            $query->whereDate('order_date', $request->date);
        }

        // Opsi pengurutan
        $sort = $request->get('sort', 'terbaru');
        switch ($sort) {
            case 'terlama':
                $query->orderBy('order_date', 'asc')->orderBy('order_id', 'asc');
                break;
            case 'klien_az':
                $query->join('clients', 'orders.client_id', '=', 'clients.client_id')
                      ->orderBy('clients.client_name', 'asc');
                break;
            case 'klien_za':
                $query->join('clients', 'orders.client_id', '=', 'clients.client_id')
                      ->orderBy('clients.client_name', 'desc');
                break;
            default:
                $query->orderBy('order_date', 'desc')->orderBy('order_id', 'desc');
        }

        $orders = $query->paginate(15)->appends($request->query());

        return view('sales_orders.index', compact('orders'));
    }

    /**
     * ===========================================================
     *  FORM PEMBUATAN PESANAN PENJUALAN BARU
     * ===========================================================
     * - Hanya dapat diakses oleh pengguna dengan izin 'create'
     * - Memuat daftar klien dan produk yang masih tersedia
     */
    public function create(): View
    {
        $this->authorize('create', Order::class);
        $clients = Client::all();
        $products = Product::where('stock_quantity', '>', 0)->get();

        return view('sales_orders.create', compact('clients', 'products'));
    }

    /**
     * ===========================================================
     *  MENYIMPAN PESANAN PENJUALAN BARU
     * ===========================================================
     * - Melakukan validasi input
     * - Menghitung total harga berdasarkan produk yang dipilih
     * - Menyimpan data order dan item secara transaksional
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

            $order = new Order();
            $order->client_id = $validated['client_id'];
            $order->order_date = $validated['order_date'];
            $order->notes = $validated['notes'] ?? null;
            $order->user_id_sales = Auth::id();
            $order->order_number = Order::generateOrderNumber(Auth::id());
            $order->status = 'pending';
            $order->order_source = 'sales';

            // Hitung total harga
            $totalAmount = 0;
            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']);
                $subtotal = $productData['quantity'] * $product->selling_price;
                $totalAmount += $subtotal;
            }

            $order->total_amount = $totalAmount;
            $order->save();

            // Simpan item order
            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']);

                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'price_per_unit' => $product->selling_price,
                    'subtotal' => $productData['quantity'] * $product->selling_price,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('sales-orders.show', $order->order_id)
                ->with('success', 'Pesanan Penjualan berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Gagal membuat pesanan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * ===========================================================
     *  MENAMPILKAN DETAIL PESANAN PENJUALAN
     * ===========================================================
     * - Memuat relasi client, sales, dan item produk
     */
    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load(['client', 'sales', 'items.product' => function ($query) {
            $query->withTrashed();
        }]);

        return view('sales_orders.show', compact('order'));
    }

    /**
     * ===========================================================
     *  FORM EDIT PESANAN PENJUALAN
     * ===========================================================
     * - Hanya pesanan dengan status "pending" yang bisa diedit
     */
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
     * ===========================================================
     *  MENYIMPAN PERUBAHAN PESANAN PENJUALAN
     * ===========================================================
     * - Menghapus item lama lalu menulis ulang item baru
     * - Mengupdate total harga pesanan
     */
    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        if ($order->status !== 'pending') {
            return back()
                ->with('error', 'Pesanan yang sudah diproses tidak dapat diedit.')
                ->withInput();
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

            $order->update([
                'client_id' => $validated['client_id'],
                'order_date' => $validated['order_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $order->items()->delete();

            $totalAmount = 0;
            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']);
                $subtotal = $productData['quantity'] * $product->purchase_price;
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'price_per_unit' => $product->selling_price,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_amount' => $totalAmount]);
            DB::commit();

            return redirect()
                ->route('sales-orders.index')
                ->with('success', 'Pesanan Penjualan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Gagal mengupdate pesanan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * ===========================================================
     *  MENGHAPUS PESANAN PENJUALAN
     * ===========================================================
     * - Menghapus data pesanan beserta item terkait
     */
    public function destroy(Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);

        $order->delete();

        return redirect()
            ->route('sales-orders.index')
            ->with('success', 'Pesanan Penjualan berhasil dihapus.');
    }
}
