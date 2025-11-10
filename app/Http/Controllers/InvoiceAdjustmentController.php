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

        // Ambil invoice yang belum dibatalkan untuk ditampilkan sebagai opsi
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
        ]);

        $invoice = SalesInvoice::findOrFail($validated['sales_invoice_id']);
        if ($invoice->status === 'cancelled') {
            return back()->with('error', 'Invoice yang sudah dibatalkan tidak dapat disesuaikan.');
        }

        DB::beginTransaction();
        try {
            InvoiceAdjustment::create([
                'sales_invoice_id' => $invoice->invoice_id,
                'user_id' => Auth::id(),
                'adjustment_date' => $validated['adjustment_date'],
                'type' => $validated['type'],
                'amount' => (float) $validated['amount'],
                'reason' => $validated['reason'],
            ]);

            // Perbarui status pembayaran invoice setelah penyesuaian
            $invoice->updatePaymentStatus();

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

        // Variabel ini disertakan untuk kompatibilitas dengan view, meski tidak digunakan
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
        ]);

        DB::beginTransaction();
        try {
            $invoice->load('items.product', 'taxes');

            // Hitung total baru berdasarkan input revisi
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

            // Tentukan jenis penyesuaian berdasarkan selisih
            if (abs($diff) <= 0.01) {
                return redirect()->route('invoices.show', $invoice->invoice_id)
                    ->with('info', 'Tidak ada perubahan nominal. Penyesuaian tidak dibuat.');
            }

            $adjustmentType = $diff > 0 ? 'credit_note' : 'debit_note';
            $adjustmentAmount = abs($diff);

            // Bangun alasan sistematis berdasarkan perubahan data
            $reasonDetails = [];

            // Perubahan diskon global
            $oldDiscount = (float) $invoice->discount_percentage;
            if (abs($oldDiscount - $discountRate) > 0.001) {
                $reasonDetails[] = "Diskon global diubah dari {$oldDiscount}% menjadi {$discountRate}%.";
            }

            // Perubahan item
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

            // Perubahan pajak
            $oldTaxes = $invoice->taxes->pluck('id')->sort()->values()->all();
            $newTaxes = collect($validated['taxes'] ?? [])->map(fn ($id) => (int) $id)->sort()->values()->all();
            if ($oldTaxes !== $newTaxes) {
                $reasonDetails[] = "Komponen pajak diubah.";
            }

            // Gabungkan alasan manual dan sistem
            $finalReason = $validated['notes'];
            if (!empty($reasonDetails)) {
                $finalReason .= "\n\n[LOG SISTEM OTOMATIS]:\n- " . implode("\n- ", $reasonDetails);
            }

            // Simpan penyesuaian
            InvoiceAdjustment::create([
                'sales_invoice_id' => $invoice->invoice_id,
                'user_id' => Auth::id(),
                'adjustment_date' => now(),
                'type' => $adjustmentType,
                'amount' => $adjustmentAmount,
                'reason' => $finalReason,
            ]);

            // Perbarui status pembayaran
            $invoice->updatePaymentStatus();

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
     * Membatalkan (menghapus) penyesuaian yang telah dibuat.
     */
    public function destroy(InvoiceAdjustment $invoiceAdjustment): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $invoiceId = $invoiceAdjustment->sales_invoice_id;
            $invoice = SalesInvoice::find($invoiceId);

            $invoiceAdjustment->delete();

            if ($invoice) {
                $invoice->updatePaymentStatus();
            }

            DB::commit();

            return redirect()->route('invoices.show', $invoiceId)
                ->with('success', 'Penyesuaian invoice berhasil dibatalkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan penyesuaian: ' . $e->getMessage());
        }
    }
}