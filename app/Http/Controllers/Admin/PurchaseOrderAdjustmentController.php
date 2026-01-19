<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderAdjustment;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;

use App\Services\AccountingService;
use App\Services\AccountingSettingService;
use App\Traits\ValidatesAccountingPeriod;

class PurchaseOrderAdjustmentController extends Controller
{
    use ValidatesAccountingPeriod;

    protected $accountingService;
    protected $accountingSettings;

    public function __construct(
        AccountingService $accountingService, 
        AccountingSettingService $accountingSettingService
    ) {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettingService;

        $this->middleware('can:edit-purchase-orders')->only(['create', 'createManual', 'storeManual', 'createAuto', 'storeAuto']);
        $this->middleware('can:delete-purchase-orders')->only(['destroy']);
    }

    public function create(Request $request): View
    {
        $preselectedPurchaseOrderId = $request->query('purchase_order_id');
        $purchaseOrders = PurchaseOrder::where('status', '!=', 'cancelled')
            ->with('supplier')
            ->orderBy('order_date', 'desc')
            ->get();
            
        return view('admin.purchase_order_adjustments.create', compact('purchaseOrders', 'preselectedPurchaseOrderId'));
    }

    public function createManual(PurchaseOrder $purchaseOrder): View|RedirectResponse
    {
        if ($purchaseOrder->status == 'cancelled') {
             return redirect()->route('admin.purchase-orders.show', $purchaseOrder->po_id)->with('error', 'PO yang sudah dibatalkan tidak dapat disesuaikan.');
        }
        return view('admin.purchase_order_adjustments.create_manual', compact('purchaseOrder'));
    }

