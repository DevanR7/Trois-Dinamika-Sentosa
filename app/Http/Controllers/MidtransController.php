<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Models\PaymentGatewayCallback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;
use Illuminate\Support\Facades\Auth;
use Exception;

class MidtransController extends Controller
{
    public function __construct()
    {
        // Set konfigurasi Midtrans dari config/midtrans.php
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * 🔹 Generate Snap Token & Redirect to Payment Popup
     */
    public function pay(Request $request, SalesInvoice $invoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $amount = $request->input('amount');
        $client = Auth::guard('client')->user();

        // ====================================================================
        // PERUBAHAN KUNCI #1: Buat order_id yang unik untuk setiap transaksi
        // ====================================================================
        // Ini memungkinkan klien membayar invoice yang sama berkali-kali (cicilan).
        $uniqueOrderId = $invoice->invoice_number . '-' . time();

        $params = [
            'transaction_details' => [
                'order_id' => $uniqueOrderId, // Gunakan ID yang unik
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => $client->client_name,
                'email' => $client->email,
                'phone' => $client->phone_number,
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            return response()->json(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Error: ' . $e->getMessage());
            // Berikan pesan error yang lebih jelas ke front-end
            return response()->json(['message' => 'Gagal memulai sesi pembayaran. Silakan coba lagi nanti.'], 500);
        }
    }

    /**
     * 🔹 Callback / Webhook dari Midtrans
     */
    public function callback(Request $request)
    {
        Log::info('Midtrans Callback Received: ');

        try {
            // Verifikasi notifikasi dari Midtrans (lebih aman)
            $notification = new Notification();

            $transactionStatus = $notification->transaction_status;
            $rawOrderId        = $notification->order_id;
            $transactionId     = $notification->transaction_id;
            $paymentType       = $notification->payment_type;
            $grossAmount       = (float) $notification->gross_amount;

            // ====================================================================
            // PERUBAHAN KUNCI #2: Ekstrak nomor invoice asli dari order_id
            // ====================================================================
            $orderIdParts = explode('-', $rawOrderId);
            array_pop($orderIdParts); // Hapus bagian terakhir (timestamp)
            $originalInvoiceNumber = implode('-', $orderIdParts); // Gabungkan kembali sisanya

            // --- Cari invoice berdasarkan nomor invoice asli ---
            $invoice = SalesInvoice::where('invoice_number', $originalInvoiceNumber)->first();

            if (!$invoice) {
                Log::error("Invoice tidak ditemukan untuk order_id: {$rawOrderId} (Parsed as: {$originalInvoiceNumber})");
                return response()->json(['error' => 'Invoice not found'], 404);
            }

            Log::info("Invoice ditemukan: #{$invoice->invoice_id} ({$invoice->invoice_number})");
            
            // --- Cek duplikasi callback berdasarkan transaction_id ---
            $isProcessed = PaymentGatewayCallback::where('vendor_transaction_id', $transactionId)->exists();
            if ($isProcessed) {
                Log::warning("Callback untuk transaction_id: {$transactionId} sudah pernah diproses.");
                return response()->json(['message' => 'Notification already processed.'], 200);
            }

            // --- Simpan log callback terlebih dahulu ---
            PaymentGatewayCallback::create([
                'invoice_id'            => $invoice->invoice_id,
                'vendor_transaction_id' => $transactionId,
                'status'                => $transactionStatus,
                'amount'                => $grossAmount,
                'payment_type'          => $paymentType,
                'raw_response'          => $request->all(),
            ]);

            // --- Proses status transaksi (HANYA JIKA BERHASIL) ---
            if (in_array($transactionStatus, ['capture', 'settlement'])) {
                DB::transaction(function () use ($invoice, $transactionId, $grossAmount, $paymentType) {
                    
                    // Cek lagi apakah pembayaran dengan transaction_id ini sudah ada untuk mencegah double entry
                    $existingPayment = Payment::where('transaction_id', $transactionId)->first();
                    if ($existingPayment) {
                        Log::warning("Payment with transaction_id {$transactionId} already exists.");
                        return; // Keluar dari transaksi jika sudah ada
                    }

                    Payment::create([
                        'invoice_id'            => $invoice->invoice_id,
                        'payment_date'          => now(),
                        'amount'                => $grossAmount,
                        'payment_method'        => $paymentType, // Lebih dinamis, mencatat metode yg dipakai
                        'transaction_id'        => $transactionId,
                        'status'                => 'completed',
                        'notes'                 => 'Auto processed from Midtrans callback.',
                    ]);
                    
                    // Refresh relasi untuk mendapatkan data pembayaran terbaru
                    $invoice->load('payments');
                    
                    // Hitung total pembayaran yang sudah 'completed'
                    $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');
                    $status    = ($totalPaid >= $invoice->total_amount) ? 'paid' : 'partially_paid';

                    $invoice->update([
                        'amount_paid' => $totalPaid,
                        'status'      => $status,
                    ]);

                    Log::info("Invoice #{$invoice->invoice_id} updated: paid={$totalPaid}, status={$status}");
                });
            }
            
            return response()->json(['message' => 'Notification processed successfully.'], 200);

        } catch (Exception $e) {
            Log::error('Midtrans Callback Error: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}