<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Models\PaymentGatewayCallback;
use App\Models\ClientLedger;
use App\Models\BatchPayment;
use App\Models\PaymentMethod; // ✅ PERBAIKAN: Wajib di-import
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
        // ... (Tidak ada perubahan) ...
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * 🔹 Generate Snap Token untuk SATU invoice
     */
    public function pay(Request $request, SalesInvoice $invoice)
    {
        // ... (Tidak ada perubahan di logika 'pay' ini) ...
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0', 
            'use_credit' => 'nullable|boolean',
        ]);
        $client = Auth::guard('client')->user();
        $sisaTagihan = $invoice->remaining_balance;
        $clientBalance = $client->balance; 
        $amountFromInput = (float) $validated['amount'];
        $useCredit = $validated['use_credit'] ?? false;
        $creditToUse = 0;
        $totalPaymentValue = $amountFromInput;
        if ($useCredit && $clientBalance > 0) {
            $totalPaymentValue = $amountFromInput + $clientBalance;
            $creditToUse = min($clientBalance, $sisaTagihan, $totalPaymentValue);
        }
        $grossAmountForMidtrans = round(max(0, $totalPaymentValue - $creditToUse));
        if ($totalPaymentValue <= 0.01 && $sisaTagihan > 0.01) {
             return response()->json(['message' => 'Jumlah pembayaran harus lebih dari 0.'], 422);
        }
        if ($totalPaymentValue > ($sisaTagihan + 0.01)) { 
            return response()->json(['message' => 'Jumlah pembayaran melebihi sisa tagihan.'], 422);
        }
        if ($grossAmountForMidtrans > 0 && $grossAmountForMidtrans < 1000) { 
             return response()->json(['message' => 'Jumlah tagihan online (setelah potong saldo) terlalu kecil. Harap bayar manual.'], 422);
        }
        $uniqueOrderId = $invoice->invoice_number . '-T' . time() . '-C' . $creditToUse;
        if ($grossAmountForMidtrans == 0 && $creditToUse > 0) {
            try {
                $this->processCreditOnlyPayment($invoice, $creditToUse);
                return response()->json(['snap_token' => null, 'status' => 'paid_by_credit']);
            } catch (Exception $e) {
                Log::error('Gagal proses bayar dengan kredit (Single): ' . $e->getMessage());
                return response()->json(['message' => 'Gagal memproses pembayaran dengan saldo kredit.'], 500);
            }
        }
        $isFullPayment = abs($totalPaymentValue - $sisaTagihan) < 0.01;
        if ($isFullPayment && $invoice->pending_snap_token && $invoice->pending_snap_expires_at > now()) {
            Log::info("Menggunakan snap token TERSIMPAN untuk Invoice: {$invoice->invoice_number}");
            return response()->json(['snap_token' => $invoice->pending_snap_token]);
        }
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
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'hour',
                'duration' => 24,
            ],
        ];
        try {
            $snapToken = Snap::getSnapToken($params);
            if ($isFullPayment) {
                $invoice->update([
                    'pending_snap_token' => $snapToken,
                    'pending_snap_expires_at' => now()->addHours(24)
                ]);
            }
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
        // ... (Logika validasi & perhitungan gross amount sudah benar) ...
        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:sales_invoices,invoice_id',
            'amount' => 'required|numeric|min:0', 
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
        $totalTagihanTerpilih = $invoices->reduce(fn($carry, $inv) => $carry + $inv->remaining_balance, 0.0);
        if ($totalTagihanTerpilih <= 0.01) {
             return response()->json(['message' => 'Tidak ada tagihan yang dipilih.'], 422);
        }
        $creditToUse = 0;
        $totalPaymentValue = $amountFromInput;
        if ($useCredit && $clientBalance > 0) {
            $totalPaymentValue = $amountFromInput + $clientBalance;
            $creditToUse = min($clientBalance, $totalTagihanTerpilih, $totalPaymentValue);
        }
        $grossAmountForMidtrans = round(max(0, $totalPaymentValue - $creditToUse));
        if ($totalPaymentValue <= 0.01 && $totalTagihanTerpilih > 0.01) {
             return response()->json(['message' => 'Jumlah pembayaran harus lebih dari 0.'], 422);
        }
        if ($grossAmountForMidtrans > 0 && $grossAmountForMidtrans < 1000) {
             return response()->json(['message' => 'Jumlah tagihan online (setelah potong saldo) terlalu kecil. Harap bayar manual.'], 422);
        }
        
        // 4. Buat BatchPayment (Induk)
        DB::beginTransaction();
        try {
            // $metodeBatch = ($creditToUse > 0) ? 'Kredit Klien' : ''; // <-- Logika String Lama
            
            $batchPayment = BatchPayment::create([
                'client_id' => $client->client_id,
                'processed_by_user_id' => null,
                'payment_date' => now(),
                'total_amount' => $totalPaymentValue, // Simpan total nilai pembayaran
                'payment_method_id' => null, // ✅ PERBAIKAN: Akan diisi oleh callback
                'status' => 'pending',
                'details' => ['invoice_ids' => $validated['invoice_ids']],
            ]);

            // ... (Sisa logika sudah benar) ...
            $uniqueOrderId = 'BATCH-' . $batchPayment->batch_payment_id . '-T' . time() . '-C' . $creditToUse;
            if ($grossAmountForMidtrans == 0 && $creditToUse > 0) {
                $this->processBatchCreditOnlyPayment($batchPayment, $invoices, $totalPaymentValue); 
                DB::commit();
                return response()->json(['snap_token' => null, 'status' => 'paid_by_credit']);
            }
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
            // ... (Logika ekstraksi $rawOrderId, $transactionId, $creditUsed, dll. sudah benar) ...
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

                    // ✅ PERBAIKAN: Dapatkan ID gateway
                    $gatewayMethod = PaymentMethod::where('type', 'gateway')->first();
                    
                    $prettyPaymentMethod = $this->translatePaymentType($notification);
                    $metodeLog = ($creditUsed > 0) ? 'Kredit Klien + ' . $prettyPaymentMethod : $prettyPaymentMethod;
                    $catatanLog = "Auto processed. Midtrans: " . number_format($grossAmount) . ". Saldo Kredit: " . number_format($creditUsed);

                    Payment::create([
                        'invoice_id'          => $invoice->invoice_id,
                        'payment_date'        => now(),
                        'amount'              => $totalPaymentAmount,
                        'payment_method_id'   => $gatewayMethod ? $gatewayMethod->payment_method_id : null, // ✅ Simpan ID
                        'transaction_id'      => $transactionId,
                        'status'              => 'completed',
                        'notes'               => $catatanLog . " | Metode: " . $metodeLog, // ✅ Simpan string di notes
                    ]);
                    
                    if ($creditUsed > 0) {
                        // ... (Logika ClientLedger Anda sudah benar) ...
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
                    
                    // ... (Logika $invoice->updatePaymentStatus() Anda sudah benar) ...
                    // Panggil fungsi yang ada di Model SalesInvoice
                    $invoice->updatePaymentStatus(); 

                });
            } 
            elseif ($transactionStatus == 'expire') {
                // ... (Logika 'expire' Anda sudah benar) ...
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
            // ... (Logika ekstraksi $batchPaymentId, $creditUsed, dll. sudah benar) ...
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
                    
                    // ✅ PERBAIKAN: Dapatkan ID gateway
                    $gatewayMethod = PaymentMethod::where('type', 'gateway')->first();

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
                        'payment_method_id' => $gatewayMethod ? $gatewayMethod->payment_method_id : null, // ✅ Simpan ID
                        'status' => 'completed',
                        'notes' => ($batchPayment->notes ?? '') . " | Midtrans TX ID: $transactionId | Metode: $metodeBatch" // ✅ Simpan string di notes
                    ]);

                    if ($creditUsed > 0) {
                        // ... (Logika ClientLedger sudah benar) ...
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
                            'payment_method_id' => $gatewayMethod ? $gatewayMethod->payment_method_id : null, // ✅ Simpan ID
                            'received_by_user_id' => null,
                            'status' => 'completed',
                            'transaction_id' => $transactionId,
                            'notes' => 'Auto-allocated from Midtrans Batch #' . $batchPayment->batch_payment_id . " | Metode: " . $metodeBatch, // ✅ Simpan string di notes
                        ]);

                        // Panggil fungsi update status
                        $invoice->updatePaymentStatus();
                        
                        $danaTersisaUntukAlokasi -= $jumlahUntukInvoiceIni;
                    }

                    if ($danaTersisaUntukAlokasi > 0.01) {
                        // ... (Logika overpayment ke ClientLedger sudah benar) ...
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
                'invoice_id'          => $invoice->invoice_id,
                'payment_date'        => now(),
                'amount'              => $creditToUse,
                'payment_method_id'   => null, // ✅ PERBAIKAN
                'transaction_id'      => $uniqueTransactionId,
                'status'              => 'completed',
                'notes'               => 'Auto processed. Dibayar dengan Saldo Kredit.', // ✅ String "Kredit Klien" disimpan di notes
            ]);

            ClientLedger::create([
                // ... (Logika ClientLedger sudah benar) ...
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
            
            // Panggil fungsi update status
            $invoice->updatePaymentStatus();
            
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
                'payment_method_id' => null, // ✅ PERBAIKAN
                'status' => 'completed',
                'notes' => ($batchPayment->notes ?? '') . " | Lunas dengan Saldo Kredit." // ✅ String "Kredit Klien" disimpan di notes
            ]);

            ClientLedger::create([
                // ... (Logika ClientLedger sudah benar) ...
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
                    'payment_method_id' => null, // ✅ PERBAIKAN
                    'received_by_user_id' => null,
                    'status' => 'completed',
                    'transaction_id' => $uniqueTransactionId,
                    'notes' => 'Auto-allocated from Credit-Only Batch #' . $batchPayment->batch_payment_id, // ✅ Notes
                ]);

                // Panggil fungsi update status
                $invoice->updatePaymentStatus();
                
                $danaTersisaUntukAlokasi -= $jumlahUntukInvoiceIni;
            }
        });
    }

    /**
     * ✅ HELPER: Menerjemahkan nama metode pembayaran
     * (Fungsi ini tidak diubah, tapi hasilnya kini disimpan di 'notes')
     */
    private function translatePaymentType(Notification $notification): string
    {
        $paymentType = $notification->payment_type;
        
        // Cek jika ada 'va_numbers' (untuk BCA, BNI, dll.)
        if (isset($notification->va_numbers) && is_array($notification->va_numbers) && count($notification->va_numbers) > 0) {
            $bank = $notification->va_numbers[0]['bank'] ?? 'va';
            return strtoupper($bank) . ' Virtual Account';
        }

        // Cek jika 'cstore' (Indomaret, Alfamart)
        if ($paymentType == 'cstore') {
            return $notification->store ?? 'Convenience Store';
        }

        // Cek jika 'qris'
        if ($paymentType == 'qris') {
            return 'QRIS (' . ($notification->acquirer ?? '') . ')';
        }
        
        // Cek jika 'gopay', 'shopeepay'
        if (in_array($paymentType, ['gopay', 'shopeepay'])) {
            return ucwords($paymentType);
        }

        // Cek jika 'bank_transfer' (Permata)
        if ($paymentType == 'bank_transfer' && isset($notification->permata_va_number)) {
            return 'Permata Virtual Account';
        }

        // Fallback umum
        return 'Payment Gateway Midtrans';
    }
}