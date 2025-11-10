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
use App\Models\SupplierLedger; 
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
            'overpayment_action' => 'required|string|in:deposit,refund', // VALIDASI BARU
        ]);

        $purchaseOrder = PurchaseOrder::findOrFail($validated['purchase_order_id']);
        if ($purchaseOrder->status == 'cancelled') {
            return back()->with('error', 'PO yang sudah dibatalkan tidak dapat disesuaikan.');
        }

        DB::beginTransaction();
        try {
            // 1. Buat penyesuaian dan tangkap hasilnya
            $adjustment = PurchaseOrderAdjustment::create([
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'adjustment_date' => $validated['adjustment_date'],
                'type' => $validated['type'],
                'amount' => (float) $validated['amount'],
                'reason' => $validated['reason'],
            ]);
            
            // 2. Update status PO untuk menghitung ulang saldo
            $purchaseOrder->updatePaymentStatus();
            
            // 3. Ambil pilihan user dan tangani overpayment
            $overpaymentAction = $validated['overpayment_action'];
            $this->handleOverpayment($purchaseOrder, $adjustment, 'dibuat', $overpaymentAction); 

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
            'overpayment_action' => 'required|string|in:deposit,refund', // VALIDASI BARU
        ]);
        
        DB::beginTransaction();
        try {
            $purchaseOrder->load('items.product', 'items.discounts', 'tax');

            // Kalkulasi item subtotal
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
            
            // Kalkulasi dengan PurchaseOrderCalculator
            $calculatorOptions = [
                'subtotal'                  => $itemSubtotal,
                'tax_id'                    => $request->input('tax_id'),
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

            // Kalkulasi selisih dan tentukan jenis penyesuaian
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

            // Buat log perubahan
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

            // 1. Buat penyesuaian dan tangkap hasilnya
            $adjustment = PurchaseOrderAdjustment::create([
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'adjustment_date' => now(),
                'type' => $adjustmentType,
                'amount' => $adjustmentAmount,
                'reason' => $finalReason,
            ]);
            
            // 2. Update status PO untuk menghitung ulang saldo
            $purchaseOrder->updatePaymentStatus();
            
            // 3. Ambil pilihan user dan tangani overpayment
            $overpaymentAction = $validated['overpayment_action'];
            $this->handleOverpayment($purchaseOrder, $adjustment, 'dibuat', $overpaymentAction);
            
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
     * ======================================================
     * FUNGSI 'DESTROY' YANG DIPERBARUI
     * ======================================================
     */
    public function destroy(PurchaseOrderAdjustment $purchaseOrderAdjustment): RedirectResponse
    {
        // --- PENCEGAHAN ---
        // Cek apakah ini 'debit_note' otomatis yang dibuat oleh sistem overpayment.
        // Jika ya, jangan biarkan dihapus langsung.
        if ($purchaseOrderAdjustment->type === 'debit_note' && str_contains($purchaseOrderAdjustment->reason, 'Otomatis: Memindahkan kelebihan bayar')) {
            return back()->with('error', 'Gagal: Ini adalah Nota Debit otomatis. Untuk membatalkan, hapus Nota Kredit asli yang memicu pemindahan deposit ini.');
        }

        DB::beginTransaction();
        try {
            $po_id = $purchaseOrderAdjustment->purchase_order_id;
            $po = PurchaseOrder::find($po_id);

            // --- LOGIKA REVERSAL ---
            
            // Cek apakah penyesuaian ini adalah 'credit_note' yang memicu overpayment
            if ($purchaseOrderAdjustment->type === 'credit_note') {
                
                // 1. Cari ledger entry (deposit) yang TEPAT merujuk ke ID adjustment ini
                $ledgerEntry = SupplierLedger::where('reference_type', PurchaseOrderAdjustment::class)
                                            ->where('reference_id', $purchaseOrderAdjustment->adjustment_id)
                                            ->first();

                if ($ledgerEntry) {
                    // Penyesuaian ini memicu overpayment. Kita harus membatalkan semua.
                    
                    // 2. Cari 'debit_note' otomatis yang dibuat BERSAMAAN DENGAN ledger ini
                    // Kita pakai 'like' untuk mencari berdasarkan ID Ledger yang unik
                    $autoDebitNote = PurchaseOrderAdjustment::where('purchase_order_id', $po_id)
                        ->where('type', 'debit_note')
                        ->where('reason', 'like', '%Ledger ID: ' . $ledgerEntry->ledger_id . '%')
                        ->first();

                    // 3. Hapus 'debit_note' otomatis (jika ada)
                    if ($autoDebitNote) {
                        $autoDebitNote->delete();
                    }

                    // 4. Hapus ledger entry (deposit)
                    $ledgerEntry->delete();
                }
            }
            // --- LOGIKA REVERSAL SELESAI ---

            // 5. Hapus penyesuaian yang diminta user (ini adalah $purchaseOrderAdjustment)
            $purchaseOrderAdjustment->delete();
            
            // 6. Update status PO (wajib untuk kalkulasi ulang)
            if ($po) {
                $po->updatePaymentStatus();
                
                // 7. Tangani overpayment dengan default ke 'deposit' saat penghapusan
                $this->handleOverpayment($po, null, 'dihapus', 'deposit');
            }
            
            DB::commit();

            return redirect()->route('purchase-orders.show', $po_id)
                             ->with('success', 'Penyesuaian PO berhasil dibatalkan. Status utang dan deposit diperbarui.');
                          
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal membatalkan penyesuaian PO: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return back()->with('error', 'Gagal membatalkan penyesuaian: ' . $e->getMessage());
        }
    }

    /**
     * ======================================================
     * FUNGSI 'handleOverpayment' YANG DIPERBARUI
     * ======================================================
     * @param PurchaseOrder $purchaseOrder PO yang dicek
     * @param PurchaseOrderAdjustment|null $originalAdjustment Penyesuaian asli (jika ada)
     * @param string $context Konteks ('dibuat' atau 'dihapus') untuk log
     * @param string $overpaymentAction Pilihan user ('deposit' atau 'refund')
     */
    private function handleOverpayment(PurchaseOrder $purchaseOrder, ?PurchaseOrderAdjustment $originalAdjustment, string $context = 'dibuat', string $overpaymentAction = 'deposit')
    {
        // Panggil refresh() untuk mendapatkan data saldo terbaru
        $purchaseOrder->refresh();
        $remainingBalance = $purchaseOrder->remaining_balance ?? 0;

        // Jika sisa saldo negatif (artinya ada kelebihan bayar)
        if ($remainingBalance < -0.01) {
            
            // ==========================================================
            // LOGIKA KONDISIONAL BARU - HANDLE REFUND
            // ==========================================================
            if ($overpaymentAction === 'refund') {
                // Pilihan B: Proses Refund Manual
                // Kita tidak melakukan apa-apa. Biarkan saldo PO negatif.
                $adjustmentId = $originalAdjustment ? $originalAdjustment->adjustment_id : 'N/A';
                Log::info("Kelebihan bayar terdeteksi di PO #{$purchaseOrder->po_id} (dari Adj. ID: {$adjustmentId}). Dibiarkan untuk proses refund manual.");
                return; // Hentikan fungsi
            }
            // ==========================================================

            // Pilihan A: Simpan sebagai Deposit (Lanjutkan logika lama)
            $overpaymentAmount = abs($remainingBalance);
            $supplier = $purchaseOrder->supplier; 

            if (!$supplier) {
                Log::warning("Gagal memindahkan kelebihan bayar PO #{$purchaseOrder->po_id}: Supplier tidak ditemukan.");
                return;
            }

            // --- Tentukan data referensi & log berdasarkan konteks ---
            $transDate = now()->format('Y-m-d');
            $descContext = "(saat penyesuaian $context)";

            // Tentukan referensi berdasarkan apakah ada originalAdjustment
            if ($originalAdjustment) {
                // Jika dipicu oleh 'create', referensinya adalah adjustment itu sendiri
                $refType = PurchaseOrderAdjustment::class;
                $refId = $originalAdjustment->adjustment_id;
                $transDate = $originalAdjustment->adjustment_date;
                $descContext = "(dari Adj. ID: {$originalAdjustment->adjustment_id})";
            } else {
                // Jika dipicu oleh 'delete', referensinya adalah PO itu sendiri
                $refType = PurchaseOrder::class;
                $refId = $purchaseOrder->po_id;
                $descContext = "(saat penyesuaian $context)";
            }

            try {
                // 1. Buat entri deposit (kredit) di Supplier Ledger
                $ledgerEntry = SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'purchase_order_id' => $purchaseOrder->po_id,
                    'reference_type' => $refType,
                    'reference_id' => $refId,
                    'transaction_date' => $transDate,
                    'type' => 'credit',
                    'amount' => $overpaymentAmount,
                    'status' => 'available',
                    'description' => 'Otomatis: Kelebihan bayar dari PO #' . $purchaseOrder->po_number . ' ' . $descContext,
                    'user_id' => Auth::id(),
                ]);

                // 2. Buat penyesuaian "lawan" (debit note) untuk menolkan saldo PO
                PurchaseOrderAdjustment::create([
                    'purchase_order_id' => $purchaseOrder->po_id,
                    'user_id' => Auth::id(),
                    'adjustment_date' => now(),
                    'type' => 'debit_note',
                    'amount' => $overpaymentAmount,
                    'reason' => 'Otomatis: Memindahkan kelebihan bayar (Rp ' . number_format($overpaymentAmount) . ') ke deposit supplier (Ledger ID: ' . $ledgerEntry->ledger_id . ')',
                ]);

                // 3. Update status PO terakhir kali untuk menolkan saldo
                $purchaseOrder->updatePaymentStatus();

            } catch (\Exception $e) {
                Log::error('Gagal memproses overpayment adjustment: ' . $e->getMessage());
                // Biarkan transaksi utama di-rollback oleh pemanggil
                throw $e; 
            }
        }
    }
}