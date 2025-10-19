<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderChangeRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\OrderChangeRequestItem;

class OrderChangeRequestController extends Controller
{
    /**
     * Menampilkan form untuk mengajukan permintaan perubahan.
     */
    public function create(Order $order): View|RedirectResponse
    {
        // 1. Keamanan: Pastikan order milik klien
        if ($order->client_id !== Auth::guard('client')->id()) {
            abort(403, 'Akses Ditolak');
        }

        // 2. Logika Bisnis: Hanya boleh request jika status order memungkinkan
        // Anda bisa sesuaikan status ini sesuai kebutuhan
        if (!in_array($order->status, ['pending', 'approved'])) {
            return redirect()->route('client.orders.show', $order->order_id)
                ->with('error', 'Permintaan perubahan tidak dapat diajukan untuk pesanan dengan status ' . $order->status . '.');
        }

        // 3. Logika Bisnis: Cek apakah sudah ada request pending untuk order ini
        if ($order->changeRequests()->where('status', 'pending')->exists()) {
             return redirect()->route('client.orders.show', $order->order_id)
                ->with('warning', 'Sudah ada permintaan perubahan yang sedang menunggu diproses untuk pesanan ini.');
        }

        // Load item order saat ini dan daftar produk yang bisa ditambahkan
        $order->load('items.product');
        $products = Product::orderBy('product_name')->get(); // Ambil semua produk

        // Tampilkan view form permintaan
        // ❗️ Nama view 'client.orders.request_change' perlu dibuat
        return view('client.orders.request_change', compact('order', 'products'));
    }

    /**
     * Menyimpan permintaan perubahan baru.
     */
    public function store(Request $request, Order $order): RedirectResponse
    {
         // 1. Keamanan & Logika Bisnis (ulangi cek dari 'create')
         if ($order->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }
        if (!in_array($order->status, ['pending', 'approved'])) {
            return back()->with('error', 'Pesanan ini sudah tidak dapat diubah.');
        }
         if ($order->changeRequests()->where('status', 'pending')->exists()) {
             return back()->with('error', 'Sudah ada permintaan perubahan yang sedang diproses.');
        }

        // 2. Validasi Input
        $validated = $request->validate([
            'request_type' => ['required', Rule::in(['cancel', 'modify'])],
            'client_notes' => 'nullable|string|max:1000',
            // Validasi item hanya jika request_type adalah 'modify'
            'items' => 'required_if:request_type,modify|array|min:1',
            'items.*.product_id' => 'required_if:request_type,modify|exists:products,product_id',
            'items.*.quantity' => 'required_if:request_type,modify|integer|min:0', // Boleh 0 untuk hapus
            'items.*.action' => ['required_if:request_type,modify', Rule::in(['add', 'remove', 'update_qty'])],
            'items.*.original_quantity' => 'nullable|integer', // Hanya ada jika action 'remove' atau 'update_qty'
        ]);

        try {
            DB::beginTransaction();

            // 3. Buat record OrderChangeRequest
            $changeRequest = $order->changeRequests()->create([
                'client_id' => $order->client_id,
                'request_type' => $validated['request_type'],
                'client_notes' => $validated['client_notes'],
                'status' => 'pending', // Status awal
            ]);

            // 4. Jika tipe 'modify', simpan detail item yang diminta
            if ($validated['request_type'] === 'modify' && isset($validated['items'])) {
                $itemsToSave = [];
                foreach ($validated['items'] as $itemData) {
                    $product = Product::find($itemData['product_id']);
                    // Gunakan harga jual (selling_price) saat request dibuat
                    // Anda bisa sesuaikan ini jika klien harusnya melihat harga lain
                    $price = $product->selling_price ?? 0;
                    $requestedQuantity = $itemData['quantity'];
                    $subtotal = $requestedQuantity * $price;

                    $itemsToSave[] = [
                        'order_change_request_id' => $changeRequest->request_id,
                        'product_id' => $itemData['product_id'],
                        'original_quantity' => $itemData['original_quantity'] ?? null,
                        'requested_quantity' => $requestedQuantity,
                        'action' => $itemData['action'],
                        'price_per_unit' => $price,
                        'subtotal' => $subtotal,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                // Simpan semua item sekaligus
                OrderChangeRequestItem::insert($itemsToSave);
            }

            DB::commit();

            // 5. Redirect kembali ke halaman detail order dengan pesan sukses
            return redirect()->route('client.orders.show', $order->order_id)
                ->with('success', 'Permintaan perubahan pesanan berhasil diajukan dan sedang menunggu diproses.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Tampilkan error yang lebih deskriptif saat development
            return back()->with('error', 'Gagal mengajukan permintaan: ' . $e->getMessage())->withInput();
            // return back()->with('error', 'Gagal mengajukan permintaan perubahan. Silakan coba lagi.')->withInput();
        }
    }
}