    public function storeManual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,po_id',
            'adjustment_date' => 'required|date',
            'type' => 'required|in:credit_note,debit_note',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:1000',
            'overpayment_action' => 'required|string|in:deposit,refund',
        ]);

        if ($this->isDateClosed($validated['adjustment_date'])) {
            return back()->with('error', 'Gagal: Tanggal penyesuaian masuk periode tutup buku.');
        }
        
        $purchaseOrder = PurchaseOrder::findOrFail($validated['purchase_order_id']);
        if ($purchaseOrder->status == 'cancelled') {
            return back()->with('error', 'PO yang sudah dibatalkan tidak dapat disesuaikan.');
        }

        // Cek Akun Akuntansi
        $apAccountId = $this->accountingSettings->getAccountsPayableId();
        $purchaseReturnAccountId = $this->accountingSettings->getPurchaseReturnId();
        $inventoryAccountId = $this->accountingSettings->getInventoryId();
        $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();
        $gatewayAccountId = $this->accountingSettings->getGatewayAccountId();

        if (!$apAccountId || !$purchaseReturnAccountId || !$inventoryAccountId || !$supplierDepositAccountId) {
            return back()->with('error', 'Gagal: Akun default belum lengkap.')->withInput();
        }

        DB::beginTransaction();
        try {
            $adjustment = PurchaseOrderAdjustment::create([
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'adjustment_date' => $validated['adjustment_date'],
                'type' => $validated['type'],
                'amount' => (float) $validated['amount'],
                'reason' => $validated['reason'],
            ]);
            
            $journalGroupId = "PO-ADJ-" . $adjustment->adjustment_id;
            $description = "Penyesuaian Manual PO #" . $purchaseOrder->po_number;
            
            $debitEntries = [];
            $creditEntries = [];

            if ($validated['type'] === 'credit_note') {
                $debitEntries[] = [$apAccountId, $validated['amount'], "Potongan Hutang PO #" . $purchaseOrder->po_number];
                $creditEntries[] = [$purchaseReturnAccountId, $validated['amount'], $validated['reason']]; 
            } else {
                $debitEntries[] = [$inventoryAccountId, $validated['amount'], $validated['reason']]; 
                $creditEntries[] = [$apAccountId, $validated['amount'], "Tambahan Hutang PO #" . $purchaseOrder->po_number];
            }

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['adjustment_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $adjustment,
                Auth::id() 
            );

            $purchaseOrder->updatePaymentStatus();
            $this->handleOverpayment($purchaseOrder, $adjustment, 'dibuat', $validated['overpayment_action']);
            
            DB::commit();
            return redirect()->route('admin.purchase-orders.show', $purchaseOrder->po_id)
                             ->with('success', 'Penyesuaian manual berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan adj manual: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan penyesuaian: ' . $e->getMessage())->withInput();
        }
    }

    public function createAuto(PurchaseOrder $purchaseOrder): View|RedirectResponse
    {
        if ($purchaseOrder->status == 'cancelled') {
             return redirect()->route('admin.purchase-orders.show', $purchaseOrder->po_id)->with('error', 'PO yang sudah dibatalkan tidak dapat disesuaikan.');
        }
        
        $purchaseOrder->load('items.product', 'items.discounts', 'tax');
        
        $suppliers = Supplier::all(); 
        $products = Product::with('unit')->where('is_active', true)->orderBy('product_name')->get();
        $users = User::all();
        $taxes = Tax::where('is_active', true)->get();
        
        return view('admin.purchase_order_adjustments.create_auto', compact('purchaseOrder', 'suppliers', 'products', 'users', 'taxes'));
    }

    public function storeAuto(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if ($purchaseOrder->status == 'cancelled') {
            return back()->with('error', 'PO yang sudah dibatalkan tidak dapat disesuaikan.');
        }

        // 1. Validasi Input
        $validated = $request->validate([
            'order_date' => 'required|date',
            'due_date' => 'nullable|date',
            'requester_user_id' => 'nullable|exists:users,user_id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|numeric|min:0', // 0 = Hapus/Batal
            'products.*.price_per_unit' => 'required|numeric|min:0',
            'products.*.discounts' => 'nullable|array',
            'products.*.discounts.*' => 'nullable|numeric|min:0|max:100',
            'products.*.update_master_price' => 'nullable|boolean',
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
            'overpayment_action' => 'required|string|in:deposit,refund', 
        ]);

        if ($this->isDateClosed(now()) || $this->isDateClosed($validated['order_date'])) {
            return back()->with('error', 'Gagal: Tanggal transaksi masuk periode tutup buku.');
        }
        
        $apAccountId = $this->accountingSettings->getAccountsPayableId();
        $purchaseReturnAccountId = $this->accountingSettings->getPurchaseReturnId(); 
        $inventoryAccountId = $this->accountingSettings->getInventoryId();
        $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();
        $gatewayAccountId = $this->accountingSettings->getGatewayAccountId();

        if (!$apAccountId || !$purchaseReturnAccountId || !$inventoryAccountId || !$supplierDepositAccountId) {
            return back()->with('error', 'Gagal: Akun default akuntansi belum lengkap.')->withInput();
        }
        
        DB::beginTransaction();
        try {
            $purchaseOrder->load('items.product', 'items.discounts', 'tax');
            
            $oldItemsMap = $purchaseOrder->items->keyBy('product_id');

            // 1. REVERSAL STOK LAMA (Jika Barang Sudah Diterima)
            if ($purchaseOrder->status === 'completed') {
                foreach ($purchaseOrder->items as $oldItem) {
                    $product = Product::lockForUpdate()->find($oldItem->product_id);
                    if ($product) {
                        if ($product->stock_quantity < $oldItem->quantity) {
                            throw new \Exception("Gagal Koreksi: Stok produk lama '{$product->product_name}' tidak cukup untuk ditarik kembali.");
                        }
                        $product->decrement('stock_quantity', $oldItem->quantity);
                    }
                }
            }

            // 2. KALKULASI & SNAPSHOT
            $itemSubtotal = 0;
            $newItemsSnapshot = []; 
            $productsToUpdatePrice = [];

            foreach ($validated['products'] as $p) {
                $finalPrice = floatval($p['price_per_unit']);
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) $finalPrice *= (1 - (floatval($d) / 100));
                    }
                }
                
                $qty = floatval($p['quantity']);
                $subtotalRow = $qty * $finalPrice;
                $itemSubtotal += $subtotalRow;
                
                $productRef = Product::find($p['product_id']);
                
                // Ambil Data Lama
                $oldItemData = $oldItemsMap->get($p['product_id']);
                $oldQty = $oldItemData ? (float)$oldItemData->quantity : 0;
                $oldPrice = $oldItemData ? (float)$oldItemData->price_per_unit : 0;

                $newItemsSnapshot[] = [
                    'product_id'    => $p['product_id'],
                    'product_name'  => $productRef->product_name ?? 'Unknown',
                    'product_code'  => $productRef->product_code ?? '-',
                    'unit_name'     => $productRef->unit->name ?? 'Unit',
                    'quantity'      => $qty,
                    'price'         => $p['price_per_unit'],
                    'discounts'     => $p['discounts'] ?? [],
                    'net_price'     => $finalPrice,
                    'subtotal'      => $subtotalRow,
                    'old_quantity'  => $oldQty,
                    'old_price'     => $oldPrice,
                    'change_logs'   => $this->generateChangeLog($oldQty, $qty, $oldPrice, $p['price_per_unit']),
                ];

                if (!empty($p['update_master_price'])) {
                    $productsToUpdatePrice[$p['product_id']] = $p['price_per_unit'];
                }
            }
            
            // 3. KALKULASI HEADER PO BARU
            $options = $request->all();
            $options['subtotal'] = $itemSubtotal;
            $calc = \App\Services\PurchaseOrderCalculator::calculate($options);
            
            $newTotalAmount = $calc['grand_total'];
            $oldTotalAmount = $purchaseOrder->grand_total; 
            $diff = $oldTotalAmount - $newTotalAmount; 
            
            $adjustmentType = $diff > 0 ? 'credit_note' : 'debit_note';
            $adjustmentAmount = abs($diff);

            // Simpan Record Adjustment
            $adjustment = PurchaseOrderAdjustment::create([
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'adjustment_date' => now(), 
                'type' => $adjustmentType,
                'amount' => $adjustmentAmount,
                'reason' => $validated['notes'],
                'details' => ['items' => $newItemsSnapshot, 'calculation' => $calc, 'diff' => $diff]
            ]);
            
            // Update PO Header
            $purchaseOrder->update([
                'order_date' => $validated['order_date'],
                'due_date' => $validated['due_date'],
                'requester_user_id' => $validated['requester_user_id'],
                'subtotal' => $calc['subtotal'],
                'disc_fee_amount' => $calc['disc_fee_amount'],
                'rounding_discount_amount' => $calc['rounding_discount_amount'],
                'taxable_amount' => $calc['taxable_base'],
                'ppn' => $calc['ppn'],
                'shipping_amount' => $calc['shipping_amount'],
                'grand_total' => $calc['grand_total'], 
                'total_amount' => $calc['grand_total'], 
                'apply_disc_fee' => $request->boolean('apply_disc_fee'),
                'disc_fee_percent' => $request->input('disc_fee_percent'),
                'apply_rounding_discount' => $request->boolean('apply_rounding_discount'),
                'use_custom_dpp_factor' => $request->boolean('use_custom_dpp_factor'),
                'custom_dpp_factor' => $request->input('custom_dpp_factor'),
                'tax_id' => $request->input('tax_id'),
            ]);

            // 4. SYNC ITEMS
            $purchaseOrder->items()->each(function($item) { $item->discounts()->delete(); $item->delete(); });
            
            foreach ($validated['products'] as $p) {
                if (floatval($p['quantity']) <= 0) continue; 

                $item = $purchaseOrder->items()->create([
                    'product_id' => $p['product_id'],
                    'quantity' => $p['quantity'],
                    'price_per_unit' => $p['price_per_unit'],
                    'subtotal' => 0 
                ]);
                
                $finalPrice = floatval($p['price_per_unit']);
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) {
                            $item->discounts()->create(['percentage' => (float) $d]);
                            $finalPrice *= (1 - (floatval($d) / 100));
                        }
                    }
                }
                $item->update(['subtotal' => $p['quantity'] * $finalPrice]);

                // 5. MASUKKAN STOK BARU (Jika Completed)
                if ($purchaseOrder->status === 'completed') {
                    $product = Product::lockForUpdate()->find($p['product_id']);
                    if ($product) {
                        $qtyReceived = (float) $p['quantity'];
                        $pricePerUnit = $finalPrice; 

                        $oldStock = (float) $product->stock_quantity;
                        $oldAvgCost = (float) $product->average_cost;

                        $totalNewStock = $oldStock + $qtyReceived;
                        $newAvgCost = $oldAvgCost;

                        if ($totalNewStock > 0) {
                            if ($oldStock < 0) {
                                $newAvgCost = $pricePerUnit;
                            } else {
                                $totalOldValue = $oldStock * $oldAvgCost;
                                $totalNewValue = $qtyReceived * $pricePerUnit;
                                $newAvgCost = ($totalOldValue + $totalNewValue) / $totalNewStock;
                            }
                        } else {
                            $newAvgCost = $pricePerUnit;
                        }

                        $product->stock_quantity = $totalNewStock;
                        $product->average_cost = round($newAvgCost, 4);
                        $product->save();
                    }
                }
            }

            foreach ($productsToUpdatePrice as $prodId => $price) {
                Product::where('product_id', $prodId)->update(['purchase_price' => $price]);
            }

            // 6. Handle Overpayment (Deposit / Refund)
            if ($adjustmentType === 'credit_note' && $purchaseOrder->payment_status === 'paid') {
                if ($validated['overpayment_action'] === 'deposit') {
                    $ledger = SupplierLedger::create([
                        'supplier_id' => $purchaseOrder->supplier_id,
                        'reference_type' => PurchaseOrderAdjustment::class,
                        'reference_id' => $adjustment->adjustment_id,
                        'transaction_date' => now(),
                        'type' => 'credit',
                        'amount' => $adjustmentAmount,
                        'status' => 'available', 
                        'description' => 'Kelebihan Bayar (Koreksi PO Lunas) #' . $purchaseOrder->po_number,
                        'user_id' => Auth::id(),
                    ]);
                    $this->accountingService->postJournal(
                        "SUP-DEP-" . $ledger->ledger_id, now(),
                        "Pindah ke Deposit dari Koreksi PO Lunas #" . $purchaseOrder->po_number,
                        [[$supplierDepositAccountId, $adjustmentAmount, "Deposit Supplier"]],
                        [[$apAccountId, $adjustmentAmount, "Pindah dari AP"]], 
                        $ledger, Auth::id()
                    );
                } elseif ($validated['overpayment_action'] === 'refund') {
                    if (!$gatewayAccountId) throw new \Exception("Akun Gateway Default belum diatur.");
                    $this->accountingService->postJournal(
                        "PO-REFUND-" . $adjustment->adjustment_id, now(),
                        "Refund Tunai atas Koreksi PO Lunas #" . $purchaseOrder->po_number,
                        [[$gatewayAccountId, $adjustmentAmount, "Penerimaan Refund"]], 
                        [[$apAccountId, $adjustmentAmount, "Penyelesaian Hutang"]], 
                        $adjustment, Auth::id()
                    );
                }
            }

            // 7. Jurnal Adjustment
            if ($adjustmentAmount > 0.01) {
                $journalGroupId = "PO-ADJ-" . $adjustment->adjustment_id;
                $description = "Penyesuaian Otomatis PO #" . $purchaseOrder->po_number;
                
                $debitEntries = [];
                $creditEntries = [];

                if ($adjustmentType === 'credit_note') {
                    $debitEntries[] = [$apAccountId, $adjustmentAmount, "Potongan Hutang PO #" . $purchaseOrder->po_number];
                    $creditEntries[] = [$inventoryAccountId, $adjustmentAmount, "Koreksi Nilai Persediaan"]; 
                } else {
                    $debitEntries[] = [$inventoryAccountId, $adjustmentAmount, "Penambahan Nilai Persediaan"]; 
                    $creditEntries[] = [$apAccountId, $adjustmentAmount, "Tambahan Hutang PO #" . $purchaseOrder->po_number];
                }

                $this->accountingService->postJournal(
                    $journalGroupId, now(), $description, $debitEntries, $creditEntries, $adjustment, Auth::id()
                );
            }

            $purchaseOrder->updatePaymentStatus();
            DB::commit();
            
            return redirect()->route('admin.purchase-orders.show', $purchaseOrder->po_id)->with('success', 'Koreksi berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan koreksi PO otomatis: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan koreksi otomatis: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Hapus / Batalkan Adjustment
     */
    public function destroy(PurchaseOrderAdjustment $purchaseOrderAdjustment): RedirectResponse
    {
        $journalGroupId = "PO-ADJ-" . $purchaseOrderAdjustment->adjustment_id;
        
        if ($error = $this->checkTransactionLock($purchaseOrderAdjustment->adjustment_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus Penyesuaian: " . $error);
        }
        
        if ($purchaseOrderAdjustment->type === 'debit_note' && str_contains($purchaseOrderAdjustment->reason, 'System: Pindah overpayment')) {
            return back()->with('error', 'Gagal: Ini adalah penyesuaian sistem otomatis (Auto Debit Note). Silakan hapus Nota Kredit manual aslinya.');
        }
        
        DB::beginTransaction();
        try {
            $po = $purchaseOrderAdjustment->purchaseOrder;
            $amount = $purchaseOrderAdjustment->amount;
            $type = $purchaseOrderAdjustment->type;

            // 1. Reversal Jurnal Keuangan
            $this->reverseAndClearJournal($journalGroupId, "Reversal Adj #" . $purchaseOrderAdjustment->adjustment_id, $purchaseOrderAdjustment);
            $this->reverseAndClearJournal("PO-REFUND-" . $purchaseOrderAdjustment->adjustment_id, "Reversal Refund Adj #" . $purchaseOrderAdjustment->adjustment_id, $purchaseOrderAdjustment);

            // 2. Reversal Deposit
            $ledgerEntry = SupplierLedger::where('reference_type', PurchaseOrderAdjustment::class)
                ->where('reference_id', $purchaseOrderAdjustment->adjustment_id)->first();
            if ($ledgerEntry) {
                $this->reverseAndClearJournal("SUP-DEP-" . $ledgerEntry->ledger_id, "Reversal Deposit Adj", $ledgerEntry);
                $ledgerEntry->delete();
            }

            // 3. RESTORASI ITEM PO & STOK FISIK
            $itemsSnapshot = $purchaseOrderAdjustment->details['items'] ?? [];

            foreach ($itemsSnapshot as $snapItem) {
                $prodId = $snapItem['product_id'];
                $targetQty = (float)($snapItem['old_quantity'] ?? 0); 
                $adjustedQty = (float)($snapItem['quantity'] ?? 0); 
                $diffQty = $targetQty - $adjustedQty;
                
                if ($diffQty == 0) continue;

                // A. Update Stok Fisik (Jika PO Completed)
                if ($po->status === 'completed') {
                    $product = Product::withTrashed()->lockForUpdate()->find($prodId);
                    if ($product) {
                        if ($diffQty > 0) {
                            $product->increment('stock_quantity', $diffQty);
                        } else {
                            $product->decrement('stock_quantity', abs($diffQty));
                        }
                    }
                }

                // B. Update / Restore PO Item di Database
                $poItem = PurchaseOrderItem::where('po_id', $po->po_id)->where('product_id', $prodId)->first();

                if ($targetQty > 0) {
                    if ($poItem) {
                        $poItem->update([
                            'quantity' => $targetQty,
                            'price_per_unit' => $snapItem['old_price'] ?? $poItem->price_per_unit, 
                            'subtotal' => $targetQty * ($snapItem['old_price'] ?? $poItem->price_per_unit)
                        ]);
                    } else {
                        PurchaseOrderItem::create([
                            'po_id' => $po->po_id,
                            'product_id' => $prodId,
                            'quantity' => $targetQty,
                            'price_per_unit' => $snapItem['old_price'] ?? 0,
                            'subtotal' => $targetQty * ($snapItem['old_price'] ?? 0)
                        ]);
                    }
                } else {
                    if ($poItem) $poItem->delete();
                }
            }

            // 4. Kembalikan Nilai Header PO
            if ($type === 'debit_note') {
                $po->total_amount -= $amount;
                $po->grand_total -= $amount;
            } elseif ($type === 'credit_note') {
                $po->total_amount += $amount;
                $po->grand_total += $amount;
            }
            $po->save();

            // 5. Hapus Adjustment Record
            $purchaseOrderAdjustment->delete();
            
            // 6. Handle Overpayment Re-check
            if ($po) {
                $po->updatePaymentStatus();
                $this->handleOverpayment($po, null, 'dihapus', 'deposit'); 
            }
            
            DB::commit();
            return redirect()->route('admin.purchase-orders.show', $po->po_id)
                ->with('success', 'Penyesuaian PO dibatalkan. Stok barang dan nilai tagihan dikembalikan ke kondisi semula.');
                                  
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal batalkan adj PO: ' . $e->getMessage());
            return back()->with('error', 'Gagal membatalkan penyesuaian: ' . $e->getMessage());
        }
    }

    private function generateChangeLog($oldQty, $newQty, $oldPrice, $newPrice) {
        $changes = [];
        if ($newQty == 0 && $oldQty > 0) {
            $changes[] = "Dibatalkan/Dihapus (Qty: $oldQty -> 0)";
        } elseif ($newQty != $oldQty) {
            $changes[] = "Qty berubah: $oldQty -> $newQty";
        } elseif ($oldQty == 0 && $newQty > 0) {
            $changes[] = "Item Baru";
        }

        if ($newPrice != $oldPrice && $newQty > 0) {
            $changes[] = "Harga berubah: " . number_format($oldPrice) . " -> " . number_format($newPrice);
        }
        return $changes;
    }

    private function handleOverpayment(PurchaseOrder $purchaseOrder, ?PurchaseOrderAdjustment $originalAdjustment, string $context = 'dibuat', string $overpaymentAction = 'deposit')
    {
        $purchaseOrder->refresh();
        
        $totalPaid = $purchaseOrder->payments()->sum('amount');
        
        // Kewajiban Bersih (Grand Total - Retur)
        // Kita gunakan grand_total yang sudah diupdate sebagai sumber kebenaran.
        $netBill = $purchaseOrder->grand_total - $purchaseOrder->total_returned;
        
        $realRemainingBalance = $netBill - $totalPaid;
        
        // 1. Cek apakah sudah ada Auto Adjustment sebelumnya?
        $existingAutoAdj = $purchaseOrder->adjustments->first(function($adj) {
            return str_contains($adj->reason, 'System:') || str_contains($adj->reason, 'Otomatis:');
        });

        // SKENARIO A: Ada Overpayment (Saldo Minus)
        if ($realRemainingBalance < -0.01) { 
            
            $overpaymentAmount = abs($realRemainingBalance);
            
            // Hapus Auto Adj lama jika ada, lalu buat baru
            if ($existingAutoAdj) {
                 if (preg_match('/Ledger #(\d+)/', $existingAutoAdj->reason, $matches)) {
                    SupplierLedger::where('ledger_id', $matches[1])->delete();
                 }
                 DB::table('general_ledgers')->where('journal_group_id', "PO-ADJ-" . $existingAutoAdj->adjustment_id)->delete();
                 $existingAutoAdj->delete();
            }

            if ($overpaymentAction === 'refund' && $context === 'dibuat') return;
            if ($context === 'dihapus') $overpaymentAction = 'deposit'; 

            $supplier = $purchaseOrder->supplier; 
            if (!$supplier) return;

            $apAccountId = $this->accountingSettings->getAccountsPayableId();
            $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();
            
            if (!$apAccountId || !$supplierDepositAccountId) throw new \Exception("Akun AP atau Deposit Supplier belum diatur.");

            $transDate = now();
            $refType = PurchaseOrder::class; 
            $refId = $purchaseOrder->po_id;
            
            if ($originalAdjustment) {
                $refType = PurchaseOrderAdjustment::class;
                $refId = $originalAdjustment->adjustment_id;
                $transDate = $originalAdjustment->adjustment_date;
            }
            
            $ledgerEntry = SupplierLedger::create([
                'supplier_id' => $supplier->supplier_id,
                'purchase_order_id' => $purchaseOrder->po_id,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'transaction_date' => $transDate,
                'type' => 'credit', 
                'amount' => $overpaymentAmount,
                'status' => 'available',
                'description' => 'Otomatis: Kelebihan bayar PO #' . $purchaseOrder->po_number,
                'user_id' => Auth::id(),
            ]);

            $autoDebitNote = PurchaseOrderAdjustment::create([
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'adjustment_date' => now(),
                'type' => 'debit_note', 
                'amount' => $overpaymentAmount,
                'reason' => 'System: Pindah overpayment ke Deposit (Ledger #' . $ledgerEntry->ledger_id . ')',
            ]);
            
            $this->accountingService->postJournal(
                "PO-ADJ-" . $autoDebitNote->adjustment_id, now(),
                "System: Pindah overpayment PO #" . $purchaseOrder->po_number . " ke deposit",
                [[$supplierDepositAccountId, $overpaymentAmount]],
                [[$apAccountId, $overpaymentAmount]],
                $autoDebitNote, Auth::id() 
            );

            $purchaseOrder->updatePaymentStatus();
        } 
        
        // SKENARIO B: TIDAK ADA Overpayment (Sudah lunas atau Kurang Bayar)
        // Maka kita harus menghapus Auto Adjustment (Deposit System) yang mungkin tertinggal
        else {
            if ($existingAutoAdj) {
                 if (preg_match('/Ledger #(\d+)/', $existingAutoAdj->reason, $matches)) {
                    SupplierLedger::where('ledger_id', $matches[1])->delete();
                 }
                 $this->reverseAndClearJournal("PO-ADJ-" . $existingAutoAdj->adjustment_id, "System Cleanup", $existingAutoAdj);
                 $existingAutoAdj->delete();
            }
        }
    }

    private function reverseAndClearJournal(string $journalGroupId, string $reversalDescription, Model $referenceModel)
    {
        $originalJournalEntries = DB::table('general_ledgers')->where('journal_group_id', $journalGroupId)->get();
        if ($originalJournalEntries->isEmpty()) return;

        $debitEntries = [];
        $creditEntries = [];

        foreach ($originalJournalEntries as $entry) {
            if ($entry->debit > 0) $creditEntries[] = [$entry->chart_of_account_id, $entry->debit, "Reversal: " . $entry->description];
            if ($entry->credit > 0) $debitEntries[] = [$entry->chart_of_account_id, $entry->credit, "Reversal: " . $entry->description];
        }

        if (!empty($debitEntries) || !empty($creditEntries)) {
            $this->accountingService->postJournal(
                str_replace(['PO-ADJ-', 'PO-ADJ-AUTO-'], 'PO-ADJ-REV-', $journalGroupId), 
                now(), $reversalDescription, $debitEntries, $creditEntries, $referenceModel
            );
        }
        
        DB::table('general_ledgers')->where('journal_group_id', $journalGroupId)->delete();
    }
}