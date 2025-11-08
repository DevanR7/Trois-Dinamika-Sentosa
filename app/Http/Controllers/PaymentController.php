<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Models\ClientLedger;
use App\Models\Client;
use App\Models\PaymentMethod; // ✅ Pastikan ini ada
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log; // ✅ Pastikan ini ada

class PaymentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        // 1. Validasi
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method_id' => [ // ✅ Gunakan payment_method_id
                Rule::requiredIf(function () use ($request) {
                    return $request->input('amount', 0) > 0 || !$request->has('use_credit');
                }),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
            'company_bank_account_id' => 'required|exists:company_bank_accounts,company_bank_account_id',
            'notes' => 'nullable|string',
            'use_credit' => 'nullable|boolean',
        ]);

        $client = $invoice->client;
        $danaDariInput = (float)($validated['amount'] ?? 0);
        $pakaiKredit = $validated['use_credit'] ?? false;
        $kreditAwalKlien = $client->balance; 
        $sisaTagihan = $invoice->remaining_balance; 
        
        $kreditAkanDigunakan = 0;
        $danaInputAkanDigunakan = 0;
        $sisaDanaInput = 0;
        $catatanLog = $validated['notes'] ?? '';

        DB::beginTransaction();
        try {
            // 2. Hitung alokasi dana
            if ($pakaiKredit && $kreditAwalKlien > 0) {
                $kreditAkanDigunakan = min($kreditAwalKlien, $sisaTagihan);
            }
            $sisaTagihanSetelahKredit = max(0, $sisaTagihan - $kreditAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahKredit);
            $totalPembayaran = $kreditAkanDigunakan + $danaInputAkanDigunakan;
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            if ($totalPembayaran <= 0.01 && $sisaDanaInput <= 0.01) {
                 if ($sisaTagihan > 0.01) {
                     throw new \Exception("Tidak ada dana (input/kredit) yang dialokasikan.");
                 }
            }

            // 3. Tentukan log metode pembayaran & status
            $paymentMethodName = 'N/A';
            $paymentMethodType = 'direct';
            if (!empty($validated['payment_method_id'])) {
                $method = PaymentMethod::find($validated['payment_method_id']);
                if ($method) {
                    $paymentMethodName = $method->name;
                    $paymentMethodType = $method->type;
                }
            }

            $metodeLog = $paymentMethodName;
            if ($kreditAkanDigunakan > 0) {
                $metodeLog = ($danaInputAkanDigunakan > 0) ? 'Kredit Klien + ' . $paymentMethodName : 'Kredit Klien';
            }
            if (!empty($catatanLog)) $catatanLog .= " | ";
            
            // ✅ Tentukan status baru (untuk Giro/Cek)
            $newPaymentStatus = ($paymentMethodType == 'pending') ? 'pending_clearance' : 'completed';

            // 4. Catat pembayaran di tabel 'payments'
            $payment = $invoice->payments()->create([
            'amount' => $totalPembayaran,
            'payment_date' => $validated['payment_date'],
            'payment_method_id' => $validated['payment_method_id'],
            'company_bank_account_id' => $validated['company_bank_account_id'], // ✅ Simpan
            'status' => $newPaymentStatus,
            'notes' => $validated['notes'],
            'received_by_user_id' => Auth::id(),
        ]);

            // 5. Proses Database (Ledger)
            if ($kreditAkanDigunakan > 0) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit',
                    'amount' => -$kreditAkanDigunakan,
                    'status' => 'available',
                    'description' => 'Digunakan untuk membayar Invoice #' . $invoice->invoice_number,
                    'user_id' => Auth::id(),
                ]);
                $catatanLog .= " Credit used: " . number_format($kreditAkanDigunakan);
            }
            
            if ($sisaDanaInput > 0.01) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit',
                    'amount' => $sisaDanaInput,
                    'status' => 'available',
                    'description' => 'Kelebihan bayar dari Invoice #' . $invoice->invoice_number,
                    'user_id' => Auth::id(),
                ]);
                $catatanLog .= ". Overpayment: " . number_format($sisaDanaInput) . " returned to credit.";
            }

            // Update catatan log di payment
            $payment->update(['notes' => $catatanLog]);

            // ======================================================
            // ✅ 6. Panggil fungsi update status dari Model
            // ======================================================
            $invoice->updatePaymentStatus(); 
            
            DB::commit();

            return redirect()->route('invoices.show', $invoice->invoice_id)
                         ->with('success', 'Pembayaran berhasil dicatat. Total: Rp ' . number_format($totalPembayaran));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mencatat pembayaran: ' . $e->getMessage());
            return back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Menyetujui pembayaran yang sedang diverifikasi (dari klien).
     */
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
            
            // ✅ PANGGIL FUNGSI INI
            $invoice->updatePaymentStatus();

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