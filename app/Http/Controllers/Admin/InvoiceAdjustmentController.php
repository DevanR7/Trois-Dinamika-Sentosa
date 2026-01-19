<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceAdjustment;
use App\Models\SalesInvoice;
use App\Models\Product;
use App\Models\Tax;
use App\Models\ClientLedger;
use App\Models\InvoiceItem;
use App\Models\InvoiceAdditionalCost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model; 

use App\Services\AccountingService;
use App\Services\AccountingSettingService;
use App\Traits\ValidatesAccountingPeriod;

class InvoiceAdjustmentController extends Controller
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

        $this->middleware('can:create-invoice-adjustments')->only(['create', 'createManual', 'storeManual', 'createAuto', 'storeAuto']);
        $this->middleware('can:delete-invoice-adjustments')->only(['destroy']);
    }

    public function create(Request $request)
    {
        $preselectedInvoiceId = $request->query('invoice_id');
        $invoices = SalesInvoice::where('status', '!=', 'cancelled')
            ->orderBy('order_date', 'desc')
            ->get();
        return view('admin.invoice_adjustments.create', compact('invoices', 'preselectedInvoiceId'));
    }

    // =========================================================================
    // FITUR 1: KOREKSI MANUAL (Hanya Nominal, Tanpa Ubah Barang)
    // =========================================================================
    
    public function createManual(SalesInvoice $invoice)
    {
        return view('admin.invoice_adjustments.create_manual', compact('invoice'));
    }

    public function storeManual(Request $request)
    {
        $validated = $request->validate([
            'sales_invoice_id' => 'required|exists:sales_invoices,invoice_id',
            'adjustment_date' => 'required|date',
            'type' => 'required|in:credit_note,debit_note',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:1000',
            'overpayment_action' => 'required|string|in:deposit,refund',
        ]);

        if ($this->isDateClosed($validated['adjustment_date'])) {
            return back()->with('error', 'Gagal: Tanggal penyesuaian masuk periode tutup buku.');
        }

        $invoice = SalesInvoice::findOrFail($validated['sales_invoice_id']);
        
        // Pagar Pengaman: Cegah Credit Note > Sisa Tagihan (Manual)
        if ($validated['type'] === 'credit_note') {
            $currentBalance = $invoice->remaining_balance; 
            if ($validated['amount'] > $currentBalance) {
                return back()->withInput()->with('error', 'GAGAL: Nominal Credit Note tidak boleh melebihi Sisa Tagihan.');
            }
        }

        DB::beginTransaction();
        try {
            $adjustment = InvoiceAdjustment::create([
                'sales_invoice_id' => $invoice->invoice_id,
                'user_id' => Auth::id(),
                'adjustment_date' => $validated['adjustment_date'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'reason' => $validated['reason'],
                
                // TRUE: Karena header invoice TIDAK berubah, adjustment ini harus dihitung matematikanya
                'is_calculation_adjustment' => true, 
            ]);

            $this->postAdjustmentJournal($adjustment, $invoice, "Manual");
            
            $invoice->updatePaymentStatus();
            $this->handleOverpayment($invoice, $adjustment, 'dibuat', $validated['overpayment_action']);
            
            DB::commit();
            return redirect()->route('admin.invoices.show', $invoice->invoice_id)->with('success', 'Penyesuaian manual disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    // =========================================================================
    // FITUR 2: KOREKSI OTOMATIS (Revisi Item & Harga)
    // =========================================================================

    public function createAuto(SalesInvoice $invoice)
    {
        $invoice->load('items.product', 'taxes');
        $products = Product::where('is_active', true)->orderBy('product_name')->get();
        $taxes = Tax::where('is_active', true)->get();
        return view('admin.invoice_adjustments.create_auto', compact('invoice', 'products', 'taxes'));
    }

    public function storeAuto(Request $request, SalesInvoice $invoice): \Illuminate\Http\RedirectResponse
    {
        if ($invoice->status == 'cancelled') return back()->with('error', 'Invoice batal tidak bisa diedit.');
        if ($this->isDateClosed(now())) return back()->with('error', 'Periode akuntansi tutup.');
        
        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|numeric|min:0', // 0 = Hapus Item
            'products.*.price_per_unit' => 'nullable|numeric',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'taxes' => 'nullable|array',
            'additional_costs' => 'nullable|array',
            'additional_costs.*.description' => 'required_with:additional_costs|string',
            'additional_costs.*.amount' => 'required_with:additional_costs|numeric',
            'notes' => 'required|string|min:5',
            'overpayment_action' => 'required|string|in:deposit,refund',
        ]);

        DB::beginTransaction();
        try {
            $invoice->load('items');
            
            // 1. SNAPSHOT HEADER LAMA (PENTING AGAR HARGA TIDAK NYANGKUT SAAT UNDO)
            $oldHeaderSnapshot = [
                'subtotal' => (float)$invoice->subtotal,
                'discount_percentage' => (float)$invoice->discount_percentage,
                'discount_amount' => (float)$invoice->discount_amount,
                'total_amount' => (float)$invoice->total_amount
            ];

            // 2. SNAPSHOT ITEM LAMA
            $oldItemsSnapshot = $invoice->items->map(function($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => (float) $item->quantity,
                    'price_per_unit' => (float) $item->price_per_unit, // Harga Base
                    'hpp' => (float) $item->hpp,
                    'subtotal' => (float) $item->subtotal
                ];
            })->toArray();

            $oldItemsMap = $invoice->items->keyBy('product_id');
            $changeLogs = [];
            $itemsToCreate = [];
            $subtotalProducts = 0; // Gross Subtotal

            // 3. PROSES DATA BARU
            foreach ($validated['products'] as $newItem) {
                $prodId = $newItem['product_id'];
                $newQty = (float) $newItem['quantity'];
                
                $product = Product::lockForUpdate()->find($prodId);
                // FIX: Ambil Harga Base (Selling Price) atau Input User. 
                $price = isset($newItem['price_per_unit']) ? (float)$newItem['price_per_unit'] : $product->selling_price;

                // A. Update Stok (Hanya jika bukan draft)
                if ($invoice->status !== 'draft') {
                    $oldItem = $oldItemsMap->get($prodId);
                    $oldQty = $oldItem ? (float)$oldItem->quantity : 0;
                    
                    $diffQty = $newQty - $oldQty; 
                    
                    if (abs($diffQty) > 0.001) {
                        if ($diffQty > 0) { // Nambah
                            if ($product->stock_quantity < $diffQty) throw new \Exception("Stok kurang: " . $product->product_name);
                            $product->decrement('stock_quantity', $diffQty);
                        } else { // Kurang
                            $product->increment('stock_quantity', abs($diffQty));
                        }
                        
                        $action = ($newQty == 0) ? "Dihapus" : "Qty Ubah";
                        $changeLogs[] = "{$product->product_name}: {$oldQty} -> {$newQty} ({$action})";
                    }
                }

                // B. Data Insert (Skip jika qty 0)
                if ($newQty > 0) {
                    $subtotalItem = $newQty * $price; // Harga Gross
                    $subtotalProducts += $subtotalItem;
                    
                    $itemsToCreate[] = [
                        'product_id' => $prodId,
                        'quantity' => $newQty,
                        'price_per_unit' => $price, // Simpan Harga Base
                        'hpp' => $product->average_cost ?? 0,
                        'subtotal' => $subtotalItem
                    ];
                }
            }

            // 4. HITUNG TOTAL HEADER BARU (Dengan Diskon Global)
            $discountRate = (float) ($validated['discount_percentage'] ?? 0);
            $discountAmount = $subtotalProducts * ($discountRate / 100);
            $subtotalAfterDiscount = $subtotalProducts - $discountAmount;

            $totalTaxAmount = 0;
            if (!empty($validated['taxes'])) {
                $taxes = Tax::whereIn('id', $validated['taxes'])->get();
                foreach ($taxes as $tax) {
                    $totalTaxAmount += $subtotalAfterDiscount * ($tax->rate / 100);
                }
            }

            $totalAdditionalCosts = 0;
            if (!empty($validated['additional_costs'])) {
                foreach ($validated['additional_costs'] as $cost) {
                    $totalAdditionalCosts += (float) $cost['amount'];
                }
            }

            $newTotalAmount = $subtotalAfterDiscount + $totalTaxAmount + $totalAdditionalCosts;
            $oldTotalAmount = $invoice->total_amount;
            $diffAmount = $oldTotalAmount - $newTotalAmount; // Positif = Tagihan Turun

            if (abs($diffAmount) <= 0.01 && empty($changeLogs)) {
                DB::rollBack();
                return redirect()->route('admin.invoices.show', $invoice->invoice_id)->with('info', 'Tidak ada perubahan.');
            }

            // 5. UPDATE INVOICE
            $invoice->items()->delete();
            $invoice->additionalCosts()->delete();
            
            if (count($itemsToCreate) > 0) {
                $invoice->items()->createMany($itemsToCreate);
            }
            if (!empty($validated['additional_costs'])) {
                foreach ($validated['additional_costs'] as $cost) {
                    $invoice->additionalCosts()->create(['description' => $cost['description'], 'amount' => $cost['amount']]);
                }
            }

            $invoice->update([
                'subtotal' => $subtotalProducts,
                'discount_percentage' => $discountRate,
                'discount_amount' => $discountAmount,
                'total_amount' => $newTotalAmount,
            ]);

            if (!empty($validated['taxes'])) $invoice->taxes()->sync($validated['taxes']);
            else $invoice->taxes()->detach();

            // 6. SIMPAN LOG (FIX DOUBLE COUNTING)
            $adjustmentType = $diffAmount > 0 ? 'credit_note' : 'debit_note';
            
            $adjustment = InvoiceAdjustment::create([
                'sales_invoice_id' => $invoice->invoice_id,
                'user_id' => Auth::id(),
                'adjustment_date' => now(),
                'type' => $adjustmentType,
                'amount' => abs($diffAmount),
                
                // FIX: FALSE agar tidak dihitung ganda di model SalesInvoice
                // Karena kita sudah update total_amount di atas.
                'is_calculation_adjustment' => false, 
                
                'reason' => $validated['notes'] . (count($changeLogs) > 0 ? " (" . implode(", ", $changeLogs) . ")" : ""),
                'details' => [
                    'old_items_snapshot' => $oldItemsSnapshot, // Backup Items
                    'old_header_snapshot' => $oldHeaderSnapshot, // Backup Header
                    'change_logs' => $changeLogs
                ]
            ]);

            // 7. JURNAL & FINALISASI
            $this->postAdjustmentJournal($adjustment, $invoice, "Otomatis");
            $invoice->updatePaymentStatus();
            $this->handleOverpayment($invoice, $adjustment, 'dibuat', $validated['overpayment_action']);

            DB::commit();
            return redirect()->route('admin.invoices.show', $invoice->invoice_id)->with('success', 'Koreksi berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto Adj Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    // =========================================================================
    // DESTROY (UNDO REVISI)
    // =========================================================================

    public function destroy(InvoiceAdjustment $invoiceAdjustment): \Illuminate\Http\RedirectResponse
    {
        $journalGroupId = "INV-ADJ-" . $invoiceAdjustment->adjustment_id;
        
        if ($error = $this->checkTransactionLock($invoiceAdjustment->adjustment_date, $journalGroupId)) {
            return back()->with('error', $error);
        }

        DB::beginTransaction();
        try {
            $invoice = $invoiceAdjustment->salesInvoice;
            
            // 1. RESTORE DARI SNAPSHOT
            if (isset($invoiceAdjustment->details['old_items_snapshot'])) {
                
                $oldItemsSnapshot = $invoiceAdjustment->details['old_items_snapshot'];
                $oldHeaderSnapshot = $invoiceAdjustment->details['old_header_snapshot'] ?? null;

                // A. Restore Stok Fisik
                if ($invoice->status !== 'draft') {
                    // 1. Balikin stok dari item "SAAT INI" (yang salah/revisi) ke gudang
                    foreach ($invoice->items as $currentItem) {
                        Product::withTrashed()->find($currentItem->product_id)
                            ->increment('stock_quantity', $currentItem->quantity);
                    }
                    // 2. Ambil stok dari gudang untuk item "SNAPSHOT" (yang benar/awal)
                    foreach ($oldItemsSnapshot as $oldItem) {
                        Product::withTrashed()->lockForUpdate()->find($oldItem['product_id'])
                            ->decrement('stock_quantity', (float)$oldItem['quantity']);
                    }
                }

                // B. Restore DB Items
                $invoice->items()->delete();
                foreach ($oldItemsSnapshot as $snap) {
                    $invoice->items()->create([
                        'product_id' => $snap['product_id'],
                        'quantity' => $snap['quantity'],
                        'price_per_unit' => $snap['price_per_unit'],
                        'hpp' => $snap['hpp'],
                        'subtotal' => $snap['subtotal']
                    ]);
                }

                // C. Restore Header Invoice (FIX NYANGKUT)
                if ($oldHeaderSnapshot) {
                    $invoice->update([
                        'subtotal' => $oldHeaderSnapshot['subtotal'],
                        'discount_percentage' => $oldHeaderSnapshot['discount_percentage'],
                        'discount_amount' => $oldHeaderSnapshot['discount_amount'],
                        'total_amount' => $oldHeaderSnapshot['total_amount']
                    ]);
                } else {
                    // Fallback Logic Lama
                    if ($invoiceAdjustment->type == 'credit_note') $invoice->total_amount += $invoiceAdjustment->amount;
                    else $invoice->total_amount -= $invoiceAdjustment->amount;
                    $invoice->save();
                }
            }

            // 2. REVERSAL JURNAL & CLEANUP
            $this->reverseAndClearJournal($journalGroupId, "Undo Koreksi", $invoiceAdjustment);
            
            $relatedLedger = ClientLedger::where('reference_type', InvoiceAdjustment::class)
                ->where('reference_id', $invoiceAdjustment->adjustment_id)
                ->first();
                
            if ($relatedLedger) {
                $this->reverseAndClearJournal("INV-ADJ-DEP-" . $relatedLedger->ledger_id, "Undo Deposit", $relatedLedger);
                $relatedLedger->delete();
                InvoiceAdjustment::where('reason', 'like', '%Ledger ID: ' . $relatedLedger->ledger_id . '%')->delete();
            }

            $invoiceAdjustment->delete();

            // 3. UPDATE STATUS (CRITICAL)
            $invoice->refresh(); 
            $invoice->updatePaymentStatus(); 

            DB::commit();
            return back()->with('success', 'Koreksi dibatalkan. Data dikembalikan ke kondisi awal.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Undo Koreksi Failed: " . $e->getMessage());
            return back()->with('error', 'Gagal membatalkan: ' . $e->getMessage());
        }
    }

    // --- HELPER METHODS ---

    private function postAdjustmentJournal($adjustment, $invoice, $typeStr) 
    {
        $arId = $this->accountingSettings->getAccountsReceivableId();
        $retId = $this->accountingSettings->getSalesReturnId();
        $revId = $this->accountingSettings->getSalesRevenueId();

        $journalGroupId = "INV-ADJ-" . $adjustment->adjustment_id;
        $description = "$typeStr Adj Inv #" . $invoice->invoice_number;

        $debitEntries = []; $creditEntries = [];

        if ($adjustment->type === 'credit_note') {
            $debitEntries[] = [$retId, $adjustment->amount, $adjustment->reason];
            $creditEntries[] = [$arId, $adjustment->amount, "Potongan Piutang"];
        } else {
            $debitEntries[] = [$arId, $adjustment->amount, "Tambahan Piutang"];
            $creditEntries[] = [$revId, $adjustment->amount, $adjustment->reason]; 
        }

        $this->accountingService->postJournal(
            $journalGroupId,
            $adjustment->adjustment_date,
            $description,
            $debitEntries,
            $creditEntries,
            $adjustment,
            Auth::id()
        );
    }

    private function handleOverpayment($invoice, $adjustment, $context, $action) {
        $invoice->refresh();
        $balance = $invoice->remaining_balance;
        
        // Cek apakah Saldo Negatif (Overpaid)
        if ($balance < -0.01) {
            $overAmt = abs($balance);

            // === [FIX LOGIC] ===
            // Jika user memilih 'refund', kita JANGAN buat ledger deposit.
            // Biarkan saldo invoice tetap minus (Overpaid).
            // Nanti di halaman Show Invoice akan muncul tombol biru "Proses Refund Uang".
            if ($action === 'refund') {
                return; // STOP DI SINI. Jangan lanjut buat deposit.
            }

            // Jika user memilih 'deposit', baru kita proses pemindahan ke ledger
            $client = $invoice->client;
            $arId = $this->accountingSettings->getAccountsReceivableId();
            $depId = $this->accountingSettings->getClientDepositId();

            try {
                // 1. Buat Ledger Kredit (Deposit Bertambah)
                $ledger = ClientLedger::create([
                    'client_id' => $client->client_id,
                    'sales_invoice_id' => $invoice->invoice_id,
                    'reference_type' => InvoiceAdjustment::class,
                    'reference_id' => $adjustment ? $adjustment->adjustment_id : null,
                    'transaction_date' => now(),
                    'type' => 'credit',
                    'amount' => $overAmt,
                    'status' => 'available',
                    'description' => 'System: Kelebihan bayar Inv #' . $invoice->invoice_number,
                    'user_id' => Auth::id()
                ]);

                // 2. Buat Adjustment Penyeimbang (Agar Invoice jadi 0/Lunas)
                $autoAdj = InvoiceAdjustment::create([
                    'sales_invoice_id' => $invoice->invoice_id,
                    'user_id' => Auth::id(),
                    'adjustment_date' => now(),
                    'type' => 'debit_note',
                    'amount' => $overAmt,
                    'is_calculation_adjustment' => true, // TRUE: Karena ini penyeimbang saldo
                    'reason' => 'System: Pindah overpayment ke Deposit (Ledger ID: '.$ledger->ledger_id.')'
                ]);

                // 3. Jurnal Pemindahan
                $this->accountingService->postJournal(
                    "INV-ADJ-DEP-" . $ledger->ledger_id, now(), "System: Overpayment to Deposit",
                    [[$arId, $overAmt]], [[$depId, $overAmt]], $autoAdj, Auth::id()
                );
                
                $invoice->updatePaymentStatus();
            } catch (\Exception $e) {
                Log::error("Overpayment fail: " . $e->getMessage());
            }
        }
    }

    private function reverseAndClearJournal($groupId, $desc, $model) {
        $entries = DB::table('general_ledgers')->where('journal_group_id', $groupId)->get();
        if ($entries->isEmpty()) return;

        $debit = []; $credit = [];
        foreach ($entries as $e) {
            if ($e->debit > 0) $credit[] = [$e->chart_of_account_id, $e->debit, "Reversal"];
            if ($e->credit > 0) $debit[] = [$e->chart_of_account_id, $e->credit, "Reversal"];
        }
        if (!empty($debit)) {
            $this->accountingService->postJournal($groupId . "-REV", now(), $desc, $debit, $credit, $model);
        }
        DB::table('general_ledgers')->where('journal_group_id', $groupId)->delete();
    }
}