<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\SalesInvoice;
use App\Models\BulkSalesPayment;
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

class BulkSalesPaymentController extends Controller
{
    protected $accountingService;
    protected $accountingSettings;

    public function __construct(
        AccountingService $accountingService,
        AccountingSettingService $accountingSettingService
    ) {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettingService;

        $this->middleware('permission:create-batch-payments')->only(['create', 'store', 'getUnpaidInvoicesApi']);
        $this->middleware('permission:review-batch-payments')->only(['pending', 'showPending', 'approve', 'reject']);
    }

    public function index(): View
    {
        $bulkSalesPayments = BulkSalesPayment::with(['client', 'paymentMethod', 'processedByUser'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.bulk_sales_payments.index', compact('bulkSalesPayments'));
    }

    public function create(): View
    {
        $clients = Client::orderBy('client_name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)
                            ->whereIn('type', ['direct', 'pending'])
                            ->orderBy('name')->get();
        $companyBankAccounts = CompanyBankAccount::where('is_active', true)
                                ->orderBy('bank_name')->get();

        return view('admin.bulk_sales_payments.create', compact('clients', 'paymentMethods', 'companyBankAccounts'));
    }

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

    public function store(Request $request): RedirectResponse
    {
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
            $client = Client::findOrFail($validated['client_id']);
            $pakaiKredit = $validated['use_credit'] ?? false;
            $danaDariInput = (float) ($validated['total_amount'] ?? 0);
            $kreditAwalKlien = $client->balance;

            $invoicesDipilih = SalesInvoice::whereIn('invoice_id', $validated['invoice_ids'])
                ->with(['deductingReturns', 'adjustments'])
                ->orderBy('due_date', 'asc')
                ->get();

            $totalTagihanTerpilih = $invoicesDipilih->reduce(fn($carry, $inv) => $carry + $inv->remaining_balance, 0.0);
            if ($totalTagihanTerpilih <= 0.01) {
                throw new \Exception("Semua invoice yang dipilih sudah lunas.");
            }

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

            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            if (!$arAccountId || !$clientDepositAccountId) {
                throw new \Exception("Akun AR atau Deposit Klien belum diatur.");
            }
            
            $cashBankAccount = null;
            $cashBankAccountId = null;
            if ($danaDariInput > 0) {
                $cashBankAccount = CompanyBankAccount::find($validated['company_bank_account_id']);
                if (!$cashBankAccount || !$cashBankAccount->chart_of_account_id) {
                    throw new \Exception("Akun Bank tidak valid.");
                }
                $cashBankAccountId = $cashBankAccount->chart_of_account_id;
            }

            $paymentMethodType = $paymentMethod->type ?? 'direct';
            $newPaymentStatus = ($paymentMethodType == 'pending') ? 'pending_clearance' : 'completed';

            $proofPath = $request->hasFile('proof_of_payment')
                ? $request->file('proof_of_payment')->store('payment_proofs', 'public')
                : null;

            $bulkPayment = BulkSalesPayment::create([
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

            if ($kreditAkanDigunakan > 0) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => BulkSalesPayment::class,
                    'reference_id' => $bulkPayment->bulk_sales_payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit',
                    'amount' => -$kreditAkanDigunakan,
                    'status' => 'available',
                    'description' => 'Digunakan untuk Bulk #' . $bulkPayment->bulk_sales_payment_id,
                    'user_id' => Auth::id(),
                ]);
            }

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

                $invoice->payments()->create([
                    'bulk_sales_payment_id' => $bulkPayment->bulk_sales_payment_id,
                    'payment_date' => $validated['payment_date'],
                    'amount' => $jumlahBayar,
                    'payment_method_id' => $validated['payment_method_id'] ?? null,
                    'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                    'status' => $newPaymentStatus,
                    'received_by_user_id' => Auth::id(),
                    'notes' => 'Auto-allocated Bulk #' . $bulkPayment->bulk_sales_payment_id,
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

            if ($sisaDanaInput > 0.01) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => BulkSalesPayment::class,
                    'reference_id' => $bulkPayment->bulk_sales_payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit',
                    'amount' => $sisaDanaInput,
                    'status' => 'available',
                    'description' => 'Kelebihan dana Bulk #' . $bulkPayment->bulk_sales_payment_id,
                    'user_id' => Auth::id(),
                ]);
            }

            if ($newPaymentStatus == 'completed') {
                $journalGroupId = "BULK-" . $bulkPayment->bulk_sales_payment_id;
                $description = "Penerimaan Bulk #" . $bulkPayment->bulk_sales_payment_id . " dari " . $client->client_name;

                $debitEntries = [];
                $creditEntries = [];

                if ($danaDariInput > 0 && $cashBankAccountId) {
                    $debitEntries[] = [$cashBankAccountId, $danaDariInput, "Kas/Bank Bulk"];
                }
                if ($kreditAkanDigunakan > 0) {
                    $debitEntries[] = [$clientDepositAccountId, $kreditAkanDigunakan, "Deposit Klien"];
                }

                if ($totalDanaAlokasi > 0) {
                    $creditEntries[] = [$arAccountId, $totalDanaAlokasi, "Pelunasan Piutang Bulk"];
                }
                if ($sisaDanaInput > 0) {
                    $creditEntries[] = [$clientDepositAccountId, $sisaDanaInput, "Kelebihan Bayar"];
                }

                $this->accountingService->postJournal(
                    $journalGroupId,
                    $validated['payment_date'],
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $bulkPayment,
                    Auth::id()
                );
            }

            DB::commit();

            return redirect()->route('admin.clients.show', $client->client_id)
                             ->with('success', 'Pembayaran bulk berhasil. ' . implode('. ', $alokasiLog));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan bulk: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal menyimpan pembayaran bulk: ' . $e->getMessage())->withInput();
        }
    }

