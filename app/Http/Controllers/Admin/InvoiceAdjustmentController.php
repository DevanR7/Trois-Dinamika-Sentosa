<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientLedger;
use App\Models\InvoiceAdjustment;
use App\Models\SalesInvoice;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model; // <-- Pastikan ini ada

// ✅ IMPORT SERVICE AKUNTANSI
use App\Services\AccountingService;
use App\Services\AccountingSettingService;
// 1. Import Trait
use App\Traits\ValidatesAccountingPeriod;

class InvoiceAdjustmentController extends Controller
{
    /**
     * ✅ (BARU) Inject Service Akuntansi
     */
    protected $accountingService;
    protected $accountingSettings;

    // 2. Gunakan Trait
    use ValidatesAccountingPeriod;

    public function __construct(
        AccountingService $accountingService, 
        AccountingSettingService $accountingSettingService
    ) {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettingService;
        
        // Middleware Anda
        $this->middleware('can:create-invoice-adjustments')->only(['create', 'createManual', 'storeManual', 'createAuto', 'storeAuto']);
        $this->middleware('can:delete-invoice-adjustments')->only(['destroy']);
    }


    /**
     * Menampilkan halaman pilihan metode penyesuaian.
     * (Tidak ada perubahan)
     */
    public function create(Request $request): View
    {
        $preselectedInvoiceId = $request->query('invoice_id');
        $invoices = SalesInvoice::where('status', '!=', 'cancelled')
            ->orderBy('order_date', 'desc')
            ->get();
        return view('admin.invoice_adjustments.create', compact('invoices', 'preselectedInvoiceId'));
    }
    
    // ======================================================
    // ALUR 1: PENYESUAIAN MANUAL
    // ======================================================

    /**
     * Menampilkan formulir penyesuaian manual.
     * (Tidak ada perubahan)
     */
    public function createManual(SalesInvoice $invoice): View
    {
        return view('admin.invoice_adjustments.create_manual', compact('invoice'));
    }

