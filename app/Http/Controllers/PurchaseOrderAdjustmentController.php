<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Services\PurchaseOrderCalculator;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use App\Models\Supplier;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PurchaseOrderAdjustmentController extends Controller
{
    /**
     * Middleware untuk kontrol akses
     */
    public function __construct()
    {
        $this->middleware('permission:create-purchase-adjustments');
    }

    /**
     * Menampilkan halaman pilihan jenis penyesuaian
     */
    public function create(Request $request): View
    {
        $preselectedPurchaseOrderId = $request->query('purchase_order_id');
        
        $purchaseOrders = PurchaseOrder::where('status', '!=', 'cancelled')
            ->with('supplier')
            ->orderBy('order_date', 'desc')
            ->get();
            
        return view('purchase_order_adjustments.create', compact('purchaseOrders', 'preselectedPurchaseOrderId'));
    }

    // ======================================================
    // ALUR PENYESUAIAN MANUAL
    // ======================================================

    /**
     * Menampilkan form penyesuaian manual
     */
    public function createManual(PurchaseOrder $purchaseOrder): View|RedirectResponse
    {
        if ($purchaseOrder->status == 'cancelled') {
             return redirect()->route('purchase-orders.show', $purchaseOrder->po_id)->with('error', 'PO yang sudah dibatalkan tidak dapat disesuaikan.');
        }
        return view('purchase_order_adjustments.create_manual', compact('purchaseOrder'));
    }

    /**
     * Menyimpan penyesuaian manual
     */
    public function storeManual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,po_id',
            'adjustment_date' => 'required|date',
            'type' => 'required|in:credit_note,debit_note',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:1000',
        ]);

        $purchaseOrder = PurchaseOrder::findOrFail($validated['purchase_order_id']);
        if ($purchaseOrder->status == 'cancelled') {
            return back()->with('error', 'PO yang sudah dibatalkan tidak dapat disesuaikan.');
        }

        DB::beginTransaction();
        try {
            PurchaseOrderAdjustment::create([
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'adjustment_date' => $validated['adjustment_date'],
                'type' => $validated['type'],
                'amount' => (float) $validated['amount'],
                'reason' => $validated['reason'],
            ]);
            
            $purchaseOrder->updatePaymentStatus();

            DB::commit();

            return redirect()->route('purchase-orders.show', $purchaseOrder->po_id)
                         ->with('success', 'Penyesuaian (Nota ' . ($validated['type'] == 'credit_note' ? 'Kredit' : 'Debit') . ') berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan penyesuaian PO manual: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return back()->with('error', 'Gagal menyimpan penyesuaian: ' . $e->getMessage())->withInput();
        }
    }

    // ======================================================
    // ALUR PENYESUAIAN OTOMATIS (REVISI)
    // ======================================================

    /**
     * Menampilkan form revisi otomatis
     */
    public function createAuto(PurchaseOrder $purchaseOrder): View|RedirectResponse
    {
        if ($purchaseOrder->status == 'cancelled') {
             return redirect()->route('purchase-orders.show', $purchaseOrder->po_id)->with('error', 'PO yang sudah dibatalkan tidak dapat disesuaikan.');
        }
        
        $purchaseOrder->load('items.product', 'items.discounts', 'tax');
        
        $suppliers = Supplier::all(); 
        $products = Product::with('unit')->orderBy('product_name')->get();
        $users = User::all();
        $taxes = Tax::all();
        
        return view('purchase_order_adjustments.create_auto', compact('purchaseOrder', 'suppliers', 'products', 'users', 'taxes'));
    }

    /**
     * Menyimpan penyesuaian otomatis
     */
    public function storeAuto(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if ($purchaseOrder->status == 'cancelled') {
            return back()->with('error', 'PO yang sudah dibatalkan tidak dapat disesuaikan.');
        }
        
        $validated = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.price_per_unit' => 'required|numeric|min:0',
            'products.*.discounts' => 'nullable|array',
            'products.*.discounts.*' => 'nullable|numeric|min:0|max:100',
            'apply_disc_fee' => 'sometimes|boolean',
            'disc_fee_percent' => 'nullable|numeric|min:0|max:100',
            'disc_fee_amount' => 'nullable|numeric|min:0',
            'apply_rounding_discount' => 'sometimes|boolean',
            'rounding_discount_amount' => 'nullable|numeric|min:0',
            'use_custom_dpp_factor' => 'sometimes|boolean',
            'custom_dpp_factor' => 'nullable|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'shipping_amount' => 'nullable|numeric|min:0',
            'notes' => 'required|string|min:5|max:1000', 
        ]);
        
        DB::beginTransaction();
        try {
            $purchaseOrder->load('items.product', 'items.discounts', 'tax');

            $itemSubtotal = 0;
            foreach ($validated['products'] as $p) {
                $finalPrice = floatval($p['price_per_unit']);
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) $finalPrice *= (1 - (floatval($d) / 100));
                    }
                }
                $itemSubtotal += (floatval($p['quantity']) * $finalPrice);
            }
            
            $calculatorOptions = [
                'subtotal'                 => $itemSubtotal,
                'tax_id'                   => $request->input('tax_id'),
                'apply_disc_fee'           => $request->boolean('apply_disc_fee'),
                'disc_fee_percent'         => $request->input('disc_fee_percent'),
                'disc_fee_amount'          => $request->input('disc_fee_amount'),
                'apply_rounding_discount'  => $request->boolean('apply_rounding_discount'),
                'rounding_discount_amount' => $request->input('rounding_discount_amount'),
                'use_custom_dpp_factor'    => $request->boolean('use_custom_dpp_factor'),
                'custom_dpp_factor'        => $request->input('custom_dpp_factor'),
                'shipping_amount'          => $request->input('shipping_amount'),
            ];

            $calc = PurchaseOrderCalculator::calculate($calculatorOptions);
            $newTotalAmount = $calc['grand_total'];

            $oldTotalAmount = $purchaseOrder->total_amount;
            $diff = $oldTotalAmount - $newTotalAmount;

            $adjustmentType = null;
            $adjustmentAmount = 0;

            if ($diff > 0.01) {
                $adjustmentType = 'credit_note';
                $adjustmentAmount = $diff;
            } elseif ($diff < -0.01) {
                $adjustmentType = 'debit_note';
                $adjustmentAmount = abs($diff);
            } else {
                DB::rollBack();
                return redirect()->route('purchase-orders.show', $purchaseOrder->po_id)->with('info', 'Tidak ada perubahan nominal yang signifikan. Penyesuaian tidak dibuat.');
            }

            $nf = fn($val) => number_format($val, 0, ',', '.');

            $headerChanges = [];
            $itemModifiedChanges = [];
            $itemAddedLogs = [];
            $itemRemovedLogs = [];
            
            $oldTaxName = $purchaseOrder->tax->name ?? 'Tidak Ada';
            $newTax = $request->input('tax_id') ? Tax::find($request->input('tax_id')) : null;
            $newTaxName = $newTax->name ?? 'Tidak Ada';
            if ($oldTaxName != $newTaxName) {
                $headerChanges[] = "Pajak: '$oldTaxName' -> '$newTaxName'";
            }

            $oldShipping = (float) $purchaseOrder->shipping_amount;
            $newShipping = (float) $calc['shipping_amount'];
            if (abs($oldShipping - $newShipping) > 0.01) {
                $headerChanges[] = "Ongkos Kirim: {$nf($oldShipping)} -> {$nf($newShipping)}";
            }

            $oldDiscAmount = (float) $purchaseOrder->disc_fee_amount;
            $newDiscAmount = (float) $calc['disc_fee_amount'];
            if (abs($oldDiscAmount - $newDiscAmount) > 0.01) {
                $headerChanges[] = "Diskon/Fee (Header): {$nf($oldDiscAmount)} -> {$nf($newDiscAmount)}";
            }
            
            $oldItemsMap = $purchaseOrder->items->keyBy('product_id');
            $newItemsMap = collect($validated['products'])->keyBy('product_id');
            $allProductIds = $oldItemsMap->keys()->merge($newItemsMap->keys())->unique();
            $productNames = Product::whereIn('product_id', $allProductIds)->pluck('product_name', 'product_id');

            foreach ($newItemsMap as $newProductId => $newItemData) {
                $productName = Str::limit($productNames->get($newProductId) ?? "Produk ID $newProductId", 30);
                $newQty = (float) $newItemData['quantity'];
                $newPrice = (float) $newItemData['price_per_unit'];
                $newDiscountsRaw = $newItemData['discounts'] ?? [];
                $newDiscountsStr = empty($newDiscountsRaw) ? '0%' : implode('%, ', $newDiscountsRaw) . '%';

                if ($oldItemsMap->has($newProductId)) {
                    $oldItem = $oldItemsMap->get($newProductId);
                    $oldQty = (float) $oldItem->quantity;
                    $oldPrice = (float) $oldItem->price_per_unit;
                    $oldDiscountsRaw = $oldItem->discounts->pluck('percentage')->toArray();
                    $oldDiscountsStr = empty($oldDiscountsRaw) ? '0%' : implode('%, ', $oldDiscountsRaw) . '%';
                    
                    $itemLogParts = [];
                    if (abs($oldQty - $newQty) > 0.001) {
                        $itemLogParts[] = "Qty: $oldQty -> $newQty";
                    }
                    if (abs($oldPrice - $newPrice) > 0.01) {
                        $itemLogParts[] = "Harga: {$nf($oldPrice)} -> {$nf($newPrice)}";
                    }
                    if ($oldDiscountsStr != $newDiscountsStr) {
                        $itemLogParts[] = "Diskon: '$oldDiscountsStr' -> '$newDiscountsStr'";
                    }

                    if (!empty($itemLogParts)) {
                        $itemModifiedChanges[] = "[$productName] " . implode(', ', $itemLogParts);
                    }

                } else {
                    $itemAddedLogs[] = "[$productName] (Qty: $newQty, Harga: {$nf($newPrice)}, Diskon: '$newDiscountsStr')";
                }
            }
            
            foreach ($oldItemsMap as $oldProductId => $oldItem) {
                if (!$newItemsMap->has($oldProductId)) {
                    $productName = Str::limit($productNames->get($oldProductId) ?? "Produk ID $oldProductId", 30);
                    $itemRemovedLogs[] = "[$productName] (Qty Asli: {$oldItem->quantity}, Harga: {$nf($oldItem->price_per_unit)})";
                }
            }

            $reasonParts = [];
            $reasonParts[] = "Alasan Pengguna:";
            $reasonParts[] = $validated['notes'];
            $reasonParts[] = "\n======= DETAIL PERUBAHAN OTOMATIS =======";

            $hasChanges = false;
            if (!empty($headerChanges)) {
                $hasChanges = true;
                $reasonParts[] = "\n--- Perubahan Header ---";
                $reasonParts = array_merge($reasonParts, $headerChanges);
            }
            if (!empty($itemModifiedChanges)) {
                $hasChanges = true;
                $reasonParts[] = "\n--- Item Diubah ---";
                $reasonParts = array_merge($reasonParts, $itemModifiedChanges);
            }
            if (!empty($itemAddedLogs)) {
                $hasChanges = true;
                $reasonParts[] = "\n--- Item Ditambahkan ---";
                $reasonParts = array_merge($reasonParts, $itemAddedLogs);
            }
            if (!empty($itemRemovedLogs)) {
                $hasChanges = true;
                $reasonParts[] = "\n--- Item Dihapus ---";
                $reasonParts = array_merge($reasonParts, $itemRemovedLogs);
            }

            if (!$hasChanges) {
                $reasonParts[] = "(Tidak ada perubahan data, hanya kalkulasi ulang.)";
            }
            
            $reasonParts[] = "=======================================";
            $finalReason = implode("\n", $reasonParts);

            PurchaseOrderAdjustment::create([
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'adjustment_date' => now(),
                'type' => $adjustmentType,
                'amount' => $adjustmentAmount,
                'reason' => $finalReason,
            ]);
            
            $purchaseOrder->updatePaymentStatus();
            
            DB::commit();
            
            return redirect()->route('purchase-orders.show', $purchaseOrder->po_id)
                             ->with('success', 'Koreksi otomatis berhasil. Nota ' . ($adjustmentType == 'credit_note' ? 'Kredit' : 'Debit') . ' senilai Rp ' . $nf($adjustmentAmount) . ' telah dibuat.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan koreksi PO otomatis: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return back()->with('error', 'Gagal menyimpan koreksi otomatis: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Membatalkan penyesuaian
     */
    public function destroy(PurchaseOrderAdjustment $purchaseOrderAdjustment): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $po_id = $purchaseOrderAdjustment->purchase_order_id;
            $po = PurchaseOrder::find($po_id);

            $purchaseOrderAdjustment->delete();
            
            if ($po) {
                $po->updatePaymentStatus();
            }
            
            DB::commit();

            return redirect()->route('purchase-orders.show', $po_id)
                         ->with('success', 'Penyesuaian PO berhasil dibatalkan.');
                         
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal membatalkan penyesuaian PO: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return back()->with('error', 'Gagal membatalkan penyesuaian: ' . $e->getMessage());
        }
    }
}