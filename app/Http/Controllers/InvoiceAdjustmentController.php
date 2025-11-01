<?php

namespace App\Http\Controllers;

use App\Models\InvoiceAdjustment;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceAdjustmentController extends Controller
{

    public function __construct()
    {
        // Melindungi semua method di controller ini
        // (Anda bisa sesuaikan jika ada method 'index' atau 'show' nantinya)
        $this->middleware('permission:create-invoice-adjustments')
             ->only(['create', 'store']);
        
        // Jika Anda ingin admin bisa melihat daftar penyesuaian (nantinya)
        // $this->middleware('permission:view-invoice-adjustments')
        //      ->only(['index', 'show']); 
    }

    /**
     * Menampilkan form untuk membuat penyesuaian baru.
     */
    public function create(): View
    {
        // Ambil invoice yang belum dibatalkan
        $invoices = SalesInvoice::where('status', '!=', 'cancelled')
            ->orderBy('order_date', 'desc')
            ->get();
            
        return view('invoice_adjustments.create', compact('invoices'));
    }

    /**
     * Menyimpan penyesuaian baru dan membuat entri ledger.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_invoice_id' => 'required|exists:sales_invoices,invoice_id',
            'adjustment_date' => 'required|date',
            'type' => 'required|in:credit_note,debit_note',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $invoice = SalesInvoice::findOrFail($validated['sales_invoice_id']);
            $amount = (float) $validated['amount'];

            // 1. Buat dokumen Penyesuaian (Nota Kredit/Debit)
            InvoiceAdjustment::create([
                'sales_invoice_id' => $invoice->invoice_id,
                'user_id' => Auth::id(),
                'adjustment_date' => $validated['adjustment_date'],
                'type' => $validated['type'],
                'amount' => $amount,
                'reason' => $validated['reason'],
            ]);

            // 2. TIDAK ADA LOGIKA LEDGER. Selesai.
            // Perhitungan sisa tagihan akan otomatis ditangani
            // oleh accessor getRemainingBalanceAttribute di model SalesInvoice.
            
            $typeString = ($validated['type'] == 'credit_note') ? 'Kredit' : 'Debit';

            return redirect()->route('invoices.show', $invoice->invoice_id)
                         ->with('success', "Penyesuaian (Nota $typeString) berhasil disimpan.");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyimpan penyesuaian: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(InvoiceAdjustment $invoiceAdjustment)
    {
        // $this->authorize('delete', $invoiceAdjustment);

        try {
            // 1. Dapatkan invoice ID untuk redirect kembali
            $invoice_id = $invoiceAdjustment->sales_invoice_id;

            // 2. Hapus dokumen penyesuaian.
            // Sisa tagihan akan otomatis terkoreksi karena relasi 'adjustments' berubah.
            $invoiceAdjustment->delete();

            return redirect()->route('invoices.show', $invoice_id)
                         ->with('success', 'Penyesuaian invoice berhasil dibatalkan.');
                         
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membatalkan penyesuaian: ' . $e->getMessage());
        }
    }
}