    public function show(BulkSalesPayment $bulkSalesPayment): View
    {
        // Load relasi lengkap untuk history
        $bulkSalesPayment->load([
            'client', 
            'paymentMethod', 
            'processedByUser', // Pembuat
            'approvedByUser',  // Penyetuju
            'rejectedByUser',  // Penolak
            'payments.salesInvoice' // Detail pembayaran per invoice (jika completed)
        ]);

        $details = $bulkSalesPayment->details ?? [];
        $invoiceIds = $details['invoice_ids'] ?? [];

        // Ambil data invoice yang terkait (baik lunas maupun yang dulu diajukan)
        $invoices = SalesInvoice::whereIn('invoice_id', $invoiceIds)->get();

        return view('admin.bulk_sales_payments.show', compact('bulkSalesPayment', 'invoices', 'details'));
    }

    public function pending(): View
    {
        $pendingBulkPayments = BulkSalesPayment::where('status', 'pending_verification')
            ->with(['client', 'paymentMethod'])
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return view('admin.bulk_sales_payments.pending', compact('pendingBulkPayments'));
    }

    public function showPending(BulkSalesPayment $bulkSalesPayment): View|RedirectResponse
    {
        if ($bulkSalesPayment->status !== 'pending_verification') {
            return redirect()->route('admin.bulk-sales-payments.pending')
                ->with('error', 'Pembayaran ini sudah diproses.');
        }

        $bulkSalesPayment->load(['client', 'paymentMethod']);
        $details = $bulkSalesPayment->details ?? [];
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

        return view('admin.bulk_sales_payments.show_pending', compact(
            'bulkSalesPayment',
            'invoices',
            'details',
            'salesUser',
            'companyBankAccounts'
        ));
    }

