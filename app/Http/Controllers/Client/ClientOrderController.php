<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order; // ✅ BERUBAH: Gunakan model Order
use App\Models\OrderItem; // ✅ BERUBAH: Gunakan model OrderItem
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ClientOrderController extends Controller
{
    /**
     * Menampilkan form untuk klien membuat pesanan baru.
     */
    public function create(): View
    {
        // Ambil produk yang stoknya ada dan gunakan harga jual (selling_price)
        $products = Product::where('stock_quantity', '>', 0)
                            ->whereNotNull('selling_price') // Pastikan harga jual ada
                            ->orderBy('product_name')
                            ->get();

        // Pastikan view mengarah ke lokasi yang benar
        return view('client.orders.create', compact('products'));
    }

    /**
     * Menyimpan pesanan baru yang dibuat oleh klien.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validasi input
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

            // Loop untuk validasi stok dan hitung total
            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']);

                // Validasi Produk & Stok
                if (!$product) {
                     throw new \Exception("Produk tidak valid.");
                }
                // Anda mungkin ingin menambahkan validasi jika purchase_price null, tergantung aturan bisnis
                // if (is_null($product->purchase_price)) {
                //      throw new \Exception("Produk {$product->product_name} tidak memiliki harga beli.");
                // }
                if ($product->stock_quantity < $productData['quantity']) {
                    throw new \Exception("Stok produk '{$product->product_name}' tidak mencukupi (sisa: {$product->stock_quantity}).");
                }

                $quantity = $productData['quantity'];
                // ✅ BERUBAH: Gunakan purchase_price, default ke 0 jika null
                $price = $product->purchase_price ?? 0;
                $subtotal = $quantity * $price;
                $totalAmount += $subtotal;

                // Siapkan data item untuk disimpan nanti
                $itemsDataForOrder[] = [
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    // ✅ BERUBAH: Simpan purchase_price
                    'price_per_unit' => $price,
                    'subtotal' => $subtotal,
                ];

                // Kurangi stok (jika perlu)
                 $product->decrement('stock_quantity', $quantity);
            }

            // Buat entri Order baru
            $order = $client->orders()->create([
                'order_number' => Order::generateOrderNumber(null),
                'user_id_sales' => null,
                'order_date' => $validated['order_date'],
                'notes' => $validated['notes'],
                'total_amount' => $totalAmount, // Total berdasarkan purchase_price
                'status' => 'pending',
                'order_source' => 'client',
            ]);

            // Simpan item-item pesanan
            $order->items()->createMany($itemsDataForOrder);

            DB::commit();

            return redirect()->route('client.orders.index')
                ->with('success', 'Permintaan pesanan Anda berhasil dikirim.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Kembalikan stok jika perlu
            return back()->with('error', 'Gagal membuat pesanan: ' . $e->getMessage())->withInput();
        }
    }
}