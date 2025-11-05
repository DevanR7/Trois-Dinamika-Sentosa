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
    // (Anda bisa menambahkan middleware permission di sini nanti)

    /**
     * Tampilkan halaman PILIHAN (Manual vs Otomatis)
     */
    public function create(Request $request): View
    {
        $preselectedInvoiceId = $request->query('invoice_id');
        
        // ✅ Ambil invoice yang BISA disesuaikan (tidak dibatalkan)
        $invoices = SalesInvoice::where('status', '!=', 'cancelled')
            ->orderBy('order_date', 'desc')
            ->get();
            
        return view('invoice_adjustments.create', compact('invoices', 'preselectedInvoiceId'));
    }

    // ======================================================
    // ALUR 1: MANUAL
    // ======================================================

    /**
     * Tampilkan form input nominal MANUAL
     */
    public function createManual(SalesInvoice $invoice): View
    {
        return view('invoice_adjustments.create_manual', compact('invoice'));
    }

    /**
     * Simpan penyesuaian MANUAL
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
        if ($invoice->status == 'cancelled') {
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
            
            // ======================================================
            // ✅ PERBAIKAN: Panggil fungsi update status
            // ======================================================
            $invoice->updatePaymentStatus();
            // ======================================================

            DB::commit();

            return redirect()->route('invoices.show', $invoice->invoice_id)
                         ->with('success', 'Penyesuaian (Nota ' . ($validated['type'] == 'credit_note' ? 'Kredit' : 'Debit') . ') berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan penyesuaian: ' . $e->getMessage())->withInput();
        }
    }

    // ======================================================
    // ALUR 2: OTOMATIS (REVISI)
    // ======================================================

    /**
     * Tampilkan form revisi OTOMATIS
     * (Meniru SalesInvoiceController@edit)
     */
    public function createAuto(SalesInvoice $invoice): View
    {
        $invoice->load('items.product', 'taxes');
        
        $products = Product::orderBy('product_name')->get();
        $taxes = Tax::where('is_active', true)->get();
        $clients = null; // Tidak perlu
        $salesUsers = null; // Tidak perlu

        return view('invoice_adjustments.create_auto', compact('invoice', 'products', 'taxes', 'clients', 'salesUsers'));
    }

    /**
     * Simpan penyesuaian OTOMATIS
     */
    public function storeAuto(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        // 1. Validasi input
        $validated = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|numeric|min:1',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'taxes' => 'nullable|array',
            'taxes.*' => 'exists:taxes,id',
            'notes' => 'required|string|min:5|max:1000', // Alasan wajib diisi
        ]);

        DB::beginTransaction();
        try {
            // Load relasi lama untuk perbandingan
            $invoice->load('items.product', 'taxes');

            // 2. Hitung TOTAL BARU
            $subtotalProducts = 0;
            foreach ($validated['products'] as $p) {
                $product = Product::find($p['product_id']);
                $price = $product->selling_price ?? 0;
                $subtotalProducts += $price * $p['quantity'];
            }
            $discountRate = (float)($validated['discount_percentage'] ?? 0);
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
            
            // 3. Hitung Selisih
            $oldTotalAmount = $invoice->total_amount;
            $diff = $oldTotalAmount - $newTotalAmount; // (Lama - Baru)

            $adjustmentType = null;
            $adjustmentAmount = 0;

            if ($diff > 0.01) { // LAMA > BARU (Kelebihan tagih)
                $adjustmentType = 'credit_note';
                $adjustmentAmount = $diff;
            } elseif ($diff < -0.01) { // LAMA < BARU (Kurang tagih)
                $adjustmentType = 'debit_note';
                $adjustmentAmount = abs($diff);
            } else {
                return redirect()->route('invoices.show', $invoice->invoice_id)->with('info', 'Tidak ada perubahan nominal. Penyesuaian tidak dibuat.');
            }

            // ======================================================
            // ✅ 4. Buat Alasan Otomatis
            // ======================================================
            $reasonDetails = [];
            
            // Cek diskon global
            $oldDiscount = (float) $invoice->discount_percentage;
            $newDiscount = $discountRate;
            if (abs($oldDiscount - $newDiscount) > 0.001) {
                $reasonDetails[] = "Diskon global diubah dari {$oldDiscount}% menjadi {$newDiscount}%.";
            }

            // Cek item
            $oldItems = $invoice->items->keyBy('product_id');
            $newItems = collect($validated['products'])->mapWithKeys(function ($item) {
                return [$item['product_id'] => ['quantity' => (int) $item['quantity']]];
            });

            // Cek item yg diubah/dihapus
            foreach ($oldItems as $pid => $oldItem) {
                if (!$newItems->has($pid)) {
                    $reasonDetails[] = "Item DIHAPUS: " . Str::limit($oldItem->product->product_name, 20) . " (Qty {$oldItem->quantity}).";
                } elseif ($oldItem->quantity != $newItems[$pid]['quantity']) {
                    $reasonDetails[] = "Qty " . Str::limit($oldItem->product->product_name, 20) . " diubah: {$oldItem->quantity} -> {$newItems[$pid]['quantity']}.";
                }
            }
            // Cek item yg ditambah
            foreach ($newItems as $pid => $newItem) {
                if (!$oldItems->has($pid)) {
                    $productName = Product::find($pid)->product_name ?? 'Produk ??';
                    $reasonDetails[] = "Item DITAMBAH: " . Str::limit($productName, 20) . " (Qty {$newItem['quantity']}).";
                }
            }
            
            // Cek pajak
            $oldTaxes = $invoice->taxes->pluck('id')->sort()->values()->all();
            $newTaxes = collect($validated['taxes'] ?? [])->map(fn($id) => (int) $id)->sort()->values()->all();
            if ($oldTaxes !== $newTaxes) {
                $reasonDetails[] = "Komponen pajak diubah.";
            }

            // Gabungkan alasan
            $finalReason = $validated['notes']; // Alasan manual dari admin
            if (!empty($reasonDetails)) {
                $finalReason .= "\n\n[LOG SISTEM OTOMATIS]:\n- " . implode("\n- ", $reasonDetails);
            }
            // ======================================================

            // 5. Buat InvoiceAdjustment
            InvoiceAdjustment::create([
                'sales_invoice_id' => $invoice->invoice_id,
                'user_id' => Auth::id(),
                'adjustment_date' => now(),
                'type' => $adjustmentType,
                'amount' => $adjustmentAmount,
                'reason' => $finalReason, // <-- Gunakan alasan baru yang detail
            ]);

            $invoice->updatePaymentStatus();
            
            DB::commit();
            
            return redirect()->route('invoices.show', $invoice->invoice_id)
                         ->with('success', 'Koreksi otomatis berhasil. Nota ' . ($adjustmentType == 'credit_note' ? 'Kredit' : 'Debit') . ' senilai Rp ' . number_format($adjustmentAmount, 0, ',', '.') . ' telah dibuat.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan koreksi otomatis: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return back()->with('error', 'Gagal menyimpan koreksi otomatis: ' . $e->getMessage())->withInput();
        }
    }


    /**
     * Membatalkan penyesuaian (Sudah Benar)
     */
    public function destroy(InvoiceAdjustment $invoiceAdjustment)
    {
        DB::beginTransaction();
        try {
            $invoice_id = $invoiceAdjustment->sales_invoice_id;
            
            // Ambil invoice SEBELUM dihapus
            $invoice = SalesInvoice::find($invoice_id);

            // Hapus dokumen penyesuaian
            $invoiceAdjustment->delete();
            
            // ======================================================
            // ✅ PERBAIKAN: Panggil fungsi update status
            // ======================================================
            if ($invoice) {
                $invoice->updatePaymentStatus();
            }
            // ======================================================
            
            DB::commit();

            return redirect()->route('invoices.show', $invoice_id)
                         ->with('success', 'Penyesuaian invoice berhasil dibatalkan.');
                         
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan penyesuaian: ' . $e->getMessage());
        }
    }
}