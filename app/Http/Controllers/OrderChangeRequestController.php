<?php

namespace App\Http\Controllers; // Sesuaikan namespace jika perlu

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class OrderChangeRequestController extends Controller
{
    /**
     * Menampilkan daftar permintaan perubahan yang pending.
     */
    public function index(Request $request): View
    {
        // $this->authorize('viewAny', OrderChangeRequest::class);

        // --- Ambil Data untuk Filter Dropdown ---
        // 1. Ambil Bulan/Tahun unik dari created_at untuk filter tanggal
        $uniqueDates = OrderChangeRequest::select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"))
            ->where('status', 'pending') // Filter hanya yg pending
            ->distinct()
            ->orderBy('ym', 'desc')
            ->get()
            ->mapWithKeys(function ($item) {
                // Format YYYY-MM jadi "Nama Bulan YYYY"
                return [$item->ym => Carbon::createFromFormat('Y-m', $item->ym)->isoFormat('MMMM YYYY')];
            });

        // --- Terapkan Filter ---
        $query = OrderChangeRequest::with(['order', 'client'])
                    ->where('status', 'pending'); // Selalu filter yg pending

        // 1. Filter Search (No. Request, No. Order, Klien)
        if ($request->filled('search')) {
            $search = $request->search;
            // Pad request ID for search: REQ-00001 -> 1
            $requestId = ltrim(str_ireplace('REQ-', '', $search), '0');

            $query->where(function ($q) use ($search, $requestId) {
                if (is_numeric($requestId) && $requestId > 0) {
                     $q->where('request_id', $requestId); // Cari berdasarkan ID request
                }
                $q->orWhereHas('order', function ($subQ) use ($search) {
                        $subQ->where('order_number', 'like', "%{$search}%"); // Cari berdasarkan nomor order
                    })
                    ->orWhereHas('client', function ($subQ) use ($search) {
                        $subQ->where('client_name', 'like', "%{$search}%"); // Cari berdasarkan nama klien
                    });
            });
        }

        // 2. Filter Tanggal (Bulan & Tahun)
        if ($request->filled('date_filter')) {
            $yearMonth = $request->date_filter; // Format YYYY-MM
            try {
                $date = Carbon::createFromFormat('Y-m', $yearMonth);
                $query->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
            } catch (\Exception $e) {
                // Handle invalid date format if necessary
            }
        }

        // 3. Filter Tipe Request
        if ($request->filled('type_filter') && in_array($request->type_filter, ['cancel', 'modify'])) {
            $query->where('request_type', $request->type_filter);
        }

        // 4. Pengurutan
        $sort = $request->get('sort', 'terbaru'); // Default terbaru
        switch ($sort) {
            case 'terlama':
                $query->orderBy('created_at', 'asc');
                break;
            case 'terbaru':
            default: // Default ke terbaru
                $query->orderBy('created_at', 'desc');
                break;
        }

        // --- Ambil Data & Kirim ke View ---
        $changeRequests = $query->paginate(20)->appends($request->query()); // appends() agar filter terbawa di pagination

        return view('order_change_requests.index', compact(
            'changeRequests',
            'uniqueDates' // Kirim data tanggal unik ke view
        ));
    }

    /**
     * Menampilkan detail satu permintaan perubahan.
     * (Opsional tapi direkomendasikan)
     */
    public function show(OrderChangeRequest $changeRequest): View
    {
         // $this->authorize('view', $changeRequest); // Tambahkan policy jika perlu

        $changeRequest->load(['order.items.product', 'client', 'items.product']);

         // ❗️ Nama view 'order_change_requests.show' perlu dibuat
        return view('order_change_requests.show', compact('changeRequest'));
    }


    /**
     * Memproses permintaan perubahan (Approve/Reject).
     */
    public function process(Request $request, OrderChangeRequest $changeRequest): RedirectResponse
    {
        // $this->authorize('process', $changeRequest); // Tambahkan policy jika perlu

        // 1. Validasi Input Admin
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        // 2. Pastikan request masih pending
        if ($changeRequest->status !== 'pending') {
            return back()->with('error', 'Permintaan ini sudah diproses sebelumnya.');
        }

        try {
            DB::beginTransaction();

            $action = $validated['action'];
            $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
            $order = $changeRequest->order; // Ambil order asli

            // 3. Update status request
            $changeRequest->update([
                'status' => $newStatus,
                'admin_notes' => $validated['admin_notes'],
                'processed_by_user_id' => Auth::id(),
                'processed_at' => now(),
            ]);

            // 4. Jika disetujui, update order asli
            if ($newStatus === 'approved') {
                if ($changeRequest->request_type === 'cancel') {
                    // Batalkan order asli (misal: ubah status jadi 'rejected' atau 'cancelled')
                    $order->status = 'rejected'; // Sesuaikan status pembatalan Anda
                    $order->save();
                    // Optional: Kembalikan stok jika perlu
                    // foreach ($order->items as $item) {
                    //     $item->product->increment('stock_quantity', $item->quantity);
                    // }
                }
                elseif ($changeRequest->request_type === 'modify') {
                    // Proses modifikasi item order asli
                    $newTotalAmount = 0;
                    $order->load('items'); // Pastikan items sudah di-load
                    $currentItems = $order->items->keyBy('product_id'); // Map item saat ini berdasarkan product_id

                    foreach ($changeRequest->items as $reqItem) {
                        $productId = $reqItem->product_id;
                        $requestedQuantity = $reqItem->requested_quantity;
                        $price = $reqItem->price_per_unit; // Gunakan harga saat request
                        $subtotal = $reqItem->subtotal;

                        if ($reqItem->action === 'add') {
                            // Tambah item baru ke order
                            $order->items()->create([
                                'product_id' => $productId,
                                'quantity' => $requestedQuantity,
                                'price_per_unit' => $price,
                                'subtotal' => $subtotal,
                            ]);
                            $newTotalAmount += $subtotal;
                        }
                        elseif ($reqItem->action === 'remove') {
                            // Hapus item dari order
                            if (isset($currentItems[$productId])) {
                                $currentItems[$productId]->delete();
                            }
                        }
                        elseif ($reqItem->action === 'update_qty') {
                            // Update kuantitas item yang ada
                            if (isset($currentItems[$productId])) {
                                $currentItem = $currentItems[$productId];
                                $currentItem->update([
                                    'quantity' => $requestedQuantity,
                                    'price_per_unit' => $price, // Update harga jika perlu
                                    'subtotal' => $subtotal,
                                ]);
                                $newTotalAmount += $subtotal;
                            } else {
                                // Seharusnya tidak terjadi, tapi handle jika item asli tidak ada
                                $order->items()->create([
                                    'product_id' => $productId,
                                    'quantity' => $requestedQuantity,
                                    'price_per_unit' => $price,
                                    'subtotal' => $subtotal,
                                ]);
                                $newTotalAmount += $subtotal;
                            }
                        }
                    }
                    // Update total amount order asli
                    $order->total_amount = $newTotalAmount;
                    // Mungkin perlu update status order juga jika ada logika tertentu
                    $order->save();
                }
            }
            // Jika ditolak ('rejected'), tidak perlu mengubah order asli

            DB::commit();

            return redirect()->route('order-change-requests.index') // Arahkan ke index admin
                ->with('success', 'Permintaan perubahan pesanan berhasil di-' . $newStatus . '.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses permintaan: ' . $e->getMessage());
        }
    }
}