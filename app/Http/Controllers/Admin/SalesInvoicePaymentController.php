<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Models\ClientLedger;
use App\Models\PaymentMethod;
use App\Models\CompanyBankAccount;
use App\Models\Client;
use App\Models\GeneralLedger;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;
use App\Traits\ValidatesAccountingPeriod;

class SalesInvoicePaymentController extends Controller
{
    use ValidatesAccountingPeriod;

    protected $accountingService;
    protected $accountingSettings;

    public function __construct(
        AccountingService $accountingService,
        AccountingSettingService $accountingSettingService
    ) {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettingService;

        // Permission Middleware
        $this->middleware('can:create-payments')->only(['store']);
        $this->middleware('can:manage-payment-clearance')->only(['approve', 'reject', 'destroy']);
    }

    /**
     * Store: Input Pembayaran Manual (Cash/Transfer Bank/Full Kredit)
     */
    public function store(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        // 1. PRE-PROCESSING & SANITIZATION
        // Konversi checkbox ke boolean dan bersihkan format angka
        $request->merge([
            'use_credit' => $request->boolean('use_credit'),
            'amount' => (float) str_replace(['.', ','], ['', '.'], $request->input('amount', 0)), // Asumsi format 1.000,00 -> 1000.00
        ]);

        $inputAmount = $request->input('amount');
        $useCredit = $request->input('use_credit');

        // 2. VALIDASI DINAMIS
        $rules = [
            'payment_date' => 'required|date',
            // Amount boleh 0 HANYA JIKA use_credit dicentang (Full Deposit Payment)
            'amount' => [
                'required', 
                'numeric', 
                'min:0',
                function ($attribute, $value, $fail) use ($useCredit) {
                    if ($value <= 0 && !$useCredit) {
                        $fail('Jumlah uang diterima harus lebih dari 0 jika tidak menggunakan deposit.');
                    }
                },
            ],
            // Payment Method & Bank wajib jika ada uang fisik yang masuk (Amount > 0)
            'payment_method_id' => [
                Rule::requiredIf(fn() => $inputAmount > 0),
                'nullable', 'exists:payment_methods,payment_method_id',
            ],
            'company_bank_account_id' => [
                Rule::requiredIf(fn() => $inputAmount > 0),
                'nullable', 'exists:company_bank_accounts,company_bank_account_id',
            ],
            'notes' => 'nullable|string',
            'use_credit' => 'boolean',
        ];

        // Validasi Bukti & Referensi (Hanya jika ada pembayaran fisik)
        if ($inputAmount > 0 && $request->filled('payment_method_id')) {
            $paymentMethod = PaymentMethod::find($request->input('payment_method_id'));
            
            $config = $paymentMethod->internal_input_config ?? 'none';
            
            $rules['proof_of_payment'] = in_array($config, ['proof_only', 'proof_and_reference']) 
                ? 'required|image|mimes:jpeg,png,jpg|max:2048' : 'nullable|image|mimes:jpeg,png,jpg|max:2048';
                
            $rules['reference_number'] = in_array($config, ['reference_only', 'proof_and_reference']) 
                ? 'required|string|max:255' : 'nullable|string|max:255';
        } else {
            $rules['proof_of_payment'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048';
            $rules['reference_number'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        // Cek Tutup Buku
        if ($this->isDateClosed($request->payment_date)) {
            return back()->with('error', 'Gagal: Tanggal pembayaran masuk periode tutup buku.')->withInput();
        }

        DB::beginTransaction();
        try {
            // Locking
            $invoiceLocked = SalesInvoice::lockForUpdate()->find($invoice->invoice_id);
            $client = Client::lockForUpdate()->find($invoiceLocked->client_id);

            // Hitung Sisa Tagihan Real-time
            $sisaTagihan = $invoiceLocked->remaining_balance; // Menggunakan Accessor di Model

            // Jika hasil remaining_balance negatif (Overpaid dari awal), anggap 0 untuk pembayaran baru
            if ($sisaTagihan < 0) $sisaTagihan = 0;

            if ($sisaTagihan <= 0.01) {
                throw new \Exception("Invoice ini sudah lunas sepenuhnya.");
            }

            // --- LOGIKA ALOKASI DANA (SUBSIDI DEPOSIT) ---
            $kreditTerpakai = 0;
            $transferTerpakai = 0;
            $depositBaru = 0; // Overpayment

            // 1. Hitung Penggunaan Kredit (Subsidi)
            if ($useCredit && $client->balance > 0) {
                // Gunakan saldo maksimal yang ada, atau maksimal sebesar tagihan
                $kreditTerpakai = min($client->balance, $sisaTagihan);
            }
            // Sisa tagihan setelah dipotong kredit
            $sisaSetelahKredit = max(0, $sisaTagihan - $kreditTerpakai);

            // 2. Hitung Penggunaan Uang Tunai/Transfer (Manual)
            if ($inputAmount > 0) {
                // Jika input transfer lebih besar dari sisa tagihan, sisanya jadi deposit baru
                if ($inputAmount >= $sisaSetelahKredit) {
                    $transferTerpakai = $sisaSetelahKredit;
                    $depositBaru = $inputAmount - $sisaSetelahKredit;
                } else {
                    $transferTerpakai = $inputAmount;
                }
            }

            // Total yang dialokasikan ke pelunasan piutang invoice ini
            $totalAllocatedToInvoice = round($kreditTerpakai + $transferTerpakai, 2);

            // Validasi Logika Bisnis: Harus ada pergerakan dana/kredit
            if ($totalAllocatedToInvoice <= 0.01 && $depositBaru <= 0.01) {
                throw new \Exception("Tidak ada alokasi pembayaran yang valid. Cek saldo deposit atau input jumlah pembayaran.");
            }

            // --- SETUP AKUN & SIMPAN ---
            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();

            if (!$arAccountId || !$clientDepositAccountId) {
                throw new \Exception("Akun AR atau Deposit belum diatur.");
            }

            $cashBankAccountId = null;
            if ($inputAmount > 0) {
                $cashBankAccount = CompanyBankAccount::find($validated['company_bank_account_id']);
                if (!$cashBankAccount || !$cashBankAccount->chart_of_account_id) throw new \Exception("Akun Bank tidak valid.");
                $cashBankAccountId = $cashBankAccount->chart_of_account_id;
            }

            // Upload Bukti
            $proofPath = $request->hasFile('proof_of_payment') 
                ? $request->file('proof_of_payment')->store('payment_proofs', 'public') : null;

            // Status default payment
            $paymentMethodObj = null;
            if ($inputAmount > 0) {
                $paymentMethodObj = PaymentMethod::find($validated['payment_method_id']);
            }
            
            $statusPayment = ($paymentMethodObj && $paymentMethodObj->internal_status_default == 'pending_verification') 
                ? 'pending_verification' 
                : 'completed';

            // Catatan
            $catatanLog = $validated['notes'] ?? '';
            if ($kreditTerpakai > 0) $catatanLog .= " | Subsidi Deposit: " . number_format($kreditTerpakai);
            if ($depositBaru > 0) $catatanLog .= " | Overpay -> Deposit: " . number_format($depositBaru);

            // A. Create Payment Record (Mencatat total pelunasan invoice)
            $payment = $invoiceLocked->payments()->create([
                'amount' => $totalAllocatedToInvoice, 
                'payment_date' => $validated['payment_date'],
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                'status' => $statusPayment,
                'notes' => $catatanLog,
                'received_by_user_id' => Auth::id(),
                'proof_of_payment_path' => $proofPath,
                'reference_number' => $validated['reference_number'] ?? null,
            ]);

            // --- HANDLING LEDGER & JURNAL ---
            
            // 1. Potong Deposit (Debit Ledger Client)
            // Ini dicatat terlepas dari status payment manual, karena deposit sifatnya internal
            if ($kreditTerpakai > 0) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'sales_invoice_id' => $invoiceLocked->invoice_id,
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit',
                    'amount' => -$kreditTerpakai,
                    'status' => 'available',
                    'description' => 'Subsidi Pembayaran Inv #' . $invoiceLocked->invoice_number,
                    'user_id' => Auth::id(),
                ]);
            }

            // 2. Tambah Deposit Baru (Credit Ledger Client - Overpayment)
            // Ini hanya jika ada uang fisik berlebih yang masuk
            if ($depositBaru > 0) {
                // Jika payment manual pending, deposit ini juga sebaiknya pending sampai verified?
                // Namun untuk simplifikasi admin, biasanya langsung available atau ikut status payment.
                // Disini kita ikut status payment manualnya.
                $depositStatus = ($statusPayment == 'completed') ? 'available' : 'pending';

                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit',
                    'amount' => $depositBaru,
                    'status' => $depositStatus,
                    'description' => 'Kelebihan bayar Inv #' . $invoiceLocked->invoice_number,
                    'user_id' => Auth::id(),
                ]);
            }

