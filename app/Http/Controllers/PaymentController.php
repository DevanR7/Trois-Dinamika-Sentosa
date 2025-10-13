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
    public function store(Request $request, SalesInvoice $invoice): RedirectResponse
{
    // 1. Validasi dasar
    $validated = $request->validate([
        'amount' => 'required|numeric|min:0.01',
        'payment_date' => 'required|date',
        'payment_method' => 'required|string', // Dibuat lebih fleksibel
        'notes' => 'nullable|string',
    ]);

    // Invoice sudah otomatis diambil dari URL melalui Route Model Binding
    $sisaTagihan = $invoice->total_amount - $invoice->amount_paid;

    if ($invoice->status === 'cancelled') {
        return back()->with('error', 'Invoice ini sudah dibatalkan.');
    }

    // 2. Validasi custom agar tidak kelebihan bayar
    if ($validated['amount'] > $sisaTagihan) {
        return back()->with('error', 'Jumlah pembayaran melebihi sisa tagihan.');
    }

    try {
        DB::beginTransaction();

        // 3. Catat pembayaran di tabel 'payments'
        $invoice->payments()->create([
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'],
            'received_by_user_id' => Auth::id(),
            'status' => 'completed', // Default status untuk pembayaran
        ]);

        // 4. Update total yang sudah dibayar di invoice terkait
        $totalPaid = $invoice->payments()->sum('amount');
        
        // 5. Cek dan update status invoice
        if ($totalPaid >= $invoice->total_amount) {
            $status = 'paid';
        } elseif ($totalPaid > 0) {
            $status = 'partially_paid';
        } else {
            $status = 'unpaid';
        }
        
        $invoice->update([
            'amount_paid' => $totalPaid,
            'status' => $status,
        ]);

        DB::commit();

        return redirect()->route('invoices.show', $invoice->invoice_id)
             ->with('success', 'Pembayaran untuk #' . $invoice->invoice_number . ' berhasil dicatat.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
    }
    }

    public function approve(Payment $payment): RedirectResponse
{
    if ($payment->status !== 'pending_verification') {
        return back()->with('error', 'Pembayaran ini tidak sedang dalam status verifikasi.');
    }

    try {
        DB::beginTransaction();

        // 1. Ubah status pembayaran menjadi 'completed'
        $payment->update(['status' => 'completed', 'received_by_user_id' => Auth::id()]);

        // 2. Update invoice terkait
        $invoice = $payment->salesInvoice;
        $invoice->amount_paid += $payment->amount;

        // 3. Cek dan update status invoice
        if ($invoice->amount_paid >= $invoice->total_amount) {
            $invoice->status = 'paid';
        } else {
            $invoice->status = 'partially_paid';
        }
        $invoice->save();

        DB::commit();
        return back()->with('success', 'Pembayaran berhasil disetujui.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal menyetujui pembayaran: ' . $e->getMessage());
    }
}

/**
 * Menolak pembayaran yang sedang diverifikasi.
 */
public function reject(Payment $payment): RedirectResponse
{
    if ($payment->status !== 'pending_verification') {
        return back()->with('error', 'Pembayaran ini tidak sedang dalam status verifikasi.');
    }

    // Cukup ubah status pembayaran menjadi 'failed'
    $payment->update(['status' => 'failed', 'received_by_user_id' => Auth::id()]);

    return back()->with('success', 'Bukti pembayaran telah ditolak.');
}
}