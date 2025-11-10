<?php

namespace App\Http\Controllers;

use App\Models\{
    PurchaseReturn,
    PurchaseOrder,
    PurchaseOrderItem,
    Product,
    Tax,
    SupplierLedger
};
use Illuminate\Http\{
    Request,
    RedirectResponse
};
use Illuminate\View\View;
use Illuminate\Support\Facades\{
    DB,
    Auth
};

class PurchaseReturnController extends Controller
{
    /**
     * Tampilkan daftar retur pembelian.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseReturn::class);

        $query = PurchaseReturn::with(['supplier', 'purchaseOrder']);

        // Pencarian umum berdasarkan nomor retur, nama supplier, atau nomor PO
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', fn($q) => $q->where('supplier_name', 'like', "%{$search}%"))
                  ->orWhereHas('purchaseOrder', fn($q) => $q->where('po_number', 'like', "%{$search}%"));
            });
        }

        // Filter berdasarkan tanggal retur
        if ($request->filled('return_date')) {
            $query->whereDate('return_date', $request->return_date);
        }

        $purchaseReturns = $query->latest('return_date')
                                 ->paginate(15)
                                 ->appends($request->query());

        return view('purchase_returns.index', compact('purchaseReturns'));
    }

    /**
     * Tampilkan form untuk membuat retur pembelian baru.
     */
    public function create(): View
    {
        $this->authorize('create', PurchaseReturn::class);

        $purchaseOrders = PurchaseOrder::where('status', 'completed')
            ->orderBy('order_date', 'desc')
            ->get();

        return view('purchase_returns.create', compact('purchaseOrders'));
    }

    /**
     * Simpan data retur pembelian baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PurchaseReturn::class);

        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,po_id',
            'return_date' => 'required|date',
            'return_handling_type' => 'required|in:deduct_invoice,store_as_deposit',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:purchase_order_items,item_id',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $purchaseOrder = PurchaseOrder::with(['tax', 'supplier'])->findOrFail($validated['purchase_order_id']);
            $totalReturnValue = 0;
            $hasReturnedItems = false;
            $handlingType = $validated['return_handling_type'];

            /**
             * LANGKAH 1: Hitung nilai retur berdasarkan item yang dikembalikan.
             */
            foreach ($validated['items'] as $itemData) {
                if (empty($itemData['quantity']) || $itemData['quantity'] <= 0) {
                    continue;
                }

                $hasReturnedItems = true;
                $originalItem = PurchaseOrderItem::with('discounts')->find($itemData['item_id']);

                $maxQty = $originalItem->quantity - $originalItem->quantity_returned;
                if ($itemData['quantity'] > $maxQty) {
                    throw new \Exception("Jumlah retur melebihi batas untuk {$originalItem->product->product_name}");
                }

                // Hitung harga per unit setelah diskon
                $netPricePerUnit = $originalItem->price_per_unit;
                foreach ($originalItem->discounts as $discount) {
                    $netPricePerUnit *= (1 - ($discount->percentage / 100));
                }

                // Hitung nilai total per unit termasuk pajak
                if ($purchaseOrder->tax) {
                    $dppFactor = $purchaseOrder->custom_dpp_factor ?? (1 / 1.11);
                    $taxRate = $purchaseOrder->tax->rate ?? 11;

                    $dpp = round($netPricePerUnit * $dppFactor);
                    $ppn = round($dpp * ($taxRate / 100));
                    $totalValuePerUnit = $netPricePerUnit + $ppn;
                } else {
                    $totalValuePerUnit = $netPricePerUnit;
                }

                $totalReturnValue += $itemData['quantity'] * $totalValuePerUnit;
            }

            if (!$hasReturnedItems) {
                throw new \Exception("Tidak ada item yang dipilih untuk diretur.");
            }

            /**
             * LANGKAH 2: Terapkan aturan bisnis retur.
             */
            $sisaTagihanPO = $purchaseOrder->total_amount - $purchaseOrder->total_returned - $purchaseOrder->amount_paid;

            if ($handlingType === 'deduct_invoice' && $totalReturnValue > $sisaTagihanPO) {
                $handlingType = 'store_as_deposit';
            }

            /**
             * LANGKAH 3: Simpan retur pembelian utama.
             */
            $purchaseReturn = PurchaseReturn::create([
                'return_number' => PurchaseReturn::generateReturnNumber(),
                'supplier_id' => $purchaseOrder->supplier_id,
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'return_date' => $validated['return_date'],
                'return_handling_type' => $handlingType,
                'notes' => $validated['notes'],
                'total_amount' => $totalReturnValue,
            ]);

