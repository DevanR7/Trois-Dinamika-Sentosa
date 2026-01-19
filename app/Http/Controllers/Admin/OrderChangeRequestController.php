<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

    public function __construct()
    {
        $this->middleware('can:review-order-change-requests');
    }

    public function index(Request $request): View
    {
        $uniqueDates = OrderChangeRequest::select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"))
            ->where('status', 'pending')
            ->distinct()
            ->orderBy('ym', 'desc')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->ym => Carbon::createFromFormat('Y-m', $item->ym)->isoFormat('MMMM YYYY')];
            });

        $query = OrderChangeRequest::with(['order', 'client'])
            ->where('status', 'pending');

        if ($request->filled('search')) {
            $search = $request->search;
            $requestId = ltrim(str_ireplace('REQ-', '', $search), '0');

            $query->where(function ($q) use ($search, $requestId) {
                if (is_numeric($requestId) && $requestId > 0) {
                    $q->where('request_id', $requestId);
                }
                $q->orWhereHas('order', function ($subQ) use ($search) {
                    $subQ->where('order_number', 'like', "%{$search}%");
                })
                ->orWhereHas('client', function ($subQ) use ($search) {
                    $subQ->where('client_name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('date_filter')) {
            try {
                $date = Carbon::createFromFormat('Y-m', $request->date_filter);
                $query->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
            } catch (\Exception $e) {
            }
        }

        if ($request->filled('type_filter') && in_array($request->type_filter, ['cancel', 'modify'])) {
            $query->where('request_type', $request->type_filter);
        }

        $sort = $request->get('sort', 'terbaru');
        $query->orderBy('created_at', $sort === 'terlama' ? 'asc' : 'desc');
        $changeRequests = $query->paginate(20)->appends($request->query());

        return view('admin.order_change_requests.index', compact('changeRequests', 'uniqueDates'));
    }

    public function show(OrderChangeRequest $changeRequest): View
    {
        $changeRequest->load(['order.items.product', 'client', 'items.product']);
        return view('admin.order_change_requests.show', compact('changeRequest'));
    }

public function process(Request $request, OrderChangeRequest $changeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($changeRequest->status !== 'pending') {
            return back()->with('error', 'Permintaan ini sudah diproses sebelumnya.');
        }

        try {
            DB::beginTransaction();

            $action = $validated['action'];
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            $order = $changeRequest->order;

            $changeRequest->update([
                'status' => $newStatus,
                'admin_notes' => $validated['admin_notes'] ?? null,
                'processed_by_user_id' => Auth::id(),
                'processed_at' => now(),
            ]);

            // HANYA JIKA ADMIN MENYETUJUI PERUBAHAN
            if ($newStatus === 'approved') {
                
                // KASUS 1: PEMBATALAN PESANAN
                if ($changeRequest->request_type === 'cancel') {
                    $order->update(['status' => 'rejected']);
                    
                    // [REVISI]: Kembalikan stok semua item
                    foreach ($order->items as $item) {
                         $product = \App\Models\Product::lockForUpdate()->find($item->product_id);
                         if ($product) {
                             $product->increment('stock_quantity', $item->quantity);
                         }
                    }
                }
                
                // KASUS 2: MODIFIKASI ITEM
                elseif ($changeRequest->request_type === 'modify') {
                    $order->load('items');
                    $currentItems = $order->items->keyBy('product_id');
                    $newTotalAmount = 0;

                    foreach ($changeRequest->items as $reqItem) {
                        $productId = $reqItem->product_id;
                        $requestedQty = $reqItem->requested_quantity;
                        $price = $reqItem->price_per_unit;
                        $subtotal = $requestedQty * $price;
                        
                        // Kunci Produk untuk update stok
                        $product = \App\Models\Product::lockForUpdate()->find($productId);

                        if ($reqItem->action === 'add') {
                            // Cek stok dulu
                            if (!$product || $product->stock_quantity < $requestedQty) {
                                throw new \Exception("Gagal: Stok produk '{$product->product_name}' tidak cukup untuk penambahan ini.");
                            }
                            
                            $order->items()->create([
                                'product_id' => $productId,
                                'quantity' => $requestedQty,
                                'price_per_unit' => $price,
                                'subtotal' => $subtotal,
                            ]);
                            
                            // [REVISI]: Kurangi stok
                            $product->decrement('stock_quantity', $requestedQty);
                            
                            $newTotalAmount += $subtotal;
                        }
                        elseif ($reqItem->action === 'remove') {
                            if (isset($currentItems[$productId])) {
                                $qtyToRestore = $currentItems[$productId]->quantity;
                                $currentItems[$productId]->delete();
                                
                                // [REVISI]: Kembalikan stok
                                if ($product) {
                                    $product->increment('stock_quantity', $qtyToRestore);
                                }
                            }
                        }
                        elseif ($reqItem->action === 'update_qty') {
                            if (isset($currentItems[$productId])) {
                                $oldQty = $currentItems[$productId]->quantity;
                                $diff = $requestedQty - $oldQty; // Positif = nambah, Negatif = kurang
                                
                                // Validasi stok jika nambah
                                if ($diff > 0) {
                                    if (!$product || $product->stock_quantity < $diff) {
                                        throw new \Exception("Gagal: Stok produk '{$product->product_name}' tidak cukup untuk penambahan qty.");
                                    }
                                    $product->decrement('stock_quantity', $diff);
                                } elseif ($diff < 0) {
                                    // Jika berkurang, kembalikan ke gudang
                                    $product->increment('stock_quantity', abs($diff));
                                }

                                $currentItems[$productId]->update([
                                    'quantity' => $requestedQty,
                                    'price_per_unit' => $price,
                                    'subtotal' => $subtotal,
                                ]);
                            } else {
                                // Fallback jika item ternyata belum ada (dianggap add)
                                if (!$product || $product->stock_quantity < $requestedQty) {
                                     throw new \Exception("Stok tidak cukup.");
                                }
                                $order->items()->create([
                                    'product_id' => $productId,
                                    'quantity' => $requestedQty,
                                    'price_per_unit' => $price,
                                    'subtotal' => $subtotal,
                                ]);
                                $product->decrement('stock_quantity', $requestedQty);
                            }
                            $newTotalAmount += $subtotal;
                        }
                    }
                    
                    // Recalculate total amount order (untuk item yg tidak berubah juga harus dihitung)
                    // Ambil fresh items setelah perubahan
                    $finalTotal = $order->items()->sum('subtotal');
                    $order->update(['total_amount' => $finalTotal]);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.order-change-requests.index')
                ->with('success', 'Permintaan perubahan pesanan berhasil di-' . $newStatus . '.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses permintaan: ' . $e->getMessage());
        }
    }
}
