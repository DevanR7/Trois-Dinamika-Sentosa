<?php

namespace App\Http\Controllers;

use App\Models\PurchaseReturn;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\Tax;
use App\Models\SupplierLedger;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseReturnController extends Controller
{
    public function index(Request $request): View
    {
        // [AUTH] Panggil policy untuk memeriksa permission 'view-purchase-returns'
        $this->authorize('viewAny', PurchaseReturn::class);

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

        // [AUTH] Panggil policy untuk memeriksa permission 'create-purchase-returns'
        $this->authorize('create', PurchaseReturn::class);

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
        // [AUTH] Panggil policy
        $this->authorize('create', PurchaseReturn::class);

        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,po_id',
            'return_date' => 'required|date',
            'return_handling_type' => 'required|in:deduct_invoice,store_as_deposit', // ✅ Validasi input baru
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

            $purchaseReturn = PurchaseReturn::create([
                'return_number' => PurchaseReturn::generateReturnNumber(),
                'supplier_id' => $purchaseOrder->supplier_id,
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'return_date' => $validated['return_date'],
                'return_handling_type' => $validated['return_handling_type'],
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

                    // (Logika perhitungan harga retur pembelian Anda tetap sama)
                    $netPricePerUnit = $originalItem->price_per_unit;
                    foreach ($originalItem->discounts as $discount) {
                        $netPricePerUnit *= (1 - ($discount->percentage / 100));
                    }
                    
                    $totalValuePerUnit = 0;
                    if ($purchaseOrder->tax) {
                        // ... (Logika pajak Anda)
                        $dppFactor = $purchaseOrder->custom_dpp_factor ?? (1 / 1.11); // Sesuaikan jika tarif PPN beda
                        $taxRate = $purchaseOrder->tax->rate ?? 11; // Sesuaikan
                        
                        $dpp_per_unit = round($netPricePerUnit * $dppFactor);
                        $ppn_per_unit = round($dpp_per_unit * ($taxRate / 100));
                        $totalValuePerUnit = $netPricePerUnit + $ppn_per_unit; // Ini mungkin perlu dicek ulang, apakah retur barang include PPN? Asumsi: Ya.
                    } else {
                        $totalValuePerUnit = $netPricePerUnit;
                    }

                    $subtotal = $itemData['quantity'] * $totalValuePerUnit;
                    $totalReturnValue += $subtotal;

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
            
            // ======================================================
            // ✅ LOGIKA BARU BERDASARKAN AKSI (DIUBAH)
            // ======================================================
            if ($validated['return_handling_type'] == 'store_as_deposit') {
                // Opsi 2: Tampung sebagai Deposit Supplier via LEDGER
                
                // HAPUS INI: $purchaseOrder->supplier->increment('debit_balance', $totalReturnValue);
                
                // ✅ TAMBAHKAN INI: Buat entri ledger
                SupplierLedger::create([
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'reference_type' => PurchaseReturn::class,
                    'reference_id' => $purchaseReturn->return_id,
                    'transaction_date' => $validated['return_date'],
                    'type' => 'credit',
                    'amount' => $totalReturnValue, // Jumlah positif (kredit)
                    'description' => 'Deposit dari Retur Pembelian #' . $purchaseReturn->return_number,
                    'user_id' => Auth::id(),
                ]);

            } else {
                // Opsi 1: Potong Tagihan (Logika lama Anda)
                // Update total retur di PO
                $totalReturDipotong = $purchaseOrder->returns()
                                          ->where('return_handling_type', 'deduct_invoice')
                                          ->sum('total_amount');
                                          
                $purchaseOrder->update(['total_returned' => $totalReturDipotong]);
                
                // Refresh data PO setelah update
                $purchaseOrder->refresh(); 

                // Update status pembayaran PO
                $sisaUtang = $purchaseOrder->total_amount - $purchaseOrder->total_returned - $purchaseOrder->amount_paid;
                if ($sisaUtang <= 0.01) {
                    $purchaseOrder->payment_status = 'paid';
                } elseif ($purchaseOrder->amount_paid > 0) {
                    $purchaseOrder->payment_status = 'partially_paid';
                } else {
                    $purchaseOrder->payment_status = 'unpaid';
                }
                $purchaseOrder->save();
            }
            // ======================================================
            
            DB::commit();
            return redirect()->route('purchase-returns.index')->with('success', 'Retur pembelian berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan retur: ' . $e->getMessage())->withInput();
        }
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        // [AUTH] Panggil policy untuk memeriksa permission 'view-purchase-returns'
        $this->authorize('view', $purchaseReturn);

        $purchaseReturn->load(['supplier', 'purchaseOrder', 'user', 'items.product.unit']);
        return view('purchase_returns.show', compact('purchaseReturn'));
    }

    public function destroy(PurchaseReturn $purchaseReturn)
    {
        // [AUTH] Panggil policy
        $this->authorize('delete', $purchaseReturn);
        
        DB::beginTransaction();
        try {
            $purchaseOrder = $purchaseReturn->purchaseOrder;
            $isDeductInvoice = $purchaseReturn->return_handling_type == 'deduct_invoice';
            
            if ($purchaseReturn->return_handling_type == 'store_as_deposit') {
                // Tipe "Simpan Deposit"
                
                // HAPUS LOGIKA INI:
                // $supplier = $purchaseReturn->supplier;
                // if ($supplier) {
                //     $newBalance = max(0, $supplier->debit_balance - $purchaseReturn->total_amount);
                //     $supplier->update(['debit_balance' => $newBalance]);
                // }

                // ✅ TAMBAHKAN INI: Hapus entri ledger yang terkait
                SupplierLedger::where('reference_type', PurchaseReturn::class)
                            ->where('reference_id', $purchaseReturn->return_id)
                            ->delete();
                            
            } else {
                // Tipe "Potong Tagihan"
                if ($purchaseOrder && $purchaseOrder->payment_status === 'paid') {
                     // Cek sisa tagihan
                     $sisaUtang = $purchaseOrder->total_amount - $purchaseOrder->total_returned - $purchaseOrder->amount_paid;
                     if ($sisaUtang <= 0.01) { // Jika lunas pas
                         throw new \Exception('Retur "Potong Tagihan" tidak bisa dibatalkan jika PO aslinya sudah lunas.');
                     }
                }
            }

            // Kembalikan stok dan kuantitas
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

            $purchaseReturn->delete(); // Hapus retur

            // Update PO HANYA JIKA tipenya 'deduct_invoice'
            if ($purchaseOrder && $isDeductInvoice) {
                // Hitung ulang total retur yang HANYA bertipe 'deduct_invoice'
                $totalReturDipotong = $purchaseOrder->returns()
                                          ->where('return_handling_type', 'deduct_invoice')
                                          ->sum('total_amount');
                
                $purchaseOrder->update(['total_returned' => $totalReturDipotong]);
                
                // Hitung ulang status pembayaran
                $sisaUtang = $purchaseOrder->total_amount - $totalReturDipotong - $purchaseOrder->amount_paid;
                if ($sisaUtang <= 0.01) {
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