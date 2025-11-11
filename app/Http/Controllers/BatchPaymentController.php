<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\SalesInvoice;
use App\Models\BatchPayment;
use App\Models\Payment;
use App\Models\ClientLedger;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\CompanyBankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;

class BatchPaymentController extends Controller
{
    /**
     * ✅ Inject Service Akuntansi
     */
    protected $accountingService;
    protected $accountingSettings;

    /**
     * ===========================================
     * BAGIAN: Middleware otorisasi (permission)
     * ===========================================
     */
    public function __construct(
        AccountingService $accountingService, 
        AccountingSettingService $accountingSettingService
    ) {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettingService;
        
        $this->middleware('permission:create-batch-payments')->only(['create', 'store', 'getUnpaidInvoicesApi']);
        $this->middleware('permission:review-batch-payments')->only(['pending', 'showPending', 'approve', 'reject']);
    }

    /**
     * ==============================================
     * BAGIAN: Form untuk membuat Batch Payment baru
     * ==============================================
     */
    public function create(): View
    {
        $clients = Client::orderBy('client_name')->get();
        
        $paymentMethods = PaymentMethod::where('is_active', true)
                            ->whereIn('type', ['direct', 'pending'])
                            ->orderBy('name')
                            ->get();
        
        $companyBankAccounts = CompanyBankAccount::where('is_active', true)
                                ->orderBy('bank_name')
                                ->get();
                            
        return view('batch_payments.create', compact('clients', 'paymentMethods', 'companyBankAccounts'));
    }

    /**
     * ======================================================
     * BAGIAN: API untuk mengambil invoice klien yang belum lunas
     * ======================================================
     */
    public function getUnpaidInvoicesApi(Client $client): JsonResponse
    {
        $invoices = $client->salesInvoices()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->with(['deductingReturns', 'adjustments'])
            ->orderBy('due_date', 'asc')
            ->get();

        $invoicesWithBalance = $invoices->map(function ($invoice) {
            return [
                'invoice_id' => $invoice->invoice_id,
                'invoice_number' => $invoice->invoice_number,
                'due_date_formatted' => $invoice->due_date->format('d M Y'),
                'sisa_tagihan' => $invoice->remaining_balance,
            ];
        })->filter(fn($inv) => $inv['sisa_tagihan'] > 0.01);

        return response()->json($invoicesWithBalance);
    }

