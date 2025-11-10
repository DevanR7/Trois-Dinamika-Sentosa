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

class MidtransController extends Controller
{
    /**
     * Konstruktor: konfigurasi Midtrans SDK dari file konfigurasi aplikasi.
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
     *
     * Alur singkat:
     *  - Validasi input (amount, use_credit)
     *  - Hitung penggunaan kredit klien dan gross amount untuk Midtrans
     *  - Jika seluruh pembayaran ditutup oleh kredit -> proses langsung (tanpa Midtrans)
     *  - Jika sudah ada pending snap token untuk pembayaran penuh, gunakan token itu
     *  - Buat Snap token dan (opsional) simpan token pending di invoice
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

        // Hitung kredit yang akan digunakan (jika diminta)
        if ($useCredit && $clientBalance > 0) {
            $totalPaymentValue = $amountFromInput + $clientBalance;
            $creditToUse = min($clientBalance, $sisaTagihan, $totalPaymentValue);
        }

        // Gross amount yang akan dibayar melalui Midtrans (dalam satuan mata uang utuh)
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
                $this->processCreditOnlyPayment($invoice, $creditToUse);
                return response()->json(['snap_token' => null, 'status' => 'paid_by_credit']);
            } catch (Exception $e) {
                Log::error('Gagal proses bayar dengan kredit (Single): ' . $e->getMessage());
                return response()->json(['message' => 'Gagal memproses pembayaran dengan saldo kredit.'], 500);
            }
        }

        // Jika ini pelunasan penuh dan sudah ada snap token pending yang belum expired, gunakan yang tersimpan
        $isFullPayment = abs($totalPaymentValue - $sisaTagihan) < 0.01;
        if ($isFullPayment && $invoice->pending_snap_token && $invoice->pending_snap_expires_at > now()) {
            Log::info("Menggunakan snap token tersimpan untuk Invoice: {$invoice->invoice_number}");
            return response()->json(['snap_token' => $invoice->pending_snap_token]);
        }

        // Persiapkan parameter untuk Snap
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

        // Panggil Midtrans Snap untuk mendapatkan token
        try {
            $snapToken = Snap::getSnapToken($params);

            // Simpan token jika ini pelunasan penuh (agar pengguna tidak membuat multiple token)
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
     *
     * Alur singkat:
     *  - Validasi input (daftar invoice, amount, use_credit)
     *  - Hitung total tagihan terpilih, kredit yang dipakai, gross untuk Midtrans
     *  - Buat BatchPayment (induk) untuk menyimpan metadata sementara
     *  - Jika pembayaran sepenuhnya oleh kredit -> proses langsung (tanpa Midtrans)
     *  - Jika perlu Midtrans -> buat Snap token untuk batch
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

        // Buat BatchPayment sebagai induk untuk proses selanjutnya
        DB::beginTransaction();
        try {
            $batchPayment = BatchPayment::create([
                'client_id' => $client->client_id,
                'processed_by_user_id' => null,
                'payment_date' => now(),
                'total_amount' => $totalPaymentValue,
                'payment_method_id' => null,
                'status' => 'pending',
                'details' => ['invoice_ids' => $validated['invoice_ids']],
            ]);

            $uniqueOrderId = 'BATCH-' . $batchPayment->batch_payment_id . '-T' . time() . '-C' . $creditToUse;

            // Jika seluruh pembayaran ditutup oleh kredit -> proses langsung
            if ($grossAmountForMidtrans == 0 && $creditToUse > 0) {
                $this->processBatchCreditOnlyPayment($batchPayment, $invoices, $totalPaymentValue);
                DB::commit();
                return response()->json(['snap_token' => null, 'status' => 'paid_by_credit']);
            }

            // Siapkan params Snap untuk batch
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
     *
     * - Menerima Notification dari Midtrans SDK
     * - Membedakan antara order tunggal dan batch berdasarkan pola order_id
     * - Mendelegasikan ke handler yang sesuai
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
     *
     * Tanggung jawab:
     *  - Validasi dan idempotency (cek apakah tx sudah diproses)
     *  - Simpan/ubah PaymentGatewayCallback
     *  - Jika status settled/capture -> buat Payment, update ClientLedger (kredit jika ada), update invoice
     *  - Jika status expire -> hapus pending snap token di invoice (jika ada)
     */
    private function handleSingleCallback(Notification $notification)
    {
        try {
            $transactionStatus = $notification->transaction_status ?? null;
            $rawOrderId = $notification->order_id ?? '';
            $transactionId = $notification->transaction_id ?? null;
            $paymentType = $notification->payment_type ?? null;
            $grossAmount = (float) ($notification->gross_amount ?? 0);

            // Ekstraksi invoice number dan kredit dari pola order_id
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

            // Idempotency: apakah transaksi capture/settlement sudah diproses sebelumnya?
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
                DB::transaction(function () use ($invoice, $transactionId, $grossAmount, $notification, $creditUsed, $totalPaymentAmount) {
                    // Pastikan tidak ganda
                    $existingPayment = Payment::where('transaction_id', $transactionId)->first();
                    if ($existingPayment) {
                        return;
                    }

                    // Ambil metode payment gateway (jika ada)
                    $gatewayMethod = PaymentMethod::where('type', 'gateway')->first();

                    // Terjemahkan jenis pembayaran untuk catatan
                    $prettyPaymentMethod = $this->translatePaymentType($notification);
                    $metodeLog = ($creditUsed > 0) ? 'Kredit Klien + ' . $prettyPaymentMethod : $prettyPaymentMethod;
                    $catatanLog = "Auto processed. Midtrans: " . number_format($grossAmount) . ". Saldo Kredit: " . number_format($creditUsed);

                    // Simpan Payment
                    Payment::create([
                        'invoice_id' => $invoice->invoice_id,
                        'payment_date' => now(),
                        'amount' => $totalPaymentAmount,
                        'payment_method_id' => $gatewayMethod ? $gatewayMethod->payment_method_id : null,
                        'transaction_id' => $transactionId,
                        'status' => 'completed',
                        'notes' => $catatanLog . " | Metode: " . $metodeLog,
                    ]);

                    // Jika ada pemakaian kredit, catat di ledger
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

                    // Update status invoice sesuai rule di model
                    $invoice->updatePaymentStatus();
                });
            } elseif ($transactionStatus === 'expire') {
                // Jika order expired, hapus pending token jika ada
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
     *
     * Tanggung jawab:
     *  - Validasi format order_id batch
     *  - Simpan callback raw
     *  - Jika settled/capture -> update BatchPayment, catat kredit (jika ada), alokasikan dana ke invoice, buat ledger untuk overpayment jika perlu
     */
    private function handleBatchCallback(Notification $notification)
    {
        try {
            $transactionStatus = $notification->transaction_status ?? null;
            $rawOrderId = $notification->order_id ?? '';
            $transactionId = $notification->transaction_id ?? null;
            $paymentType = $notification->payment_type ?? null;
            $grossAmount = (float) ($notification->gross_amount ?? 0);

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

            // Idempotency: cek apakah tx sudah diproses
            $isProcessed = PaymentGatewayCallback::where('vendor_transaction_id', $transactionId)
                ->whereIn('status', ['settlement', 'capture'])
                ->exists();

            if ($isProcessed) {
                Log::warning("Batch Callback already processed for tx_id: {$transactionId}");
                return;
            }

            // Simpan atau update callback record
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
                $prettyPaymentMethod = $this->translatePaymentType($notification);

                DB::transaction(function () use ($batchPayment, $transactionId, $prettyPaymentMethod, $creditUsed, $totalPaymentAmount) {
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

                    // Update batch sebagai completed dan simpan metode/notes
                    $batchPayment->update([
                        'payment_method_id' => $gatewayMethod ? $gatewayMethod->payment_method_id : null,
                        'status' => 'completed',
                        'notes' => ($batchPayment->notes ?? '') . " | Midtrans TX ID: $transactionId | Metode: $metodeBatch",
                    ]);

                    // Jika ada penggunaan kredit, catat di ledger
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

                    // Alokasikan dana ke invoice satu per satu
                    $danaTersisaUntukAlokasi = $totalPaymentAmount;
                    foreach ($invoices as $invoice) {
                        if ($danaTersisaUntukAlokasi <= 0.01) {
                            break;
                        }

                        $sisaTagihanInvoice = $invoice->remaining_balance;
                        if ($sisaTagihanInvoice <= 0.01) {
                            continue;
                        }

                        $jumlahUntukInvoiceIni = min($sisaTagihanInvoice, $danaTersisaUntukAlokasi);

                        $invoice->payments()->create([
                            'batch_payment_id' => $batchPayment->batch_payment_id,
                            'payment_date' => now(),
                            'amount' => $jumlahUntukInvoiceIni,
                            'payment_method_id' => $gatewayMethod ? $gatewayMethod->payment_method_id : null,
                            'received_by_user_id' => null,
                            'status' => 'completed',
                            'transaction_id' => $transactionId,
                            'notes' => 'Auto-allocated from Midtrans Batch #' . $batchPayment->batch_payment_id . " | Metode: " . $metodeBatch,
                        ]);

                        // Update status invoice sesuai model
                        $invoice->updatePaymentStatus();

                        $danaTersisaUntukAlokasi -= $jumlahUntukInvoiceIni;
                    }

                    // Jika ada sisa dana setelah alokasi -> simpan ke ClientLedger sebagai kredit
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
     * Proses pembayaran single yang dibayar hanya dengan kredit klien (tanpa Midtrans).
     *
     * - Buat Payment
     * - Buat ClientLedger untuk mengurangi saldo
     * - Update status invoice
     */
    private function processCreditOnlyPayment(SalesInvoice $invoice, float $creditToUse)
    {
        DB::transaction(function () use ($invoice, $creditToUse) {
            $uniqueTransactionId = 'CREDIT-' . time() . '-' . $invoice->invoice_id;

            Payment::create([
                'invoice_id' => $invoice->invoice_id,
                'payment_date' => now(),
                'amount' => $creditToUse,
                'payment_method_id' => null,
                'transaction_id' => $uniqueTransactionId,
                'status' => 'completed',
                'notes' => 'Auto processed. Dibayar dengan Saldo Kredit.',
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

            $invoice->updatePaymentStatus();

            Log::info("Invoice #{$invoice->invoice_id} lunas dengan saldo kredit.");
        });
    }

    /**
     * Proses pembayaran batch yang dibayar hanya dengan kredit klien (tanpa Midtrans).
     *
     * - Update BatchPayment menjadi completed
     * - Buat ClientLedger (debit)
     * - Alokasikan kredit ke invoice
     */
    private function processBatchCreditOnlyPayment(BatchPayment $batchPayment, $invoices, float $creditToUse)
    {
        DB::transaction(function () use ($batchPayment, $invoices, $creditToUse) {
            $uniqueTransactionId = 'CREDIT-BATCH-' . time() . '-' . $batchPayment->batch_payment_id;

            $batchPayment->update([
                'payment_method_id' => null,
                'status' => 'completed',
                'notes' => ($batchPayment->notes ?? '') . " | Lunas dengan Saldo Kredit.",
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
                if ($danaTersisaUntukAlokasi <= 0.01) {
                    break;
                }

                $sisaTagihanInvoice = $invoice->remaining_balance;
                if ($sisaTagihanInvoice <= 0.01) {
                    continue;
                }

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
            }
        });
    }

    /**
     * Terjemahkan Notification Midtrans menjadi nama metode pembayaran yang human-readable.
     *
     * Contoh hasil:
     *  - "BCA Virtual Account"
     *  - "Indomaret"
     *  - "QRIS (AcquirerName)"
     *  - "Gopay"
     *  - "Payment Gateway Midtrans" (fallback)
     */
    private function translatePaymentType(Notification $notification): string
    {
        $paymentType = $notification->payment_type ?? null;

        if (isset($notification->va_numbers) && is_array($notification->va_numbers) && count($notification->va_numbers) > 0) {
            $bank = $notification->va_numbers[0]['bank'] ?? 'va';
            return strtoupper($bank) . ' Virtual Account';
        }

        if ($paymentType === 'cstore') {
            return $notification->store ?? 'Convenience Store';
        }

        if ($paymentType === 'qris') {
            return 'QRIS (' . ($notification->acquirer ?? '') . ')';
        }

        if (in_array($paymentType, ['gopay', 'shopeepay'])) {
            return ucwords($paymentType);
        }

        if ($paymentType === 'bank_transfer' && isset($notification->permata_va_number)) {
            return 'Permata Virtual Account';
        }

        return 'Payment Gateway Midtrans';
    }
}
