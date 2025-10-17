<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientOrder;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ClientOrderController extends Controller
{
    public function create(): View
    {
        $products = Product::where('stock_quantity', '>', 0)->orderBy('product_name')->get();
        return view('client.orders.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_date' => 'required|date',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        $client = Auth::guard('client')->user();

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            $itemsToSave = [];

            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']);
                if (!$product) continue;

                $subtotal = $productData['quantity'] * $product->selling_price;
                $totalAmount += $subtotal;

                $itemsToSave[] = [
                    'product_id' => $product->product_id,
                    'quantity' => $productData['quantity'],
                    'price_per_unit' => $product->selling_price,
                    'subtotal' => $subtotal,
                ];
            }

            $clientOrder = $client->clientOrders()->create([
                'order_date' => $validated['order_date'],
                'notes' => $validated['notes'],
                'total_amount' => $totalAmount,
                'status' => 'pending_review',
            ]);

            $clientOrder->items()->createMany($itemsToSave);

            DB::commit();

            // Redirect to a future client order history page
            return redirect()->route('client.dashboard')->with('success', 'Permintaan pesanan Anda berhasil dikirim dan akan ditinjau oleh tim kami.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat pesanan: ' . $e->getMessage())->withInput();
        }
    }
}