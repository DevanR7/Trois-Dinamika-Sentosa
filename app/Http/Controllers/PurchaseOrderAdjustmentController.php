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
use Illuminate\Database\Eloquent\Model; // <-- Pastikan ini ada

// ✅ TAMBAHKAN IMPORT BARU
use App\Services\AccountingService;
use App\Services\AccountingSettingService;

class PurchaseOrderAdjustmentController extends Controller
{
    /**
     * ✅ (BARU) Inject Service Akuntansi
     */
    protected $accountingService;
    protected $accountingSettings;

    /**
     * Middleware untuk kontrol akses
     */
    public function __construct(
        AccountingService $accountingService, 
        AccountingSettingService $accountingSettingService
    ) {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettingService;
        
        // $this->middleware('permission:create-purchase-adjustments');
        // (Anda bisa aktifkan middleware Anda di sini)
    }

    /**
     * Menampilkan halaman pilihan jenis penyesuaian
     * (Tidak ada perubahan)
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
     * (Tidak ada perubahan)
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
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi.
     */
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
        
        $purchaseOrder = PurchaseOrder::findOrFail($validated['purchase_order_id']);
        if ($purchaseOrder->status == 'cancelled') {
            return back()->with('error', 'PO yang sudah dibatalkan tidak dapat disesuaikan.');
        }

        // ✅ Validasi Akun Akuntansi
        $apAccountId = $this->accountingSettings->getAccountsPayableId();
        $purchaseReturnAccountId = $this->accountingSettings->getPurchaseReturnId(); // Asumsi ke Akun Persediaan
        $inventoryAccountId = $this->accountingSettings->getInventoryId();

        if (!$apAccountId || !$purchaseReturnAccountId || !$inventoryAccountId) {
            return back()->with('error', 'Gagal: Akun default (Hutang, Retur Pembelian/Persediaan) belum diatur.')->withInput();
        }