            // 3. Post Jurnal (Hanya jika Completed)
            if ($statusPayment == 'completed') {
                $journalGroupId = "PAY-" . $payment->payment_id;
                $debitEntries = [];
                $creditEntries = [];

                // Debit 1: Kas (Jika ada uang fisik masuk) - Total input (tagihan + overpay)
                if ($inputAmount > 0 && $cashBankAccountId) {
                    $bankAcc = CompanyBankAccount::find($validated['company_bank_account_id']);
                    $debitEntries[] = [$cashBankAccountId, $inputAmount, "Masuk ke " . $bankAcc->account_name];
                }
                
                // Debit 2: Deposit Klien (Jika pakai saldo)
                if ($kreditTerpakai > 0) {
                    $debitEntries[] = [$clientDepositAccountId, $kreditTerpakai, "Potong Deposit"];
                }

                // Kredit 1: Piutang (Sebesar alokasi ke invoice)
                if ($totalAllocatedToInvoice > 0) {
                    $creditEntries[] = [$arAccountId, $totalAllocatedToInvoice, "Pelunasan Piutang"];
                }

                // Kredit 2: Deposit Klien (Jika Overpay)
                if ($depositBaru > 0) {
                    $creditEntries[] = [$clientDepositAccountId, $depositBaru, "Overpayment (Deposit)"];
                }

                $this->accountingService->postJournal(
                    $journalGroupId, 
                    $validated['payment_date'], 
                    "Pembayaran Inv #".$invoiceLocked->invoice_number, 
                    $debitEntries, 
                    $creditEntries, 
                    $payment, 
                    Auth::id()
                );
            }

