<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Client;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        // Validasi
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0', // Boleh 0 jika bayar pakai kredit
            'payment_date' => 'required|date',
            'payment_method' => [
                // Wajib KECUALI jika amount 0 DAN use_credit dicentang
                Rule::requiredIf(function () use ($request) {
                    return $request->input('amount', 0) > 0 || !$request->has('use_credit');
                }),
                'string',
                'nullable',
            ],
            'notes' => 'nullable|string',
            'use_credit' => 'nullable|boolean', // Ambil data checkbox
        ]);

        $client = $invoice->client;
        $danaDariInput = (float)($validated['amount'] ?? 0);
        $pakaiKredit = $validated['use_credit'] ?? false;
        $kreditAwalKlien = (float)($client->credit_balance ?? 0);
        $totalRetur = $invoice->returns->sum('total_amount');
        $sisaTagihan = $invoice->total_amount - $invoice->amount_paid - $totalRetur;
        
        $kreditAkanDigunakan = 0;
        $danaInputAkanDigunakan = 0;
        $sisaDanaInput = 0;
        $metodeLog = $validated['payment_method'] ?? 'N/A';
        $catatanLog = $validated['notes'] ?? '';

        DB::beginTransaction();
        try {
            // 1. Hitung alokasi dana
            if ($pakaiKredit && $kreditAwalKlien > 0) {
                // Berapa kredit yg bisa dipakai (maksimal sisa tagihan)
                $kreditAkanDigunakan = min($kreditAwalKlien, $sisaTagihan);
            }

            // Berapa sisa tagihan setelah ditutup kredit
            $sisaTagihanSetelahKredit = max(0, $sisaTagihan - $kreditAkanDigunakan);
            
            // Berapa dana input yg akan dipakai (maksimal sisa tagihan setelah kredit)
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahKredit);

            // Hitung total pembayaran
            $totalPembayaran = $kreditAkanDigunakan + $danaInputAkanDigunakan;
            
            // Hitung sisa dana input (jika ada overpayment)
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            // Cek jika tidak ada pembayaran sama sekali
            if ($totalPembayaran <= 0.01) {
                 throw new \Exception("Tidak ada dana (input/kredit) yang dialokasikan.");
            }

            // 2. Tentukan log metode pembayaran
            if ($kreditAkanDigunakan > 0) {
                $metodeLog = ($danaInputAkanDigunakan > 0) ? 'Kredit + ' . $validated['payment_method'] : 'Kredit Klien';
            }
            if (!empty($catatanLog)) $catatanLog .= " | ";
            $catatanLog .= "Auto-processed payment. Credit used: " . number_format($kreditAkanDigunakan) . ". Input used: " . number_format($danaInputAkanDigunakan);


            // 3. Proses Database
            // Kurangi kredit klien
            if ($kreditAkanDigunakan > 0) {
                $client->decrement('credit_balance', $kreditAkanDigunakan);
            }
            
            // Tambahkan sisa dana input (overpayment) ke kredit
            if ($sisaDanaInput > 0.01) {
                $client->increment('credit_balance', $sisaDanaInput);
                 $catatanLog .= ". Overpayment: " . number_format($sisaDanaInput) . " returned to credit.";
            }

            // 4. Catat pembayaran di tabel 'payments'
            $invoice->payments()->create([
                'amount' => $totalPembayaran, // Catat total yg dialokasikan
                'payment_date' => $validated['payment_date'],
                'payment_method' => $metodeLog,
                'notes' => $catatanLog,
                'received_by_user_id' => Auth::id(),
                'status' => 'completed',
            ]);

            // 5. Update total yang sudah dibayar di invoice terkait
            $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');
            
            // 6. Cek dan update status invoice
            $status = 'unpaid';
            if ($totalPaid >= ($invoice->total_amount - $totalRetur - 0.01)) { // Toleransi pembulatan
                $status = 'paid';
            } elseif ($totalPaid > 0) {
                $status = 'partially_paid';
            }
            
            $invoice->update([
                'amount_paid' => $totalPaid,
                'status' => $status,
            ]);

            DB::commit();

            return redirect()->route('invoices.show', $invoice->invoice_id)
                         ->with('success', 'Pembayaran berhasil dicatat. Total: Rp ' . number_format($totalPembayaran));

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