<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Models\ClientLedger;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
        
        // ✅ DIUBAH: Baca dari accessor 'balance' baru
        $kreditAwalKlien = $client->balance; 

        // ====================================================================
        // ✅✅ INI ADALAH PERBAIKAN UTAMA UNTUK BUG ANDA ✅✅
        // ====================================================================
        
        // HAPUS BARIS LAMA:
        // $totalRetur = $invoice->returns->sum('total_amount');
        // $sisaTagihan = $invoice->total_amount - $invoice->amount_paid - $totalRetur;
        
        // GANTI DENGAN INI:
        // Gunakan accessor yang sudah benar (hanya menghitung retur potong tagihan)
        $sisaTagihan = $invoice->remaining_balance; 
        
        // ====================================================================
        
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
            if ($totalPembayaran <= 0.01 && $sisaDanaInput <= 0.01) {
                 // Izinkan jika sisa tagihan 0 atau kurang (untuk kasus overpayment)
                 if ($sisaTagihan > 0.01) {
                     throw new \Exception("Tidak ada dana (input/kredit) yang dialokasikan.");
                 }
            }

            // 2. Tentukan log metode pembayaran
            if ($kreditAkanDigunakan > 0) {
                $metodeLog = ($danaInputAkanDigunakan > 0) ? 'Kredit + ' . $validated['payment_method'] : 'Kredit Klien';
            }
            if (!empty($catatanLog)) $catatanLog .= " | ";
            // (Catatan log akan di-update di bawah)

            // 3. Catat pembayaran di tabel 'payments' DULU
            $payment = $invoice->payments()->create([
                'amount' => $totalPembayaran, // Catat total yg dialokasikan
                'payment_date' => $validated['payment_date'],
                'payment_method' => $metodeLog,
                'notes' => $validated['notes'], // Catatan awal
                'received_by_user_id' => Auth::id(),
                'status' => 'completed',
            ]);

            // 4. Proses Database (Ledger)
            if ($kreditAkanDigunakan > 0) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit',
                    'amount' => -$kreditAkanDigunakan, // Jumlah negatif (debit)
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
                    'status' => 'available', // <-- Kelebihan bayar selalu available
                    'description' => 'Kelebihan bayar dari Invoice #' . $invoice->invoice_number,
                    'user_id' => Auth::id(),
                ]);
                $catatanLog .= ". Overpayment: " . number_format($sisaDanaInput) . " returned to credit.";
            }

            // Update catatan log di payment
            $payment->update(['notes' => $catatanLog]);

            // 5. Update total yang sudah dibayar di invoice terkait
            $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');
            
            // 6. Cek dan update status invoice
            // Ambil ulang data retur potong tagihan
            $totalReturDipotong = $invoice->deductingReturns()->sum('total_amount');
            $sisaTagihanSetelahBayar = $invoice->total_amount - $totalPaid - $totalReturDipotong;

            $status = 'unpaid';
            if ($sisaTagihanSetelahBayar <= 0.01) { // Toleransi pembulatan
                $status = 'paid';
            } elseif ($totalPaid > 0) {
                $status = 'partially_paid';
            }
            
            // Update amount_paid dan status
            $invoice->update([
                'amount_paid' => $totalPaid,
                'status' => $status,
            ]);

            if ($status == 'paid') {
                ClientLedger::where('sales_invoice_id', $invoice->invoice_id)
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'available',
                                'description' => DB::raw("REPLACE(description, ' (Ditahan)', '')") // Hapus tanda (Ditahan)
                            ]);
            }

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
            // Ambil total bayar dan retur potong tagihan
            $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');
            $totalReturDipotong = $invoice->deductingReturns()->sum('total_amount');

            // 3. Cek dan update status invoice
            $sisaTagihan = $invoice->total_amount - $totalPaid - $totalReturDipotong;
            if ($sisaTagihan <= 0.01) {
                $invoice->status = 'paid';
            } else {
                $invoice->status = 'partially_paid';
            }
            $invoice->amount_paid = $totalPaid; // Update total amount_paid
            $invoice->save();

            if ($invoice->status == 'paid') {
                ClientLedger::where('sales_invoice_id', $invoice->invoice_id)
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'available',
                                'description' => DB::raw("REPLACE(description, ' (Ditahan)', '')")
                            ]);
            }

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