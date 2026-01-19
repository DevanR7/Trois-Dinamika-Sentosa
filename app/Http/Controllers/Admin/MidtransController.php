<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Models\PaymentGatewayCallback;
use App\Models\ClientLedger;
use App\Models\BulkSalesPayment;
use App\Models\PaymentMethod;
use App\Models\CompanyBankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Notification;
use Exception;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;

class MidtransController extends Controller
{
    public function __construct()
    {
        // Konfigurasi Midtrans Global
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * PUBLIC WEBHOOK: Menerima notifikasi otomatis dari Server Midtrans.
     * Endpoint ini dipanggil oleh Midtrans, bukan oleh User.
     */
    public function callback(Request $request)
    {
        try {
            // 1. Ambil Notifikasi dari Payload Midtrans
            $notification = new Notification();
        } catch (Exception $e) {
            Log::error('Midtrans Payload Error: ' . $e->getMessage());
            return response()->json(['message' => 'Invalid Payload'], 400);
        }

        // 2. Verifikasi Signature Key (Keamanan)
        // Rumus: SHA512(order_id + status_code + gross_amount + server_key)
        $input = $notification->order_id . $notification->status_code . $notification->gross_amount . config('midtrans.server_key');
        $signature = openssl_digest($input, 'sha512');

        if ($signature !== $notification->signature_key) {
            Log::warning("Invalid Signature for Order ID: " . $notification->order_id);
            return response()->json(['message' => 'Invalid Signature'], 403);
        }

        $rawOrderId = $notification->order_id;

        // 3. Routing Logic berdasarkan prefix Order ID
        // Format Bulk: BULK-{ID}-...
        // Format Single: INV-{ID}-...
        if (str_starts_with($rawOrderId, 'BULK-')) {
            $this->handleBulkCallback($notification);
        } else {
            $this->handleSingleCallback($notification);
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Handle Pembayaran Single Invoice
     */
    private function handleSingleCallback($notification)
    {
        $accountingService = app(AccountingService::class);
        $accountingSettings = app(AccountingSettingService::class);

        $transactionStatus = $notification->transaction_status;
        $rawOrderId = $notification->order_id;
        $transactionId = $notification->transaction_id;
        $grossAmount = (float) $notification->gross_amount; // Uang yang masuk ke Midtrans
        $paymentType = $notification->payment_type;

        // Parse Order ID untuk mendapatkan Invoice ID dan Credit Used
        // Format: INV-{ID}-T{TIMESTAMP}-C{CREDIT}
        $invoiceId = 0;
        $creditUsed = 0;
        
        if (preg_match('/^INV-(\d+)-T\d+-C([\d\.]+)$/', $rawOrderId, $matches)) {
            $invoiceId = $matches[1];
            $creditUsed = (float) $matches[2];
        } else {
            // Fallback untuk format lama (jika ada data legacy)
            $parts = explode('-', $rawOrderId);
            // Hapus bagian timestamp/random di belakang
            // Asumsi format lama: INV-{ID}-{RANDOM}
            if(count($parts) >= 2 && is_numeric($parts[1])) {
                 $invoiceId = $parts[1];
            }
        }

        // Total Pembayaran Sebenarnya = Uang Midtrans + Saldo Deposit
        $totalPaymentAmount = round($grossAmount + $creditUsed, 2);

        DB::transaction(function () use ($invoiceId, $transactionId, $transactionStatus, $grossAmount, $creditUsed, $totalPaymentAmount, $accountingService, $accountingSettings, $notification, $paymentType) {
            
            // 1. Lock Invoice untuk mencegah Race Condition
            $invoice = SalesInvoice::lockForUpdate()->find($invoiceId);
            
            if (!$invoice) {
                Log::warning("Invoice ID $invoiceId not found via Midtrans Callback.");
                return;
            }

            // 2. Idempotency Check (Cegah proses ganda jika Midtrans kirim notif berkali-kali)
            if (Payment::where('transaction_id', $transactionId)->exists()) {
                return; 
            }

            // 3. Simpan Log Raw (Untuk audit trail teknis)
            PaymentGatewayCallback::create([
                'invoice_id' => $invoice->invoice_id,
                'vendor_transaction_id' => $transactionId,
                'status' => $transactionStatus,
                'amount' => $grossAmount,
                'payment_type' => $paymentType,
                'raw_response' => (array) $notification
            ]);

            // 4. Proses Jika Status Sukses (Settlement / Capture)
            if (in_array($transactionStatus, ['capture', 'settlement'])) {
                
                // Ambil Setting Akun Akuntansi
                $gatewayAccountId = $accountingSettings->getGatewayAccountId();
                $arAccountId = $accountingSettings->getAccountsReceivableId();
                $depositAccountId = $accountingSettings->getClientDepositId();
                
                // Cari Bank Account internal yang terhubung ke Akun Gateway
                $gatewayBank = CompanyBankAccount::where('chart_of_account_id', $gatewayAccountId)->first();
                $gatewayMethod = PaymentMethod::where('type', 'gateway')->first();

                if (!$gatewayBank || !$gatewayAccountId) {
                    throw new Exception("Setup Gateway Bank / COA belum benar di sistem Admin.");
                }

                // A. Buat Payment Record (Tercatat Lunas)
                $payment = Payment::create([
                    'invoice_id' => $invoice->invoice_id,
                    'payment_date' => now(),
                    'amount' => $totalPaymentAmount, // Mencatat Total Pelunasan
                    'payment_method_id' => $gatewayMethod->payment_method_id ?? null,
                    'company_bank_account_id' => $gatewayBank->company_bank_account_id,
                    'transaction_id' => $transactionId,
                    'status' => 'completed', // Langsung Completed karena verified by System
                    'notes' => "Midtrans Auto: Gateway Rp ".number_format($grossAmount)." + Credit Rp ".number_format($creditUsed),
                ]);

                // B. Potong Saldo Klien (Ledger) jika ada penggunaan deposit
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
                        'description' => 'Pembayaran Inv #' . $invoice->invoice_number . ' (Partial Midtrans)',
                        'user_id' => null // System
                    ]);
                }

                // C. Posting Jurnal Akuntansi Otomatis
                $journalGroupId = "PAY-" . $payment->payment_id;
                
                $debitEntries = [];
                // Debit 1: Uang Masuk ke Gateway
                if ($grossAmount > 0) {
                    $debitEntries[] = [$gatewayAccountId, $grossAmount, "Masuk Gateway (Midtrans)"];
                }
                // Debit 2: Potong Deposit Klien
                if ($creditUsed > 0) {
                    $debitEntries[] = [$depositAccountId, $creditUsed, "Potong Deposit Klien"];
                }

                // Kredit: Pelunasan Piutang
                $creditEntries = [[$arAccountId, $totalPaymentAmount, "Pelunasan Inv #" . $invoice->invoice_number]];

                $accountingService->postJournal($journalGroupId, now(), "Pembayaran Midtrans Inv #".$invoice->invoice_number, $debitEntries, $creditEntries, $payment);

                // D. Update Status Invoice (Unpaid -> Paid)
                $invoice->updatePaymentStatus();
                
                // Clear Token agar tidak bisa dibayar ulang
                $invoice->update(['pending_snap_token' => null]);
            }
            // Handle Expire / Cancel / Deny
            elseif (in_array($transactionStatus, ['expire', 'cancel', 'deny'])) {
                // Hapus token agar user bisa request ulang
                $invoice->update(['pending_snap_token' => null]);
            }
        });
    }

