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
use Illuminate\Database\Eloquent\Model; 

use App\Services\AccountingService;
use App\Services\AccountingSettingService;
use App\Traits\ValidatesAccountingPeriod;

class InvoiceAdjustmentController extends Controller
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
        $this->middleware('can:create-invoice-adjustments')->only(['create', 'createManual', 'storeManual', 'createAuto', 'storeAuto']);
        $this->middleware('can:delete-invoice-adjustments')->only(['destroy']);
    }

    public function create(Request $request): View
    {
        $preselectedInvoiceId = $request->query('invoice_id');
        $invoices = SalesInvoice::where('status', '!=', 'cancelled')
            ->orderBy('order_date', 'desc')
            ->get();
        return view('admin.invoice_adjustments.create', compact('invoices', 'preselectedInvoiceId'));
    }

    public function createManual(SalesInvoice $invoice): View
    {
        return view('admin.invoice_adjustments.create_manual', compact('invoice'));
    }

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

        if ($this->isDateClosed($request->adjustment_date)) {
            return back()->with('error', 'Gagal: Tanggal penyesuaian masuk periode tutup buku.')->withInput();
        }

        $invoice = SalesInvoice::findOrFail($validated['sales_invoice_id']);

        if ($invoice->status === 'cancelled') {
            return back()->with('error', 'Invoice yang sudah dibatalkan tidak dapat disesuaikan.');
        }

        $arAccountId = $this->accountingSettings->getAccountsReceivableId();
        $salesReturnAccountId = $this->accountingSettings->getSalesReturnId();
        $salesRevenueAccountId = $this->accountingSettings->getSalesRevenueId();

        if (!$arAccountId || !$salesReturnAccountId || !$salesRevenueAccountId) {
            return back()->with('error', 'Gagal: Akun default (Piutang, Retur Penjualan, Pendapatan) belum diatur.')->withInput();
        }

        DB::beginTransaction();
        try {
            $adjustment = InvoiceAdjustment::create([
                'sales_invoice_id' => $invoice->invoice_id,
                'user_id' => Auth::id(),
                'adjustment_date' => $validated['adjustment_date'],
                'type' => $validated['type'],
                'amount' => (float) $validated['amount'],
                'reason' => $validated['reason'],
            ]);

            $journalGroupId = "INV-ADJ-" . $adjustment->adjustment_id;
            $description = "Penyesuaian Manual Inv #" . $invoice->invoice_number;

            $debitEntries = [];
            $creditEntries = [];

            if ($validated['type'] === 'credit_note') {
                $debitEntries[] = [$salesReturnAccountId, $validated['amount'], $validated['reason']];
                $creditEntries[] = [$arAccountId, $validated['amount'], "Potongan Piutang Inv #" . $invoice->invoice_number];
            } else {
                $debitEntries[] = [$arAccountId, $validated['amount'], "Tambahan Piutang Inv #" . $invoice->invoice_number];
                $creditEntries[] = [$salesRevenueAccountId, $validated['amount'], $validated['reason']]; 
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
            
            $invoice->updatePaymentStatus();
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

    public function createAuto(SalesInvoice $invoice): View
    {
        $invoice->load('items.product', 'taxes');
        $products = Product::orderBy('product_name')->get();
        $taxes = Tax::where('is_active', true)->get();
        $clients = null;
        $salesUsers = null;
        return view('admin.invoice_adjustments.create_auto', compact('invoice', 'products', 'taxes', 'clients', 'salesUsers'));
    }

    public function storeAuto(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        if ($invoice->status == 'cancelled') {
            return back()->with('error', 'Invoice yang sudah dibatalkan tidak dapat disesuaikan.');
        }
        if ($this->isDateClosed(now())) {
            return back()->with('error', 'Gagal: Periode akuntansi saat ini sudah ditutup.');
        }
        
        $validated = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'taxes' => 'nullable|array',
            'taxes.*' => 'exists:taxes,id',
            'additional_costs' => 'nullable|array',
            'additional_costs.*.description' => 'required_with:additional_costs|string|max:255',
            'additional_costs.*.amount' => 'required_with:additional_costs|numeric|min:0',
            'notes' => 'required|string|min:5|max:1000',
            'overpayment_action' => 'required|string|in:deposit,refund',
        ]);

        $arAccountId = $this->accountingSettings->getAccountsReceivableId();
        $salesReturnAccountId = $this->accountingSettings->getSalesReturnId();
        $salesRevenueAccountId = $this->accountingSettings->getSalesRevenueId();

        if (!$arAccountId || !$salesReturnAccountId || !$salesRevenueAccountId) {
            return back()->with('error', 'Gagal: Akun default (Piutang, Retur Penjualan, Pendapatan) belum diatur.')->withInput();
        }

        DB::beginTransaction();
        try {
            $invoice->load('items.product', 'taxes');
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

            $totalAdditionalCosts = 0;
            if (!empty($validated['additional_costs'])) {
                foreach ($validated['additional_costs'] as $cost) {
                    $totalAdditionalCosts += (float) $cost['amount'];
                }
            }

            $newTotalAmount = $subtotalAfterDiscount + $totalTaxAmount + $totalAdditionalCosts;
            $oldTotalAmount = $invoice->total_amount;
            $diff = $oldTotalAmount - $newTotalAmount; 

            if (abs($diff) <= 0.01) {
                DB::rollBack(); 
                return redirect()->route('admin.invoices.show', $invoice->invoice_id)
                    ->with('info', 'Tidak ada perubahan nominal. Penyesuaian tidak dibuat.');
            }
            
            $adjustmentType = $diff > 0 ? 'credit_note' : 'debit_note';
            $adjustmentAmount = abs($diff);
            $reasonDetails = [];
            $oldAdditionalTotal = $invoice->additionalCosts()->sum('amount');

            if (abs($oldAdditionalTotal - $totalAdditionalCosts) > 0.01) {
                $reasonDetails[] = "Biaya tambahan berubah dari Rp " . number_format($oldAdditionalTotal) . " menjadi Rp " . number_format($totalAdditionalCosts);
            }

            $finalReason = $validated['notes'];
            if (!empty($reasonDetails)) {
                $finalReason .= "\n\n[LOG SISTEM]:\n- " . implode("\n- ", $reasonDetails);
            }

            $adjustment = InvoiceAdjustment::create([
                'sales_invoice_id' => $invoice->invoice_id,
                'user_id' => Auth::id(),
                'adjustment_date' => now(),
                'type' => $adjustmentType,
                'amount' => $adjustmentAmount,
                'reason' => $finalReason,
            ]);
            
            $journalGroupId = "INV-ADJ-" . $adjustment->adjustment_id;
            $description = "Penyesuaian Otomatis Inv #" . $invoice->invoice_number;

            $debitEntries = [];
            $creditEntries = [];

            if ($adjustmentType === 'credit_note') {
                $debitEntries[] = [$salesReturnAccountId, $adjustmentAmount, $finalReason];
                $creditEntries[] = [$arAccountId, $adjustmentAmount, "Potongan Piutang Inv #" . $invoice->invoice_number];
            } else {
                $debitEntries[] = [$arAccountId, $adjustmentAmount, "Tambahan Piutang Inv #" . $invoice->invoice_number];
                $creditEntries[] = [$salesRevenueAccountId, $adjustmentAmount, $finalReason];
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

            $invoice->updatePaymentStatus();
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

    public function destroy(InvoiceAdjustment $invoiceAdjustment): RedirectResponse
    {
        $journalGroupId = "INV-ADJ-" . $invoiceAdjustment->adjustment_id;

        if ($error = $this->checkTransactionLock($invoiceAdjustment->adjustment_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus Penyesuaian: " . $error);
        }
        if ($invoiceAdjustment->type === 'debit_note' && str_contains($invoiceAdjustment->reason, 'Otomatis: Memindahkan kelebihan bayar')) {
            return back()->with('error', 'Gagal: Ini adalah Nota Debit otomatis. Untuk membatalkan, hapus Nota Kredit asli yang memicu pemindahan deposit ini.');
        }
        
        DB::beginTransaction();
        try {
            $invoiceId = $invoiceAdjustment->sales_invoice_id;
            $invoice = SalesInvoice::find($invoiceId);
            
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
                        $this->reverseAndClearJournal("INV-ADJ-" . $autoDebitNote->adjustment_id, "Reversal Overpayment Adj #" . $autoDebitNote->adjustment_id, $autoDebitNote);
                        $autoDebitNote->delete();
                    }
                    $ledgerEntry->delete();
                }
            }
            
            $this->reverseAndClearJournal("INV-ADJ-" . $invoiceAdjustment->adjustment_id, "Reversal Adj #" . $invoiceAdjustment->adjustment_id, $invoiceAdjustment);

            $invoiceAdjustment->delete();
            
            if ($invoice) {
                $invoice->updatePaymentStatus();
                
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

    private function handleOverpayment(SalesInvoice $invoice, ?InvoiceAdjustment $originalAdjustment, string $context = 'dibuat', string $overpaymentAction = 'deposit')
    {
        $invoice->refresh();
        $remainingBalance = $invoice->remaining_balance ?? 0;
        $realRemainingBalance = $invoice->total_due - $invoice->amount_paid;

        if ($realRemainingBalance < -0.01) { 
            
            if ($overpaymentAction === 'refund') {
                Log::info("Kelebihan bayar terdeteksi di Inv #{$invoice->invoice_id}. Dibiarkan untuk proses refund manual.");
                return; 
            }

            $overpaymentAmount = abs($realRemainingBalance);
            $client = $invoice->client; 
            if (!$client) {
                Log::warning("Gagal memindahkan kelebihan bayar Invoice #{$invoice->invoice_id}: Klien tidak ditemukan.");
                return;
            }

            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            if (!$arAccountId || !$clientDepositAccountId) {
                Log::error("Gagal proses overpayment Inv #{$invoice->invoice_id}: Akun AR atau Deposit Klien belum diatur.");
                throw new \Exception("Akun AR atau Deposit Klien belum diatur.");
            }

            $transDate = now()->format('Y-m-d');
            $refType = SalesInvoice::class; 
            $refId = $invoice->invoice_id;
            if ($originalAdjustment) {
                $refType = InvoiceAdjustment::class; 
                $refId = $originalAdjustment->adjustment_id;
                $transDate = $originalAdjustment->adjustment_date;
            }
            
            try {
                $ledgerEntry = ClientLedger::create([
                    'client_id' => $client->client_id,
                    'sales_invoice_id' => $invoice->invoice_id,
                    'reference_type' => $refType,
                    'reference_id' => $refId,
                    'transaction_date' => $transDate,
                    'type' => 'credit', 
                    'amount' => $overpaymentAmount,
                    'status' => 'available',
                    'description' => 'Otomatis: Kelebihan bayar dari Inv #' . $invoice->invoice_number,
                    'user_id' => Auth::id(),
                ]);

                $autoDebitNote = InvoiceAdjustment::create([
                    'sales_invoice_id' => $invoice->invoice_id,
                    'user_id' => Auth::id(),
                    'adjustment_date' => now(),
                    'type' => 'debit_note', 
                    'amount' => $overpaymentAmount,
                    'reason' => 'Otomatis: Memindahkan kelebihan bayar (Rp ' . number_format($overpaymentAmount) . ') ke deposit klien (Ledger ID: ' . $ledgerEntry->ledger_id . ')',
                ]);
                
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
                    $autoDebitNote,
                    Auth::id()
                );

                $invoice->updatePaymentStatus();
            } catch (\Exception $e) {
                Log::error('Gagal memproses overpayment adjustment invoice: ' . $e->getMessage());
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
                str_replace('INV-ADJ-', 'INV-ADJ-REV-', $journalGroupId), 
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