<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Models\PaymentGatewayCallback;
use App\Models\ClientLedger;
use App\Models\BatchPayment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;
use Illuminate\Support\Facades\Auth;
use Exception;

// Import Service Akuntansi
use App\Services\AccountingService;
use App\Services\AccountingSettingService;
use App\Models\CompanyBankAccount; 

class MidtransController extends Controller
{
    /**
     * Konstruktor: konfigurasi Midtrans SDK.
     */
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * Men-generate Snap Token Midtrans untuk satu Invoice.
     * (Tidak ada perubahan akuntansi di sini)
     */
    public function pay(Request $request, SalesInvoice $invoice)
    {
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

        // Validasi bisnis
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

        // Jika pembayaran sepenuhnya ditutup oleh kredit klien -> proses langsung
        if ($grossAmountForMidtrans == 0 && $creditToUse > 0) {
            try {
                // Panggil method yang sudah di-update dengan jurnal
                $this->processCreditOnlyPayment($invoice, $creditToUse); 
                return response()->json(['snap_token' => null, 'status' => 'paid_by_credit']);
            } catch (Exception $e) {
                Log::error('Gagal proses bayar dengan kredit (Single): ' . $e->getMessage());
                return response()->json(['message' => 'Gagal memproses pembayaran dengan saldo kredit.'], 500);
            }
        }

        // (Sisa logika 'pay' Anda tetap sama)
        $isFullPayment = abs($totalPaymentValue - $sisaTagihan) < 0.01;
        if ($isFullPayment && $invoice->pending_snap_token && $invoice->pending_snap_expires_at > now()) {
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
                    'pending_snap_expires_at' => now()->addHours(24),
                ]);
            }
            return response()->json(['snap_token' => $snapToken]);
        } catch (Exception $e) {
            Log::error('Midtrans Snap Error (Single): ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memulai sesi pembayaran. Silakan coba lagi nanti.'], 500);
        }
    }

    /**
     * Men-generate Snap Token Midtrans untuk pembayaran BATCH (beberapa invoice).
     * (Tidak ada perubahan akuntansi di sini)
     */
    public function payBatch(Request $request)
    {
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

        $totalTagihanTerpilih = $invoices->reduce(fn ($carry, $inv) => $carry + $inv->remaining_balance, 0.0);

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

        DB::beginTransaction();
        try {
            $batchPayment = BatchPayment::create([
                'client_id' => $client->client_id,
                'processed_by_user_id' => null,
                'payment_date' => now(),
                'total_amount' => $totalPaymentValue, // total gabungan
                'payment_method_id' => null,
                'status' => 'pending',
                'details' => ['invoice_ids' => $validated['invoice_ids']],
            ]);

            $uniqueOrderId = 'BATCH-' . $batchPayment->batch_payment_id . '-T' . time() . '-C' . $creditToUse;

            // Jika seluruh pembayaran ditutup oleh kredit -> proses langsung
            if ($grossAmountForMidtrans == 0 && $creditToUse > 0) {
                // Panggil method yang sudah di-update dengan jurnal
                $this->processBatchCreditOnlyPayment($batchPayment, $invoices, $totalPaymentValue); 
                DB::commit();
                return response()->json(['snap_token' => null, 'status' => 'paid_by_credit']);
            }

            // (Sisa logika 'payBatch' Anda tetap sama)
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
     * Endpoint callback/webhook Midtrans.
     * (Tidak ada perubahan di sini)
     */
    public function callback(Request $request)
    {
        Log::info('Midtrans Callback Received');

        $notification = new Notification();
        $rawOrderId = $notification->order_id ?? '';

        if (str_starts_with($rawOrderId, 'BATCH-')) {
            $this->handleBatchCallback($notification);
        } else {
            $this->handleSingleCallback($notification);
        }

        return response()->json(['message' => 'Notification processed successfully.'], 200);
    }

    /**
     * Handle callback untuk transaksi tunggal (single invoice).
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi.
     */
    private function handleSingleCallback(Notification $notification)
    {
        // ✅ Panggil Service Akuntansi
        $accountingService = app(AccountingService::class);
        $accountingSettings = app(AccountingSettingService::class);

        try {
            $transactionStatus = $notification->transaction_status ?? null;
            $rawOrderId = $notification->order_id ?? '';
            $transactionId = $notification->transaction_id ?? null;
            $paymentType = $notification->payment_type ?? null;
            $grossAmount = (float) ($notification->gross_amount ?? 0); // Uang dari Midtrans

            // Ekstraksi invoice number dan kredit dari pola order_id
            $creditUsed = 0; // Uang dari Deposit Klien
            if (preg_match('/^(.*)-T\d+-C([\d\.]+)$/', $rawOrderId, $matches)) {
                $originalInvoiceNumber = $matches[1];
                $creditUsed = (float) $matches[2];
            } else {
                $orderIdParts = explode('-', $rawOrderId);
                array_pop($orderIdParts);
                $originalInvoiceNumber = implode('-', $orderIdParts);
            }

            $totalPaymentAmount = $grossAmount + $creditUsed; // Total Piutang yg lunas

            $invoice = SalesInvoice::where('invoice_number', $originalInvoiceNumber)->firstOrFail();
            
            // Idempotency
            $isProcessed = PaymentGatewayCallback::where('vendor_transaction_id', $transactionId)
                ->whereIn('status', ['settlement', 'capture'])
                ->exists();

            if ($isProcessed) {
                Log::warning("Single Callback already processed for tx_id: {$transactionId}");
                return;
            }

            // Simpan atau update record callback raw
            PaymentGatewayCallback::updateOrCreate(
                ['invoice_id' => $invoice->invoice_id, 'vendor_transaction_id' => $transactionId],
                [
                    'status' => $transactionStatus,
                    'amount' => $grossAmount,
                    'payment_type' => $paymentType,
                    'raw_response' => (array) $notification,
                    'processed_at' => now(),
                ]
            );

            // Jika pembayaran berhasil di sisi gateway
            if (in_array($transactionStatus, ['capture', 'settlement'])) {
                
                // ✅ Validasi Akun Akuntansi
                $arAccountId = $accountingSettings->getAccountsReceivableId();
                $clientDepositAccountId = $accountingSettings->getClientDepositId();
                $gatewayAccountId = $accountingSettings->getGatewayAccountId(); // Akun Kas Midtrans
                $gatewayMethod = PaymentMethod::where('type', 'gateway')->first();
                $gatewayBank = CompanyBankAccount::where('chart_of_account_id', $gatewayAccountId)->first();

                if (!$arAccountId || !$clientDepositAccountId || !$gatewayAccountId || !$gatewayBank) {
                    throw new Exception("Akun default (AR, Deposit, Gateway, atau Akun Bank Gateway) belum diatur.");
                }

                DB::transaction(function () use (
                    $invoice, $transactionId, $grossAmount, $notification, $creditUsed, $totalPaymentAmount,
                    $gatewayMethod, $gatewayBank, $accountingService, $arAccountId, $clientDepositAccountId, $gatewayAccountId
                ) {
                    // Pastikan tidak ganda
                    $existingPayment = Payment::where('transaction_id', $transactionId)->first();
                    if ($existingPayment) {
                        return;
                    }

                    // Terjemahkan jenis pembayaran untuk catatan
                    $prettyPaymentMethod = $this->translatePaymentType($notification); // ✅ AKAN SELALU RETURN "Payment Gateway Midtrans"
                    $metodeLog = ($creditUsed > 0) ? 'Kredit Klien + ' . $prettyPaymentMethod : $prettyPaymentMethod;
                    $catatanLog = "Auto processed. Midtrans: " . number_format($grossAmount) . ". Saldo Kredit: " . number_format($creditUsed);

                    // Simpan Payment
                    $payment = Payment::create([
                        'invoice_id' => $invoice->invoice_id,
                        'payment_date' => now(),
                        'amount' => $totalPaymentAmount,
                        'payment_method_id' => $gatewayMethod ? $gatewayMethod->payment_method_id : null,
                        'company_bank_account_id' => $gatewayBank->company_bank_account_id, // ✅ Masukkan ID Akun Bank Gateway
                        'transaction_id' => $transactionId,
                        'status' => 'completed',
                        'notes' => $catatanLog . " | Metode: " . $metodeLog,
                    ]);

                    // Jika ada pemakaian kredit, catat di ledger
                    if ($creditUsed > 0) {
                        ClientLedger::create([
                            'client_id' => $invoice->client_id,
                            'sales_invoice_id' => $invoice->invoice_id,
                            'reference_type' => SalesInvoice::class, // Referensi ke Invoice
                            'reference_id' => $invoice->invoice_id,
                            'transaction_date' => now(),
                            'type' => 'debit',
                            'amount' => -$creditUsed,
                            'status' => 'available',
                            'description' => 'Digunakan untuk membayar Invoice #' . $invoice->invoice_number,
                            'user_id' => null,
                        ]);
                    }
                    
                    // ✅ Post Jurnal Akuntansi
                    $journalGroupId = "PAY-" . $payment->payment_id;
                    $description = "Pembayaran Midtrans Inv #" . $invoice->invoice_number;
                    
                    $debitEntries = [];
                    $creditEntries = [];

                    if ($grossAmount > 0) {
                        $debitEntries[] = [$gatewayAccountId, $grossAmount, "Penerimaan Midtrans " . $prettyPaymentMethod];
                    }
                    if ($creditUsed > 0) {
                        $debitEntries[] = [$clientDepositAccountId, $creditUsed, "Penggunaan deposit klien"];
                    }
                    if ($totalPaymentAmount > 0) {
                        $creditEntries[] = [$arAccountId, $totalPaymentAmount, "Pelunasan Piutang Inv #" . $invoice->invoice_number];
                    }

                    $accountingService->postJournal(
                        $journalGroupId,
                        now(),
                        $description,
                        $debitEntries,
                        $creditEntries,
                        $payment // Referensi ke Payment
                    );

                    // Update status invoice
                    $invoice->updatePaymentStatus();
                });
            } elseif ($transactionStatus === 'expire') {
                if ($invoice->pending_snap_token) {
                    $invoice->update([
                        'pending_snap_token' => null,
                        'pending_snap_expires_at' => null,
                    ]);
                    Log::info("Snap token expired and removed for Invoice: {$invoice->invoice_number}");
                }
            }
        } catch (Exception $e) {
            Log::error('Midtrans Single Callback Error: ' . $e->getMessage() . ' on line ' . $e->getLine());
        }
    }

    /**
     * Handle callback untuk transaksi batch.
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi.
     */
    private function handleBatchCallback(Notification $notification)
    {
        // ✅ Panggil Service Akuntansi
        $accountingService = app(AccountingService::class);
        $accountingSettings = app(AccountingSettingService::class);

        try {
            $transactionStatus = $notification->transaction_status ?? null;
            $rawOrderId = $notification->order_id ?? '';
            $transactionId = $notification->transaction_id ?? null;
            $paymentType = $notification->payment_type ?? null;
            $grossAmount = (float) ($notification->gross_amount ?? 0); // Uang dari Midtrans

            if (!preg_match('/^BATCH-(\d+)-T\d+-C([\d\.]+)$/', $rawOrderId, $matches)) {
                throw new Exception("Format Batch Order ID salah: $rawOrderId");
            }

            $batchPaymentId = $matches[1];
            $creditUsed = (float) $matches[2]; // Uang dari Deposit Klien
            $totalPaymentAmount = $grossAmount + $creditUsed; // Total uang yg digunakan

            $batchPayment = BatchPayment::find($batchPaymentId);
            if (!$batchPayment) {
                throw new Exception("BatchPayment ID #$batchPaymentId tidak ditemukan.");
            }

            // Idempotency
            $isProcessed = PaymentGatewayCallback::where('vendor_transaction_id', $transactionId)
                ->whereIn('status', ['settlement', 'capture'])
                ->exists();
            if ($isProcessed) {
                Log::warning("Batch Callback already processed for tx_id: {$transactionId}");
                return;
            }

            // Simpan callback raw
            PaymentGatewayCallback::updateOrCreate(
                ['vendor_transaction_id' => $transactionId],
                [
                    'invoice_id' => null,
                    'status' => $transactionStatus,
                    'amount' => $grossAmount,
                    'payment_type' => $paymentType,
                    'raw_response' => (array) $notification,
                    'processed_at' => now(),
                ]
            );

            if (in_array($transactionStatus, ['capture', 'settlement'])) {
                
                // ✅ Validasi Akun Akuntansi
                $arAccountId = $accountingSettings->getAccountsReceivableId();
                $clientDepositAccountId = $accountingSettings->getClientDepositId();
                $gatewayAccountId = $accountingSettings->getGatewayAccountId(); // Akun Kas Midtrans
                $gatewayMethod = PaymentMethod::where('type', 'gateway')->first();
                $gatewayBank = CompanyBankAccount::where('chart_of_account_id', $gatewayAccountId)->first();

                if (!$arAccountId || !$clientDepositAccountId || !$gatewayAccountId || !$gatewayBank) {
                    throw new Exception("Akun default (AR, Deposit, Gateway, atau Akun Bank Gateway) belum diatur.");
                }
                
                $prettyPaymentMethod = $this->translatePaymentType($notification); // ✅ AKAN SELALU RETURN "Payment Gateway Midtrans"

                DB::transaction(function () use (
                    $batchPayment, $transactionId, $prettyPaymentMethod, $creditUsed, $totalPaymentAmount, $grossAmount,
                    $gatewayMethod, $gatewayBank, $accountingService, $arAccountId, $clientDepositAccountId, $gatewayAccountId
                ) {
                    
                    $invoiceIds = $batchPayment->details['invoice_ids'] ?? [];
                    $invoices = SalesInvoice::whereIn('invoice_id', $invoiceIds)
                        ->with(['deductingReturns', 'adjustments'])
                        ->orderBy('due_date', 'asc')
                        ->get();

                    $metodeBatch = $prettyPaymentMethod;
                    if ($creditUsed > 0) {
                        $metodeBatch = 'Kredit Klien + ' . $prettyPaymentMethod;
                    }

                    // Update batch
                    $batchPayment->update([
                        'payment_method_id' => $gatewayMethod ? $gatewayMethod->payment_method_id : null,
                        'company_bank_account_id' => $gatewayBank->company_bank_account_id, // ✅ Simpan Akun Bank
                        'status' => 'completed',
                        'notes' => ($batchPayment->notes ?? '') . " | Midtrans TX ID: $transactionId | Metode: $metodeBatch",
                    ]);

                    // Catat penggunaan kredit
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

                    // Alokasi dana ke invoice
                    $danaTersisaUntukAlokasi = $totalPaymentAmount;
                    $totalPiutangLunas = 0; // ✅ Hitung total piutang lunas
                    foreach ($invoices as $invoice) {
                        if ($danaTersisaUntukAlokasi <= 0.01) break;
                        $sisaTagihanInvoice = $invoice->remaining_balance;
                        if ($sisaTagihanInvoice <= 0.01) continue;
                        $jumlahUntukInvoiceIni = min($sisaTagihanInvoice, $danaTersisaUntukAlokasi);

                        $invoice->payments()->create([
                            'batch_payment_id' => $batchPayment->batch_payment_id,
                            'payment_date' => now(),
                            'amount' => $jumlahUntukInvoiceIni,
                            'payment_method_id' => $gatewayMethod ? $gatewayMethod->payment_method_id : null,
                            'company_bank_account_id' => $gatewayBank->company_bank_account_id, // ✅ Simpan Akun Bank
                            'received_by_user_id' => null,
                            'status' => 'completed',
                            'transaction_id' => $transactionId,
                            'notes' => 'Auto-allocated from Midtrans Batch #' . $batchPayment->batch_payment_id . " | Metode: " . $metodeBatch,
                        ]);

                        $invoice->updatePaymentStatus();
                        $danaTersisaUntukAlokasi -= $jumlahUntukInvoiceIni;
                        $totalPiutangLunas += $jumlahUntukInvoiceIni; // ✅ Hitung
                    }

                    // Kelebihan bayar
                    $overpaymentAmount = $danaTersisaUntukAlokasi; 
                    if ($overpaymentAmount > 0.01) {
                        ClientLedger::create([
                            'client_id' => $batchPayment->client_id,
                            'reference_type' => BatchPayment::class,
                            'reference_id' => $batchPayment->batch_payment_id,
                            'transaction_date' => now(),
                            'type' => 'credit',
                            'amount' => $overpaymentAmount,
                            'status' => 'available',
                            'description' => 'Kelebihan dana dari Pembayaran Batch #' . $batchPayment->batch_payment_id,
                            'user_id' => null,
                        ]);
                    }

                    // ✅ Post Jurnal Akuntansi (Agregat)
                    $journalGroupId = "BPAY-" . $batchPayment->batch_payment_id;
                    $description = "Pembayaran Midtrans Batch #" . $batchPayment->batch_payment_id;

                    $debitEntries = [];
                    $creditEntries = [];

                    if ($grossAmount > 0) {
                        $debitEntries[] = [$gatewayAccountId, $grossAmount, "Penerimaan Midtrans Batch " . $prettyPaymentMethod];
                    }
                    if ($creditUsed > 0) {
                        $debitEntries[] = [$clientDepositAccountId, $creditUsed, "Penggunaan deposit klien batch"];
                    }
                    if ($totalPiutangLunas > 0) {
                        $creditEntries[] = [$arAccountId, $totalPiutangLunas, "Pelunasan Piutang batch"];
                    }
                    if ($overpaymentAmount > 0) {
                        $creditEntries[] = [$clientDepositAccountId, $overpaymentAmount, "Kelebihan bayar batch"];
                    }
                    
                    $accountingService->postJournal(
                        $journalGroupId,
                        now(),
                        $description,
                        $debitEntries,
                        $creditEntries,
                        $batchPayment // Referensi ke BatchPayment
                    );

                });
            }
        } catch (Exception $e) {
            Log::error('Midtrans Batch Callback Error: ' . $e->getMessage() . ' on line ' . $e->getLine());
        }
    }

    /**
     * Proses pembayaran single (HANYA KREDIT).
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi.
     */
    private function processCreditOnlyPayment(SalesInvoice $invoice, float $creditToUse)
    {
        // ✅ Panggil Service Akuntansi
        $accountingService = app(AccountingService::class);
        $accountingSettings = app(AccountingSettingService::class);

        // ✅ Validasi Akun Akuntansi
        $arAccountId = $accountingSettings->getAccountsReceivableId();
        $clientDepositAccountId = $accountingSettings->getClientDepositId();
        if (!$arAccountId || !$clientDepositAccountId) {
            throw new Exception("Akun AR atau Deposit Klien belum diatur.");
        }

        DB::transaction(function () use ($invoice, $creditToUse, $accountingService, $arAccountId, $clientDepositAccountId) {
            $uniqueTransactionId = 'CREDIT-' . time() . '-' . $invoice->invoice_id;

            // 1. Buat Payment (Logika Anda)
            $payment = Payment::create([
                'invoice_id' => $invoice->invoice_id,
                'payment_date' => now(),
                'amount' => $creditToUse,
                'payment_method_id' => null,
                'transaction_id' => $uniqueTransactionId,
                'status' => 'completed',
                'notes' => 'Auto processed. Dibayar dengan Saldo Kredit.',
            ]);

            // 2. Buat ClientLedger (Logika Anda)
            ClientLedger::create([
                'client_id' => $invoice->client_id,
                'sales_invoice_id' => $invoice->invoice_id,
                'reference_type' => SalesInvoice::class, // Referensi ke Invoice
                'reference_id' => $invoice->invoice_id,
                'transaction_date' => now(),
                'type' => 'debit',
                'amount' => -$creditToUse,
                'status' => 'available',
                'description' => 'Digunakan untuk membayar Invoice #' . $invoice->invoice_number,
                'user_id' => null,
            ]);

            // 3. ✅ Post Jurnal Akuntansi
            $journalGroupId = "PAY-" . $payment->payment_id;
            $description = "Pembayaran (Kredit) Inv #" . $invoice->invoice_number;
            
            // Jurnal: (D) Deposit Klien, (K) Piutang Usaha
            $debitEntries = [
                [$clientDepositAccountId, $creditToUse, "Penggunaan deposit klien"]
            ];
            $creditEntries = [
                [$arAccountId, $creditToUse, "Pelunasan Piutang Inv #" . $invoice->invoice_number]
            ];
            
            $accountingService->postJournal($journalGroupId, now(), $description, $debitEntries, $creditEntries, $payment);

            // 4. Update status invoice
            $invoice->updatePaymentStatus();

            Log::info("Invoice #{$invoice->invoice_id} lunas dengan saldo kredit (dan dijurnal).");
        });
    }

    /**
     * Proses pembayaran batch (HANYA KREDIT).
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi.
     */
    private function processBatchCreditOnlyPayment(BatchPayment $batchPayment, $invoices, float $creditToUse)
    {
        // ✅ Panggil Service Akuntansi
        $accountingService = app(AccountingService::class);
        $accountingSettings = app(AccountingSettingService::class);

        // ✅ Validasi Akun Akuntansi
        $arAccountId = $accountingSettings->getAccountsReceivableId();
        $clientDepositAccountId = $accountingSettings->getClientDepositId();
        if (!$arAccountId || !$clientDepositAccountId) {
            throw new Exception("Akun AR atau Deposit Klien belum diatur.");
        }

        DB::transaction(function () use ($batchPayment, $invoices, $creditToUse, $accountingService, $arAccountId, $clientDepositAccountId) {
            $uniqueTransactionId = 'CREDIT-BATCH-' . time() . '-' . $batchPayment->batch_payment_id;

            // 1. Update BatchPayment (Logika Anda)
            $batchPayment->update([
                'payment_method_id' => null,
                'status' => 'completed',
                'notes' => ($batchPayment->notes ?? '') . " | Lunas dengan Saldo Kredit.",
                // 'total_amount' akan di-update di bawah
            ]);

            // 2. Buat ClientLedger (Logika Anda)
            ClientLedger::create([
                'client_id' => $batchPayment->client_id,
                'reference_type' => BatchPayment::class,
                'reference_id' => $batchPayment->batch_payment_id,
                'transaction_date' => now(),
                'type' => 'debit',
                'amount' => -$creditToUse, // Mendebit (mengurangi) saldo klien
                'status' => 'available',
                'description' => 'Digunakan untuk Pembayaran Batch #' . $batchPayment->batch_payment_id,
                'user_id' => null,
            ]);

            // 3. Alokasi (Logika Anda)
            $danaTersisaUntukAlokasi = $creditToUse;
            $totalPiutangLunas = 0;
            foreach ($invoices as $invoice) {
                if ($danaTersisaUntukAlokasi <= 0.01) break;
                $sisaTagihanInvoice = $invoice->remaining_balance;
                if ($sisaTagihanInvoice <= 0.01) continue;
                $jumlahUntukInvoiceIni = min($sisaTagihanInvoice, $danaTersisaUntukAlokasi);

                $invoice->payments()->create([
                    'batch_payment_id' => $batchPayment->batch_payment_id,
                    'payment_date' => now(),
                    'amount' => $jumlahUntukInvoiceIni,
                    'payment_method_id' => null,
                    'received_by_user_id' => null,
                    'status' => 'completed',
                    'transaction_id' => $uniqueTransactionId,
                    'notes' => 'Auto-allocated from Credit-Only Batch #' . $batchPayment->batch_payment_id,
                ]);

                $invoice->updatePaymentStatus();
                $danaTersisaUntukAlokasi -= $jumlahUntukInvoiceIni;
                $totalPiutangLunas += $jumlahUntukInvoiceIni; // ✅ Hitung total piutang lunas
            }
            
            // Sisa dana (jika ada) tetap di saldo klien, kita hanya catat yg terpakai
            // Update total_amount di batch payment sebesar yg lunas saja
            $batchPayment->update(['total_amount' => $totalPiutangLunas]);
            
            // 4. ✅ Post Jurnal Akuntansi
            $journalGroupId = "BPAY-" . $batchPayment->batch_payment_id;
            $description = "Pembayaran Batch (Kredit) #" . $batchPayment->batch_payment_id;
            
            // Jurnal: (D) Deposit Klien, (K) Piutang Usaha
            $debitEntries = [
                [$clientDepositAccountId, $totalPiutangLunas, "Penggunaan deposit klien batch"]
            ];
            $creditEntries = [
                [$arAccountId, $totalPiutangLunas, "Pelunasan Piutang batch"]
            ];
            
            $accountingService->postJournal($journalGroupId, now(), $description, $debitEntries, $creditEntries, $batchPayment);
        });
    }

    /**
     * Terjemahkan Notification Midtrans
     * ✅ DIPERBARUI: Sesuai permintaan Anda, semua disamakan.
     */
    private function translatePaymentType(Notification $notification): string
    {
        // $paymentType = $notification->payment_type ?? null;
        // (Semua logika parsing VA, QRIS, Gopay, dll dihapus)

        return 'Payment Gateway Midtrans';
    }
}