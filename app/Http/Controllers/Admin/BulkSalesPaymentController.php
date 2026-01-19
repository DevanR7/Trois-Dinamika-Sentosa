<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\SalesInvoice;
use App\Models\BulkSalesPayment;
use App\Models\ClientLedger;
use App\Models\PaymentMethod;
use App\Models\Payment;
use App\Models\User;
use App\Models\CompanyBankAccount;
use App\Models\GeneralLedger; // Tambahkan ini
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
use App\Traits\ValidatesAccountingPeriod;

class BulkSalesPaymentController extends Controller
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
        
        // Permission middleware
        $this->middleware('can:create-bulk-payments')->only(['create', 'store', 'getUnpaidInvoicesApi']);
        $this->middleware('can:review-bulk-payments')->only(['pending', 'showPending', 'approve', 'reject']);
        $this->middleware('can:view-invoices')->only(['index', 'show']);
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
        // Ambil invoice yang belum lunas
        $invoices = $client->salesInvoices()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->with(['deductingReturns', 'adjustments'])
            ->orderBy('due_date', 'asc')
            ->get();

        // Hitung sisa tagihan secara real-time
        $invoicesWithBalance = $invoices->map(function ($invoice) {
            // Hitung ulang sisa tagihan (Total - Bayar - Retur)
            $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');
            $totalReturned = $invoice->deductingReturns()->sum('total_amount');
            
            // Hitung adjustments (Hanya yang Manual / Calculation Adjustment)
            $totalAdj = $invoice->adjustments
                ->where('is_calculation_adjustment', true) // Filter Fix Double Counting
                ->reduce(function ($carry, $adj) {
                    return $carry + ($adj->type === 'debit_note' ? $adj->amount : -$adj->amount);
                }, 0);

            $sisa = max(0, round(($invoice->total_amount + $totalAdj) - $totalPaid - $totalReturned, 2));

            return [
                'invoice_id' => $invoice->invoice_id,
                'invoice_number' => $invoice->invoice_number,
                'due_date_formatted' => $invoice->due_date->format('d M Y'),
                'sisa_tagihan' => $sisa,
            ];
        })->filter(fn($inv) => $inv['sisa_tagihan'] > 0.01); 

        return response()->json($invoicesWithBalance->values());
    }

    public function store(Request $request): RedirectResponse
    {
        // 1. Validasi Input
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
        
        // Validasi Proof & Reference
        $paymentMethod = $request->filled('payment_method_id') 
            ? PaymentMethod::find($request->input('payment_method_id')) 
            : null;

        if ($paymentMethod) {
            $config = $paymentMethod->internal_input_config ?? 'none';
            $rules['proof_of_payment'] = in_array($config, ['proof_only', 'proof_and_reference']) 
                ? 'required|image|mimes:jpeg,png,jpg|max:2048' 
                : 'nullable|image|mimes:jpeg,png,jpg|max:2048';
                
            $rules['reference_number'] = in_array($config, ['reference_only', 'proof_and_reference']) 
                ? 'required|string|max:255' 
                : 'nullable|string|max:255';
        } else {
            $rules['proof_of_payment'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048';
            $rules['reference_number'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        // 2. Cek Periode Tutup Buku
        if ($this->isDateClosed($request->payment_date)) {
            return back()->with('error', 'Gagal: Tanggal pembayaran masuk periode tutup buku.')->withInput();
        }

        DB::beginTransaction();
        try {
            // Locking Client untuk membaca saldo deposit yang akurat
            $client = Client::lockForUpdate()->findOrFail($validated['client_id']);
            
            $pakaiKredit = $validated['use_credit'] ?? false;
            $danaDariInput = round((float) ($validated['total_amount'] ?? 0), 2);
            $kreditAwalKlien = $client->balance;
            
            // 3. LOCKING INVOICES
            $invoicesDipilih = SalesInvoice::whereIn('invoice_id', $validated['invoice_ids'])
                ->lockForUpdate()
                ->orderBy('due_date', 'asc')
                ->get();

            // 4. Hitung Sisa Tagihan Real-time
            $invoicesValid = $invoicesDipilih->filter(function($inv) {
                $paid = $inv->payments()->where('status', 'completed')->sum('amount');
                $returns = $inv->deductingReturns()->sum('total_amount');
                
                $inv->load('adjustments');
                $totalAdj = $inv->adjustments
                    ->where('is_calculation_adjustment', true) // Filter Fix Double Counting
                    ->reduce(function ($carry, $adj) {
                        return $carry + ($adj->type === 'debit_note' ? $adj->amount : -$adj->amount);
                    }, 0);

                $sisa = max(0, round(($inv->total_amount + $totalAdj) - $paid - $returns, 2));
                
                // Property sementara (Akan di-unset nanti)
                $inv->temp_remaining = $sisa; 
                
                return $sisa > 0.01;
            });

            if ($invoicesValid->isEmpty()) {
                throw new \Exception("Semua invoice yang dipilih sudah lunas (mungkin baru saja dibayar user lain).");
            }

            // Hitung Total Tagihan Valid
            $totalTagihanTerpilih = round($invoicesValid->sum('temp_remaining'), 2);

            // 5. Kalkulasi Alokasi Dana
            $kreditAkanDigunakan = 0;
            if ($pakaiKredit && $kreditAwalKlien > 0) {
                $kreditAkanDigunakan = min($kreditAwalKlien, $totalTagihanTerpilih);
            }
            $kreditAkanDigunakan = round($kreditAkanDigunakan, 2);

            $sisaTagihanSetelahKredit = max(0, round($totalTagihanTerpilih - $kreditAkanDigunakan, 2));
            
            // Dana input maksimal yang dipakai untuk invoice (sisanya jadi deposit)
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahKredit);
            $danaInputAkanDigunakan = round($danaInputAkanDigunakan, 2);
            
            $totalDanaAlokasi = round($kreditAkanDigunakan + $danaInputAkanDigunakan, 2);
            
            // Overpayment dari input (Uang masuk yang tidak terpakai invoice)
            $sisaDanaInput = max(0, round($danaDariInput - $danaInputAkanDigunakan, 2)); 

            if ($totalDanaAlokasi <= 0.01 && $sisaDanaInput <= 0.01) {
                throw new \Exception("Tidak ada dana (input/kredit) yang bisa dialokasikan.");
            }

            // 6. Validasi Akun Akuntansi
            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();

            if (!$arAccountId || !$clientDepositAccountId) {
                throw new \Exception("Akun AR atau Deposit Klien belum diatur di Pengaturan.");
            }
            
            $cashBankAccount = null;
            if ($danaDariInput > 0) {
                $cashBankAccount = CompanyBankAccount::find($validated['company_bank_account_id']);
                if (!$cashBankAccount || !$cashBankAccount->chart_of_account_id) {
                    throw new \Exception("Akun Bank tidak valid atau belum terhubung ke COA.");
                }
            }

            // 7. Tentukan Status & Simpan File
            $paymentMethodType = $paymentMethod->type ?? 'direct';
            $newPaymentStatus = ($paymentMethodType == 'pending') ? 'pending_verification' : 'completed';

            $proofPath = $request->hasFile('proof_of_payment')
                ? $request->file('proof_of_payment')->store('payment_proofs', 'public')
                : null;

            // 8. Buat Header Bulk Sales Payment
            $bulkPayment = BulkSalesPayment::create([
                'client_id' => $validated['client_id'],
                'payment_number' => BulkSalesPayment::generateNumber(),
                'processed_by_user_id' => Auth::id(),
                'payment_date' => $validated['payment_date'],
                'total_amount' => $danaDariInput, // Mencatat total uang Masuk (Cash In)
                'notes' => $validated['notes'],
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                'status' => $newPaymentStatus,
                'details' => [
                    'invoice_ids' => $invoicesValid->pluck('invoice_id')->toArray(),
                    'credit_amount_to_use' => $kreditAkanDigunakan,
                    'cash_amount_allocated' => $danaInputAkanDigunakan
                ],
                'reference_number' => $validated['reference_number'] ?? null,
                'proof_of_payment_path' => $proofPath,
            ]);

            // Jika COMPLETED, langsung eksekusi pemotongan saldo dan pencatatan pembayaran
            if ($newPaymentStatus == 'completed') {
                
                // 9. Debit Deposit Klien (Jika pakai saldo)
                if ($kreditAkanDigunakan > 0) {
                    ClientLedger::create([
                        'client_id' => $client->client_id,
                        'sales_invoice_id' => null, // Bulk
                        'reference_type' => BulkSalesPayment::class,
                        'reference_id' => $bulkPayment->bulk_sales_payment_id,
                        'transaction_date' => $validated['payment_date'],
                        'type' => 'debit',
                        'amount' => -$kreditAkanDigunakan,
                        'status' => 'available',
                        'description' => 'Digunakan untuk Bulk #' . $bulkPayment->payment_number,
                        'user_id' => Auth::id(),
                    ]);
                }

                // 10. Loop Alokasi Pembayaran ke Invoice
                $sisaKredit = $kreditAkanDigunakan;
                $sisaInput = $danaInputAkanDigunakan;
                $alokasiLog = [];

                foreach ($invoicesValid as $invoice) {
                    // === [FIX SQL ERROR: REMOVE TEMP ATTRIBUTE] ===
                    $sisaTagihan = $invoice->temp_remaining; 
                    unset($invoice['temp_remaining']); // Hapus agar Eloquent tidak mencoba menyimpannya
                    // ==============================================

                    if ($sisaKredit <= 0.01 && $sisaInput <= 0.01) break;
                    if ($sisaTagihan <= 0.01) continue;

                    // Alokasikan Kredit dulu
                    $bayarDariKredit = min($sisaTagihan, $sisaKredit);
                    $bayarDariKredit = round($bayarDariKredit, 2);
                    $sisaTagihan = round($sisaTagihan - $bayarDariKredit, 2);

                    // Alokasikan Input (Cash)
                    $bayarDariInput = min($sisaTagihan, $sisaInput);
                    $bayarDariInput = round($bayarDariInput, 2);

                    $jumlahBayar = round($bayarDariKredit + $bayarDariInput, 2);

                    if ($jumlahBayar <= 0.01) continue;

                    $invoice->payments()->create([
                        'bulk_sales_payment_id' => $bulkPayment->bulk_sales_payment_id,
                        'payment_date' => $validated['payment_date'],
                        'amount' => $jumlahBayar,
                        'payment_method_id' => $validated['payment_method_id'] ?? null,
                        'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                        'status' => 'completed',
                        'received_by_user_id' => Auth::id(),
                        'notes' => 'Auto-allocated Bulk #' . $bulkPayment->payment_number,
                        'reference_number' => $validated['reference_number'] ?? null,
                        'proof_of_payment_path' => $proofPath,
                    ]);

                    $invoice->updatePaymentStatus();

                    $sisaKredit = round($sisaKredit - $bayarDariKredit, 2);
                    $sisaInput = round($sisaInput - $bayarDariInput, 2);
                    $alokasiLog[] = $invoice->invoice_number;
                }

                // 11. Logika Overpayment (Masuk Deposit)
                // Jika ada sisa uang input yang tidak terpakai
                $totalDepositBaru = round($sisaDanaInput + $sisaInput, 2);

                if ($totalDepositBaru > 0.01) {
                    ClientLedger::create([
                        'client_id' => $client->client_id,
                        'sales_invoice_id' => null,
                        'reference_type' => BulkSalesPayment::class,
                        'reference_id' => $bulkPayment->bulk_sales_payment_id,
                        'transaction_date' => $validated['payment_date'],
                        'type' => 'credit',
                        'amount' => $totalDepositBaru,
                        'status' => 'available',
                        'description' => 'Kelebihan dana Bulk #' . $bulkPayment->payment_number,
                        'user_id' => Auth::id(),
                    ]);
                }

                // 12. Posting Jurnal Akuntansi
                $journalGroupId = "BULK-" . $bulkPayment->bulk_sales_payment_id;
                $description = "Penerimaan Bulk #" . $bulkPayment->payment_number . " (" . $client->client_name . ")";
                
                $debitEntries = [];
                $creditEntries = [];

                if ($danaDariInput > 0 && $cashBankAccount) {
                    $debitEntries[] = [$cashBankAccount->chart_of_account_id, $danaDariInput, "Masuk Bank (Verified)"];
                }
                if ($kreditAkanDigunakan > 0) {
                    $debitEntries[] = [$clientDepositAccountId, $kreditAkanDigunakan, "Potong Deposit Klien"];
                }

                // Total yang masuk ke piutang = (Dana Input - Overpay) + Kredit
                $totalLunasPiutang = round($totalDanaAlokasi - $sisaInput - $sisaKredit, 2); 
                
                if ($totalLunasPiutang > 0) {
                    $creditEntries[] = [$arAccountId, $totalLunasPiutang, "Pelunasan Piutang Bulk"];
                }
                if ($totalDepositBaru > 0) {
                    $creditEntries[] = [$clientDepositAccountId, $totalDepositBaru, "Kelebihan Bayar (Deposit)"];
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
            
            $msg = ($newPaymentStatus == 'completed') 
                ? 'Pembayaran massal berhasil disimpan dan dialokasikan.' 
                : 'Pembayaran massal disimpan sebagai draft (Menunggu Verifikasi).';
                
            return redirect()->route('admin.bulk-sales-payments.show', $bulkPayment->bulk_sales_payment_id)
                ->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan bulk payment: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal menyimpan pembayaran bulk: ' . $e->getMessage())->withInput();
        }
    }

    public function pending(): View
    {
        $pendingBulkPayments = BulkSalesPayment::whereIn('status', ['pending_verification', 'pending'])
            ->with(['client', 'paymentMethod'])
            ->orderBy('created_at', 'asc')
            ->paginate(15);
        return view('admin.bulk_sales_payments.pending', compact('pendingBulkPayments'));
    }

    public function showPending(BulkSalesPayment $bulkSalesPayment): View|RedirectResponse
    {
        if (!in_array($bulkSalesPayment->status, ['pending_verification', 'pending'])) {
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
        if (!in_array($bulkSalesPayment->status, ['pending_verification', 'pending'])) {
            return redirect()->route('admin.bulk-sales-payments.pending')
                ->with('error', 'Pembayaran ini sudah diproses atau status tidak valid.');
        }

        $validated = $request->validate([
            'company_bank_account_id' => 'required|exists:company_bank_accounts,company_bank_account_id'
        ]);

        if ($this->isDateClosed($bulkSalesPayment->payment_date)) {
            return back()->with('error', 'Gagal: Tanggal pembayaran masuk periode tutup buku.');
        }

        DB::beginTransaction();
        try {
            // Locking Client
            $client = Client::lockForUpdate()->find($bulkSalesPayment->client_id);
            
            $details = $bulkSalesPayment->details ?? [];
            $invoiceIds = $details['invoice_ids'] ?? [];
            
            // 1. LOCKING ULANG INVOICES
            $invoices = SalesInvoice::whereIn('invoice_id', $invoiceIds)
                ->lockForUpdate()
                ->orderBy('due_date', 'asc')
                ->get();

            $danaDariInput = round((float) $bulkSalesPayment->total_amount, 2);
            $kreditAkanDigunakan = round((float) ($details['credit_amount_to_use'] ?? 0), 2);
            
            // Validasi ulang saldo klien
            if ($kreditAkanDigunakan > 0 && $client->balance < $kreditAkanDigunakan) {
                $kreditAkanDigunakan = round($client->balance, 2);
            }

            $bankAccountId = $validated['company_bank_account_id'];
            $cashBankAccount = CompanyBankAccount::find($bankAccountId);
            
            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            if (!$arAccountId || !$clientDepositAccountId) throw new \Exception("Akun AR/Deposit belum diatur.");
            if (!$cashBankAccount || !$cashBankAccount->chart_of_account_id) throw new \Exception("Akun Bank tidak valid.");

            $sisaKredit = $kreditAkanDigunakan;
            $sisaInput = $danaDariInput;
            $totalTerpakaiUntukInvoice = 0;

            $paymentMethodId = $details['payment_method_id'] ?? $bulkSalesPayment->payment_method_id;

            // Loop Invoices
            foreach ($invoices as $invoice) {
                if ($sisaKredit <= 0.01 && $sisaInput <= 0.01) break;

                // Hitung sisa tagihan real-time
                $paid = $invoice->payments()->where('status', 'completed')->sum('amount');
                $returns = $invoice->deductingReturns()->sum('total_amount');
                
                $invoice->load('adjustments');
                $totalAdj = $invoice->adjustments
                    ->where('is_calculation_adjustment', true) // Fix Double Counting
                    ->reduce(function ($carry, $adj) {
                        return $carry + ($adj->type === 'debit_note' ? $adj->amount : -$adj->amount);
                    }, 0);

                $sisaTagihanInvoice = max(0, round(($invoice->total_amount + $totalAdj) - $paid - $returns, 2));

                if ($sisaTagihanInvoice <= 0.01) continue;

                // Alokasi Kredit
                $bayarDariKredit = min($sisaTagihanInvoice, $sisaKredit);
                $bayarDariKredit = round($bayarDariKredit, 2);
                $sisaTagihanInvoice = round($sisaTagihanInvoice - $bayarDariKredit, 2);

                // Alokasi Cash
                $bayarDariInput = min($sisaTagihanInvoice, $sisaInput);
                $bayarDariInput = round($bayarDariInput, 2);

                $jumlahBayar = round($bayarDariKredit + $bayarDariInput, 2);

                if ($jumlahBayar <= 0.01) continue;

                $invoice->payments()->create([
                    'bulk_sales_payment_id' => $bulkSalesPayment->bulk_sales_payment_id,
                    'payment_date' => $bulkSalesPayment->payment_date,
                    'amount' => $jumlahBayar,
                    'payment_method_id' => $paymentMethodId,
                    'company_bank_account_id' => $bankAccountId,
                    'status' => 'completed', 
                    'received_by_user_id' => Auth::id(),
                    'notes' => 'Disetujui Bulk #' . $bulkSalesPayment->payment_number,
                    'proof_of_payment_path' => $bulkSalesPayment->proof_of_payment_path,
                    'reference_number' => $bulkSalesPayment->reference_number
                ]);

                $invoice->updatePaymentStatus();
                
                $sisaKredit = round($sisaKredit - $bayarDariKredit, 2);
                $sisaInput = round($sisaInput - $bayarDariInput, 2);
                $totalTerpakaiUntukInvoice = round($totalTerpakaiUntukInvoice + $jumlahBayar, 2);
            }

            // Hitung Total Penggunaan Deposit Aktual
            $depositTerpakai = round($kreditAkanDigunakan - $sisaKredit, 2);
            
            // Hitung Total Overpayment
            $sisaInputTotal = round($sisaInput, 2); 
            
            // Catat penggunaan deposit
            if ($depositTerpakai > 0) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => BulkSalesPayment::class,
                    'reference_id' => $bulkSalesPayment->bulk_sales_payment_id,
                    'transaction_date' => $bulkSalesPayment->payment_date,
                    'type' => 'debit',
                    'amount' => -$depositTerpakai,
                    'status' => 'available',
                    'description' => 'Digunakan untuk Bulk #' . $bulkSalesPayment->payment_number . ' (Approved)',
                    'user_id' => Auth::id(),
                ]);
            }

            // Catat penambahan deposit (Overpayment)
            if ($sisaInputTotal > 0.01) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => BulkSalesPayment::class,
                    'reference_id' => $bulkSalesPayment->bulk_sales_payment_id,
                    'transaction_date' => $bulkSalesPayment->payment_date,
                    'type' => 'credit',
                    'amount' => $sisaInputTotal,
                    'status' => 'available',
                    'description' => 'Kelebihan dana Bulk #' . $bulkSalesPayment->payment_number,
                    'user_id' => Auth::id(),
                ]);
            }

            $bulkSalesPayment->update([
                'status' => 'approved',
                'approved_by_user_id' => Auth::id(),
                'approved_at' => now(),
                'company_bank_account_id' => $bankAccountId,
            ]);

            // Jurnal Akuntansi
            $journalGroupId = "BULK-" . $bulkSalesPayment->bulk_sales_payment_id;
            $description = "Persetujuan Bulk #" . $bulkSalesPayment->payment_number . " (" . $client->client_name . ")";
            
            $debitEntries = [];
            $creditEntries = [];

            if ($danaDariInput > 0) {
                $debitEntries[] = [$cashBankAccount->chart_of_account_id, $danaDariInput, "Masuk Bank (Verified)"];
            }
            if ($depositTerpakai > 0) {
                $debitEntries[] = [$clientDepositAccountId, $depositTerpakai, "Potong Deposit"];
            }

            if ($totalTerpakaiUntukInvoice > 0) {
                $creditEntries[] = [$arAccountId, $totalTerpakaiUntukInvoice, "Pelunasan AR"];
            }
            if ($sisaInputTotal > 0) {
                $creditEntries[] = [$clientDepositAccountId, $sisaInputTotal, "Overpayment (Deposit)"];
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
                ->with('success', 'Bulk #' . $bulkSalesPayment->payment_number . ' berhasil disetujui.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyetujui bulk payment: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal menyetujui bulk: ' . $e->getMessage());
        }
    }

    public function reject(BulkSalesPayment $bulkSalesPayment, Request $request): RedirectResponse
    {
        if (!in_array($bulkSalesPayment->status, ['pending_verification', 'pending'])) {
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

            DB::commit();
            return redirect()->route('admin.bulk-sales-payments.pending')
                ->with('success', 'Bulk #' . $bulkSalesPayment->payment_number . ' berhasil ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menolak bulk payment: ' . $e->getMessage());
            return back()->with('error', 'Gagal menolak bulk payment: ' . $e->getMessage());
        }
    }

    public function index(Request $request): View
    {
        $query = BulkSalesPayment::with(['client', 'paymentMethod', 'processedByUser']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('payment_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function($c) use ($search) {
                      $c->where('client_name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->filled('start_date')) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bulkSalesPayments = $query->orderByDesc('created_at')
            ->paginate(15)
            ->appends($request->query());

        return view('admin.bulk_sales_payments.index', compact('bulkSalesPayments'));
    }

    public function show(BulkSalesPayment $bulkSalesPayment): View
    {
        $bulkSalesPayment->load([
            'client', 
            'paymentMethod', 
            'processedByUser', 
            'approvedByUser', 
            'rejectedByUser', 
            'payments.salesInvoice',
            'companyBankAccount'
        ]);
        
        $details = $bulkSalesPayment->details ?? [];
        $invoiceIds = $details['invoice_ids'] ?? [];
        $invoices = SalesInvoice::whereIn('invoice_id', $invoiceIds)->get();
    
        return view('admin.bulk_sales_payments.show', compact('bulkSalesPayment', 'invoices', 'details'));
    }

    public function destroy(BulkSalesPayment $bulkSalesPayment): RedirectResponse
    {
        $journalGroupId = "BULK-" . $bulkSalesPayment->bulk_sales_payment_id;
        
        if ($error = $this->checkTransactionLock($bulkSalesPayment->payment_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus: " . $error);
        }

        DB::beginTransaction();
        try {
            // 1. Hapus Jurnal Umum
            GeneralLedger::where('journal_group_id', $journalGroupId)->delete();

            // 2. [FIX] Hapus Ledger Deposit/Kredit yang terkait dengan bulk ini
            // Ini akan menghapus Debit (penggunaan deposit) dan Kredit (Overpayment deposit)
            ClientLedger::where('reference_type', BulkSalesPayment::class)
                ->where('reference_id', $bulkSalesPayment->bulk_sales_payment_id)
                ->delete();

            // 3. Hapus Pembayaran Anak (Allocated Payments)
            foreach ($bulkSalesPayment->payments as $payment) {
                $invoice = $payment->salesInvoice;
                $payment->delete();
                
                // Update Invoice Status kembali
                if ($invoice) {
                    $invoice->updatePaymentStatus();
                }
            }

            // 4. Hapus Header Bulk Payment
            $bulkSalesPayment->delete();

            DB::commit();
            return redirect()->route('admin.bulk-sales-payments.index')
                ->with('success', 'Pembayaran massal berhasil dibatalkan. Jurnal dan deposit telah dibersihkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan bulk: ' . $e->getMessage());
        }
    }
}