<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model; 

use App\Services\AccountingService;
use App\Services\AccountingSettingService;
use App\Traits\ValidatesAccountingPeriod;

class PurchaseOrderAdjustmentController extends Controller
{
    protected $accountingService;
    protected $accountingSettings;
    use ValidatesAccountingPeriod;

    public function __construct(
        AccountingService $accountingService, 
        AccountingSettingService $accountingSettingService
    ) {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettingService;
        
        // $this->middleware('permission:create-purchase-adjustments');
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

        if ($this->isDateClosed($request->adjustment_date)) {
            return back()->with('error', 'Gagal: Tanggal penyesuaian masuk periode tutup buku.');
        }
        
        $purchaseOrder = PurchaseOrder::findOrFail($validated['purchase_order_id']);
        if ($purchaseOrder->status == 'cancelled') {
            return back()->with('error', 'PO yang sudah dibatalkan tidak dapat disesuaikan.');
        }

        $apAccountId = $this->accountingSettings->getAccountsPayableId();
        $purchaseReturnAccountId = $this->accountingSettings->getPurchaseReturnId();
        $inventoryAccountId = $this->accountingSettings->getInventoryId();

        if (!$apAccountId || !$purchaseReturnAccountId || !$inventoryAccountId) {
            return back()->with('error', 'Gagal: Akun default (Hutang, Retur Pembelian/Persediaan) belum diatur.')->withInput();
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
            
            $overpaymentAction = $validated['overpayment_action'];
            $this->handleOverpayment($purchaseOrder, $adjustment, 'dibuat', $overpaymentAction); 
            
            DB::commit();
            return redirect()->route('admin.purchase-orders.show', $purchaseOrder->po_id)
                         ->with('success', 'Penyesuaian (Nota ' . ($validated['type'] == 'credit_note' ? 'Kredit' : 'Debit') . ') berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan penyesuaian PO manual: ' . $e->getMessage() . ' on line ' . $e->getLine());
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
        $products = Product::with('unit')->orderBy('product_name')->get();
        $users = User::all();
        $taxes = Tax::all();
        
        return view('admin.purchase_order_adjustments.create_auto', compact('purchaseOrder', 'suppliers', 'products', 'users', 'taxes'));
    }

    public function storeAuto(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if ($purchaseOrder->status == 'cancelled') {
            return back()->with('error', 'PO yang sudah dibatalkan tidak dapat disesuaikan.');
        }
        if ($this->isDateClosed(now())) {
            return back()->with('error', 'Gagal: Periode akuntansi saat ini sudah ditutup.');
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
        
        $apAccountId = $this->accountingSettings->getAccountsPayableId();
        $purchaseReturnAccountId = $this->accountingSettings->getPurchaseReturnId(); 
        $inventoryAccountId = $this->accountingSettings->getInventoryId();
        $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();

        if (!$apAccountId || !$purchaseReturnAccountId || !$inventoryAccountId || !$supplierDepositAccountId) {
            return back()->with('error', 'Gagal: Akun default (Hutang, Retur, Persediaan, atau Deposit) belum diatur.')->withInput();
        }
        
        DB::beginTransaction();
        try {

            $purchaseOrder->load('items.product', 'items.discounts', 'tax');
            $itemSubtotal = 0;
            $newItemsSnapshot = []; 

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

                $newItemsSnapshot[] = [
                    'product_id'   => $p['product_id'],
                    'product_name' => $productRef->product_name ?? 'Unknown',
                    'product_code' => $productRef->product_code ?? '-',
                    'unit_name'    => $productRef->unit->name ?? 'Unit',
                    'quantity'     => $qty,
                    'price'        => $p['price_per_unit'], 
                    'discounts'    => $p['discounts'] ?? [],
                    'net_price'    => $finalPrice,
                    'subtotal'     => $subtotalRow
                ];
            }
            
            $options = $request->all();
            $options['subtotal'] = $itemSubtotal;
            $calc = PurchaseOrderCalculator::calculate($options);
            
            $newTotalAmount = $calc['grand_total'];
            $oldTotalAmount = $purchaseOrder->grand_total; 
            $diff = $oldTotalAmount - $newTotalAmount;
            
            if (abs($diff) <= 0.01) {
                DB::rollBack();
                return redirect()->route('admin.purchase-orders.show', $purchaseOrder->po_id)
                    ->with('info', 'Tidak ada perubahan nilai finansial. Penyesuaian tidak dibuat.');
            }
            
            $adjustmentType = $diff > 0 ? 'credit_note' : 'debit_note';
            $adjustmentAmount = abs($diff);

            $adjustment = PurchaseOrderAdjustment::create([
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'adjustment_date' => now(),
                'type' => $adjustmentType,
                'amount' => $adjustmentAmount,
                'reason' => $validated['notes'],
                'details' => [
                    'items' => $newItemsSnapshot, 
                    'calculation' => $calc,       
                    'diff' => $diff
                ]
            ]);
            
            $purchaseOrder->update([
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

            if ($adjustmentType === 'credit_note' && $purchaseOrder->payment_status === 'paid' && $validated['overpayment_action'] === 'deposit') {
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
                    "SUP-DEP-" . $ledger->ledger_id,
                    now(),
                    "Pindah ke Deposit dari Koreksi PO Lunas #" . $purchaseOrder->po_number,
                    [[$apAccountId, $adjustmentAmount]], 
                    [[$supplierDepositAccountId, $adjustmentAmount]],
                    $ledger,
                    Auth::id()
                );
            }

            $journalGroupId = "PO-ADJ-" . $adjustment->adjustment_id;
            $description = "Penyesuaian Otomatis PO #" . $purchaseOrder->po_number;
            
            $debitEntries = [];
            $creditEntries = [];

            if ($adjustmentType === 'credit_note') {
                $debitEntries[] = [$apAccountId, $adjustmentAmount, "Potongan Hutang PO #" . $purchaseOrder->po_number];
                $creditEntries[] = [$purchaseReturnAccountId, $adjustmentAmount, $validated['notes']];
            } else {
                $debitEntries[] = [$inventoryAccountId, $adjustmentAmount, $validated['notes']];
                $creditEntries[] = [$apAccountId, $adjustmentAmount, "Tambahan Hutang PO #" . $purchaseOrder->po_number];
            }

            $this->accountingService->postJournal(
                $journalGroupId,
                now(),
                $description,
                $debitEntries,
                $creditEntries,
                $adjustment,
                Auth::id()
            );

            $purchaseOrder->updatePaymentStatus();
            
            DB::commit();
            
            $formattedAmount = number_format($adjustmentAmount, 0, ',', '.');
            $noteLabel = ($adjustmentType == 'credit_note') ? 'Kredit' : 'Debit';
            
            return redirect()->route('admin.purchase-orders.show', $purchaseOrder->po_id)
                     ->with('success', "Koreksi berhasil. Nota {$noteLabel} senilai Rp {$formattedAmount} telah dibuat. Tagihan diperbarui.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan koreksi PO otomatis: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return back()->with('error', 'Gagal menyimpan koreksi otomatis: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(PurchaseOrderAdjustment $purchaseOrderAdjustment): RedirectResponse
    {
        $journalGroupId = "PO-ADJ-" . $purchaseOrderAdjustment->adjustment_id;
        if ($error = $this->checkTransactionLock($purchaseOrderAdjustment->adjustment_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus Penyesuaian: " . $error);
        }
        if ($purchaseOrderAdjustment->type === 'debit_note' && str_contains($purchaseOrderAdjustment->reason, 'Otomatis: Memindahkan kelebihan bayar')) {
            return back()->with('error', 'Gagal: Ini adalah Nota Debit otomatis. Untuk membatalkan, hapus Nota Kredit asli yang memicu pemindahan deposit ini.');
        }
        
        DB::beginTransaction();
        try {
            $po_id = $purchaseOrderAdjustment->purchase_order_id;
            $po = PurchaseOrder::find($po_id);
            
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
                        $this->reverseAndClearJournal("PO-ADJ-" . $autoDebitNote->adjustment_id, "Reversal Overpayment Adj #" . $autoDebitNote->adjustment_id, $autoDebitNote);
                        $autoDebitNote->delete();
                    }
                    $ledgerEntry->delete();
                }
            }
            
            $this->reverseAndClearJournal("PO-ADJ-" . $purchaseOrderAdjustment->adjustment_id, "Reversal Adj #" . $purchaseOrderAdjustment->adjustment_id, $purchaseOrderAdjustment);
            
            $purchaseOrderAdjustment->delete();
            
            if ($po) {
                $po->updatePaymentStatus();
                $this->handleOverpayment($po, null, 'dihapus', 'deposit');
            }
            
            DB::commit();
            return redirect()->route('admin.purchase-orders.show', $po_id)
                             ->with('success', 'Penyesuaian PO berhasil dibatalkan. Status utang, deposit, dan jurnal diperbarui.');
                          
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal membatalkan penyesuaian PO: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return back()->with('error', 'Gagal membatalkan penyesuaian: ' . $e->getMessage());
        }
    }

    private function handleOverpayment(PurchaseOrder $purchaseOrder, ?PurchaseOrderAdjustment $originalAdjustment, string $context = 'dibuat', string $overpaymentAction = 'deposit')
    {
        $purchaseOrder->refresh();
        $realRemainingBalance = $purchaseOrder->remaining_balance;
        
        if ($realRemainingBalance < -0.01) { 
            
            if ($overpaymentAction === 'refund') {
                Log::info("Kelebihan bayar terdeteksi di PO #{$purchaseOrder->po_id}. Dibiarkan untuk proses refund manual.");
                return; 
            }

            $overpaymentAmount = abs($realRemainingBalance);
            $supplier = $purchaseOrder->supplier; 
            if (!$supplier) {
                Log::warning("Gagal memindahkan kelebihan bayar PO #{$purchaseOrder->po_id}: Supplier tidak ditemukan.");
                return;
            }

            $apAccountId = $this->accountingSettings->getAccountsPayableId();
            $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();
            if (!$apAccountId || !$supplierDepositAccountId) {
                Log::error("Gagal proses overpayment PO #{$purchaseOrder->po_id}: Akun AP atau Deposit Supplier belum diatur.");
                throw new \Exception("Akun AP atau Deposit Supplier belum diatur.");
            }

            $transDate = now()->format('Y-m-d');
            $refType = PurchaseOrder::class; 
            $refId = $purchaseOrder->po_id;
            if ($originalAdjustment) {
                $refType = PurchaseOrderAdjustment::class;
                $refId = $originalAdjustment->adjustment_id;
                $transDate = $originalAdjustment->adjustment_date;
            }
            
            try {
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

                $autoDebitNote = PurchaseOrderAdjustment::create([
                    'purchase_order_id' => $purchaseOrder->po_id,
                    'user_id' => Auth::id(),
                    'adjustment_date' => now(),
                    'type' => 'debit_note',
                    'amount' => $overpaymentAmount,
                    'reason' => 'Otomatis: Memindahkan kelebihan bayar (Rp ' . number_format($overpaymentAmount) . ') ke deposit supplier (Ledger ID: ' . $ledgerEntry->ledger_id . ')',
                ]);
                
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
                    $autoDebitNote,
                    Auth::id() 
                );

                $purchaseOrder->updatePaymentStatus();
            } catch (\Exception $e) {
                Log::error('Gagal memproses overpayment adjustment PO: ' . $e->getMessage());
                throw $e; 
            }
        }
    }

    private function reverseAndClearJournal(string $journalGroupId, string $reversalDescription, Model $referenceModel)
    {
        $originalJournalEntries = DB::table('general_ledgers')
                                    ->where('journal_group_id', $journalGroupId)
                                    ->get();
        
        if ($originalJournalEntries->isEmpty()) {
            return; 
        }

        $debitEntries = [];
        $creditEntries = [];

        foreach ($originalJournalEntries as $entry) {
            if ($entry->debit > 0) {
                $creditEntries[] = [$entry->chart_of_account_id, $entry->debit, "Reversal: " . $entry->description];
            }
            if ($entry->credit > 0) {
                $debitEntries[] = [$entry->chart_of_account_id, $entry->credit, "Reversal: " . $entry->description];
            }
        }

        if (!empty($debitEntries) || !empty($creditEntries)) {
            $this->accountingService->postJournal(
                str_replace('PO-ADJ-', 'PO-ADJ-REV-', $journalGroupId), 
                now(), 
                $reversalDescription,
                $debitEntries,
                $creditEntries,
                $referenceModel
            );
        }

        DB::table('general_ledgers')->where('journal_group_id', $journalGroupId)->delete();
    }
}