    public function approve(Request $request, BulkSalesPayment $bulkSalesPayment): RedirectResponse
    {
        if ($bulkSalesPayment->status !== 'pending_verification') {
            return redirect()->route('admin.bulk-sales-payments.pending')
                ->with('error', 'Pembayaran ini sudah diproses.');
        }

        $validated = $request->validate([
            'company_bank_account_id' => 'required|exists:company_bank_accounts,company_bank_account_id'
        ]);

        $bankAccountId = $validated['company_bank_account_id'];

        DB::beginTransaction();
        try {
            $client = $bulkSalesPayment->client;
            $details = $bulkSalesPayment->details ?? [];
            $invoiceIds = $details['invoice_ids'] ?? [];
            
            $danaDariInput = (float) $bulkSalesPayment->total_amount;
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

            if (!$method && !empty($bulkSalesPayment->payment_method)) {
                $method = PaymentMethod::where('name', $bulkSalesPayment->payment_method)->first();
                if ($method) $paymentMethodId = $method->payment_method_id;
            }

            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            if (!$arAccountId || !$clientDepositAccountId) {
                throw new \Exception("Akun AR atau Deposit Klien belum diatur.");
            }
            
            $cashBankAccount = CompanyBankAccount::find($bankAccountId);
            if (!$cashBankAccount || !$cashBankAccount->chart_of_account_id) {
                throw new \Exception("Akun Bank tidak valid.");
            }
            $cashBankAccountId = $cashBankAccount->chart_of_account_id;

            if ($kreditAkanDigunakan > 0) {
                $alokasiLog[] = "Menggunakan kredit Rp " . number_format($kreditAkanDigunakan);
            }

            if ($danaDariInput > 0) {
                $alokasiLog[] = "Menggunakan dana input Rp " . number_format($danaDariInput);
            }

            foreach ($invoices as $invoice) {
                if ($sisaKredit <= 0.01 && $sisaInput <= 0.01) break;

                $sisaTagihanInvoice = $invoice->remaining_balance;
                if ($sisaTagihanInvoice <= 0.01) continue;

                $bayarDariKredit = min($sisaTagihanInvoice, $sisaKredit);
                $bayarDariInput = min($sisaTagihanInvoice - $bayarDariKredit, $sisaInput);
                $jumlahBayar = $bayarDariKredit + $bayarDariInput;

                if ($jumlahBayar <= 0.01) continue;

                $invoice->payments()->create([
                    'bulk_sales_payment_id' => $bulkSalesPayment->bulk_sales_payment_id,
                    'payment_date' => $bulkSalesPayment->payment_date,
                    'amount' => $jumlahBayar,
                    'payment_method_id' => $paymentMethodId,
                    'company_bank_account_id' => $bankAccountId,
                    'status' => 'completed',
                    'received_by_user_id' => Auth::id(),
                    'notes' => 'Disetujui Bulk #' . $bulkSalesPayment->bulk_sales_payment_id,
                    'proof_of_payment_path' => $proofPathFromDetails,
                    'reference_number' => $referenceNumberFromDetails
                ]);

                $invoice->updatePaymentStatus();
                $sisaKredit -= $bayarDariKredit;
                $sisaInput -= $bayarDariInput;
            }

            $sisaDana = $sisaKredit + $sisaInput;
            
            if ($sisaDana > 0.01) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => BulkSalesPayment::class,
                    'reference_id' => $bulkSalesPayment->bulk_sales_payment_id,
                    'transaction_date' => $bulkSalesPayment->payment_date,
                    'type' => 'credit',
                    'amount' => $sisaDana,
                    'status' => 'available',
                    'description' => 'Kelebihan dana setelah persetujuan Bulk #' . $bulkSalesPayment->bulk_sales_payment_id,
                    'user_id' => Auth::id(),
                ]);
                $alokasiLog[] = "Rp " . number_format($sisaDana) . " dikembalikan ke saldo klien.";
            }

            $bulkSalesPayment->update([
                'status' => 'approved',
                'approved_by_user_id' => Auth::id(),
                'approved_at' => now(),
                'company_bank_account_id' => $bankAccountId,
            ]);

            $journalGroupId = "BULK-" . $bulkSalesPayment->bulk_sales_payment_id;
            $description = "Persetujuan Bulk #" . $bulkSalesPayment->bulk_sales_payment_id . " dari " . $client->client_name;
            
            $totalDanaAlokasi = $danaDariInput + $kreditAkanDigunakan;
            
            $debitEntries = [];
            $creditEntries = [];

            // (D) Kas/Bank
            if ($danaDariInput > 0 && $cashBankAccountId) {
                $debitEntries[] = [$cashBankAccountId, $danaDariInput, "Penerimaan Bulk (Verified)"];
            }
            // (D) Deposit Klien
            if ($kreditAkanDigunakan > 0) {
                $debitEntries[] = [$clientDepositAccountId, $kreditAkanDigunakan, "Deposit Klien"];
            }
            
            // (K) Piutang Usaha
            $totalTerpakai = $totalDanaAlokasi - $sisaDana;
            if ($totalTerpakai > 0) {
                $creditEntries[] = [$arAccountId, $totalTerpakai, "Pelunasan Piutang Bulk (Verified)"];
            }

            // (K) Deposit Klien (Kelebihan bayar)
            if ($sisaDana > 0) {
                $creditEntries[] = [$clientDepositAccountId, $sisaDana, "Kelebihan bayar Bulk (Verified)"];
            }
            
            $this->accountingService->postJournal(
                $journalGroupId,
                $bulkSalesPayment->payment_date,
                $description,
                $debitEntries,
                $creditEntries,
                $bulkSalesPayment,
                Auth::id()
            );

            DB::commit();

            return redirect()->route('admin.bulk-sales-payments.pending')
                ->with('success', 'Bulk #' . $bulkSalesPayment->bulk_sales_payment_id . ' berhasil disetujui. ' . implode(' ', $alokasiLog));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyetujui bulk payment: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal menyetujui bulk: ' . $e->getMessage());
        }
    }

    public function reject(BulkSalesPayment $bulkSalesPayment, Request $request): RedirectResponse
    {
        if ($bulkSalesPayment->status !== 'pending_verification') {
            return redirect()->route('admin.bulk-sales-payments.pending')
                ->with('error', 'Pembayaran ini sudah diproses.');
        }

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $bulkSalesPayment->update([
                'status' => 'rejected',
                'rejected_by_user_id' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => $request->reason,
            ]);

            ClientLedger::create([
                'client_id' => $bulkSalesPayment->client_id,
                'reference_type' => BulkSalesPayment::class,
                'reference_id' => $bulkSalesPayment->bulk_sales_payment_id,
                'transaction_date' => now(),
                'type' => 'credit',
                'amount' => $bulkSalesPayment->total_amount,
                'status' => 'available',
                'description' => 'Dana dikembalikan karena Bulk #' . $bulkSalesPayment->bulk_sales_payment_id . ' ditolak',
                'user_id' => Auth::id(),
            ]);

            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            if (!$clientDepositAccountId) {
                throw new \Exception("Akun Deposit Klien belum diatur.");
            }

            $journalGroupId = "BULK-REJ-" . $bulkSalesPayment->bulk_sales_payment_id;
            $description = "Penolakan Bulk #" . $bulkSalesPayment->bulk_sales_payment_id;
            
            $debitEntries = [];
            $creditEntries = [
                [$clientDepositAccountId, $bulkSalesPayment->total_amount, "Pengembalian dana ditolak"]
            ];

            $this->accountingService->postJournal(
                $journalGroupId,
                now(),
                $description,
                $debitEntries,
                $creditEntries,
                $bulkSalesPayment,
                Auth::id()
            );

            DB::commit();
            return redirect()->route('admin.bulk-sales-payments.pending')
                ->with('success', 'Bulk #' . $bulkSalesPayment->bulk_sales_payment_id . ' berhasil ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menolak bulk payment: ' . $e->getMessage());
            return back()->with('error', 'Gagal menolak bulk payment: ' . $e->getMessage());
        }
    }
}