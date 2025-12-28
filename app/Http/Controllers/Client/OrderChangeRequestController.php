<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
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
    public function create(Order $order): View|RedirectResponse
    {
        if ($order->client_id !== Auth::guard('client')->id()) {
            abort(403, 'Akses Ditolak');
        }
        if (!in_array($order->status, ['pending', 'approved'])) {
            return redirect()
                ->route('client.sales-orders.show', $order->order_id)
                ->with('error', 'Pesanan dengan status ' . $order->status . ' tidak dapat diajukan perubahan.');
        }
        if ($order->changeRequests()->where('status', 'pending')->exists()) {
            return redirect()
                ->route('client.sales-orders.show', $order->order_id)
                ->with('warning', 'Sudah ada permintaan perubahan yang sedang diproses.');
        }

        $order->load('items.product');
        $products = Product::orderBy('product_name')->get();

        return view('client.sales_orders.request_change', compact('order', 'products'));
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        if ($order->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }
        if (!in_array($order->status, ['pending', 'approved'])) {
            return back()->with('error', 'Pesanan ini sudah tidak dapat diubah.');
        }
        if ($order->changeRequests()->where('status', 'pending')->exists()) {
            return back()->with('error', 'Sudah ada permintaan perubahan yang sedang diproses.');
        }

        $validated = $request->validate([
            'request_type' => ['required', Rule::in(['cancel', 'modify'])],
            'client_notes' => 'nullable|string|max:1000',
            'items' => 'required_if:request_type,modify|array|min:1',
            'items.*.product_id' => 'required_if:request_type,modify|exists:products,product_id',
            'items.*.quantity' => 'required_if:request_type,modify|numeric|min:0',
            'items.*.action' => ['nullable', Rule::in(['add', 'remove', 'update_qty'])], 
            'items.*.original_quantity' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            $changeRequest = $order->changeRequests()->create([
                'client_id'     => $order->client_id,
                'request_type'  => $validated['request_type'],
                'client_notes'  => $validated['client_notes'],
                'status'        => 'pending',
            ]);

            if ($validated['request_type'] === 'modify' && isset($validated['items'])) {
                $itemsToSave = [];

                foreach ($validated['items'] as $itemData) {
                    if (empty($itemData['action'])) {
                        continue; 
                    }

                    $product = Product::find($itemData['product_id']);
                    $price = $product->selling_price ?? 0;
                    $qty = $itemData['quantity'];
                    $subtotal = $qty * $price;

                    $itemsToSave[] = [
                        'order_change_request_id' => $changeRequest->request_id,
                        'product_id'              => $itemData['product_id'],
                        'original_quantity'       => $itemData['original_quantity'] ?? null,
                        'requested_quantity'      => $qty,
                        'action'                  => $itemData['action'],
                        'price_per_unit'          => $price,
                        'subtotal'                => $subtotal,
                        'created_at'              => now(),
                        'updated_at'              => now(),
                    ];
                }

                if (empty($itemsToSave) && $validated['request_type'] === 'modify') {
                    throw new \Exception("Tidak ada perubahan yang mendeteksi pada item pesanan.");
                }

                OrderChangeRequestItem::insert($itemsToSave);
            }

            DB::commit();

            return redirect()
                ->route('client.sales-orders.show', $order->order_id)
                ->with('success', 'Permintaan perubahan berhasil diajukan. Menunggu konfirmasi admin.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengajukan permintaan: ' . $e->getMessage())->withInput();
        }
    }
}