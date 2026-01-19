<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\BulkSalesPayment;
use App\Models\ClientLedger;
use App\Models\PaymentMethod;
use App\Models\CompanyBankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Exception;

class ClientPaymentController extends Controller
{
    public function __construct()
    {
        // Konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * Client melakukan pembayaran Single Invoice via Midtrans
     */
    public function payMidtrans(Request $request, SalesInvoice $invoice)
    {
        $client = Auth::guard('client')->user();

        // 1. Security Check: Pastikan invoice milik client yang login
        if ($invoice->client_id !== $client->client_id) {
            return response()->json(['message' => 'Unauthorized Access'], 403);
        }

        // 2. Cek Saldo Kredit (Jika Client ingin pakai deposit)
        $useCredit = $request->boolean('use_credit');
        $sisaTagihan = $invoice->remaining_balance;
        $clientBalance = $client->balance;
        
        $creditToUse = 0;
        if ($useCredit && $clientBalance > 0) {
            $creditToUse = min($clientBalance, $sisaTagihan);
        }

        // 3. Hitung yang harus dibayar ke Midtrans
        $grossAmount = round($sisaTagihan - $creditToUse);

        // Validasi: Jika lunas pakai saldo (tanpa Midtrans)
        if ($grossAmount <= 0) {
            // Sebaiknya arahkan ke controller internal untuk proses pelunasan via deposit
            // Di sini kita return status agar frontend me-redirect ke endpoint pelunasan manual/deposit
            return response()->json([
                'status' => 'paid_by_credit', 
                'message' => 'Tagihan akan dilunasi sepenuhnya menggunakan saldo deposit.'
            ]);
        }

        if ($grossAmount < 1000) {
            return response()->json(['message' => 'Sisa tagihan kurang dari Rp 1.000, tidak bisa diproses via Midtrans.'], 422);
        }

        // 4. Generate Order ID Unik
        // Format: INV-{ID}-T{TIMESTAMP}-C{CREDIT_USED}
        // Kita embed credit_used di Order ID agar nanti saat callback kita tahu berapa deposit yang harus dipotong
        $uniqueOrderId = 'INV-' . $invoice->invoice_id . '-T' . time() . '-C' . $creditToUse;

        $params = [
            'transaction_details' => [
                'order_id' => $uniqueOrderId,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $client->client_name,
                'email' => $client->email,
                'phone' => $client->phone_number,
            ],
            'item_details' => [
                [
                    'id' => $invoice->invoice_id,
                    'price' => $grossAmount,
                    'quantity' => 1,
                    'name' => "Pelunasan Invoice #" . $invoice->invoice_number . ($creditToUse > 0 ? " (Partial Credit)" : "")
                ]
            ]
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            
            // Simpan token sementara di invoice (opsional, untuk resume payment)
            $invoice->update([
                'pending_snap_token' => $snapToken,
                'pending_snap_expires_at' => now()->addDay()
            ]);

            return response()->json(['snap_token' => $snapToken]);

        } catch (Exception $e) {
            Log::error('Client Midtrans Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses pembayaran: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Client melakukan Pembayaran Massal (Bulk) via Midtrans
     */
    public function payBulkMidtrans(Request $request)
    {
        $client = Auth::guard('client')->user();
        
        $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:sales_invoices,invoice_id',
            'use_credit' => 'boolean'
        ]);

        $invoices = SalesInvoice::whereIn('invoice_id', $request->invoice_ids)
            ->where('client_id', $client->client_id)
            ->get();

        if ($invoices->isEmpty()) {
            return response()->json(['message' => 'Invoice tidak valid.'], 422);
        }

        $totalTagihan = $invoices->sum('remaining_balance');
        $useCredit = $request->boolean('use_credit');
        $clientBalance = $client->balance;

        $creditToUse = 0;
        if ($useCredit && $clientBalance > 0) {
            $creditToUse = min($clientBalance, $totalTagihan);
        }

        $grossAmount = round($totalTagihan - $creditToUse);
        
        if ($grossAmount < 1000) {
            return response()->json(['message' => 'Total pembayaran kurang dari Rp 1.000 (Batas Midtrans).'], 422);
        }

        DB::beginTransaction();
        try {
            // Buat Record Bulk Payment (Status Pending)
            // Ini penting agar saat callback kita tahu invoice mana saja yang dibayar
            $bulkPayment = BulkSalesPayment::create([
                'client_id' => $client->client_id,
                'payment_number' => BulkSalesPayment::generateNumber(),
                'payment_date' => now(),
                'total_amount' => $grossAmount, // Yang dibayar via gateway
                'status' => 'pending', // Menunggu callback Midtrans
                'details' => [
                    'invoice_ids' => $request->invoice_ids,
                    'credit_used_intent' => $creditToUse // Simpan niat pakai kredit
                ],
                'payment_method_id' => null, // Nanti diisi saat callback
            ]);

            // Format Order ID Bulk: BULK-{ID}-T{TIMESTAMP}-C{CREDIT}
            $uniqueOrderId = 'BULK-' . $bulkPayment->bulk_sales_payment_id . '-T' . time() . '-C' . $creditToUse;

            $params = [
                'transaction_details' => [
                    'order_id' => $uniqueOrderId,
                    'gross_amount' => $grossAmount,
                ],
                'customer_details' => [
                    'first_name' => $client->client_name,
                    'email' => $client->email,
                    'phone' => $client->phone_number,
                ],
                'item_details' => [
                    [
                        'id' => "BULK-" . $bulkPayment->bulk_sales_payment_id,
                        'price' => $grossAmount,
                        'quantity' => 1,
                        'name' => "Pembayaran Massal #" . $bulkPayment->payment_number
                    ]
                ]
            ];

            $snapToken = Snap::getSnapToken($params);
            
            DB::commit();
            return response()->json(['snap_token' => $snapToken]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Client Bulk Midtrans Error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}