<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validasi dasar
        $validated = $request->validate([
            'invoice_id' => 'required|exists:sales_invoices,invoice_id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer',
            'notes' => 'nullable|string',
        ]);

        $invoice = SalesInvoice::findOrFail($validated['invoice_id']);
        $sisaTagihan = $invoice->total_amount - $invoice->amount_paid;

        if ($invoice->status === 'cancelled') {
        return redirect()->route('invoices.show', $invoice->invoice_id)
                     ->with('error', 'Invoice ini sudah dibatalkan dan tidak bisa menerima pembayaran.');
    }

        // 2. Validasi custom untuk mencegah kelebihan bayar
        if ($validated['amount'] > $sisaTagihan) {
            return redirect()->route('invoices.show', $invoice->invoice_id)
                         ->with('error', 'Jumlah pembayaran melebihi sisa tagihan (Rp ' . number_format($sisaTagihan, 0, ',', '.') . ').');
        }

        try {
            DB::beginTransaction();

            // 3. Tambahkan data user yang mencatat pembayaran
            $validated['received_by_user_id'] = Auth::id();

            // 4. Catat pembayaran di tabel 'payments'
            Payment::create($validated);

            // 5. Update total yang sudah dibayar di invoice terkait
            $invoice->amount_paid += $validated['amount'];

            // 6. Cek dan update status invoice
            if ($invoice->amount_paid >= $invoice->total_amount) {
                $invoice->status = 'paid';
            } elseif ($invoice->amount_paid > 0) {
                $invoice->status = 'partially_paid';
            } else {
                $invoice->status = 'unpaid';
            }
            $invoice->save();

            DB::commit();

            return redirect()->route('invoices.index')
                 ->with('success', 'Pembayaran untuk #' . $invoice->invoice_number . ' berhasil dicatat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('invoices.show', $request->invoice_id)
                         ->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
    }
}