<?php

namespace App\Http\Controllers;

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

class InvoiceAdjustmentController extends Controller
{
    /**
     * Menampilkan halaman pilihan metode penyesuaian: manual atau otomatis.
     */
    public function create(Request $request): View
    {
        $preselectedInvoiceId = $request->query('invoice_id');
        $invoices = SalesInvoice::where('status', '!=', 'cancelled')
            ->orderBy('order_date', 'desc')
            ->get();
        return view('invoice_adjustments.create', compact('invoices', 'preselectedInvoiceId'));
    }

    // ======================================================
    // ALUR 1: PENYESUAIAN MANUAL
    // ======================================================

    /**
     * Menampilkan formulir penyesuaian manual untuk invoice tertentu.
     */
    public function createManual(SalesInvoice $invoice): View
    {
        return view('invoice_adjustments.create_manual', compact('invoice'));
    }

    /**
     * Menyimpan penyesuaian manual (credit note atau debit note).
     */
    public function storeManual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sales_invoice_id' => 'required|exists:sales_invoices,invoice_id',
            'adjustment_date' => 'required|date',
            'type' => 'required|in:credit_note,debit_note',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:1000',
            'overpayment_action' => 'required|string|in:deposit,refund', // VALIDASI BARU
        ]);

        $invoice = SalesInvoice::findOrFail($validated['sales_invoice_id']);
        if ($invoice->status === 'cancelled') {
            return back()->with('error', 'Invoice yang sudah dibatalkan tidak dapat disesuaikan.');
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

            // 2. Perbarui status pembayaran invoice
            $invoice->updatePaymentStatus();

            // 3. PERUBAHAN: Ambil pilihan user dan tangani kelebihan bayar
            $overpaymentAction = $validated['overpayment_action'];
            $this->handleOverpayment($invoice, $adjustment, 'dibuat', $overpaymentAction);

            DB::commit();

            $noteType = $validated['type'] === 'credit_note' ? 'Kredit' : 'Debit';
            return redirect()->route('invoices.show', $invoice->invoice_id)
                ->with('success', "Penyesuaian (Nota {$noteType}) berhasil disimpan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan penyesuaian: ' . $e->getMessage())->withInput();
        }
    }

    // ======================================================
    // ALUR 2: PENYESUAIAN OTOMATIS (REVISI DETAIL INVOICE)
    // ======================================================

    /**
     * Menampilkan formulir revisi otomatis yang meniru tampilan edit invoice.
     */
    public function createAuto(SalesInvoice $invoice): View
    {
        $invoice->load('items.product', 'taxes');
        $products = Product::orderBy('product_name')->get();
        $taxes = Tax::where('is_active', true)->get();
        $clients = null;
        $salesUsers = null;

        return view('invoice_adjustments.create_auto', compact('invoice', 'products', 'taxes', 'clients', 'salesUsers'));
    }

    /**
     * Menyimpan penyesuaian otomatis berdasarkan perubahan struktur invoice.
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
            'overpayment_action' => 'required|string|in:deposit,refund', // VALIDASI BARU
        ]);

        DB::beginTransaction();
        try {
            $invoice->load('items.product', 'taxes');

            // Kalkulasi subtotal produk
            $subtotalProducts = 0;
            foreach ($validated['products'] as $item) {
                $product = Product::find($item['product_id']);
                $price = $product?->selling_price ?? 0;
                $subtotalProducts += $price * $item['quantity'];
            }

            // Kalkulasi diskon
            $discountRate = (float) ($validated['discount_percentage'] ?? 0);
            $discountAmount = $subtotalProducts * ($discountRate / 100);
            $subtotalAfterDiscount = $subtotalProducts - $discountAmount;

            // Kalkulasi pajak
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
                return redirect()->route('invoices.show', $invoice->invoice_id)
                    ->with('info', 'Tidak ada perubahan nominal. Penyesuaian tidak dibuat.');
            }

            $adjustmentType = $diff > 0 ? 'credit_note' : 'debit_note';
            $adjustmentAmount = abs($diff);

            // Buat log perubahan detail
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

            // 2. Perbarui status pembayaran
            $invoice->updatePaymentStatus();

            // 3. PERUBAHAN: Ambil pilihan user dan tangani kelebihan bayar
            $overpaymentAction = $validated['overpayment_action'];
            $this->handleOverpayment($invoice, $adjustment, 'dibuat', $overpaymentAction);

            DB::commit();

            $formattedAmount = number_format($adjustmentAmount, 0, ',', '.');
            $noteType = $adjustmentType === 'credit_note' ? 'Kredit' : 'Debit';
            return redirect()->route('invoices.show', $invoice->invoice_id)
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
     * ======================================================
     */
    public function destroy(InvoiceAdjustment $invoiceAdjustment): RedirectResponse
    {
        // --- PENCEGAHAN ---
        if ($invoiceAdjustment->type === 'debit_note' && str_contains($invoiceAdjustment->reason, 'Otomatis: Memindahkan kelebihan bayar')) {
            return back()->with('error', 'Gagal: Ini adalah Nota Debit otomatis. Untuk membatalkan, hapus Nota Kredit asli yang memicu pemindahan deposit ini.');
        }

        DB::beginTransaction();
        try {
            $invoiceId = $invoiceAdjustment->sales_invoice_id;
            $invoice = SalesInvoice::find($invoiceId);

            // --- LOGIKA REVERSAL (Membatalkan overpayment yg TERTULIS) ---
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
                        $autoDebitNote->delete();
                    }
                    $ledgerEntry->delete();
                }
            }

            // 5. Hapus penyesuaian yang diminta user
            $invoiceAdjustment->delete();
            
            // 6. Update status invoice (wajib untuk kalkulasi ulang)
            if ($invoice) {
                $invoice->updatePaymentStatus();
                
                // 7. PERUBAHAN: Tangani overpayment dengan default ke 'deposit' saat penghapusan
                $this->handleOverpayment($invoice, null, 'dihapus', 'deposit');
            }
            
            DB::commit();

            return redirect()->route('invoices.show', $invoiceId)
                             ->with('success', 'Penyesuaian invoice berhasil dibatalkan. Status utang dan deposit diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan penyesuaian: ' . $e->getMessage());
        }
    }

    /**
     * ======================================================
     * FUNGSI 'handleOverpayment' YANG DIPERBARUI UNTUK SALES INVOICES
     * ======================================================
     * @param SalesInvoice $invoice Invoice yang dicek
     * @param InvoiceAdjustment|null $originalAdjustment Penyesuaian asli (jika ada)
     * @param string $context Konteks ('dibuat' atau 'dihapus') untuk log
     * @param string $overpaymentAction Pilihan user ('deposit' atau 'refund')
     */
    private function handleOverpayment(SalesInvoice $invoice, ?InvoiceAdjustment $originalAdjustment, string $context = 'dibuat', string $overpaymentAction = 'deposit')
    {
        $invoice->refresh();
        $remainingBalance = $invoice->remaining_balance ?? 0;

        if ($remainingBalance < -0.01) { // Jika ada kelebihan bayar
            
            // ==========================================================
            // LOGIKA KONDISIONAL BARU - HANDLE REFUND
            // ==========================================================
            if ($overpaymentAction === 'refund') {
                // Pilihan B: Proses Refund Manual
                // Kita tidak melakukan apa-apa. Biarkan saldo invoice negatif.
                $adjustmentId = $originalAdjustment ? $originalAdjustment->adjustment_id : 'N/A';
                Log::info("Kelebihan bayar terdeteksi di Inv #{$invoice->invoice_id} (dari Adj. ID: {$adjustmentId}). Dibiarkan untuk proses refund manual.");
                return; // Hentikan fungsi
            }
            // ==========================================================

            // Pilihan A: Simpan sebagai Deposit (Lanjutkan logika lama)
            $overpaymentAmount = abs($remainingBalance);
            $client = $invoice->client; 

            if (!$client) {
                Log::warning("Gagal memindahkan kelebihan bayar Invoice #{$invoice->invoice_id}: Klien tidak ditemukan.");
                return;
            }

            // Tentukan data referensi
            $transDate = now()->format('Y-m-d');
            $descContext = "(saat penyesuaian $context)";
            $refType = SalesInvoice::class; // Default referensi ke Invoice...
            $refId = $invoice->invoice_id;

            if ($originalAdjustment) {
                // ...kecuali jika kita punya adjustment-nya
                $refType = InvoiceAdjustment::class; 
                $refId = $originalAdjustment->adjustment_id;
                $transDate = $originalAdjustment->adjustment_date;
                $descContext = "(dari Adj. ID: {$originalAdjustment->adjustment_id})";
            }

            try {
                // 1. Buat entri deposit (kredit) di Client Ledger
                $ledgerEntry = ClientLedger::create([
                    'client_id' => $client->client_id,
                    'sales_invoice_id' => $invoice->invoice_id,
                    'reference_type' => $refType,
                    'reference_id' => $refId,
                    'transaction_date' => $transDate,
                    'type' => 'credit', // Menambah deposit klien
                    'amount' => $overpaymentAmount,
                    'status' => 'available',
                    'description' => 'Otomatis: Kelebihan bayar dari Inv #' . $invoice->invoice_number . ' ' . $descContext,
                    'user_id' => Auth::id(),
                ]);

                // 2. Buat penyesuaian "lawan" (debit note) untuk menolkan saldo Invoice
                InvoiceAdjustment::create([
                    'sales_invoice_id' => $invoice->invoice_id,
                    'user_id' => Auth::id(),
                    'adjustment_date' => now(),
                    'type' => 'debit_note', // Menambah tagihan (untuk menetralkan minus)
                    'amount' => $overpaymentAmount,
                    'reason' => 'Otomatis: Memindahkan kelebihan bayar (Rp ' . number_format($overpaymentAmount) . ') ke deposit klien (Ledger ID: ' . $ledgerEntry->ledger_id . ')',
                ]);

                // 3. Update status invoice terakhir kali untuk menolkan saldo
                $invoice->updatePaymentStatus();

            } catch (\Exception $e) {
                Log::error('Gagal memproses overpayment adjustment invoice: ' . $e->getMessage());
                throw $e; 
            }
        }
    }
}