            /**
             * LANGKAH 4: Simpan detail item retur, update stok dan jumlah dikembalikan.
             */
            foreach ($validated['items'] as $itemData) {
                if (empty($itemData['quantity']) || $itemData['quantity'] <= 0) {
                    continue;
                }

                $originalItem = PurchaseOrderItem::with('discounts')->find($itemData['item_id']);
                $netPricePerUnit = $originalItem->price_per_unit;

                foreach ($originalItem->discounts as $discount) {
                    $netPricePerUnit *= (1 - ($discount->percentage / 100));
                }

                if ($purchaseOrder->tax) {
                    $dppFactor = $purchaseOrder->custom_dpp_factor ?? (1 / 1.11);
                    $taxRate = $purchaseOrder->tax->rate ?? 11;
                    $dpp = round($netPricePerUnit * $dppFactor);
                    $ppn = round($dpp * ($taxRate / 100));
                    $totalValuePerUnit = $netPricePerUnit + $ppn;
                } else {
                    $totalValuePerUnit = $netPricePerUnit;
                }

                $subtotal = $itemData['quantity'] * $totalValuePerUnit;

                $purchaseReturn->items()->create([
                    'product_id' => $originalItem->product_id,
                    'quantity' => $itemData['quantity'],
                    'price_per_unit' => $totalValuePerUnit,
                    'subtotal' => $subtotal,
                ]);

                $originalItem->increment('quantity_returned', $itemData['quantity']);
                Product::find($originalItem->product_id)->decrement('stock_quantity', $itemData['quantity']);
            }

            /**
             * LANGKAH 5: Proses hasil retur berdasarkan tipe penanganan.
             */
            if ($handlingType === 'store_as_deposit') {
                $ledgerStatus = ($purchaseOrder->payment_status === 'paid') ? 'available' : 'pending';
                $description = "Deposit dari Retur Pembelian #{$purchaseReturn->return_number}";
                if ($ledgerStatus === 'pending') {
                    $description .= ' (Ditahan)';
                }

                SupplierLedger::create([
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'purchase_order_id' => $purchaseOrder->po_id,
                    'reference_type' => PurchaseReturn::class,
                    'reference_id' => $purchaseReturn->return_id,
                    'transaction_date' => $validated['return_date'],
                    'type' => 'credit',
                    'amount' => $totalReturnValue,
                    'status' => $ledgerStatus,
                    'description' => $description,
                    'user_id' => Auth::id(),
                ]);
            } else {
                $totalReturDipotong = $purchaseOrder->returns()
                    ->where('return_handling_type', 'deduct_invoice')
                    ->sum('total_amount');

                $purchaseOrder->update(['total_returned' => $totalReturDipotong]);

                $purchaseOrder->refresh();

                $sisaUtang = $purchaseOrder->total_amount - $purchaseOrder->total_returned - $purchaseOrder->amount_paid;

                $purchaseOrder->payment_status =
                    $sisaUtang <= 0.01 ? 'paid' :
                    ($purchaseOrder->amount_paid > 0 ? 'partially_paid' : 'unpaid');

                $purchaseOrder->save();
            }

            DB::commit();
            return redirect()->route('purchase-returns.index')->with('success', 'Retur pembelian berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan retur: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Tampilkan detail retur pembelian.
     */
    public function show(PurchaseReturn $purchaseReturn): View
    {
        $this->authorize('view', $purchaseReturn);

        $purchaseReturn->load(['supplier', 'purchaseOrder', 'user', 'items.product.unit']);

        return view('purchase_returns.show', compact('purchaseReturn'));
    }

    /**
     * Hapus atau batalkan retur pembelian.
     */
    public function destroy(PurchaseReturn $purchaseReturn): RedirectResponse
    {
        $this->authorize('delete', $purchaseReturn);

        DB::beginTransaction();

        try {
            $purchaseOrder = $purchaseReturn->purchaseOrder;
            $isDeductInvoice = $purchaseReturn->return_handling_type === 'deduct_invoice';

            // Hapus entri ledger jika retur berupa deposit
            if ($purchaseReturn->return_handling_type === 'store_as_deposit') {
                SupplierLedger::where('reference_type', PurchaseReturn::class)
                    ->where('reference_id', $purchaseReturn->return_id)
                    ->delete();
            } else {
                // Cegah pembatalan jika PO sudah lunas
                if ($purchaseOrder && $purchaseOrder->payment_status === 'paid') {
                    $sisaUtang = $purchaseOrder->total_amount - $purchaseOrder->total_returned - $purchaseOrder->amount_paid;
                    if ($sisaUtang <= 0.01) {
                        throw new \Exception('Retur "Potong Tagihan" tidak bisa dibatalkan jika PO sudah lunas.');
                    }
                }
            }

            // Kembalikan stok produk dan jumlah dikembalikan
            foreach ($purchaseReturn->items as $item) {
                if ($product = Product::find($item->product_id)) {
                    $product->increment('stock_quantity', $item->quantity);
                }

                $originalItem = PurchaseOrderItem::where('po_id', $purchaseReturn->purchase_order_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($originalItem) {
                    $originalItem->decrement('quantity_returned', $item->quantity);
                }
            }

            $purchaseReturn->delete();

            if ($purchaseOrder && $isDeductInvoice) {
                $totalReturDipotong = $purchaseOrder->returns()
                    ->where('return_handling_type', 'deduct_invoice')
                    ->sum('total_amount');

                $purchaseOrder->update(['total_returned' => $totalReturDipotong]);

                $sisaUtang = $purchaseOrder->total_amount - $totalReturDipotong - $purchaseOrder->amount_paid;

                $purchaseOrder->payment_status =
                    $sisaUtang <= 0.01 ? 'paid' :
                    ($purchaseOrder->amount_paid > 0 ? 'partially_paid' : 'unpaid');

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
