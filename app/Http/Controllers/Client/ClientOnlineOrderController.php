<?php

namespace App\Http\Controllers\Client;

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
    // ============================================================
    // 📦 1. Menampilkan daftar pesanan yang dibuat oleh klien
    // ============================================================
    public function index(Request $request): View
    {
        $client = Auth::guard('client')->user();

        // === Query dasar: hanya pesanan dari klien ===
        $query = $client->orders()
                        ->where('order_source', 'client');

        // === Ambil data tanggal unik (untuk filter dropdown) ===
        $uniqueDates = $client->orders()
            ->where('order_source', 'client')
            ->select(DB::raw("DATE_FORMAT(order_date, '%Y-%m') as ym"))
            ->distinct()
            ->orderBy('ym', 'desc')
            ->get()
            ->mapWithKeys(fn($item) => [
                $item->ym => Carbon::createFromFormat('Y-m', $item->ym)->isoFormat('MMMM YYYY'),
            ]);

        // === Filter pencarian berdasarkan nomor pesanan ===
        if ($request->filled('search')) {
            $query->where('order_number', 'like', "%{$request->search}%");
        }

        // === Filter berdasarkan bulan dan tahun ===
        if ($request->filled('date_filter')) {
            $yearMonth = $request->date_filter; // Format: YYYY-MM
            try {
                $date = Carbon::createFromFormat('Y-m', $yearMonth);
                $query->whereYear('order_date', $date->year)
                      ->whereMonth('order_date', $date->month);
            } catch (\Exception $e) {
                // Abaikan jika format salah
            }
        }

        // === Filter status pesanan ===
        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        // === Urutkan data (default: terbaru) ===
        $sort = $request->get('sort', 'terbaru');
        $query->orderBy('order_date', $sort === 'terlama' ? 'asc' : 'desc');

        // === Pagination ===
        $myOrders = $query->paginate(15)->appends($request->query());

        return view('client.client_online_orders.index', compact('myOrders', 'uniqueDates'));
    }

    // ============================================================
    // 📄 2. Menampilkan detail satu pesanan klien
    // ============================================================
    public function show(Order $order): View|RedirectResponse
    {
        // === Keamanan: pastikan pesanan milik klien & dibuat oleh klien ===
        if ($order->client_id !== Auth::guard('client')->id() || $order->order_source !== 'client') {
            return redirect()->route('client.client-orders.index')
                             ->with('error', 'Pesanan tidak ditemukan.');
        }

        // === Load relasi items dan produk (tidak perlu relasi sales) ===
        $order->load(['items.product']);

        return view('client.client_online_orders.show', compact('order'));
    }

    // ============================================================
    // 🛒 3. Menampilkan form untuk membuat pesanan baru
    // ============================================================
    public function create(): View
    {
        // === Hanya produk yang memiliki stok dan harga jual ===
        $products = Product::where('stock_quantity', '>', 0)
            ->whereNotNull('selling_price')
            ->orderBy('product_name')
            ->get();

        return view('client.client_online_orders.create', compact('products'));
    }

    // ============================================================
    // 💾 4. Menyimpan pesanan baru dari klien
    // ============================================================
    public function store(Request $request): RedirectResponse
    {
        // === Validasi input ===
        $validated = $request->validate([
            'order_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        $client = Auth::guard('client')->user();
        $itemsDataForOrder = []; // Simpan data item agar bisa rollback stok jika gagal

        try {
            DB::beginTransaction();
            $totalAmount = 0;

            // === Iterasi setiap produk yang dipesan ===
            foreach ($validated['products'] as $productData) {
                // Lock produk agar stok tidak dikurangi bersamaan oleh transaksi lain
                $product = Product::where('product_id', $productData['product_id'])->lockForUpdate()->first();

                if (!$product) {
                    throw new \Exception("Produk tidak valid.");
                }

                // === Validasi stok tersedia ===
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

                // === Kurangi stok produk ===
                $product->decrement('stock_quantity', $quantity);
            }

            // === Simpan data pesanan utama ===
            $order = $client->orders()->create([
                'order_number' => Order::generateOrderNumber(null),
                'user_id_sales' => null,
                'order_date' => $validated['order_date'],
                'notes' => $validated['notes'],
                'total_amount' => $totalAmount,
                'status' => 'pending_review', // Status awal
                'order_source' => 'client',
            ]);

            // === Simpan item pesanan ===
            $order->items()->createMany($itemsDataForOrder);

            DB::commit();

            return redirect()->route('client.client-orders.index')
                ->with('success', 'Permintaan pesanan Anda berhasil dikirim dan akan ditinjau oleh tim kami.');

        } catch (\Exception $e) {
            DB::rollBack();

            // === Jika gagal, kembalikan stok produk ===
            if (!empty($itemsDataForOrder)) {
                foreach ($itemsDataForOrder as $itemDataFailed) {
                    $productFailed = Product::find($itemDataFailed['product_id']);
                    if ($productFailed) {
                        $productFailed->increment('stock_quantity', $itemDataFailed['quantity']);
                    }
                }
            }

            return back()->with('error', 'Gagal membuat pesanan: ' . $e->getMessage())->withInput();
        }
    }
}