    /**
     * =========================================================
     * BAGIAN: Menyimpan batch payment yang dibuat oleh Admin
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi
     * =========================================================
     */
    public function store(Request $request): RedirectResponse
    {
        // Validasi awal input pengguna
        $rules = [
            'client_id' => 'required|exists:clients,client_id',
            'payment_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'payment_method_id' => [
                'required_unless:total_amount,0',
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
            'company_bank_account_id' => [
                'required_unless:total_amount,0',
                'nullable',
                'exists:company_bank_accounts,company_bank_account_id',
            ],
            'notes' => 'nullable|string',
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'required|exists:sales_invoices,invoice_id',
            'use_credit' => 'nullable|boolean',
        ];

        // Atur validasi tambahan berdasarkan konfigurasi Payment Method
        $paymentMethod = $request->filled('payment_method_id')
            ? PaymentMethod::find($request->input('payment_method_id'))
            : null;

        if ($paymentMethod) {
            $config = $paymentMethod->required_fields_config;

            if ($config === 'proof_only' || $config === 'proof_and_reference') {
                $rules['proof_of_payment'] = 'required|image|mimes:jpeg,png,jpg|max:2048';
            } else {
                $rules['proof_of_payment'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048';
            }

            if ($config === 'reference_only' || $config === 'proof_and_reference') {
                $rules['reference_number'] = 'required|string|max:255';
            } else {
                $rules['reference_number'] = 'nullable|string|max:255';
            }
        } else {
            $rules['proof_of_payment'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048';
            $rules['reference_number'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            // Inisialisasi data klien dan input
            $client = Client::findOrFail($validated['client_id']);
            $pakaiKredit = $validated['use_credit'] ?? false;
            $danaDariInput = (float) ($validated['total_amount'] ?? 0);
            $kreditAwalKlien = $client->balance;

            // Ambil daftar invoice yang akan dibayar
            $invoicesDipilih = SalesInvoice::whereIn('invoice_id', $validated['invoice_ids'])
                ->with(['deductingReturns', 'adjustments'])
                ->orderBy('due_date', 'asc')
                ->get();

            // Hitung total tagihan yang dipilih
            $totalTagihanTerpilih = $invoicesDipilih->reduce(fn($carry, $inv) => $carry + $inv->remaining_balance, 0.0);
            if ($totalTagihanTerpilih <= 0.01) {
                throw new \Exception("Semua invoice yang dipilih sudah lunas.");
            }

            // Hitung alokasi kredit dan dana input
            $kreditAkanDigunakan = ($pakaiKredit && $kreditAwalKlien > 0)
                ? min($kreditAwalKlien, $totalTagihanTerpilih)
                : 0;

            $sisaTagihanSetelahKredit = max(0, $totalTagihanTerpilih - $kreditAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahKredit);
            $totalDanaAlokasi = $kreditAkanDigunakan + $danaInputAkanDigunakan;
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            if ($totalDanaAlokasi <= 0.01 && $sisaDanaInput <= 0.01) {
                throw new \Exception("Tidak ada dana (input/kredit) yang bisa dialokasikan.");
            }

            // ✅ Validasi Akun Akuntansi
            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            if (!$arAccountId || !$clientDepositAccountId) {
                throw new \Exception("Akun Piutang Usaha (AR) atau Akun Deposit Klien belum diatur.");
            }
            
            $cashBankAccount = null;
            $cashBankAccountId = null;
            if ($danaDariInput > 0) {
                $cashBankAccount = CompanyBankAccount::find($validated['company_bank_account_id']);
                if (!$cashBankAccount || !$cashBankAccount->chart_of_account_id) {
                    throw new \Exception("Akun Bank Perusahaan yang dipilih belum terhubung ke Chart of Account.");
                }
                $cashBankAccountId = $cashBankAccount->chart_of_account_id;
            }

            // Tentukan status pembayaran berdasarkan tipe metode
            $paymentMethodType = $paymentMethod->type ?? 'direct';
            $newPaymentStatus = ($paymentMethodType == 'pending') ? 'pending_clearance' : 'completed';

            // Simpan bukti pembayaran jika ada
            $proofPath = $request->hasFile('proof_of_payment')
                ? $request->file('proof_of_payment')->store('payment_proofs', 'public')
                : null;

            // Buat record batch payment baru
            $batchPayment = BatchPayment::create([
                'client_id' => $validated['client_id'],
                'processed_by_user_id' => Auth::id(),
                'payment_date' => $validated['payment_date'],
                'total_amount' => $totalDanaAlokasi,
                'notes' => $validated['notes'],
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                'status' => $newPaymentStatus,
                'details' => ['invoice_ids' => $validated['invoice_ids']],
                'reference_number' => $validated['reference_number'] ?? null,
                'proof_of_payment_path' => $proofPath,
            ]);

            // Catat penggunaan kredit (jika ada)
            if ($kreditAkanDigunakan > 0) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => BatchPayment::class,
                    'reference_id' => $batchPayment->batch_payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit',
                    'amount' => -$kreditAkanDigunakan,
                    'status' => 'available',
                    'description' => 'Digunakan untuk Pembayaran Batch #' . $batchPayment->batch_payment_id,
                    'user_id' => Auth::id(),
                ]);
            }

            // Distribusikan pembayaran ke tiap invoice
            $sisaKredit = $kreditAkanDigunakan;
            $sisaInput = $danaInputAkanDigunakan;
            $alokasiLog = [];

            foreach ($invoicesDipilih as $invoice) {
                if ($sisaKredit <= 0.01 && $sisaInput <= 0.01) break;

                $sisaTagihan = $invoice->remaining_balance;
                if ($sisaTagihan <= 0.01) continue;

                $bayarDariKredit = min($sisaTagihan, $sisaKredit);
                $bayarDariInput = min($sisaTagihan - $bayarDariKredit, $sisaInput);
                $jumlahBayar = $bayarDariKredit + $bayarDariInput;

                $payment = $invoice->payments()->create([
                    'batch_payment_id' => $batchPayment->batch_payment_id,
                    'payment_date' => $validated['payment_date'],
                    'amount' => $jumlahBayar,
                    'payment_method_id' => $validated['payment_method_id'] ?? null,
                    'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                    'status' => $newPaymentStatus,
                    'received_by_user_id' => Auth::id(),
                    'notes' => 'Auto-allocated dari Batch Payment #' . $batchPayment->batch_payment_id,
                    'reference_number' => $validated['reference_number'] ?? null,
                    'proof_of_payment_path' => $proofPath,
                ]);

                if ($newPaymentStatus == 'completed') {
                    $invoice->updatePaymentStatus();
                }

                $sisaKredit -= $bayarDariKredit;
                $sisaInput -= $bayarDariInput;
                $alokasiLog[] = "Rp " . number_format($jumlahBayar) . " dialokasikan ke " . $invoice->invoice_number;
            }

            // Jika ada sisa dana input, dikembalikan ke saldo klien
            if ($sisaDanaInput > 0.01) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => BatchPayment::class,
                    'reference_id' => $batchPayment->batch_payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit',
                    'amount' => $sisaDanaInput,
                    'status' => 'available',
                    'description' => 'Kelebihan dana dari Pembayaran Batch #' . $batchPayment->batch_payment_id,
                    'user_id' => Auth::id(),
                ]);
            }

            // ✅ Post Jurnal Akuntansi (Agregat)
            if ($newPaymentStatus == 'completed') {
                $journalGroupId = "BPAY-" . $batchPayment->batch_payment_id;
                $description = "Penerimaan Batch Payment #" . $batchPayment->batch_payment_id . " dari " . $client->client_name;
                
                // Jurnal seimbang:
                // (D) Kas/Bank         (Total Kas Masuk)   : $danaDariInput
                // (D) Deposit Klien    (Deposit Terpakai)  : $kreditAkanDigunakan
                // (K) Piutang Usaha    (Piutang Lunas)     : $totalDanaAlokasi
                // (K) Deposit Klien    (Kelebihan Bayar)   : $sisaDanaInput
                
                $debitEntries = [];
                $creditEntries = [];

                if ($danaDariInput > 0 && $cashBankAccountId) {
                    $debitEntries[] = [$cashBankAccountId, $danaDariInput, "Penerimaan batch ke " . $cashBankAccount->account_name];
                }
                if ($kreditAkanDigunakan > 0) {
                    $debitEntries[] = [$clientDepositAccountId, $kreditAkanDigunakan, "Penggunaan deposit klien batch"];
                }
                
                if ($totalDanaAlokasi > 0) {
                    $creditEntries[] = [$arAccountId, $totalDanaAlokasi, "Pelunasan Piutang batch"];
                }
                if ($sisaDanaInput > 0) {
                    $creditEntries[] = [$clientDepositAccountId, $sisaDanaInput, "Kelebihan bayar batch"];
                }
                
                $this->accountingService->postJournal(
                    $journalGroupId,
                    $validated['payment_date'],
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $batchPayment
                );
            }

            DB::commit();
            return redirect()->route('clients.show', $client->client_id)
                             ->with('success', 'Pembayaran batch berhasil. ' . implode('. ', $alokasiLog));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan batch: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal menyimpan pembayaran batch: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * ========================================
     * BAGIAN: Menampilkan batch yang pending
     * ========================================
     */
    public function pending(): View
    {
        $pendingBatches = BatchPayment::where('status', 'pending_verification')
            ->with(['client', 'paymentMethod'])
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return view('batch_payments.pending', compact('pendingBatches'));
    }

    /**
     * ====================================================
     * BAGIAN: Menampilkan detail batch payment yang pending
     * ====================================================
     */
    public function showPending(BatchPayment $batchPayment): View|RedirectResponse
    {
        if ($batchPayment->status !== 'pending_verification') {
            return redirect()->route('batch-payments.pending')
                ->with('error', 'Pembayaran ini sudah diproses.');
        }

        $batchPayment->load(['client', 'paymentMethod']); 
        $details = $batchPayment->details ?? [];
        $invoiceIds = $details['invoice_ids'] ?? [];

        $invoices = SalesInvoice::whereIn('invoice_id', $invoiceIds)
            ->with(['deductingReturns', 'adjustments'])
            ->get();

        $salesUser = !empty($details['sales_receiver_id'])
            ? User::find($details['sales_receiver_id'])
            : null;

        $companyBankAccounts = CompanyBankAccount::where('is_active', true)
                                ->orderBy('bank_name')
                                ->get();

        return view('batch_payments.show_pending', compact(
            'batchPayment', 
            'invoices', 
            'details', 
            'salesUser', 
            'companyBankAccounts'
        ));
    }

    /**
     * ===================================================
     * BAGIAN: Menyetujui batch payment yang pending
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi
     * ===================================================
     */
    public function approve(Request $request, BatchPayment $batchPayment): RedirectResponse
    {
        if ($batchPayment->status !== 'pending_verification') {
            return redirect()->route('batch-payments.pending')
                ->with('error', 'Pembayaran ini sudah diproses.');
        }

        $validated = $request->validate([
            'company_bank_account_id' => 'required|exists:company_bank_accounts,company_bank_account_id'
        ]);

        $bankAccountId = $validated['company_bank_account_id'];

        DB::beginTransaction();
        try {
            $client = $batchPayment->client;
            $details = $batchPayment->details ?? [];
            $invoiceIds = $details['invoice_ids'] ?? [];
            
            $danaDariInput = (float) $batchPayment->total_amount;
            $kreditAkanDigunakan = (float) ($details['credit_amount_to_use'] ?? 0);
            $proofPathFromDetails = $details['proof_path'] ?? null;
            $referenceNumberFromDetails = $details['reference_number'] ?? null;
            
            $invoices = SalesInvoice::whereIn('invoice_id', $invoiceIds)
                ->with(['deductingReturns', 'adjustments'])
                ->orderBy('due_date', 'asc')
                ->get();

            $sisaKredit = $kreditAkanDigunakan;
            $sisaInput = $danaDariInput;
            $alokasiLog = [];

            $paymentMethodId = $details['payment_method_id'] ?? null;
            $method = $paymentMethodId ? PaymentMethod::find($paymentMethodId) : null;

            if (!$method && !empty($batchPayment->payment_method)) {
                $method = PaymentMethod::where('name', $batchPayment->payment_method)->first();
                if ($method) $paymentMethodId = $method->payment_method_id;
            }

            $paymentMethodType = $method->type ?? 'direct';
            $newPaymentStatus = ($paymentMethodType == 'pending') ? 'pending_clearance' : 'completed';

            // ✅ Validasi Akun Akuntansi
            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            if (!$arAccountId || !$clientDepositAccountId) {
                throw new \Exception("Akun Piutang Usaha (AR) atau Akun Deposit Klien belum diatur.");
            }
            
            $cashBankAccount = CompanyBankAccount::find($bankAccountId);
            if (!$cashBankAccount || !$cashBankAccount->chart_of_account_id) {
                throw new \Exception("Akun Bank Perusahaan yang dipilih belum terhubung ke Chart of Account.");
            }
            $cashBankAccountId = $cashBankAccount->chart_of_account_id;

            // Catat penggunaan kredit
            if ($kreditAkanDigunakan > 0) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => BatchPayment::class,
                    'reference_id' => $batchPayment->batch_payment_id,
                    'transaction_date' => $batchPayment->payment_date,
                    'type' => 'debit',
                    'amount' => -$kreditAkanDigunakan,
                    'status' => 'available',
                    'description' => 'Disetujui dari Pembayaran Batch #' . $batchPayment->batch_payment_id,
                    'user_id' => Auth::id(),
                ]);
                $alokasiLog[] = "Menggunakan kredit Rp " . number_format($kreditAkanDigunakan);
            }

            if ($danaDariInput > 0) {
                $alokasiLog[] = "Menggunakan dana input Rp " . number_format($danaDariInput);
            }

            // Distribusi ke invoice
            foreach ($invoices as $invoice) {
                if ($sisaKredit <= 0.01 && $sisaInput <= 0.01) break;

                $sisaTagihanInvoice = $invoice->remaining_balance;
                if ($sisaTagihanInvoice <= 0.01) continue;

                $bayarDariKredit = min($sisaTagihanInvoice, $sisaKredit);
                $bayarDariInput = min($sisaTagihanInvoice - $bayarDariKredit, $sisaInput);
                $jumlahBayar = $bayarDariKredit + $bayarDariInput;

                if ($jumlahBayar <= 0.01) continue;

                $invoice->payments()->create([
                    'batch_payment_id' => $batchPayment->batch_payment_id,
                    'payment_date' => $batchPayment->payment_date,
                    'amount' => $jumlahBayar,
                    'payment_method_id' => $paymentMethodId,
                    'company_bank_account_id' => $bankAccountId,
                    'status' => $newPaymentStatus,
                    'received_by_user_id' => $details['sales_receiver_id'] ?? Auth::id(),
                    'notes' => 'Disetujui dari Batch Payment #' . $batchPayment->batch_payment_id,
                    'proof_of_payment_path' => $proofPathFromDetails,
                    'reference_number' => $referenceNumberFromDetails
                ]);

                $invoice->updatePaymentStatus();
                $sisaKredit -= $bayarDariKredit;
                $sisaInput -= $bayarDariInput;
            }

            // Kembalikan kelebihan dana ke saldo klien jika ada
            $sisaDana = $sisaKredit + $sisaInput;
            if ($sisaDana > 0.01) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => BatchPayment::class,
                    'reference_id' => $batchPayment->batch_payment_id,
                    'transaction_date' => $batchPayment->payment_date,
                    'type' => 'credit',
                    'amount' => $sisaDana,
                    'status' => 'available',
                    'description' => 'Kelebihan dana setelah persetujuan Batch Payment #' . $batchPayment->batch_payment_id,
                    'user_id' => Auth::id(),
                ]);
                $alokasiLog[] = "Rp " . number_format($sisaDana) . " dikembalikan ke saldo klien.";
            }

            // Update status batch menjadi approved
            $batchPayment->update([
                'status' => 'approved',
                'approved_by_user_id' => Auth::id(),
                'approved_at' => now(),
                'company_bank_account_id' => $bankAccountId,
            ]);

            // ✅ Post Jurnal Akuntansi (Agregat)
            if ($newPaymentStatus == 'completed') {
                $journalGroupId = "BPAY-" . $batchPayment->batch_payment_id;
                $description = "Persetujuan Batch Payment #" . $batchPayment->batch_payment_id . " dari " . $client->client_name;
                
                $totalDanaAlokasi = $danaDariInput + $kreditAkanDigunakan;
                
                $debitEntries = [];
                $creditEntries = [];

                if ($danaDariInput > 0 && $cashBankAccountId) {
                    $debitEntries[] = [$cashBankAccountId, $danaDariInput, "Penerimaan batch (Verified) ke " . $cashBankAccount->account_name];
                }
                if ($kreditAkanDigunakan > 0) {
                    $debitEntries[] = [$clientDepositAccountId, $kreditAkanDigunakan, "Penggunaan deposit klien batch"];
                }
                
                if (($totalDanaAlokasi - $sisaDana) > 0) {
                    $creditEntries[] = [$arAccountId, ($totalDanaAlokasi - $sisaDana), "Pelunasan Piutang batch (Verified)"];
                }
                if ($sisaDana > 0) {
                    $creditEntries[] = [$clientDepositAccountId, $sisaDana, "Kelebihan bayar batch (Verified)"];
                }
                
                $this->accountingService->postJournal(
                    $journalGroupId,
                    $batchPayment->payment_date,
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $batchPayment
                );
            }

            DB::commit();

            return redirect()->route('batch-payments.pending')
                ->with('success', 'Batch payment #' . $batchPayment->batch_payment_id . ' berhasil disetujui. ' . implode(' ', $alokasiLog));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyetujui batch payment: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal menyetujui batch payment: ' . $e->getMessage());
        }
    }

    /**
     * ===================================================
     * BAGIAN: Menolak batch payment yang pending
     * ✅ DIPERBARUI: Menambahkan Jurnal Reversal
     * ===================================================
     */
    public function reject(BatchPayment $batchPayment, Request $request): RedirectResponse
    {
        if ($batchPayment->status !== 'pending_verification') {
            return redirect()->route('batch-payments.pending')
                ->with('error', 'Pembayaran ini sudah diproses.');
        }

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Update status menjadi rejected
            $batchPayment->update([
                'status' => 'rejected',
                'rejected_by_user_id' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => $request->reason,
            ]);

            // Kembalikan dana batch ke saldo klien
            ClientLedger::create([
                'client_id' => $batchPayment->client_id,
                'reference_type' => BatchPayment::class,
                'reference_id' => $batchPayment->batch_payment_id,
                'transaction_date' => now(),
                'type' => 'credit',
                'amount' => $batchPayment->total_amount,
                'status' => 'available',
                'description' => 'Dana dikembalikan karena Batch Payment #' . $batchPayment->batch_payment_id . ' ditolak',
                'user_id' => Auth::id(),
            ]);

            // ✅ Jurnal Akuntansi untuk Penolakan
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            if (!$clientDepositAccountId) {
                throw new \Exception("Akun Deposit Klien belum diatur.");
            }

            // Untuk penolakan, kita asumsikan uang dikembalikan ke deposit klien
            $journalGroupId = "BPAY-REJ-" . $batchPayment->batch_payment_id;
            $description = "Penolakan Batch Payment #" . $batchPayment->batch_payment_id;
            
            $debitEntries = [];
            $creditEntries = [
                [$clientDepositAccountId, $batchPayment->total_amount, "Pengembalian dana ditolak"]
            ];

            $this->accountingService->postJournal(
                $journalGroupId,
                now(),
                $description,
                $debitEntries,
                $creditEntries,
                $batchPayment
            );

            DB::commit();
            return redirect()->route('batch-payments.pending')
                ->with('success', 'Batch payment #' . $batchPayment->batch_payment_id . ' berhasil ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menolak batch payment: ' . $e->getMessage());
            return back()->with('error', 'Gagal menolak batch payment: ' . $e->getMessage());
        }
    }

    /**
     * ===========================================================
     * BAGIAN: Daftar Batch Payment (untuk semua status)
     * ===========================================================
     */
    public function index(): View
    {
        $batchPayments = BatchPayment::with(['client', 'paymentMethod', 'processedByUser'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('batch_payments.index', compact('batchPayments'));
    }

    /**
     * ===================================================
     * BAGIAN: Menampilkan detail Batch Payment tertentu
     * ===================================================
     */
    public function show(BatchPayment $batchPayment): View
    {
        $batchPayment->load(['client', 'paymentMethod', 'processedByUser', 'approvedByUser', 'rejectedByUser']);
        $details = $batchPayment->details ?? [];
        $invoiceIds = $details['invoice_ids'] ?? [];

        $invoices = SalesInvoice::whereIn('invoice_id', $invoiceIds)
            ->with(['payments', 'deductingReturns', 'adjustments'])
            ->get();

        return view('batch_payments.show', compact('batchPayment', 'invoices', 'details'));
    }
}