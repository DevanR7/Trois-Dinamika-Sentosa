<?php

namespace App\Http\Controllers; // Sesuaikan namespace jika perlu

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth; // <-- Tambahkan ini

class ClientOrderReviewController extends Controller
{
    /**
     * Menampilkan daftar pesanan klien yang menunggu review.
     */
    public function index(Request $request): View
    {
        // $this->authorize('reviewClientOrders'); // Buat permission jika perlu

        $query = Order::with(['client'])
                    ->where('order_source', 'client')
                    ->where('status', 'pending_review') // Hanya tampilkan yang perlu direview
                    ->latest('order_date'); // Tampilkan yg terbaru

        // Tambahkan filter search (opsional)
        if ($request->filled('search')) {
            $search = $request->search;
             $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($subQ) use ($search) {
                        $subQ->where('client_name', 'like', "%{$search}%");
                    });
            });
        }

        $pendingClientOrders = $query->paginate(15)->appends($request->query());

        // View baru: admin.client_order_reviews.index
        return view('client_order_reviews.index', compact('pendingClientOrders'));
    }

    /**
     * Menampilkan detail pesanan klien untuk direview.
     */
    public function show(Order $order): View|RedirectResponse
    {
         // $this->authorize('reviewClientOrders');

        // Pastikan ini adalah order klien yang statusnya pending_review
        if ($order->order_source !== 'client' || $order->status !== 'pending_review') {
             return redirect()->route('client-order-reviews.index') // Sesuaikan nama route
                ->with('error', 'Pesanan ini tidak valid atau sudah diproses.');
        }

        $order->load(['client', 'items.product']); // Load relasi

         // View baru: admin.client_order_reviews.show
        return view('client_order_reviews.show', compact('order'));
    }

    /**
     * Menyetujui pesanan klien.
     */
    public function approve(Order $order): RedirectResponse
    {
        // $this->authorize('approveClientOrders');

         if ($order->order_source !== 'client' || $order->status !== 'pending_review') {
             return back()->with('error', 'Pesanan ini tidak dapat disetujui.');
        }

         try {
            DB::beginTransaction();

            // 1. Update status order (misal jadi 'approved' atau 'pending')
            //    Pilih 'pending' jika ingin masuk alur yg sama dgn Sales Order
            //    Pilih 'approved' jika pesanan klien dianggap langsung siap
            $order->status = 'approved'; // Atau 'pending'
            $order->save();

            // 2. Kurangi Stok Produk (JIKA BELUM dikurangi saat klien memesan)
            //    Jika Anda sudah mengurangi stok di ClientOrderController@store,
            //    maka bagian ini TIDAK PERLU dijalankan.
            // foreach ($order->items as $item) {
            //     $product = Product::find($item->product_id);
            //     if (!$product || $product->stock_quantity < $item->quantity) {
            //         throw new \Exception("Stok produk '{$product->product_name}' tidak mencukupi untuk disetujui.");
            //     }
            //     $product->decrement('stock_quantity', $item->quantity);
            // }

            DB::commit();

            return redirect()->route('client-order-reviews.index') // Sesuaikan nama route
                 ->with('success', "Pesanan {$order->order_number} berhasil disetujui.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyetujui pesanan: ' . $e->getMessage());
        }
    }

    /**
     * Menolak pesanan klien.
     */
    public function reject(Request $request, Order $order): RedirectResponse // Tambah Request
    {
        // $this->authorize('rejectClientOrders');

        if ($order->order_source !== 'client' || $order->status !== 'pending_review') {
             return back()->with('error', 'Pesanan ini tidak dapat ditolak.');
        }

        // Validasi input alasan penolakan (opsional)
        $validated = $request->validate(['rejection_notes' => 'nullable|string|max:500']);

         try {
            DB::beginTransaction();

            // 1. Update status order menjadi 'rejected'
            $order->status = 'rejected';
             // Simpan alasan penolakan di kolom 'notes' order (atau buat kolom baru jika perlu)
             if ($request->filled('rejection_notes')) {
                $order->notes = ($order->notes ? $order->notes . "\n\n" : '') . "Alasan Penolakan: " . $validated['rejection_notes'];
            }
            $order->save();

            // 2. Kembalikan Stok Produk (JIKA stok dikurangi saat klien memesan)
            //    Jika Anda TIDAK mengurangi stok di ClientOrderController@store,
            //    maka bagian ini TIDAK PERLU dijalankan.
            // foreach ($order->items as $item) {
            //     $product = Product::find($item->product_id);
            //     if ($product) {
            //         $product->increment('stock_quantity', $item->quantity);
            //     }
            // }

            DB::commit();

             return redirect()->route('client-order-reviews.index') // Sesuaikan nama route
                 ->with('success', "Pesanan {$order->order_number} berhasil ditolak.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menolak pesanan: ' . $e->getMessage());
        }
    }
}