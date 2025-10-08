<?php

namespace App\Http\Controllers;

use App\Models\PurchaseReturn;
use App\Models\PurchaseOrder;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseReturnController extends Controller
{
    public function index(): View
    {
        $purchaseReturns = PurchaseReturn::with(['supplier', 'purchaseOrder'])
            ->latest('return_date')
            ->paginate(15);
            
        return view('purchase_returns.index', compact('purchaseReturns'));
    }

    public function create(): View
    {
        $purchaseOrders = PurchaseOrder::where('status', 'completed')
            ->orderBy('order_date', 'desc')
            ->get();
            
        return view('purchase_returns.create', compact('purchaseOrders'));
    }

    /**
     * Menyimpan data retur pembelian baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,po_id',
            'return_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,product_id',
            'items.*.quantity' => 'nullable|integer|min:1', // Boleh kosong
            'items.*.price_per_unit' => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($validated['purchase_order_id']);
            $totalReturnValue = 0;
            $hasReturnedItems = false;

            // 1. Buat data utama retur
            $purchaseReturn = PurchaseReturn::create([
                'return_number' => 'PR/' . date('Y/m/') . time(), // Ganti dengan generator nomor nanti
                'supplier_id' => $purchaseOrder->supplier_id,
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'return_date' => $validated['return_date'],
                'notes' => $validated['notes'],
                'total_amount' => 0, // Akan diupdate nanti
            ]);

            // 2. Loop melalui item yang diretur
            foreach ($validated['items'] as $itemData) {
                if (!empty($itemData['quantity']) && $itemData['quantity'] > 0) {
                    $hasReturnedItems = true;
                    $subtotal = $itemData['quantity'] * $itemData['price_per_unit'];
                    $totalReturnValue += $subtotal;

                    // Simpan item retur
                    $purchaseReturn->items()->create([
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'price_per_unit' => $itemData['price_per_unit'],
                        'subtotal' => $subtotal,
                    ]);

                    // Kurangi stok produk
                    $product = Product::find($itemData['product_id']);
                    if ($product) {
                        // Pastikan stok tidak menjadi minus
                        if ($product->stock_quantity < $itemData['quantity']) {
                             throw new \Exception("Stok untuk produk '{$product->product_name}' tidak mencukupi untuk diretur.");
                        }
                        $product->decrement('stock_quantity', $itemData['quantity']);
                    }
                }
            }

            if (!$hasReturnedItems) {
                throw new \Exception("Tidak ada item yang dipilih untuk diretur.");
            }
            
            // 3. Update total nilai retur
            $purchaseReturn->update(['total_amount' => $totalReturnValue]);
            
            DB::commit();

            return redirect()->route('purchase-returns.index')->with('success', 'Retur pembelian berhasil disimpan dan stok telah diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan retur: ' . $e->getMessage())->withInput();
        }
    }

    public function show(PurchaseReturn $purchaseReturn): View
{
    // Load semua relasi yang dibutuhkan oleh view
    $purchaseReturn->load(['supplier', 'purchaseOrder', 'user', 'items.product.unit']);
    
    return view('purchase_returns.show', compact('purchaseReturn'));
}

public function destroy(PurchaseReturn $purchaseReturn): RedirectResponse
{
    DB::beginTransaction();
    try {
        // 1. Loop melalui item retur untuk mengembalikan stok
        foreach ($purchaseReturn->items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                // Tambah kembali stok produk
                $product->increment('stock_quantity', $item->quantity);
            }
        }

        // 2. Hapus data retur
        $purchaseReturn->delete();

        DB::commit();

        return redirect()->route('purchase-returns.index')->with('success', 'Retur pembelian berhasil dibatalkan dan stok telah disesuaikan kembali.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal membatalkan retur: ' . $e->getMessage());
    }
}
}