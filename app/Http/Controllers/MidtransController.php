<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Models\PaymentGatewayCallback;
use App\Models\ClientLedger;
use App\Models\BatchPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Str;

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
     * 🔹 Generate Snap Token untuk SATU invoice
     * (Termasuk logika cek token pending)
     */
    public function pay(Request $request, SalesInvoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0', // Boleh 0 jika lunas pakai kredit
            'use_credit' => 'nullable|boolean',
        ]);

        $client = Auth::guard('client')->user();
        $sisaTagihan = $invoice->remaining_balance;
        $clientBalance = $client->balance; // Saldo available

        $amountFromInput = (float) $validated['amount'];
        $useCredit = $validated['use_credit'] ?? false;
        
        $creditToUse = 0;
        $totalPaymentValue = $amountFromInput; // Total nilai pembayaran = input

        if ($useCredit && $clientBalance > 0) {
            $totalPaymentValue = $amountFromInput + $clientBalance;
            $creditToUse = min($clientBalance, $sisaTagihan, $totalPaymentValue);
        }

        $grossAmountForMidtrans = round(max(0, $totalPaymentValue - $creditToUse));

        // Validasi Akhir
        if ($totalPaymentValue <= 0.01 && $sisaTagihan > 0.01) {
             return response()->json(['message' => 'Jumlah pembayaran harus lebih dari 0.'], 422);
        }
        if ($totalPaymentValue > ($sisaTagihan + 0.01)) { // +0.01 untuk toleransi pembulatan
            return response()->json(['message' => 'Jumlah pembayaran melebihi sisa tagihan.'], 422);
        }
        if ($grossAmountForMidtrans > 0 && $grossAmountForMidtrans < 1000) { // Asumsi min 1000
             return response()->json(['message' => 'Jumlah tagihan online (setelah potong saldo) terlalu kecil. Harap bayar manual.'], 422);
        }

        $uniqueOrderId = $invoice->invoice_number . '-T' . time() . '-C' . $creditToUse;
        
        // Jika lunas pakai kredit (tidak perlu ke Midtrans)
        if ($grossAmountForMidtrans == 0 && $creditToUse > 0) {
            try {
                $this->processCreditOnlyPayment($invoice, $creditToUse);
                return response()->json(['snap_token' => null, 'status' => 'paid_by_credit']);
            } catch (Exception $e) {
                Log::error('Gagal proses bayar dengan kredit (Single): ' . $e->getMessage());
                return response()->json(['message' => 'Gagal memproses pembayaran dengan saldo kredit.'], 500);
            }
        }
        
        // ======================================================
        // ✅ LOGIKA BARU: Cek Token yang Ada
        // ======================================================
        // Cek apakah ini pembayaran penuh (atau lunas dengan kredit)
        $isFullPayment = abs($totalPaymentValue - $sisaTagihan) < 0.01;

        // Hanya gunakan ulang token JIKA ini pembayaran penuh DAN token ada & valid
        if ($isFullPayment && $invoice->pending_snap_token && $invoice->pending_snap_expires_at > now()) {
            Log::info("Menggunakan snap token TERSIMPAN untuk Invoice: {$invoice->invoice_number}");
            return response()->json(['snap_token' => $invoice->pending_snap_token]);
        }
        // ======================================================
        
        // Jika ada yang ditagih ke Midtrans
        $params = [
            'transaction_details' => [
                'order_id' => $uniqueOrderId,
                'gross_amount' => $grossAmountForMidtrans,
            ],
            'customer_details' => [
                'first_name' => $client->client_name,
                'email' => $client->email,
                'phone' => $client->phone_number,
            ],
            // Set expiry 24 jam
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'hour',
                'duration' => 24,
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);

            // ======================================================
            // ✅ LOGIKA BARU: Simpan Token
            // ======================================================
            // Hanya simpan token jika ini pembayaran penuh (bukan cicilan)
            if ($isFullPayment) {
                $invoice->update([
                    'pending_snap_token' => $snapToken,
                    'pending_snap_expires_at' => now()->addHours(24)
                ]);
            }
            // ======================================================
            
            return response()->json(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Error (Single): ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memulai sesi pembayaran. Silakan coba lagi nanti.'], 500);
        }
    }

    /**
     * 🔹 Generate Snap Token untuk BATCH invoice
     */
    public function payBatch(Request $request)
    {
        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:sales_invoices,invoice_id',
            'amount' => 'required|numeric|min:0', // Jumlah yg diinput klien
            'use_credit' => 'nullable|boolean',
        ]);

        $client = Auth::guard('client')->user();
        $clientBalance = $client->balance;
        $useCredit = $validated['use_credit'] ?? false;
        $amountFromInput = (float) $validated['amount'];

        $invoices = SalesInvoice::whereIn('invoice_id', $validated['invoice_ids'])
            ->where('client_id', $client->client_id)
            ->with(['deductingReturns', 'adjustments'])
            ->get();

        // 1. Hitung Total Tagihan (sebagai batas atas)
        $totalTagihanTerpilih = $invoices->reduce(function ($carry, $invoice) {
            return $carry + $invoice->remaining_balance;
        }, 0.0);

        if ($totalTagihanTerpilih <= 0.01) {
             return response()->json(['message' => 'Tidak ada tagihan yang dipilih.'], 422);
        }

        // 2. Hitung Kredit & Tagihan Midtrans
        $creditToUse = 0;
        $totalPaymentValue = $amountFromInput; // Total nilai pembayaran = input

        if ($useCredit && $clientBalance > 0) {
            $totalPaymentValue = $amountFromInput + $clientBalance;
            $creditToUse = min($clientBalance, $totalTagihanTerpilih, $totalPaymentValue);
        }

        $grossAmountForMidtrans = round(max(0, $totalPaymentValue - $creditToUse));

        // 3. Validasi Akhir
        if ($totalPaymentValue <= 0.01 && $totalTagihanTerpilih > 0.01) {
             return response()->json(['message' => 'Jumlah pembayaran harus lebih dari 0.'], 422);
        }
        // Overpayment diizinkan, jadi validasi ini dihapus
        // if ($totalPaymentValue > ($totalTagihanTerpilih + 0.01)) { ... }
        
        if ($grossAmountForMidtrans > 0 && $grossAmountForMidtrans < 1000) {
             return response()->json(['message' => 'Jumlah tagihan online (setelah potong saldo) terlalu kecil. Harap bayar manual.'], 422);
        }
        
        // 4. Buat BatchPayment (Induk)
        DB::beginTransaction();
        try {
            $metodeBatch = ($creditToUse > 0) ? 'Kredit Klien' : '';
            
            $batchPayment = BatchPayment::create([
                'client_id' => $client->client_id,
                'processed_by_user_id' => null,
                'payment_date' => now(),
                'total_amount' => $totalPaymentValue, // Simpan total nilai pembayaran
                'payment_method' => $metodeBatch,
                'status' => 'pending',
                'details' => ['invoice_ids' => $validated['invoice_ids']],
            ]);

            // 5. Buat Order ID Unik
            $uniqueOrderId = 'BATCH-' . $batchPayment->batch_payment_id . '-T' . time() . '-C' . $creditToUse;

            // 6. Jika Lunas pakai Kredit
            if ($grossAmountForMidtrans == 0 && $creditToUse > 0) {
                $this->processBatchCreditOnlyPayment($batchPayment, $invoices, $totalPaymentValue); 
                DB::commit();
                return response()->json(['snap_token' => null, 'status' => 'paid_by_credit']);
            }

            // 7. Jika perlu ke Midtrans
            $params = [
                'transaction_details' => [
                    'order_id' => $uniqueOrderId,
                    'gross_amount' => $grossAmountForMidtrans,
                ],
                'customer_details' => [
                    'first_name' => $client->client_name,
                    'email' => $client->email,
                    'phone' => $client->phone_number,
                ],
            ];
            
            $snapToken = Snap::getSnapToken($params);
            DB::commit();
            return response()->json(['snap_token' => $snapToken]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Midtrans Batch Pay Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memulai sesi pembayaran batch.'], 500);
        }
    }


    /**
     * 🔹 Callback / Webhook dari Midtrans
     */
    public function callback(Request $request)
    {
        Log::info('Midtrans Callback Received: ');
        $notification = new Notification();
        $rawOrderId = $notification->order_id;
        
        if (str_starts_with($rawOrderId, 'BATCH-')) {
            $this->handleBatchCallback($notification);
        } else {
            $this->handleSingleCallback($notification);
        }
        
        return response()->json(['message' => 'Notification processed successfully.'], 200);
    }
    
    
    /**
     * 🔹 HELPER: Menangani Callback Pembayaran TUNGGAL
     */
    private function handleSingleCallback(Notification $notification)
    {
        try {
            $transactionStatus = $notification->transaction_status;
            $rawOrderId = $notification->order_id;
            $transactionId = $notification->transaction_id;
            $paymentType = $notification->payment_type;
            $grossAmount = (float) $notification->gross_amount;
            
            $creditUsed = 0;
            if (preg_match('/^(.*)-T\d+-C([\d\.]+)$/', $rawOrderId, $matches)) {
                $originalInvoiceNumber = $matches[1];
                $creditUsed = (float) $matches[2];
            } else {
                $orderIdParts = explode('-', $rawOrderId);
                array_pop($orderIdParts); 
                $originalInvoiceNumber = implode('-', $orderIdParts);
            }
            $totalPaymentAmount = $grossAmount + $creditUsed;
            
            $invoice = SalesInvoice::where('invoice_number', $originalInvoiceNumber)->firstOrFail();
            
            $isProcessed = PaymentGatewayCallback::where('vendor_transaction_id', $transactionId)
                                                 ->whereIn('status', ['settlement', 'capture'])
                                                 ->exists();
            if ($isProcessed) {
                Log::warning("Single Callback 'settlement/capture' for tx_id: {$transactionId} already processed.");
                return;
            }

            PaymentGatewayCallback::updateOrCreate(
                ['invoice_id' => $invoice->invoice_id, 'vendor_transaction_id' => $transactionId],
                ['status' => $transactionStatus, 'amount' => $grossAmount, 'payment_type' => $paymentType, 'raw_response' => (array) $notification, 'processed_at' => now()]
            );

            if (in_array($transactionStatus, ['capture', 'settlement'])) {
                
                DB::transaction(function () use ($invoice, $transactionId, $grossAmount, $paymentType, $creditUsed, $totalPaymentAmount, $notification) {
                    $existingPayment = Payment::where('transaction_id', $transactionId)->first();
                    if ($existingPayment) return;

                    $prettyPaymentMethod = $this->translatePaymentType($notification);
                    $metodeLog = ($creditUsed > 0) ? 'Kredit Klien + ' . $prettyPaymentMethod : $prettyPaymentMethod;
                    $catatanLog = "Auto processed. Midtrans: " . number_format($grossAmount) . ". Saldo Kredit: " . number_format($creditUsed);

                    Payment::create([
                        'invoice_id'            => $invoice->invoice_id,
                        'payment_date'          => now(),
                        'amount'                => $totalPaymentAmount,
                        'payment_method'        => $metodeLog,
                        'transaction_id'        => $transactionId,
                        'status'                => 'completed',
                        'notes'                 => $catatanLog,
                    ]);
                    
                    if ($creditUsed > 0) {
                        ClientLedger::create([
                            'client_id' => $invoice->client_id,
                            'sales_invoice_id' => $invoice->invoice_id,
                            'reference_type' => SalesInvoice::class,
                            'reference_id' => $invoice->invoice_id,
                            'transaction_date' => now(),
                            'type' => 'debit',
                            'amount' => -$creditUsed,
                            'status' => 'available',
                            'description' => 'Digunakan untuk membayar Invoice #' . $invoice->invoice_number,
                            'user_id' => null,
                        ]);
                    }
                    
                    $invoice->load('payments', 'deductingReturns', 'adjustments');
                    $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');
                    $totalDeductingReturns = $invoice->deductingReturns->sum('total_amount');
                    $totalCreditNotes = $invoice->adjustments->where('type', 'credit_note')->sum('amount');
                    $totalDebitNotes = $invoice->adjustments->where('type', 'debit_note')->sum('amount');
                    $totalDue = $invoice->total_amount + $totalDebitNotes - $totalCreditNotes - $totalDeductingReturns;
                    $sisaTagihan = $totalDue - $totalPaid;
                    
                    $status = ($sisaTagihan <= 0.01) ? 'paid' : 'partially_paid';

                    $invoice->update([
                        'amount_paid' => $totalPaid, 
                        'status' => $status,
                        'pending_snap_token' => null, // Hapus token
                        'pending_snap_expires_at' => null // Hapus expiry
                    ]);

                    if ($status == 'paid') {
                        ClientLedger::where('sales_invoice_id', $invoice->invoice_id)
                                    ->where('status', 'pending')
                                    ->update([
                                        'status' => 'available',
                                        'description' => DB::raw("REPLACE(description, ' (Ditahan)', '')")
                                    ]);
                    }
                });
            } 
            elseif ($transactionStatus == 'expire') {
                if ($invoice->pending_snap_token) {
                    $invoice->update([
                        'pending_snap_token' => null,
                        'pending_snap_expires_at' => null
                    ]);
                    Log::info("Snap token kedaluwarsa dihapus untuk Invoice: {$invoice->invoice_number}");
                }
            }
            
        } catch (Exception $e) {
            Log::error('Midtrans Single Callback Error: ' . $e->getMessage() . ' on line ' . $e->getLine());
        }
    }


    /**
     * 🔹 HELPER: Menangani Callback Pembayaran BATCH
     */
    private function handleBatchCallback(Notification $notification)
    {
        try {
            $transactionStatus = $notification->transaction_status;
            $rawOrderId = $notification->order_id;
            $transactionId = $notification->transaction_id;
            $paymentType = $notification->payment_type;
            $grossAmount = (float) $notification->gross_amount;

            if (!preg_match('/^BATCH-(\d+)-T\d+-C([\d\.]+)$/', $rawOrderId, $matches)) {
                 throw new Exception("Format Batch Order ID salah: $rawOrderId");
            }
            
            $batchPaymentId = $matches[1];
            $creditUsed = (float) $matches[2];
            $totalPaymentAmount = $grossAmount + $creditUsed;

            $batchPayment = BatchPayment::find($batchPaymentId);
            if (!$batchPayment) {
                 throw new Exception("BatchPayment ID #$batchPaymentId tidak ditemukan.");
            }
            
            $isProcessed = PaymentGatewayCallback::where('vendor_transaction_id', $transactionId)
                                                 ->whereIn('status', ['settlement', 'capture'])
                                                 ->exists();
            if ($isProcessed) {
                 Log::warning("Batch Callback 'settlement/capture' for tx_id: {$transactionId} already processed.");
                 return;
            }

            PaymentGatewayCallback::updateOrCreate(
                ['vendor_transaction_id' => $transactionId],
                ['invoice_id' => null, 'status' => $transactionStatus, 'amount' => $grossAmount, 'payment_type' => $paymentType, 'raw_response' => (array) $notification, 'processed_at' => now()]
            );
            
            if (in_array($transactionStatus, ['capture', 'settlement'])) {
                
                $prettyPaymentMethod = $this->translatePaymentType($notification);

                DB::transaction(function () use ($batchPayment, $transactionId, $prettyPaymentMethod, $creditUsed, $totalPaymentAmount) {
                    
                    $invoiceIds = $batchPayment->details['invoice_ids'] ?? [];
                    $invoices = SalesInvoice::whereIn('invoice_id', $invoiceIds)
                                    ->with(['deductingReturns', 'adjustments'])
                                    ->orderBy('due_date', 'asc')
                                    ->get();

                    $metodeBatch = $prettyPaymentMethod;
                    if ($creditUsed > 0) {
                        $metodeBatch = 'Kredit Klien + ' . $prettyPaymentMethod;
                    }

                    $batchPayment->update([
                        'payment_method' => $metodeBatch,
                        'status' => 'completed',
                        'notes' => ($batchPayment->notes ?? '') . " | Midtrans TX ID: $transactionId"
                    ]);

                    if ($creditUsed > 0) {
                        ClientLedger::create([
                            'client_id' => $batchPayment->client_id,
                            'reference_type' => BatchPayment::class,
                            'reference_id' => $batchPayment->batch_payment_id,
                            'transaction_date' => now(),
                            'type' => 'debit',
                            'amount' => -$creditUsed,
                            'status' => 'available',
                            'description' => 'Digunakan untuk Pembayaran Batch #' . $batchPayment->batch_payment_id,
                            'user_id' => null,
                        ]);
                    }
                    
                    $danaTersisaUntukAlokasi = $totalPaymentAmount;

                    foreach ($invoices as $invoice) {
                        if ($danaTersisaUntukAlokasi <= 0.01) break;
                        $sisaTagihanInvoice = $invoice->remaining_balance;
                        if ($sisaTagihanInvoice <= 0.01) continue;
                        
                        $jumlahUntukInvoiceIni = min($sisaTagihanInvoice, $danaTersisaUntukAlokasi);

                        $invoice->payments()->create([
                            'batch_payment_id' => $batchPayment->batch_payment_id,
                            'payment_date' => now(),
                            'amount' => $jumlahUntukInvoiceIni,
                            'payment_method' => $metodeBatch,
                            'received_by_user_id' => null,
                            'status' => 'completed',
                            'transaction_id' => $transactionId,
                            'notes' => 'Auto-allocated from Midtrans Batch #' . $batchPayment->batch_payment_id,
                        ]);

                        $invoice->load('payments', 'deductingReturns', 'adjustments');
                        $totalPaidBaru = $invoice->payments()->where('status', 'completed')->sum('amount');
                        
                        $totalDeductingReturns = $invoice->deductingReturns->sum('total_amount');
                        $totalCreditNotes = $invoice->adjustments->where('type', 'credit_note')->sum('amount');
                        $totalDebitNotes = $invoice->adjustments->where('type', 'debit_note')->sum('amount');
                        $totalDue = $invoice->total_amount + $totalDebitNotes - $totalCreditNotes - $totalDeductingReturns;
                        
                        $sisaTagihanBaru = $totalDue - $totalPaidBaru;
                        $newStatus = ($sisaTagihanBaru <= 0.01) ? 'paid' : 'partially_paid';

                        $invoice->update([
                            'amount_paid' => $totalPaidBaru,
                            'status' => $newStatus,
                            'pending_snap_token' => null, // Hapus token
                            'pending_snap_expires_at' => null
                        ]);

                        if ($newStatus == 'paid') {
                            ClientLedger::where('sales_invoice_id', $invoice->invoice_id)
                                        ->where('status', 'pending')
                                        ->update([
                                            'status' => 'available',
                                            'description' => DB::raw("REPLACE(description, ' (Ditahan)', '')")
                                        ]);
                        }
                        
                        $danaTersisaUntukAlokasi -= $jumlahUntukInvoiceIni;
                    }

                    if ($danaTersisaUntukAlokasi > 0.01) {
                         ClientLedger::create([
                            'client_id' => $batchPayment->client_id,
                            'reference_type' => BatchPayment::class,
                            'reference_id' => $batchPayment->batch_payment_id,
                            'transaction_date' => now(),
                            'type' => 'credit',
                            'amount' => $danaTersisaUntukAlokasi,
                            'status' => 'available',
                            'description' => 'Kelebihan dana dari Pembayaran Batch #' . $batchPayment->batch_payment_id,
                            'user_id' => null,
                         ]);
                    }
                });
            }

        } catch (Exception $e) {
            Log::error('Midtrans Batch Callback Error: ' . $e->getMessage() . ' on line ' . $e->getLine());
        }
    }


    /**
     * 🔹 Helper Pembayaran Kredit Saja (Single)
     */
    private function processCreditOnlyPayment(SalesInvoice $invoice, float $creditToUse)
    {
        DB::transaction(function () use ($invoice, $creditToUse) {
            $uniqueTransactionId = 'CREDIT-' . time() . '-' . $invoice->invoice_id;

            Payment::create([
                'invoice_id'            => $invoice->invoice_id,
                'payment_date'          => now(),
                'amount'                => $creditToUse,
                'payment_method'        => 'Kredit Klien',
                'transaction_id'        => $uniqueTransactionId,
                'status'                => 'completed',
                'notes'                 => 'Auto processed. Dibayar dengan Saldo Kredit.',
            ]);

            ClientLedger::create([
                'client_id' => $invoice->client_id,
                'sales_invoice_id' => $invoice->invoice_id,
                'reference_type' => SalesInvoice::class,
                'reference_id' => $invoice->invoice_id,
                'transaction_date' => now(),
                'type' => 'debit',
                'amount' => -$creditToUse,
                'status' => 'available',
                'description' => 'Digunakan untuk membayar Invoice #' . $invoice->invoice_number,
                'user_id' => null,
            ]);
            
            $invoice->load('payments', 'deductingReturns', 'adjustments');
            $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');
            $totalDeductingReturns = $invoice->deductingReturns->sum('total_amount');
            $totalCreditNotes = $invoice->adjustments->where('type', 'credit_note')->sum('amount');
            $totalDebitNotes = $invoice->adjustments->where('type', 'debit_note')->sum('amount');
            $totalDue = $invoice->total_amount + $totalDebitNotes - $totalCreditNotes - $totalDeductingReturns;
            $sisaTagihan = $totalDue - $totalPaid;
            
            $status = ($sisaTagihan <= 0.01) ? 'paid' : 'partially_paid';

            $invoice->update([
                'amount_paid' => $totalPaid,
                'status'      => $status,
                'pending_snap_token' => null, // Hapus token
                'pending_snap_expires_at' => null
            ]);
            
            if ($status == 'paid') {
                ClientLedger::where('sales_invoice_id', $invoice->invoice_id)
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'available',
                                'description' => DB::raw("REPLACE(description, ' (Ditahan)', '')")
                            ]);
            }
            Log::info("Invoice #{$invoice->invoice_id} lunas dengan saldo kredit.");
        });
    }

    /**
     * 🔹 Helper Pembayaran Kredit Saja (Batch)
     */
    private function processBatchCreditOnlyPayment(BatchPayment $batchPayment, $invoices, float $creditToUse)
    {
         DB::transaction(function () use ($batchPayment, $invoices, $creditToUse) {
            $uniqueTransactionId = 'CREDIT-BATCH-' . time() . '-' . $batchPayment->batch_payment_id;
            
            $batchPayment->update([
                'payment_method' => 'Kredit Klien',
                'status' => 'completed',
                'notes' => ($batchPayment->notes ?? '') . " | Lunas dengan Saldo Kredit."
            ]);

            ClientLedger::create([
                'client_id' => $batchPayment->client_id,
                'reference_type' => BatchPayment::class,
                'reference_id' => $batchPayment->batch_payment_id,
                'transaction_date' => now(),
                'type' => 'debit',
                'amount' => -$creditToUse,
                'status' => 'available',
                'description' => 'Digunakan untuk Pembayaran Batch #' . $batchPayment->batch_payment_id,
                'user_id' => null,
            ]);
            
            $danaTersisaUntukAlokasi = $creditToUse;
            foreach ($invoices as $invoice) {
                if ($danaTersisaUntukAlokasi <= 0.01) break;
                $sisaTagihanInvoice = $invoice->remaining_balance;
                if ($sisaTagihanInvoice <= 0.01) continue;
                
                $jumlahUntukInvoiceIni = min($sisaTagihanInvoice, $danaTersisaUntukAlokasi);

                $invoice->payments()->create([
                    'batch_payment_id' => $batchPayment->batch_payment_id,
                    'payment_date' => now(),
                    'amount' => $jumlahUntukInvoiceIni,
                    'payment_method' => 'Kredit Klien',
                    'received_by_user_id' => null,
                    'status' => 'completed',
                    'transaction_id' => $uniqueTransactionId,
                    'notes' => 'Auto-allocated from Credit-Only Batch #' . $batchPayment->batch_payment_id,
                ]);

                $invoice->load('payments', 'deductingReturns', 'adjustments');
                $totalPaidBaru = $invoice->payments()->where('status', 'completed')->sum('amount');
                
                $totalDeductingReturns = $invoice->deductingReturns->sum('total_amount');
                $totalCreditNotes = $invoice->adjustments->where('type', 'credit_note')->sum('amount');
                $totalDebitNotes = $invoice->adjustments->where('type', 'debit_note')->sum('amount');
                $totalDue = $invoice->total_amount + $totalDebitNotes - $totalCreditNotes - $totalDeductingReturns;

                $sisaTagihanBaru = $totalDue - $totalPaidBaru;
                $newStatus = ($sisaTagihanBaru <= 0.01) ? 'paid' : 'partially_paid';

                $invoice->update([
                    'amount_paid' => $totalPaidBaru,
                    'status' => $newStatus,
                    'pending_snap_token' => null, // Hapus token
                    'pending_snap_expires_at' => null
                ]);

                if ($newStatus == 'paid') {
                    ClientLedger::where('sales_invoice_id', $invoice->invoice_id)
                                ->where('status', 'pending')
                                ->update([
                                    'status' => 'available',
                                    'description' => DB::raw("REPLACE(description, ' (Ditahan)', '')")
                                ]);
                }
                
                $danaTersisaUntukAlokasi -= $jumlahUntukInvoiceIni;
            }
        });
    }

    /**
     * ✅ HELPER: Menerjemahkan nama metode pembayaran
     */
    private function translatePaymentType(Notification $notification): string
    {
        // $paymentType = $notification->payment_type;
        
        // ✅ PERMINTAAN ANDA: Selalu kembalikan string ini
        return 'Payment Gateway Midtrans';
    }
}