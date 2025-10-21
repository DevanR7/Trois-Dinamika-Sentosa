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

// Nama class diubah
class ClientOnlineOrderController extends Controller
{
    /**
     * Menampilkan daftar pesanan yang dibuat oleh klien sendiri.
     */
    public function index(): View
    {
        $client = Auth::guard('client')->user();
        $myOrders = $client->orders()
                           ->where('order_source', 'client') // Filter hanya order dari klien
                           ->latest('order_date')
                           ->paginate(15);

        return view('client.client_online_orders.index', compact('myOrders'));
    }

     /**
     * Menampilkan detail pesanan yang dibuat oleh klien sendiri.
     */
    public function show(Order $order): View | RedirectResponse // Tambah RedirectResponse
    {
        // Keamanan: Pastikan order milik klien DAN dibuat oleh klien
        if ($order->client_id !== Auth::guard('client')->id() || $order->order_source !== 'client') {
             // Redirect ke index jika mencoba akses order sales di sini
             return redirect()->route('client.client-orders.index')->with('error', 'Pesanan tidak ditemukan.');
            // abort(403, 'Akses Ditolak');
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

        try {
            DB::beginTransaction();
            $totalAmount = 0;
            $itemsDataForOrder = [];

            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']);
                if (!$product) throw new \Exception("Produk tidak valid.");
                if ($product->stock_quantity < $productData['quantity']) {
                    throw new \Exception("Stok produk '{$product->product_name}' tidak mencukupi (sisa: {$product->stock_quantity}).");
                }
                $quantity = $productData['quantity'];
                $price = $product->purchase_price ?? 0; // Tetap pakai purchase_price
                $subtotal = $quantity * $price;
                $totalAmount += $subtotal;
                $itemsDataForOrder[] = [ /* ... data item ... */
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'price_per_unit' => $price,
                    'subtotal' => $subtotal,
                ];
                // $product->decrement('stock_quantity', $quantity); // Pindahkan decrement ke admin saat approval?
            }

            $order = $client->orders()->create([
                'order_number' => Order::generateOrderNumber(null),
                'user_id_sales' => null,
                'order_date' => $validated['order_date'],
                'notes' => $validated['notes'],
                'total_amount' => $totalAmount,
                'status' => 'pending_review', // Ubah status awal jadi 'pending_review'
                'order_source' => 'client',
            ]);
            $order->items()->createMany($itemsDataForOrder);

            DB::commit();

            // Redirect ke riwayat pesanan online klien
            return redirect()->route('client.client-orders.index')
                ->with('success', 'Permintaan pesanan Anda berhasil dikirim dan akan ditinjau oleh tim kami.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat pesanan: ' . $e->getMessage())->withInput();
        }
    }
}