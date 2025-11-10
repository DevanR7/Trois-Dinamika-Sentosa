<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Models\ClientLedger;
use App\Models\Client;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * Simpan pembayaran baru ke database.
     */
    public function store(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        // 1. Validasi dasar
        $rules = [
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method_id' => [
                Rule::requiredIf(fn() => $request->input('amount', 0) > 0 || !$request->has('use_credit')),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
            'company_bank_account_id' => [
                Rule::requiredIf(fn() => $request->input('amount', 0) > 0 || !$request->has('use_credit')),
                'nullable',
                'exists:company_bank_accounts,company_bank_account_id',
            ],
            'notes' => 'nullable|string',
            'use_credit' => 'nullable|boolean',
        ];

        // 2. Validasi dinamis berdasarkan konfigurasi metode pembayaran
        $paymentMethod = $request->filled('payment_method_id')
            ? PaymentMethod::find($request->input('payment_method_id'))
            : null;

        if ($paymentMethod) {
            $config = $paymentMethod->required_fields_config;

            $rules['proof_of_payment'] =
                in_array($config, ['proof_only', 'proof_and_reference'])
                ? 'required|image|mimes:jpeg,png,jpg|max:2048'
                : 'nullable|image|mimes:jpeg,png,jpg|max:2048';

            $rules['reference_number'] =
                in_array($config, ['reference_only', 'proof_and_reference'])
                ? 'required|string|max:255'
                : 'nullable|string|max:255';
        } else {
            // Fallback untuk tanpa metode pembayaran (mis. pembayaran via kredit)
            $rules['proof_of_payment'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048';
            $rules['reference_number'] = 'nullable|string|max:255';
        }

        // 3. Jalankan validasi
        $validated = $request->validate($rules);

        $client = $invoice->client;
        $danaDariInput = (float) ($validated['amount'] ?? 0);
        $pakaiKredit = $validated['use_credit'] ?? false;
        $kreditAwalKlien = $client->balance;
        $sisaTagihan = $invoice->remaining_balance;

        $catatanLog = $validated['notes'] ?? '';
        $kreditAkanDigunakan = 0;
        $danaInputAkanDigunakan = 0;
        $sisaDanaInput = 0;

        DB::beginTransaction();

        try {
            // 4. Hitung alokasi dana
            if ($pakaiKredit && $kreditAwalKlien > 0) {
                $kreditAkanDigunakan = min($kreditAwalKlien, $sisaTagihan);
            }

            $sisaTagihanSetelahKredit = max(0, $sisaTagihan - $kreditAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahKredit);
            $totalPembayaran = $kreditAkanDigunakan + $danaInputAkanDigunakan;
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            if ($totalPembayaran <= 0.01 && $sisaDanaInput <= 0.01 && $sisaTagihan > 0.01) {
                throw new \Exception("Tidak ada dana (input/kredit) yang dialokasikan.");
            }

            // 5. Siapkan metadata pembayaran
            $paymentMethodName = $paymentMethod->name ?? 'N/A';
            $paymentMethodType = $paymentMethod->type ?? 'direct';

            $metodeLog = $paymentMethodName;
            if ($kreditAkanDigunakan > 0) {
                $metodeLog = $danaInputAkanDigunakan > 0
                    ? 'Kredit Klien + ' . $paymentMethodName
                    : 'Kredit Klien';
            }

            if (!empty($catatanLog)) {
                $catatanLog .= ' | ';
            }

            $newPaymentStatus = $paymentMethodType === 'pending'
                ? 'pending_clearance'
                : 'completed';

            // 6. Upload bukti pembayaran
            $proofPath = $request->hasFile('proof_of_payment')
                ? $request->file('proof_of_payment')->store('payment_proofs', 'public')
                : null;

            // 7. Simpan pembayaran
            $payment = $invoice->payments()->create([
                'amount' => $totalPembayaran,
                'payment_date' => $validated['payment_date'],
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                'status' => $newPaymentStatus,
                'notes' => $validated['notes'] ?? null,
                'received_by_user_id' => Auth::id(),
                'proof_of_payment_path' => $proofPath,
                'reference_number' => $validated['reference_number'] ?? null,
            ]);

            // 8. Catat transaksi ke ClientLedger
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

                $catatanLog .= 'Credit used: ' . number_format($kreditAkanDigunakan);
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

                $catatanLog .= '. Overpayment: ' . number_format($sisaDanaInput) . ' returned to credit.';
            }

            // 9. Update catatan dan status invoice
            $payment->update(['notes' => $catatanLog]);
            $invoice->updatePaymentStatus();

            DB::commit();

            return redirect()
                ->route('invoices.show', $invoice->invoice_id)
                ->with('success', 'Pembayaran berhasil dicatat. Total: Rp ' . number_format($totalPembayaran));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mencatat pembayaran: ' . $e->getMessage());

            return back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Menyetujui pembayaran yang sedang diverifikasi.
     */
    public function approve(Payment $payment): RedirectResponse
    {
        if ($payment->status !== 'pending_verification') {
            return back()->with('error', 'Pembayaran ini tidak sedang dalam status verifikasi.');
        }

        DB::beginTransaction();
        try {
            $payment->update([
                'status' => 'completed',
                'received_by_user_id' => Auth::id(),
            ]);

            $invoice = $payment->salesInvoice;
            $invoice->updatePaymentStatus();

            DB::commit();
            return back()->with('success', 'Pembayaran berhasil disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyetujui pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Menolak pembayaran yang masih diverifikasi.
     */
    public function reject(Payment $payment): RedirectResponse
    {
        if ($payment->status !== 'pending_verification') {
            return back()->with('error', 'Pembayaran ini tidak sedang dalam status verifikasi.');
        }

        $payment->update([
            'status' => 'failed',
            'received_by_user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Bukti pembayaran telah ditolak.');
    }
}
