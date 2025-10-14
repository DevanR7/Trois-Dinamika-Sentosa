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

        // Gunakan nomor invoice asli agar cocok dengan callback Midtrans
        $orderId = $invoice->invoice_number;

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
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
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 🔹 Callback / Webhook dari Midtrans (atau simulasi dari Postman)
     */
    public function callback(Request $request)
    {
        Log::info('Midtrans Callback Received: ');

        try {
            // Deteksi apakah request berasal dari Midtrans atau dari Postman lokal
            $isFromMidtrans = $request->hasHeader('X-Callback-Signature') || str_contains($request->header('User-Agent', ''), 'Midtrans');
            if ($isFromMidtrans) {
                Log::info('Mode: Midtrans Real Callback');
            } else {
                Log::info('Mode: Local/Postman Testing');
            }

            // --- Ambil data dari request ---
            $transactionStatus = $request->input('transaction_status');
            $orderId           = $request->input('order_id') ?? $request->input('invoice_number');
            $transactionId     = $request->input('transaction_id');
            $paymentType       = $request->input('payment_type');
            $grossAmount       = (float) $request->input('gross_amount', 0);

            // --- Cari invoice berdasarkan order_id ---
            $invoice = SalesInvoice::where('invoice_number', $orderId)->first();

            if (!$invoice) {
                Log::error("Invoice tidak ditemukan untuk order_id: {$orderId}");
                return response()->json(['error' => 'Invoice not found'], 404);
            }

            Log::info("Invoice ditemukan: #{$invoice->invoice_id} ({$invoice->invoice_number})");

            // --- Proses status transaksi ---
            if (in_array($transactionStatus, ['capture', 'settlement'])) {
                DB::transaction(function () use ($invoice, $transactionId, $grossAmount, $paymentType) {
                    // Simpan ke tabel payments
                    Payment::create([
                        'invoice_id'      => $invoice->invoice_id,
                        'payment_date'    => now(),
                        'amount'          => $grossAmount,
                        'payment_method'  => 'payment_gateway', // fixed sesuai enum
                        'transaction_id'  => $transactionId,
                        'status'          => 'completed',
                        'notes'           => 'Auto processed from Midtrans callback',
                    ]);

                    // Hitung total pembayaran
                    $totalPaid = $invoice->payments()->sum('amount');
                    $status    = $totalPaid >= $invoice->total_amount ? 'paid' : 'partially_paid';

                    $invoice->update([
                        'amount_paid' => $totalPaid,
                        'status'      => $status,
                    ]);

                    Log::info("Invoice #{$invoice->invoice_id} updated: paid={$totalPaid}, status={$status}");
                });
            } elseif ($transactionStatus === 'pending') {
                $invoice->update(['status' => 'pending']);
            } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
                $invoice->update(['status' => 'cancelled']);
            }

            // --- Simpan log callback ke tabel payment_gateway_callbacks ---
            PaymentGatewayCallback::create([
                'invoice_id'            => $invoice->invoice_id,
                'vendor_transaction_id' => $transactionId ?? 'unknown',
                'status'                => $transactionStatus ?? 'unknown',
                'amount'                => $grossAmount ?? 0,
                'payment_type'          => $paymentType ?? 'unknown',
                'raw_response'          => $request->all(),
            ]);

            Log::info("Callback saved successfully for invoice #{$invoice->invoice_id}");

            // 💬 Tambahkan notifikasi dashboard (SweetAlert via session flash)
            session()->flash('success', '💰 Pembayaran berhasil diterima untuk ' . $invoice->invoice_number);

            return response()->json(['message' => 'Notification processed successfully.'], 200);
        } catch (Exception $e) {
            Log::error('Midtrans Callback Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