    /**
     * Menyimpan penyesuaian manual (credit note atau debit note).
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi.
     * ✅ DIPERBARUI: Menambahkan validasi periode akuntansi
     */
    public function storeManual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sales_invoice_id' => 'required|exists:sales_invoices,invoice_id',
            'adjustment_date' => 'required|date',
            'type' => 'required|in:credit_note,debit_note',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:1000',
            'overpayment_action' => 'required|string|in:deposit,refund',
        ]);

        // --- 🔒 VALIDASI PERIODE AKUNTANSI ---
        if ($this->isDateClosed($request->adjustment_date)) {
            return back()->with('error', 'Gagal: Tanggal penyesuaian masuk periode tutup buku.')->withInput();
        }
        // -------------------------------------

        $invoice = SalesInvoice::findOrFail($validated['sales_invoice_id']);
        if ($invoice->status === 'cancelled') {
            return back()->with('error', 'Invoice yang sudah dibatalkan tidak dapat disesuaikan.');
        }

        // ✅ Validasi Akun Akuntansi
        $arAccountId = $this->accountingSettings->getAccountsReceivableId();
        $salesReturnAccountId = $this->accountingSettings->getSalesReturnId();
        $salesRevenueAccountId = $this->accountingSettings->getSalesRevenueId();

        if (!$arAccountId || !$salesReturnAccountId || !$salesRevenueAccountId) {
            return back()->with('error', 'Gagal: Akun default (Piutang, Retur Penjualan, Pendapatan) belum diatur.')->withInput();
        }

        DB::beginTransaction();
        try {
            // 1. Buat penyesuaian
            $adjustment = InvoiceAdjustment::create([
                'sales_invoice_id' => $invoice->invoice_id,
                'user_id' => Auth::id(),
                'adjustment_date' => $validated['adjustment_date'],
                'type' => $validated['type'],
                'amount' => (float) $validated['amount'],
                'reason' => $validated['reason'],
            ]);

            // 2. ✅ Post Jurnal Akuntansi
            $journalGroupId = "INV-ADJ-" . $adjustment->adjustment_id;
            $description = "Penyesuaian Manual Inv #" . $invoice->invoice_number;

            $debitEntries = [];
            $creditEntries = [];

            if ($validated['type'] === 'credit_note') {
                // Nota Kredit: (D) Retur Penjualan, (K) Piutang Usaha
                $debitEntries[] = [$salesReturnAccountId, $validated['amount'], $validated['reason']];
                $creditEntries[] = [$arAccountId, $validated['amount'], "Potongan Piutang Inv #" . $invoice->invoice_number];
            } else {
                // Nota Debit: (D) Piutang Usaha, (K) Pendapatan (atau akun lain)
                $debitEntries[] = [$arAccountId, $validated['amount'], "Tambahan Piutang Inv #" . $invoice->invoice_number];
                $creditEntries[] = [$salesRevenueAccountId, $validated['amount'], $validated['reason']]; // Asumsi dikredit ke Pendapatan
            }

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['adjustment_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $adjustment, // Model referensi
                Auth::id()
            );
            
            // 3. Perbarui status pembayaran invoice
            $invoice->updatePaymentStatus();
            
            // 4. Tangani kelebihan bayar
            $overpaymentAction = $validated['overpayment_action'];
            $this->handleOverpayment($invoice, $adjustment, 'dibuat', $overpaymentAction);
            
            DB::commit();
            $noteType = $validated['type'] === 'credit_note' ? 'Kredit' : 'Debit';
            return redirect()->route('admin.invoices.show', $invoice->invoice_id)
                ->with('success', "Penyesuaian (Nota {$noteType}) berhasil disimpan dan dijurnal.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan adj manual: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal menyimpan penyesuaian: ' . $e->getMessage())->withInput();
        }
    }

    // ======================================================
    // ALUR 2: PENYESUAIAN OTOMATIS (REVISI DETAIL INVOICE)
    // ======================================================

    /**
     * Menampilkan formulir revisi otomatis.
     * (Tidak ada perubahan)
     */
    public function createAuto(SalesInvoice $invoice): View
    {
        $invoice->load('items.product', 'taxes');
        $products = Product::orderBy('product_name')->get();
        $taxes = Tax::where('is_active', true)->get();
        $clients = null;
        $salesUsers = null;
        return view('admin.invoice_adjustments.create_auto', compact('invoice', 'products', 'taxes', 'clients', 'salesUsers'));
    }

    /**
     * Menyimpan penyesuaian otomatis berdasarkan perubahan struktur invoice.
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi.
     * ✅ DIPERBARUI: Menambahkan validasi periode akuntansi
     */
    public function storeAuto(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|numeric|min:1',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'taxes' => 'nullable|array',
            'taxes.*' => 'exists:taxes,id',
            'notes' => 'required|string|min:5|max:1000',
            'overpayment_action' => 'required|string|in:deposit,refund',
        ]);

        // --- 🔒 VALIDASI PERIODE AKUNTANSI ---
        // Penyesuaian otomatis menggunakan now() sebagai tanggal
        if ($this->isDateClosed(now())) {
            return back()->with('error', 'Gagal: Periode akuntansi saat ini sudah ditutup. Tidak bisa membuat penyesuaian otomatis.')->withInput();
        }
        // -------------------------------------
        
        // ✅ Validasi Akun Akuntansi
        $arAccountId = $this->accountingSettings->getAccountsReceivableId();
        $salesReturnAccountId = $this->accountingSettings->getSalesReturnId();
        $salesRevenueAccountId = $this->accountingSettings->getSalesRevenueId();

        if (!$arAccountId || !$salesReturnAccountId || !$salesRevenueAccountId) {
            return back()->with('error', 'Gagal: Akun default (Piutang, Retur Penjualan, Pendapatan) belum diatur.')->withInput();
        }

        DB::beginTransaction();
        try {
            $invoice->load('items.product', 'taxes');
            // (Logika kalkulasi Anda untuk $newTotalAmount, $oldTotalAmount, $diff)
            $subtotalProducts = 0;
            foreach ($validated['products'] as $item) {
                $product = Product::find($item['product_id']);
                $price = $product?->selling_price ?? 0;
                $subtotalProducts += $price * $item['quantity'];
            }
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
            $newTotalAmount = $subtotalAfterDiscount + $totalTaxAmount;
            $oldTotalAmount = $invoice->total_amount;
            $diff = $oldTotalAmount - $newTotalAmount;

            if (abs($diff) <= 0.01) {
                DB::rollBack(); 
                return redirect()->route('admin.invoices.show', $invoice->invoice_id)
                    ->with('info', 'Tidak ada perubahan nominal. Penyesuaian tidak dibuat.');
            }
            
            $adjustmentType = $diff > 0 ? 'credit_note' : 'debit_note';
            $adjustmentAmount = abs($diff);
            
            // (Logika Anda untuk 'reasonDetails')
            $reasonDetails = [];
            $oldDiscount = (float) $invoice->discount_percentage;
            if (abs($oldDiscount - $discountRate) > 0.001) {
                $reasonDetails[] = "Diskon global diubah dari {$oldDiscount}% menjadi {$discountRate}%.";
            }
            $oldItems = $invoice->items->keyBy('product_id');
            $newItems = collect($validated['products'])->mapWithKeys(fn ($item) => [
                $item['product_id'] => ['quantity' => (int) $item['quantity']],
            ]);
            foreach ($oldItems as $pid => $oldItem) {
                if (!$newItems->has($pid)) {
                    $reasonDetails[] = "Item DIHAPUS: " . Str::limit($oldItem->product->product_name, 20) . " (Qty {$oldItem->quantity}).";
                } elseif ($oldItem->quantity != $newItems[$pid]['quantity']) {
                    $reasonDetails[] = "Qty " . Str::limit($oldItem->product->product_name, 20) . " diubah: {$oldItem->quantity} -> {$newItems[$pid]['quantity']}.";
                }
            }
            foreach ($newItems as $pid => $newItem) {
                if (!$oldItems->has($pid)) {
                    $productName = Product::find($pid)?->product_name ?? 'Produk ??';
                    $reasonDetails[] = "Item DITAMBAH: " . Str::limit($productName, 20) . " (Qty {$newItem['quantity']}).";
                }
            }
            $oldTaxes = $invoice->taxes->pluck('id')->sort()->values()->all();
            $newTaxes = collect($validated['taxes'] ?? [])->map(fn ($id) => (int) $id)->sort()->values()->all();
            if ($oldTaxes !== $newTaxes) {
                $reasonDetails[] = "Komponen pajak diubah.";
            }

            $finalReason = $validated['notes'];
            if (!empty($reasonDetails)) {
                $finalReason .= "\n\n[LOG SISTEM OTOMATIS]:\n- " . implode("\n- ", $reasonDetails);
            }

            // 1. Simpan penyesuaian
            $adjustment = InvoiceAdjustment::create([
                'sales_invoice_id' => $invoice->invoice_id,
                'user_id' => Auth::id(),
                'adjustment_date' => now(),
                'type' => $adjustmentType,
                'amount' => $adjustmentAmount,
                'reason' => $finalReason,
            ]);
            
            // 2. ✅ Post Jurnal Akuntansi
            $journalGroupId = "INV-ADJ-" . $adjustment->adjustment_id;
            $description = "Penyesuaian Otomatis Inv #" . $invoice->invoice_number;

            $debitEntries = [];
            $creditEntries = [];

            if ($adjustmentType === 'credit_note') {
                // Nota Kredit: (D) Retur Penjualan, (K) Piutang Usaha
                $debitEntries[] = [$salesReturnAccountId, $adjustmentAmount, $finalReason];
                $creditEntries[] = [$arAccountId, $adjustmentAmount, "Potongan Piutang Inv #" . $invoice->invoice_number];
            } else {
                // Nota Debit: (D) Piutang Usaha, (K) Pendapatan
                $debitEntries[] = [$arAccountId, $adjustmentAmount, "Tambahan Piutang Inv #" . $invoice->invoice_number];
                $creditEntries[] = [$salesRevenueAccountId, $adjustmentAmount, $finalReason];
            }

            $this->accountingService->postJournal(
                $journalGroupId,
                now(),
                $description,
                $debitEntries,
                $creditEntries,
                $adjustment, // Model referensi
                Auth::id()
            );

            // 3. Perbarui status pembayaran
            $invoice->updatePaymentStatus();
            
            // 4. Tangani kelebihan bayar
            $overpaymentAction = $validated['overpayment_action'];
            $this->handleOverpayment($invoice, $adjustment, 'dibuat', $overpaymentAction);
            
            DB::commit();
            $formattedAmount = number_format($adjustmentAmount, 0, ',', '.');
            $noteType = $adjustmentType === 'credit_note' ? 'Kredit' : 'Debit';
            return redirect()->route('admin.invoices.show', $invoice->invoice_id)
                ->with('success', "Koreksi otomatis berhasil. Nota {$noteType} senilai Rp {$formattedAmount} telah dibuat.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan koreksi otomatis: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return back()->with('error', 'Gagal menyimpan koreksi otomatis: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * ======================================================
     * FUNGSI 'DESTROY' YANG DIPERBARUI
     * ✅ DIPERBARUI: Menambahkan Jurnal Reversal.
     * ✅ DIPERBARUI: Menambahkan validasi periode akuntansi
     * ======================================================
     */
    public function destroy(InvoiceAdjustment $invoiceAdjustment): RedirectResponse
    {
        // --- 🔒 VALIDASI PERIODE AKUNTANSI ---
        $journalGroupId = "INV-ADJ-" . $invoiceAdjustment->adjustment_id;

        if ($error = $this->checkTransactionLock($invoiceAdjustment->adjustment_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus Penyesuaian: " . $error);
        }
        // -------------------------------------

        // --- PENCEGAHAN (Logika Anda) ---
        if ($invoiceAdjustment->type === 'debit_note' && str_contains($invoiceAdjustment->reason, 'Otomatis: Memindahkan kelebihan bayar')) {
            return back()->with('error', 'Gagal: Ini adalah Nota Debit otomatis. Untuk membatalkan, hapus Nota Kredit asli yang memicu pemindahan deposit ini.');
        }
        
        DB::beginTransaction();
        try {
            $invoiceId = $invoiceAdjustment->sales_invoice_id;
            $invoice = SalesInvoice::find($invoiceId);
            
            // --- LOGIKA REVERSAL LEDGER (Logika Anda) ---
            if ($invoiceAdjustment->type === 'credit_note') {
                $ledgerEntry = ClientLedger::where('reference_type', InvoiceAdjustment::class)
                                            ->where('reference_id', $invoiceAdjustment->adjustment_id)
                                            ->first();
                if ($ledgerEntry) {
                    $autoDebitNote = InvoiceAdjustment::where('sales_invoice_id', $invoiceId)
                        ->where('type', 'debit_note')
                        ->where('reason', 'like', '%Ledger ID: ' . $ledgerEntry->ledger_id . '%')
                        ->first();
                    
                    if ($autoDebitNote) {
                        // ✅ Hapus Jurnal 'autoDebitNote' (Overpayment)
                        $this->reverseAndClearJournal("INV-ADJ-" . $autoDebitNote->adjustment_id, "Reversal Overpayment Adj #" . $autoDebitNote->adjustment_id, $autoDebitNote);
                        $autoDebitNote->delete();
                    }
                    $ledgerEntry->delete();
                }
            }
            
            // ✅ Hapus Jurnal 'invoiceAdjustment' (Utama)
            $this->reverseAndClearJournal("INV-ADJ-" . $invoiceAdjustment->adjustment_id, "Reversal Adj #" . $invoiceAdjustment->adjustment_id, $invoiceAdjustment);
            
            // 5. Hapus penyesuaian yang diminta user
            $invoiceAdjustment->delete();
            
            // 6. Update status invoice
            if ($invoice) {
                $invoice->updatePaymentStatus();
                
                // 7. Tangani overpayment (jika ada)
                $this->handleOverpayment($invoice, null, 'dihapus', 'deposit');
            }
            
            DB::commit();
            return redirect()->route('admin.invoices.show', $invoiceId)
                             ->with('success', 'Penyesuaian invoice berhasil dibatalkan. Status utang, deposit, dan jurnal diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal batalkan adj invoice: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal membatalkan penyesuaian: ' . $e->getMessage());
        }
    }

    /**
     * ======================================================
     * FUNGSI 'handleOverpayment' YANG DIPERBARUI
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi.
     * ======================================================
     */
    private function handleOverpayment(SalesInvoice $invoice, ?InvoiceAdjustment $originalAdjustment, string $context = 'dibuat', string $overpaymentAction = 'deposit')
    {
        $invoice->refresh();
        $remainingBalance = $invoice->remaining_balance ?? 0;
        
        // PENTING: Gunakan 'total_due' (Tagihan Asli) vs 'amount_paid' (Total Bayar)
        // remaining_balance = total_due - amount_paid
        // Jika amount_paid > total_due, maka remaining_balance akan negatif
        // Kita gunakan accessor baru dari SalesInvoice
        $realRemainingBalance = $invoice->total_due - $invoice->amount_paid;

        if ($realRemainingBalance < -0.01) { // Jika ada kelebihan bayar
            
            if ($overpaymentAction === 'refund') {
                Log::info("Kelebihan bayar terdeteksi di Inv #{$invoice->invoice_id}. Dibiarkan untuk proses refund manual.");
                return; // Hentikan fungsi
            }

            // --- Pilihan A: Simpan sebagai Deposit ---
            $overpaymentAmount = abs($realRemainingBalance);
            $client = $invoice->client; 
            if (!$client) {
                Log::warning("Gagal memindahkan kelebihan bayar Invoice #{$invoice->invoice_id}: Klien tidak ditemukan.");
                return;
            }

            // ✅ Validasi Akun Akuntansi
            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            if (!$arAccountId || !$clientDepositAccountId) {
                Log::error("Gagal proses overpayment Inv #{$invoice->invoice_id}: Akun AR atau Deposit Klien belum diatur.");
                throw new \Exception("Akun AR atau Deposit Klien belum diatur.");
            }

            // Tentukan data referensi (Logika Anda)
            $transDate = now()->format('Y-m-d');
            $refType = SalesInvoice::class; 
            $refId = $invoice->invoice_id;
            if ($originalAdjustment) {
                $refType = InvoiceAdjustment::class; 
                $refId = $originalAdjustment->adjustment_id;
                $transDate = $originalAdjustment->adjustment_date;
            }
            
            try {
                // 1. Buat entri deposit (kredit) di Client Ledger (Logika Anda)
                $ledgerEntry = ClientLedger::create([
                    'client_id' => $client->client_id,
                    'sales_invoice_id' => $invoice->invoice_id,
                    'reference_type' => $refType,
                    'reference_id' => $refId,
                    'transaction_date' => $transDate,
                    'type' => 'credit', // Menambah deposit klien
                    'amount' => $overpaymentAmount,
                    'status' => 'available',
                    'description' => 'Otomatis: Kelebihan bayar dari Inv #' . $invoice->invoice_number,
                    'user_id' => Auth::id(),
                ]);

                // 2. Buat penyesuaian "lawan" (debit note) (Logika Anda)
                $autoDebitNote = InvoiceAdjustment::create([
                    'sales_invoice_id' => $invoice->invoice_id,
                    'user_id' => Auth::id(),
                    'adjustment_date' => now(),
                    'type' => 'debit_note', // Menambah tagihan (untuk menetralkan minus)
                    'amount' => $overpaymentAmount,
                    'reason' => 'Otomatis: Memindahkan kelebihan bayar (Rp ' . number_format($overpaymentAmount) . ') ke deposit klien (Ledger ID: ' . $ledgerEntry->ledger_id . ')',
                ]);
                
                // 3. ✅ Post Jurnal Akuntansi (BARU)
                // Jurnal ini untuk 'autoDebitNote' yang baru dibuat
                // (D) Piutang Usaha (karena kita buat Nota Debit)
                // (K) Deposit Klien (karena uangnya masuk ke deposit)
                $journalGroupId = "INV-ADJ-" . $autoDebitNote->adjustment_id;
                $description = "Otomatis: Pindah overpayment Inv #" . $invoice->invoice_number . " ke deposit";
                
                $debitEntries = [
                    [$arAccountId, $overpaymentAmount]
                ];
                $creditEntries = [
                    [$clientDepositAccountId, $overpaymentAmount]
                ];

                $this->accountingService->postJournal(
                    $journalGroupId,
                    now(),
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $autoDebitNote, // Referensi ke adjustment 'debit_note' otomatis
                    Auth::id()
                );

                // 4. Update status invoice terakhir kali
                $invoice->updatePaymentStatus();
            } catch (\Exception $e) {
                Log::error('Gagal memproses overpayment adjustment invoice: ' . $e->getMessage());
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
            /** @var \App\Models\GeneralLedger $entry */
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
                str_replace('INV-ADJ-', 'INV-ADJ-REV-', $journalGroupId), // Buat ID Reversal unik
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