            $invoiceLocked->updatePaymentStatus();
            DB::commit();

            return back()->with('success', 'Pembayaran berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sales Payment Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        if ($payment->bulk_sales_payment_id) {
            return back()->with('error', 'GAGAL: Pembayaran ini adalah bagian dari Pembayaran Massal (Bulk #' . $payment->bulkPayment->payment_number . '). Untuk membatalkan, silakan hapus data induk di menu "Pembayaran Massal".');
        }
        // =================

        $journalGroupId = "PAY-" . $payment->payment_id;

        if ($error = $this->checkTransactionLock($payment->payment_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus: " . $error);
        }

        DB::beginTransaction();
        try {
            $invoice = $payment->salesInvoice;
            
            // 1. Hapus Ledger terkait (Baik debit subsidi maupun credit overpayment)
            ClientLedger::where('reference_type', Payment::class)
                        ->where('reference_id', $payment->payment_id)
                        ->delete();

            // 2. Reversal Jurnal (Jika Completed)
            if ($payment->status == 'completed') {
                $this->reverseAndClearJournal($journalGroupId, $payment);
            }

            // 3. Hapus Payment
            $payment->delete();
            
            // 4. Update Invoice
            if ($invoice) $invoice->updatePaymentStatus();

            DB::commit();
            return back()->with('success', 'Pembayaran berhasil dihapus.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }
    
    // --- Helper Methods ---
    
    public function approve(Payment $payment): RedirectResponse
    {
        // ... (Logic approve sama seperti di PaymentClearanceController, atau redirect kesana)
        // Untuk konsistensi, sebaiknya gunakan logic yang sama. Disini saya singkatkan redirect.
        return app(PaymentClearanceController::class)->approveSalesPayment($payment);
    }

    public function reject(Payment $payment): RedirectResponse
    {
        return app(PaymentClearanceController::class)->rejectSalesPayment($payment);
    }

    private function reverseAndClearJournal($journalGroupId, $payment)
    {
        $entries = GeneralLedger::where('journal_group_id', $journalGroupId)->get();
        if ($entries->isEmpty()) return;

        $debitEntries = [];
        $creditEntries = [];

        foreach ($entries as $entry) {
            if ($entry->debit > 0) $creditEntries[] = [$entry->chart_of_account_id, $entry->debit, "Reversal"];
            if ($entry->credit > 0) $debitEntries[] = [$entry->chart_of_account_id, $entry->credit, "Reversal"];
        }

        if (!empty($debitEntries)) {
            $this->accountingService->postJournal("PAY-REV-" . $payment->payment_id, now(), "Reversal Pembayaran", $debitEntries, $creditEntries, $payment, Auth::id());
        }
        
        GeneralLedger::where('journal_group_id', $journalGroupId)->delete();
    }
}