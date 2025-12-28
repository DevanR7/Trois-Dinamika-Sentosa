<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Models\ClientLedger;
use App\Models\PaymentMethod;
use App\Models\CompanyBankAccount;
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

class PaymentController extends Controller
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
    }

    public function store(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        $rules = [
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method_id' => [
                Rule::requiredIf(fn() => $request->input('amount', 0) > 0 || !$request->has('use_credit')),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
            'company_bank_account_id' => [
                Rule::requiredIf(fn() => $request->input('amount', 0) > 0 || !$request->has('use_credit')),
                'nullable',
                'exists:company_bank_accounts,company_bank_account_id',
            ],
            'notes' => 'nullable|string',
            'use_credit' => 'nullable|boolean',
        ];

        $paymentMethod = null;
        if ($request->filled('payment_method_id')) {
            $paymentMethod = PaymentMethod::find($request->input('payment_method_id'));
        }

        if ($paymentMethod) {
            $config = $paymentMethod->current_input_config;
            $rules['proof_of_payment'] = in_array($config, ['proof_only', 'proof_and_reference']) ? 'required|image|mimes:jpeg,png,jpg|max:2048' : 'nullable|image|mimes:jpeg,png,jpg|max:2048';
            $rules['reference_number'] = in_array($config, ['reference_only', 'proof_and_reference']) ? 'required|string|max:255' : 'nullable|string|max:255';
        } else {
            $rules['proof_of_payment'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048';
            $rules['reference_number'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        if ($this->isDateClosed($request->payment_date)) {
            return back()->with('error', 'Gagal: Tanggal pembayaran masuk periode tutup buku.')->withInput();
        }

        $client = $invoice->client;
        $danaDariInput = (float) ($validated['amount'] ?? 0);
        $pakaiKredit = (bool) ($validated['use_credit'] ?? false);
        $kreditAwalKlien = $client->balance;
        $sisaTagihan = $invoice->remaining_balance; 

        if ($pakaiKredit && $kreditAwalKlien <= 0.01) {
            $pakaiKredit = false;
        }

        $catatanLog = $validated['notes'] ?? '';
        $kreditAkanDigunakan = 0;
        $danaInputAkanDigunakan = 0;
        $sisaDanaInput = 0;

        DB::beginTransaction();

        try {
            if ($pakaiKredit && $kreditAwalKlien > 0) {
                $kreditAkanDigunakan = min($kreditAwalKlien, $sisaTagihan);
            }

            $sisaTagihanSetelahKredit = max(0, $sisaTagihan - $kreditAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahKredit);
            $totalPembayaranAllocated = $kreditAkanDigunakan + $danaInputAkanDigunakan;
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            if ($totalPembayaranAllocated <= 0.01 && $sisaDanaInput <= 0.01 && $sisaTagihan > 0.01) {
                throw new \Exception("Tidak ada dana (input/kredit) yang dialokasikan.");
            }

            $paymentMethodName = $paymentMethod->name ?? 'N/A';
            $paymentMethodType = $paymentMethod->type ?? 'direct';

            if ($kreditAkanDigunakan > 0) {
                $catatanLog .= ($catatanLog ? ' | ' : '') . 'Credit used: ' . number_format($kreditAkanDigunakan);
            }

            $newPaymentStatus = $paymentMethod ? $paymentMethod->current_status : 'completed';

            $proofPath = null;
            if ($request->hasFile('proof_of_payment')) {
                $proofPath = $request->file('proof_of_payment')->store('payment_proofs', 'public');
            }

            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            if (!$arAccountId || !$clientDepositAccountId) {
                throw new \Exception("Akun Piutang Usaha (AR) atau Akun Deposit Klien belum diatur di Pengaturan Akuntansi.");
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

            $payment = $invoice->payments()->create([
                'amount' => $totalPembayaranAllocated,
                'payment_date' => $validated['payment_date'],
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                'status' => $newPaymentStatus,
                'notes' => $catatanLog,
                'received_by_user_id' => Auth::id(),
                'proof_of_payment_path' => $proofPath,
                'reference_number' => $validated['reference_number'] ?? null,
            ]);

            if ($kreditAkanDigunakan > 0) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit', 
                    'amount' => -$kreditAkanDigunakan,
                    'status' => 'available',
                    'description' => 'Digunakan untuk membayar Invoice #' . $invoice->invoice_number,
                    'user_id' => Auth::id(),
                ]);
            }

            if ($sisaDanaInput > 0.01) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit', 
                    'amount' => $sisaDanaInput,
                    'status' => 'available',
                    'description' => 'Kelebihan bayar dari Invoice #' . $invoice->invoice_number,
                    'user_id' => Auth::id(),
                ]);
                
                $payment->update(['notes' => $payment->notes . ' | Overpayment: ' . number_format($sisaDanaInput)]);
            }

            if ($newPaymentStatus == 'completed') {
                $journalGroupId = "PAY-" . $payment->payment_id;
                $description = "Penerimaan Pembayaran Inv #" . $invoice->invoice_number;
                $debitEntries = [];
                $creditEntries = [];

                if ($danaDariInput > 0 && $cashBankAccountId) {
                    $debitEntries[] = [$cashBankAccountId, $danaDariInput, "Penerimaan ke " . $cashBankAccount->account_name];
                }
                if ($kreditAkanDigunakan > 0) {
                    $debitEntries[] = [$clientDepositAccountId, $kreditAkanDigunakan, "Penggunaan deposit klien"];
                }
                if ($totalPembayaranAllocated > 0) {
                    $creditEntries[] = [$arAccountId, $totalPembayaranAllocated, "Pelunasan Piutang Inv #" . $invoice->invoice_number];
                }
                if ($sisaDanaInput > 0) {
                    $creditEntries[] = [$clientDepositAccountId, $sisaDanaInput, "Kelebihan bayar Inv #" . $invoice->invoice_number];
                }

                $this->accountingService->postJournal(
                    $journalGroupId,
                    $validated['payment_date'],
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $payment,
                    Auth::id()
                );
            }

            $invoice->updatePaymentStatus();

            DB::commit();
            return redirect()
                ->route('admin.invoices.show', $invoice->invoice_id)
                ->with('success', 'Pembayaran berhasil dicatat. Total dialokasikan: Rp ' . number_format($totalPembayaranAllocated));
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mencatat pembayaran: ' . $e->getMessage());
            return back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
    }

    public function approve(Payment $payment): RedirectResponse
    {
        if ($payment->status !== 'pending_verification') {
            return back()->with('error', 'Pembayaran ini tidak sedang dalam status verifikasi.');
        }

        DB::beginTransaction();
        try {
            $payment->update([
                'status' => 'completed',
                'received_by_user_id' => Auth::id(),
            ]);

            $invoice = $payment->salesInvoice;
            $invoice->updatePaymentStatus();

            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            
            if ($arAccountId && $clientDepositAccountId) {
                $journalGroupId = "PAY-APP-" . $payment->payment_id;
                $description = "Persetujuan Pembayaran Inv #" . $invoice->invoice_number;

                $debitEntries = [];
                $creditEntries = [];

                $cashBankAccount = $payment->companyBankAccount;
                if ($cashBankAccount && $cashBankAccount->chart_of_account_id) {
                    $debitEntries[] = [$cashBankAccount->chart_of_account_id, $payment->amount, "Penerimaan (Approved) ke " . $cashBankAccount->account_name];
                }
                
                $creditEntries[] = [$arAccountId, $payment->amount, "Pelunasan Piutang (Approved) Inv #" . $invoice->invoice_number];

                $this->accountingService->postJournal(
                    $journalGroupId,
                    now(),
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $payment,
                    Auth::id()
                );
            }

            DB::commit();
            return back()->with('success', 'Pembayaran berhasil disetujui dan jurnal telah diposting.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyetujui pembayaran: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyetujui pembayaran: ' . $e->getMessage());
        }
    }

    public function reject(Payment $payment): RedirectResponse
    {
        if ($payment->status !== 'pending_verification') {
            return back()->with('error', 'Pembayaran ini tidak sedang dalam status verifikasi.');
        }

        DB::beginTransaction();
        try {
            $payment->update([
                'status' => 'failed',
                'received_by_user_id' => Auth::id(),
            ]);
            DB::commit();
            return back()->with('success', 'Bukti pembayaran telah ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menolak pembayaran: ' . $e->getMessage());
            return back()->with('error', 'Gagal menolak pembayaran: ' . $e->getMessage());
        }
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $journalGroupId = "PAY-" . $payment->payment_id;
        if ($error = $this->checkTransactionLock($payment->payment_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus Pembayaran: " . $error);
        }

        DB::beginTransaction();
        try {
            $invoice = $payment->salesInvoice;
            
            ClientLedger::where('reference_type', Payment::class)
                        ->where('reference_id', $payment->payment_id)
                        ->delete();

            if ($payment->status == 'completed') {
                $journalGroupId = "PAY-REV-" . $payment->payment_id;
                $originalJournalEntries = GeneralLedger::where('journal_group_id', "PAY-" . $payment->payment_id)->get();
                
                $debitEntries = [];
                $creditEntries = [];

                foreach ($originalJournalEntries as $entry) {
                    if ($entry->debit > 0) $creditEntries[] = [$entry->chart_of_account_id, $entry->debit, "Reversal: " . $entry->description];
                    if ($entry->credit > 0) $debitEntries[] = [$entry->chart_of_account_id, $entry->credit, "Reversal: " . $entry->description];
                }
                
                if (!empty($debitEntries)) {
                    $this->accountingService->postJournal($journalGroupId, now(), "Reversal Pembayaran Inv #" . $invoice->invoice_number, $debitEntries, $creditEntries, $payment);
                }

                GeneralLedger::where('journal_group_id', "PAY-" . $payment->payment_id)->delete();
            }

            $payment->delete();
            if ($invoice) $invoice->updatePaymentStatus();

            DB::commit();
            return back()->with('success', 'Pembayaran berhasil dibatalkan (Rollback).');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan pembayaran: ' . $e->getMessage());
        }
    }
}