    /**
     * Handle Pembayaran Bulk (Massal)
     */
    private function handleBulkCallback($notification)
    {
        $accountingService = app(AccountingService::class);
        $accountingSettings = app(AccountingSettingService::class);

        $transactionStatus = $notification->transaction_status;
        $rawOrderId = $notification->order_id;
        $transactionId = $notification->transaction_id;
        $grossAmount = (float) $notification->gross_amount;
        $paymentType = $notification->payment_type;

        // Parse: BULK-{ID}-T{TIMESTAMP}-C{CREDIT}
        $bulkId = 0;
        $creditUsed = 0;
        if (preg_match('/^BULK-(\d+)-T\d+-C([\d\.]+)$/', $rawOrderId, $matches)) {
            $bulkId = $matches[1];
            $creditUsed = (float) $matches[2];
        } else {
            return; 
        }

        $totalPaymentAmount = round($grossAmount + $creditUsed, 2);

        DB::transaction(function () use ($bulkId, $transactionId, $transactionStatus, $grossAmount, $creditUsed, $totalPaymentAmount, $accountingService, $accountingSettings, $notification) {
            
            // 1. Lock Bulk Payment Record
            $bulkPayment = BulkSalesPayment::lockForUpdate()->find($bulkId);
            
            // Jika sudah completed, skip (Idempotency)
            if (!$bulkPayment || $bulkPayment->status === 'completed') return;

            // 2. Simpan Log Raw
            PaymentGatewayCallback::create([
                'invoice_id' => null, 
                'vendor_transaction_id' => $transactionId,
                'status' => $transactionStatus,
                'amount' => $grossAmount,
                'payment_type' => $notification->payment_type,
                'raw_response' => (array) $notification
            ]);

            if (in_array($transactionStatus, ['capture', 'settlement'])) {
                
                // Setup Akun
                $gatewayAccountId = $accountingSettings->getGatewayAccountId();
                $arAccountId = $accountingSettings->getAccountsReceivableId();
                $depositAccountId = $accountingSettings->getClientDepositId();
                
                $gatewayBank = CompanyBankAccount::where('chart_of_account_id', $gatewayAccountId)->first();
                $gatewayMethod = PaymentMethod::where('type', 'gateway')->first();

                if (!$gatewayBank || !$gatewayAccountId) throw new Exception("Setup Gateway Bank salah.");

                // A. Update Header Bulk menjadi Completed
                $bulkPayment->update([
                    'status' => 'completed',
                    'payment_method_id' => $gatewayMethod->payment_method_id ?? null,
                    'company_bank_account_id' => $gatewayBank->company_bank_account_id ?? null,
                    'notes' => $bulkPayment->notes . " | Midtrans TX: $transactionId",
                ]);

                // B. Ambil Invoice yang akan dibayar (Dari detail JSON yang disimpan saat request)
                $invoiceIds = $bulkPayment->details['invoice_ids'] ?? [];
                $invoices = SalesInvoice::whereIn('invoice_id', $invoiceIds)
                    ->lockForUpdate()
                    ->orderBy('due_date', 'asc')
                    ->get();

                // C. Potong Deposit Klien (Untuk Bulk)
                if ($creditUsed > 0) {
                    ClientLedger::create([
                        'client_id' => $bulkPayment->client_id,
                        'reference_type' => BulkSalesPayment::class,
                        'reference_id' => $bulkPayment->bulk_sales_payment_id,
                        'transaction_date' => now(),
                        'type' => 'debit',
                        'amount' => -$creditUsed,
                        'status' => 'available',
                        'description' => 'Digunakan untuk Bulk Payment #' . $bulkPayment->payment_number,
                        'user_id' => null
                    ]);
                }

                // D. Loop Alokasi Dana ke Invoice
                $danaTersisa = $totalPaymentAmount;
                $totalAllocatedAR = 0;

                foreach ($invoices as $inv) {
                    if ($danaTersisa <= 0.01) break;

                    $sisaTagihan = $inv->remaining_balance;
                    if ($sisaTagihan <= 0.01) continue;

                    $bayarIni = min($sisaTagihan, $danaTersisa);
                    $bayarIni = round($bayarIni, 2);

                    $inv->payments()->create([
                        'bulk_sales_payment_id' => $bulkPayment->bulk_sales_payment_id,
                        'payment_date' => now(),
                        'amount' => $bayarIni,
                        'payment_method_id' => $gatewayMethod->payment_method_id ?? null,
                        'company_bank_account_id' => $gatewayBank->company_bank_account_id ?? null,
                        'status' => 'completed',
                        'transaction_id' => $transactionId,
                        'notes' => 'Auto-allocated Bulk #' . $bulkPayment->payment_number,
                    ]);

                    $inv->updatePaymentStatus();
                    $inv->update(['pending_snap_token' => null]); 

                    $danaTersisa -= $bayarIni;
                    $totalAllocatedAR += $bayarIni;
                }

                // E. Handle Overpayment (Sisa Dana -> Masuk Deposit Klien)
                if ($danaTersisa > 0.01) {
                    ClientLedger::create([
                        'client_id' => $bulkPayment->client_id,
                        'reference_type' => BulkSalesPayment::class,
                        'reference_id' => $bulkPayment->bulk_sales_payment_id,
                        'transaction_date' => now(),
                        'type' => 'credit',
                        'amount' => $danaTersisa,
                        'status' => 'available',
                        'description' => 'Kelebihan dana Bulk #' . $bulkPayment->payment_number,
                        'user_id' => null
                    ]);
                }

                // F. Jurnal Akuntansi Bulk
                $journalGroupId = "BULK-" . $bulkPayment->bulk_sales_payment_id;
                $debitEntries = [];
                $creditEntries = [];

                // Debit
                if ($grossAmount > 0) $debitEntries[] = [$gatewayAccountId, $grossAmount, "Masuk Gateway (Bulk)"];
                if ($creditUsed > 0) $debitEntries[] = [$depositAccountId, $creditUsed, "Potong Deposit (Bulk)"];

                // Kredit
                if ($totalAllocatedAR > 0) $creditEntries[] = [$arAccountId, $totalAllocatedAR, "Pelunasan AR (Bulk)"];
                if ($danaTersisa > 0) $creditEntries[] = [$depositAccountId, $danaTersisa, "Overpayment (Bulk)"];

                $accountingService->postJournal($journalGroupId, now(), "Pembayaran Midtrans Bulk", $debitEntries, $creditEntries, $bulkPayment);
            }
        });
    }
}