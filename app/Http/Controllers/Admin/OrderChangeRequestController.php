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

            if ($newStatus === 'approved') {
                if ($changeRequest->request_type === 'cancel') {
                    $order->update(['status' => 'rejected']);
                }

                elseif ($changeRequest->request_type === 'modify') {
                    $order->load('items');
                    $currentItems = $order->items->keyBy('product_id');
                    $newTotalAmount = 0;

                    foreach ($changeRequest->items as $reqItem) {
                        $productId = $reqItem->product_id;
                        $requestedQty = $reqItem->requested_quantity;
                        $price = $reqItem->price_per_unit;
                        $subtotal = $requestedQty * $price;

                        if ($reqItem->action === 'add') {
                            $order->items()->create([
                                'product_id' => $productId,
                                'quantity' => $requestedQty,
                                'price_per_unit' => $price,
                                'subtotal' => $subtotal,
                            ]);
                            $newTotalAmount += $subtotal;
                        }

                        elseif ($reqItem->action === 'remove') {
                            if (isset($currentItems[$productId])) {
                                $currentItems[$productId]->delete();
                            }
                        }

                        elseif ($reqItem->action === 'update_qty') {
                            if (isset($currentItems[$productId])) {
                                $currentItems[$productId]->update([
                                    'quantity' => $requestedQty,
                                    'price_per_unit' => $price,
                                    'subtotal' => $subtotal,
                                ]);
                            } else {
                                $order->items()->create([
                                    'product_id' => $productId,
                                    'quantity' => $requestedQty,
                                    'price_per_unit' => $price,
                                    'subtotal' => $subtotal,
                                ]);
                            }
                            $newTotalAmount += $subtotal;
                        }
                    }

                    $order->update(['total_amount' => $newTotalAmount]);
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
