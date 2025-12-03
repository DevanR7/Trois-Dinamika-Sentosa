<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderChangeRequest;
use App\Models\OrderChangeRequestItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class OrderChangeRequestController extends Controller
{
    /**
     * ============================================================
     * 🔹 METHOD: create()
     * Menampilkan form pengajuan permintaan perubahan pesanan.
     * ============================================================
     */
    public function create(Order $order): View|RedirectResponse
    {
        // === 1. Keamanan: Pastikan order milik klien yang sedang login ===
        if ($order->client_id !== Auth::guard('client')->id()) {
            abort(403, 'Akses Ditolak');
        }

        // === 2. Logika bisnis: Hanya izinkan jika status order masih bisa diubah ===
        if (!in_array($order->status, ['pending', 'approved'])) {
            return redirect()
                ->route('client.sales-orders.show', $order->order_id)
                ->with('error', 'Permintaan perubahan tidak dapat diajukan untuk pesanan dengan status ' . $order->status . '.');
        }

        // === 3. Cegah duplikasi: Hanya satu permintaan perubahan pending per order ===
        if ($order->changeRequests()->where('status', 'pending')->exists()) {
            return redirect()
                ->route('client.sales-orders.show', $order->order_id)
                ->with('warning', 'Sudah ada permintaan perubahan yang sedang menunggu diproses untuk pesanan ini.');
        }

        // === 4. Siapkan data untuk form ===
        $order->load('items.product');                    // Item dalam pesanan
        $products = Product::orderBy('product_name')->get(); // Semua produk yang bisa dipilih

        // === 5. Tampilkan view untuk pengajuan perubahan ===
        return view('client.sales_orders.request_change', compact('order', 'products'));
    }

    /**
     * ============================================================
     * 🔹 METHOD: store()
     * Menyimpan permintaan perubahan baru dari klien.
     * ============================================================
     */
    public function store(Request $request, Order $order): RedirectResponse
    {
        // === 1. Keamanan: Pastikan order milik klien dan masih bisa diubah ===
        if ($order->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        if (!in_array($order->status, ['pending', 'approved'])) {
            return back()->with('error', 'Pesanan ini sudah tidak dapat diubah.');
        }

        if ($order->changeRequests()->where('status', 'pending')->exists()) {
            return back()->with('error', 'Sudah ada permintaan perubahan yang sedang diproses.');
        }

        // === 2. Validasi input permintaan perubahan ===
        $validated = $request->validate([
            'request_type' => ['required', Rule::in(['cancel', 'modify'])],
            'client_notes' => 'nullable|string|max:1000',

            // Validasi untuk tipe "modify"
    'items' => 'required_if:request_type,modify|array|min:1',
    'items.*.product_id' => 'required_if:request_type,modify|exists:products,product_id',
    
    // ✅ GANTI integer -> numeric
    'items.*.quantity' => 'required_if:request_type,modify|numeric|min:0', 
    'items.*.action' => ['required_if:request_type,modify', Rule::in(['add', 'remove', 'update_qty'])],
    
    // ✅ GANTI integer -> numeric
    'items.*.original_quantity' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            // === 3. Simpan header permintaan perubahan ===
            $changeRequest = $order->changeRequests()->create([
                'client_id'     => $order->client_id,
                'request_type'  => $validated['request_type'],
                'client_notes'  => $validated['client_notes'],
                'status'        => 'pending', // status awal
            ]);

            // === 4. Jika tipe 'modify', simpan detail item perubahan ===
            if ($validated['request_type'] === 'modify' && isset($validated['items'])) {
                $itemsToSave = [];

                foreach ($validated['items'] as $itemData) {
                    $product = Product::find($itemData['product_id']);
                    $price = $product->selling_price ?? 0;
                    $requestedQuantity = $itemData['quantity'];
                    $subtotal = $requestedQuantity * $price;

                    $itemsToSave[] = [
                        'order_change_request_id' => $changeRequest->request_id,
                        'product_id'              => $itemData['product_id'],
                        'original_quantity'       => $itemData['original_quantity'] ?? null,
                        'requested_quantity'      => $requestedQuantity,
                        'action'                  => $itemData['action'],
                        'price_per_unit'          => $price,
                        'subtotal'                => $subtotal,
                        'created_at'              => now(),
                        'updated_at'              => now(),
                    ];
                }

                // Simpan semua item sekaligus (lebih efisien)
                OrderChangeRequestItem::insert($itemsToSave);
            }

            DB::commit();

            // === 5. Kembali ke halaman detail order ===
            return redirect()
                ->route('client.sales-orders.show', $order->order_id)
                ->with('success', 'Permintaan perubahan pesanan berhasil diajukan dan sedang menunggu diproses.');

        } catch (\Exception $e) {
            DB::rollBack();

            // Saat development: tampilkan pesan error detail
            return back()
                ->with('error', 'Gagal mengajukan permintaan: ' . $e->getMessage())
                ->withInput();

            // Untuk production, bisa disederhanakan:
            // return back()->with('error', 'Gagal mengajukan permintaan perubahan. Silakan coba lagi.')->withInput();
        }
    }
}