        DB::beginTransaction();
        try {
            // 1. Buat penyesuaian
            $adjustment = PurchaseOrderAdjustment::create([
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'adjustment_date' => $validated['adjustment_date'],
                'type' => $validated['type'],
                'amount' => (float) $validated['amount'],
                'reason' => $validated['reason'],
            ]);
            
            // 2. ✅ Post Jurnal Akuntansi
            $journalGroupId = "PO-ADJ-" . $adjustment->adjustment_id;
            $description = "Penyesuaian Manual PO #" . $purchaseOrder->po_number;
            
            $debitEntries = [];
            $creditEntries = [];

            if ($validated['type'] === 'credit_note') {
                // Nota Kredit Supplier: (D) Hutang Dagang, (K) Persediaan (Potongan)
                $debitEntries[] = [$apAccountId, $validated['amount'], "Potongan Hutang PO #" . $purchaseOrder->po_number];
                $creditEntries[] = [$purchaseReturnAccountId, $validated['amount'], $validated['reason']]; // Dikredit ke Akun Retur/Persediaan
            } else {
                // Nota Debit Supplier: (D) Persediaan (Tambahan biaya), (K) Hutang Dagang
                $debitEntries[] = [$inventoryAccountId, $validated['amount'], $validated['reason']]; // Asumsi tambahan biaya dibebankan ke Persediaan
                $creditEntries[] = [$apAccountId, $validated['amount'], "Tambahan Hutang PO #" . $purchaseOrder->po_number];
            }

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['adjustment_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $adjustment // Model referensi
            );

            // 3. Update status PO
            $purchaseOrder->updatePaymentStatus();
            
            // 4. Tangani overpayment
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
     * (Tidak ada perubahan)
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
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi.
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
            'overpayment_action' => 'required|string|in:deposit,refund',
        ]);
        
        // ✅ Validasi Akun Akuntansi
        $apAccountId = $this->accountingSettings->getAccountsPayableId();
        $purchaseReturnAccountId = $this->accountingSettings->getPurchaseReturnId(); // Asumsi ke Akun Persediaan
        $inventoryAccountId = $this->accountingSettings->getInventoryId();

        if (!$apAccountId || !$purchaseReturnAccountId || !$inventoryAccountId) {
            return back()->with('error', 'Gagal: Akun default (Hutang, Retur Pembelian/Persediaan) belum diatur.')->withInput();
        }
        
        DB::beginTransaction();
        try {
            $purchaseOrder->load('items.product', 'items.discounts', 'tax');
            
            // (Logika kalkulasi Anda untuk $newTotalAmount, $oldTotalAmount, $diff)
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
            
            $calculatorOptions = $request->all();
            $calculatorOptions['subtotal'] = $itemSubtotal;

            $calc = PurchaseOrderCalculator::calculate($calculatorOptions);
            $newTotalAmount = $calc['grand_total'];
            $oldTotalAmount = $purchaseOrder->total_amount;
            $diff = $oldTotalAmount - $newTotalAmount;
            
            if (abs($diff) <= 0.01) {
                DB::rollBack();
                return redirect()->route('purchase-orders.show', $purchaseOrder->po_id)->with('info', 'Tidak ada perubahan nominal. Penyesuaian tidak dibuat.');
            }
            $adjustmentType = $diff > 0 ? 'credit_note' : 'debit_note';
            $adjustmentAmount = abs($diff);

            // (Logika 'reasonDetails' Anda)
            $nf = fn($val) => number_format($val, 0, ',', '.');
            $headerChanges = [];
            $itemModifiedChanges = [];
            $itemAddedLogs = [];
            $itemRemovedLogs = [];
            // ... (sisa logika reasonDetails Anda) ...
            $reasonParts = [];
            $reasonParts[] = "Alasan Pengguna:";
            $reasonParts[] = $validated['notes'];
            // ... (sisa logika penggabungan reasonParts Anda) ...
            $finalReason = implode("\n", $reasonParts);

            // 1. Buat penyesuaian
            $adjustment = PurchaseOrderAdjustment::create([
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'adjustment_date' => now(),
                'type' => $adjustmentType,
                'amount' => $adjustmentAmount,
                'reason' => $finalReason,
            ]);
            
            // 2. ✅ Post Jurnal Akuntansi
            $journalGroupId = "PO-ADJ-" . $adjustment->adjustment_id;
            $description = "Penyesuaian Otomatis PO #" . $purchaseOrder->po_number;
            
            $debitEntries = [];
            $creditEntries = [];

            if ($adjustmentType === 'credit_note') {
                // Nota Kredit Supplier: (D) Hutang Dagang, (K) Persediaan (Potongan)
                $debitEntries[] = [$apAccountId, $adjustmentAmount, "Potongan Hutang PO #" . $purchaseOrder->po_number];
                $creditEntries[] = [$purchaseReturnAccountId, $adjustmentAmount, $finalReason];
            } else {
                // Nota Debit Supplier: (D) Persediaan (Tambahan biaya), (K) Hutang Dagang
                $debitEntries[] = [$inventoryAccountId, $adjustmentAmount, $finalReason];
                $creditEntries[] = [$apAccountId, $adjustmentAmount, "Tambahan Hutang PO #" . $purchaseOrder->po_number];
            }

            $this->accountingService->postJournal(
                $journalGroupId,
                now(),
                $description,
                $debitEntries,
                $creditEntries,
                $adjustment // Model referensi
            );

            // 3. Update status PO
            $purchaseOrder->updatePaymentStatus();
            
            // 4. Tangani overpayment
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
     * ✅ DIPERBARUI: Menambahkan Jurnal Reversal.
     * ======================================================
     */
    public function destroy(PurchaseOrderAdjustment $purchaseOrderAdjustment): RedirectResponse
    {
        // --- PENCEGAHAN (Logika Anda) ---
        if ($purchaseOrderAdjustment->type === 'debit_note' && str_contains($purchaseOrderAdjustment->reason, 'Otomatis: Memindahkan kelebihan bayar')) {
            return back()->with('error', 'Gagal: Ini adalah Nota Debit otomatis. Untuk membatalkan, hapus Nota Kredit asli yang memicu pemindahan deposit ini.');
        }
        
        DB::beginTransaction();
        try {
            $po_id = $purchaseOrderAdjustment->purchase_order_id;
            $po = PurchaseOrder::find($po_id);
            
            // --- LOGIKA REVERSAL LEDGER (Logika Anda) ---
            if ($purchaseOrderAdjustment->type === 'credit_note') {
                $ledgerEntry = SupplierLedger::where('reference_type', PurchaseOrderAdjustment::class)
                                            ->where('reference_id', $purchaseOrderAdjustment->adjustment_id)
                                            ->first();
                if ($ledgerEntry) {
                    $autoDebitNote = PurchaseOrderAdjustment::where('purchase_order_id', $po_id)
                        ->where('type', 'debit_note')
                        ->where('reason', 'like', '%Ledger ID: ' . $ledgerEntry->ledger_id . '%')
                        ->first();
                    
                    if ($autoDebitNote) {
                        // ✅ Hapus Jurnal 'autoDebitNote' (Overpayment)
                        $this->reverseAndClearJournal("PO-ADJ-" . $autoDebitNote->adjustment_id, "Reversal Overpayment Adj #" . $autoDebitNote->adjustment_id, $autoDebitNote);
                        $autoDebitNote->delete();
                    }
                    $ledgerEntry->delete();
                }
            }
            
            // ✅ Hapus Jurnal 'purchaseOrderAdjustment' (Utama)
            $this->reverseAndClearJournal("PO-ADJ-" . $purchaseOrderAdjustment->adjustment_id, "Reversal Adj #" . $purchaseOrderAdjustment->adjustment_id, $purchaseOrderAdjustment);
            
            // 5. Hapus penyesuaian
            $purchaseOrderAdjustment->delete();
            
            // 6. Update status PO
            if ($po) {
                $po->updatePaymentStatus();
                // 7. Tangani overpayment
                $this->handleOverpayment($po, null, 'dihapus', 'deposit');
            }
            
            DB::commit();
            return redirect()->route('purchase-orders.show', $po_id)
                             ->with('success', 'Penyesuaian PO berhasil dibatalkan. Status utang, deposit, dan jurnal diperbarui.');
                          
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal membatalkan penyesuaian PO: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return back()->with('error', 'Gagal membatalkan penyesuaian: ' . $e->getMessage());
        }
    }

    /**
     * ======================================================
     * FUNGSI 'handleOverpayment' YANG DIPERBARUI
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi.
     * ======================================================
     */
    private function handleOverpayment(PurchaseOrder $purchaseOrder, ?PurchaseOrderAdjustment $originalAdjustment, string $context = 'dibuat', string $overpaymentAction = 'deposit')
    {
        $purchaseOrder->refresh();
        // Gunakan accessor 'remaining_balance' dari model PO
        $realRemainingBalance = $purchaseOrder->remaining_balance;
        
        if ($realRemainingBalance < -0.01) { // Jika ada kelebihan bayar
            
            if ($overpaymentAction === 'refund') {
                Log::info("Kelebihan bayar terdeteksi di PO #{$purchaseOrder->po_id}. Dibiarkan untuk proses refund manual.");
                return; // Hentikan fungsi
            }

            // --- Pilihan A: Simpan sebagai Deposit ---
            $overpaymentAmount = abs($realRemainingBalance);
            $supplier = $purchaseOrder->supplier; 
            if (!$supplier) {
                Log::warning("Gagal memindahkan kelebihan bayar PO #{$purchaseOrder->po_id}: Supplier tidak ditemukan.");
                return;
            }

            // ✅ Validasi Akun Akuntansi
            $apAccountId = $this->accountingSettings->getAccountsPayableId();
            $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();
            if (!$apAccountId || !$supplierDepositAccountId) {
                Log::error("Gagal proses overpayment PO #{$purchaseOrder->po_id}: Akun AP atau Deposit Supplier belum diatur.");
                throw new \Exception("Akun AP atau Deposit Supplier belum diatur.");
            }

            // (Logika referensi Anda)
            $transDate = now()->format('Y-m-d');
            $refType = PurchaseOrder::class; 
            $refId = $purchaseOrder->po_id;
            if ($originalAdjustment) {
                $refType = PurchaseOrderAdjustment::class;
                $refId = $originalAdjustment->adjustment_id;
                $transDate = $originalAdjustment->adjustment_date;
            }
            
            try {
                // 1. Buat entri deposit (kredit) di Supplier Ledger (Logika Anda)
                $ledgerEntry = SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'purchase_order_id' => $purchaseOrder->po_id,
                    'reference_type' => $refType,
                    'reference_id' => $refId,
                    'transaction_date' => $transDate,
                    'type' => 'credit',
                    'amount' => $overpaymentAmount,
                    'status' => 'available',
                    'description' => 'Otomatis: Kelebihan bayar dari PO #' . $purchaseOrder->po_number,
                    'user_id' => Auth::id(),
                ]);

                // 2. Buat penyesuaian "lawan" (debit note) (Logika Anda)
                $autoDebitNote = PurchaseOrderAdjustment::create([
                    'purchase_order_id' => $purchaseOrder->po_id,
                    'user_id' => Auth::id(),
                    'adjustment_date' => now(),
                    'type' => 'debit_note',
                    'amount' => $overpaymentAmount,
                    'reason' => 'Otomatis: Memindahkan kelebihan bayar (Rp ' . number_format($overpaymentAmount) . ') ke deposit supplier (Ledger ID: ' . $ledgerEntry->ledger_id . ')',
                ]);
                
                // 3. ✅ Post Jurnal Akuntansi (BARU)
                // Jurnal ini untuk 'autoDebitNote' yang baru dibuat
                // (D) Deposit Supplier (karena uangnya masuk ke deposit)
                // (K) Hutang Dagang (karena kita buat Nota Debit)
                $journalGroupId = "PO-ADJ-" . $autoDebitNote->adjustment_id;
                $description = "Otomatis: Pindah overpayment PO #" . $purchaseOrder->po_number . " ke deposit";
                
                $debitEntries = [
                    [$supplierDepositAccountId, $overpaymentAmount]
                ];
                $creditEntries = [
                    [$apAccountId, $overpaymentAmount]
                ];

                $this->accountingService->postJournal(
                    $journalGroupId,
                    now(),
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $autoDebitNote // Referensi ke adjustment 'debit_note' otomatis
                );

                // 4. Update status PO terakhir kali
                $purchaseOrder->updatePaymentStatus();
            } catch (\Exception $e) {
                Log::error('Gagal memproses overpayment adjustment PO: ' . $e->getMessage());
                throw $e; 
            }
        }
    }

    /**
     * ✅ (BARU) Helper untuk membalik dan menghapus jurnal
     */
    private function reverseAndClearJournal(string $journalGroupId, string $reversalDescription, Model $referenceModel)
    {
        // 1. Ambil jurnal asli
        $originalJournalEntries = DB::table('general_ledgers')
                                    ->where('journal_group_id', $journalGroupId)
                                    ->get();
        
        if ($originalJournalEntries->isEmpty()) {
            return; // Tidak ada jurnal untuk dibalik
        }

        $debitEntries = [];
        $creditEntries = [];

        foreach ($originalJournalEntries as $entry) {
            // Balikkan Debit jadi Kredit
            if ($entry->debit > 0) {
                $creditEntries[] = [$entry->chart_of_account_id, $entry->debit, "Reversal: " . $entry->description];
            }
            // Balikkan Kredit jadi Debit
            if ($entry->credit > 0) {
                $debitEntries[] = [$entry->chart_of_account_id, $entry->credit, "Reversal: " . $entry->description];
            }
        }

        // 2. Post Jurnal Reversal
        if (!empty($debitEntries) || !empty($creditEntries)) {
            $this->accountingService->postJournal(
                str_replace('PO-ADJ-', 'PO-ADJ-REV-', $journalGroupId), // Buat ID Reversal unik
                now(), // Tanggal reversal
                $reversalDescription,
                $debitEntries,
                $creditEntries,
                $referenceModel
            );
        }

        // 3. Hapus Jurnal Asli
        DB::table('general_ledgers')->where('journal_group_id', $journalGroupId)->delete();
    }
}