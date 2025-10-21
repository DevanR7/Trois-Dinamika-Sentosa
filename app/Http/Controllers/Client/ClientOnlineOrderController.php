<?php

namespace App\Http\Controllers\Client; // Pastikan namespace benar

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class ClientOnlineOrderController extends Controller
{
    /**
     * Menampilkan daftar pesanan yang dibuat oleh klien sendiri.
     */
    public function index(Request $request): View // <-- Tambahkan Request
    {
        $client = Auth::guard('client')->user();
        
        $query = $client->orders()
                       ->where('order_source', 'client'); // Filter hanya order dari klien

        // --- Ambil Data untuk Dropdown Filter Tanggal ---
        $uniqueDates = $client->orders()
            ->where('order_source', 'client')
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
        
        // 3. Filter Status
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

        $myOrders = $query->paginate(15)->appends($request->query());

        return view('client.client_online_orders.index', compact('myOrders', 'uniqueDates'));
    }

     /**
     * Menampilkan detail pesanan yang dibuat oleh klien sendiri.
     */
    public function show(Order $order): View | RedirectResponse
    {
        // Keamanan: Pastikan order milik klien DAN dibuat oleh klien
        if ($order->client_id !== Auth::guard('client')->id() || $order->order_source !== 'client') {
             return redirect()->route('client.client-orders.index')->with('error', 'Pesanan tidak ditemukan.');
        }

        $order->load(['items.product']); // Tidak perlu load 'sales'

        return view('client.client_online_orders.show', compact('order'));
    }

    /**
     * Menampilkan form untuk klien membuat pesanan baru.
     */
    public function create(): View
    {
        $products = Product::where('stock_quantity', '>', 0)
                            ->whereNotNull('purchase_price') // Tetap pakai purchase_price
                            ->orderBy('product_name')
                            ->get();

        return view('client.client_online_orders.create', compact('products'));
    }

    /**
     * Menyimpan pesanan baru yang dibuat oleh klien.
     */
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
        $itemsDataForOrder = []; // Inisialisasi di luar try agar bisa diakses di catch

        try {
            DB::beginTransaction();
            $totalAmount = 0;

            foreach ($validated['products'] as $productData) {
                // Lock produk untuk cek stok & decrement
                $product = Product::where('product_id', $productData['product_id'])->lockForUpdate()->first(); 

                if (!$product) throw new \Exception("Produk tidak valid.");
                if ($product->stock_quantity < $productData['quantity']) {
                    throw new \Exception("Stok produk '{$product->product_name}' tidak mencukupi (sisa: {$product->stock_quantity}).");
                }

                $quantity = $productData['quantity'];
                $price = $product->purchase_price ?? 0;
                $subtotal = $quantity * $price;
                $totalAmount += $subtotal;

                $itemsDataForOrder[] = [
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'price_per_unit' => $price,
                    'subtotal' => $subtotal,
                ];

                // ✅ STOK DIKURANGI DI SINI
                $product->decrement('stock_quantity', $quantity);
            }

            $order = $client->orders()->create([
                'order_number' => Order::generateOrderNumber(null),
                'user_id_sales' => null,
                'order_date' => $validated['order_date'],
                'notes' => $validated['notes'],
                'total_amount' => $totalAmount,
                'status' => 'pending_review', // Status menunggu review
                'order_source' => 'client',
            ]);

            $order->items()->createMany($itemsDataForOrder);

            DB::commit();

            return redirect()->route('client.client-orders.index')
                ->with('success', 'Permintaan pesanan Anda berhasil dikirim dan akan ditinjau oleh tim kami.');

        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ KEMBALIKAN STOK JIKA TRANSAKSI GAGAL
            if (!empty($itemsDataForOrder)) {
                foreach ($itemsDataForOrder as $itemDataFailed) {
                    $productFailed = Product::find($itemDataFailed['product_id']);
                    if ($productFailed) {
                        // Tidak perlu lock, karena transaksi sudah di-rollback
                        $productFailed->increment('stock_quantity', $itemDataFailed['quantity']);
                    }
                }
            }
            // =============================================

            return back()->with('error', 'Gagal membuat pesanan: ' . $e->getMessage())->withInput();
        }
    }
}