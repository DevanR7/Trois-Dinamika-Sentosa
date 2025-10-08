<?php

namespace App\Http\Controllers;

use App\Models\PurchaseReturn;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseReturnController extends Controller
{
    public function index(Request $request): View
    {
        $query = PurchaseReturn::with(['supplier', 'purchaseOrder']);

        // Logika untuk Pencarian Umum
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function($q_supplier) use ($search) {
                      $q_supplier->where('supplier_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('purchaseOrder', function($q_po) use ($search) {
                      $q_po->where('po_number', 'like', "%{$search}%");
                  });
            });
        }

        // Logika untuk Filter Tanggal
        if ($request->filled('return_date')) {
            $query->whereDate('return_date', $request->return_date);
        }

        $purchaseReturns = $query->latest('return_date')->paginate(15)->appends($request->query());
            
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
     * Menyimpan data retur pembelian baru dengan logika perhitungan yang akurat.
     */
     public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,po_id',
            'return_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:purchase_order_items,item_id',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Load PO asli beserta relasi pajaknya
            $purchaseOrder = PurchaseOrder::with('tax')->findOrFail($validated['purchase_order_id']);
            $totalReturnValue = 0;
            $hasReturnedItems = false;

            $purchaseReturn = PurchaseReturn::create([
                'return_number' => PurchaseReturn::generateReturnNumber(),
                'supplier_id' => $purchaseOrder->supplier_id,
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'return_date' => $validated['return_date'],
                'notes' => $validated['notes'],
                'total_amount' => 0,
            ]);

            foreach ($validated['items'] as $itemData) {
                if (!empty($itemData['quantity']) && $itemData['quantity'] > 0) {
                    $hasReturnedItems = true;
                    $originalItem = PurchaseOrderItem::with('discounts')->find($itemData['item_id']);
                    
                    $maxQty = $originalItem->quantity - $originalItem->quantity_returned;
                    if ($itemData['quantity'] > $maxQty) {
                        throw new \Exception("Jumlah retur melebihi batas untuk " . $originalItem->product->product_name);
                    }

                    // 1. Hitung harga beli bersih (Harga Nett) per unit setelah semua diskon berlapis
                    $netPricePerUnit = $originalItem->price_per_unit;
                    foreach ($originalItem->discounts as $discount) {
                        $netPricePerUnit *= (1 - ($discount->percentage / 100));
                    }
                    
                    // 2. Tentukan nilai total per unit berdasarkan kondisi PO asli
                    $totalValuePerUnit = 0;
                    if ($purchaseOrder->tax) {
                        // JIKA PO ASLI PUNYA PAJAK, hitung dengan PPN
                        $dppFactor = $purchaseOrder->custom_dpp_factor ?? (11 / 12);
                        $taxRate = $purchaseOrder->tax->rate ?? 12;
                        
                        $dpp_per_unit = round($netPricePerUnit * $dppFactor);
                        $ppn_per_unit = round($dpp_per_unit * ($taxRate / 100));
                        
                        $totalValuePerUnit = $netPricePerUnit + $ppn_per_unit;
                    } else {
                        // JIKA PO ASLI TIDAK PUNYA PAJAK, nilai retur adalah harga bersih saja
                        $totalValuePerUnit = $netPricePerUnit;
                    }

                    // 3. Hitung subtotal untuk jumlah barang yang diretur
                    $subtotal = $itemData['quantity'] * $totalValuePerUnit;
                    $totalReturnValue += $subtotal;

                    // 4. Simpan ke database dengan nilai yang akurat
                    $purchaseReturn->items()->create([
                        'product_id' => $originalItem->product_id,
                        'quantity' => $itemData['quantity'],
                        'price_per_unit' => $totalValuePerUnit,
                        'subtotal' => $subtotal,
                    ]);
                    
                    $originalItem->increment('quantity_returned', $itemData['quantity']);
                    Product::find($originalItem->product_id)->decrement('stock_quantity', $itemData['quantity']);
                }
            }

            if (!$hasReturnedItems) throw new \Exception("Tidak ada item yang dipilih untuk diretur.");
            
            $purchaseReturn->update(['total_amount' => $totalReturnValue]);
            $purchaseOrder->update(['total_returned' => $purchaseOrder->returns()->sum('total_amount')]);

            $sisaUtang = $purchaseOrder->total_amount - $purchaseOrder->total_returned - $purchaseOrder->amount_paid;
        if ($sisaUtang <= 0) {
            $purchaseOrder->payment_status = 'paid';
        } elseif ($purchaseOrder->amount_paid > 0) {
            $purchaseOrder->payment_status = 'partially_paid';
        } else {
            $purchaseOrder->payment_status = 'unpaid';
        }
        $purchaseOrder->save();
            
            DB::commit();
            return redirect()->route('purchase-returns.index')->with('success', 'Retur pembelian berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan retur: ' . $e->getMessage())->withInput();
        }
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['supplier', 'purchaseOrder', 'user', 'items.product.unit']);
        return view('purchase_returns.show', compact('purchaseReturn'));
    }

    public function destroy(PurchaseReturn $purchaseReturn)
    {
        DB::beginTransaction();
        try {
            foreach ($purchaseReturn->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                }
                $originalItem = PurchaseOrderItem::where('po_id', $purchaseReturn->purchase_order_id)
                                                   ->where('product_id', $item->product_id)
                                                   ->first();
                if ($originalItem) {
                    $originalItem->decrement('quantity_returned', $item->quantity);
                }
            }

            $purchaseOrder = PurchaseOrder::find($purchaseReturn->purchase_order_id);
            $purchaseReturn->delete();

            if ($purchaseOrder) {
                $purchaseOrder->update(['total_returned' => $purchaseOrder->returns()->sum('total_amount')]);
                
                $sisaUtang = $purchaseOrder->total_amount - $purchaseOrder->total_returned - $purchaseOrder->amount_paid;
            if ($sisaUtang <= 0) {
                $purchaseOrder->payment_status = 'paid';
            } elseif ($purchaseOrder->amount_paid > 0) {
                $purchaseOrder->payment_status = 'partially_paid';
            } else {
                $purchaseOrder->payment_status = 'unpaid';
            }
            $purchaseOrder->save();
            }
            
            DB::commit();
            return redirect()->route('purchase-returns.index')->with('success', 'Retur pembelian berhasil dibatalkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan retur: ' . $e->getMessage());
        }
    }
}