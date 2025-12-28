<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderPayment;
use App\Models\PaymentMethod;
use App\Models\SupplierLedger;
use App\Models\GeneralLedger;
use App\Models\CompanyBankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;
use App\Traits\ValidatesAccountingPeriod;

class PurchaseOrderPaymentController extends Controller
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
        
        $this->middleware('can:pay-purchase-orders')->only(['store', 'destroy']);
    }

    public function store(Request $request, PurchaseOrder $purchaseOrder): \Illuminate\Http\RedirectResponse
    {
        $rules = [
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method_id' => [
                Rule::requiredIf(fn () => $request->input('amount', 0) > 0 || !$request->has('use_debit_balance')),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
            'company_bank_account_id' => [
                Rule::requiredIf(fn () => $request->input('amount', 0) > 0 || !$request->has('use_debit_balance')),
                'nullable',
                'exists:company_bank_accounts,company_bank_account_id',
            ],
            'notes' => 'nullable|string',
            'use_debit_balance' => 'nullable|boolean',
        ];

        $paymentMethod = null;
        if ($request->filled('payment_method_id')) {
            $paymentMethod = PaymentMethod::find($request->input('payment_method_id'));
        }

        if ($paymentMethod) {
            $config = $paymentMethod->internal_input_config;

            $rules['proof_of_payment'] = ($config === 'proof_only' || $config === 'proof_and_reference') 
                ? 'required|image|mimes:jpeg,png,jpg|max:2048' 
                : 'nullable|image|mimes:jpeg,png,jpg|max:2048';
                
            $rules['reference_number'] = ($config === 'reference_only' || $config === 'proof_and_reference') 
                ? 'required|string|max:255' 
                : 'nullable|string|max:255';
        } else {
            $rules['proof_of_payment'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048';
            $rules['reference_number'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        if ($this->isDateClosed($request->payment_date)) {
            return back()->with('error', 'Gagal: Tanggal pembayaran masuk periode tutup buku.')->withInput();
        }

        $purchaseOrder->refresh();
        $purchaseOrder->load('adjustments'); 
        $supplier = $purchaseOrder->supplier;
        $danaDariInput = (float) ($validated['amount'] ?? 0);
        $pakaiDeposit = (bool) ($validated['use_debit_balance'] ?? false);
        $depositAwalSupplier = $supplier->balance; 
        $sisaTagihan = $purchaseOrder->remaining_balance; 
        $sisaTagihan = max(0, $sisaTagihan);

        if ($pakaiDeposit && $depositAwalSupplier <= 0.01) {
            $pakaiDeposit = false;
        }

        $depositAkanDigunakan = 0;
        $danaInputAkanDigunakan = 0;
        $sisaDanaInput = 0;
        $catatanLog = $validated['notes'] ?? '';

        DB::beginTransaction();
        try {
            
            if ($pakaiDeposit && $depositAwalSupplier > 0) {
                $depositAkanDigunakan = min($depositAwalSupplier, $sisaTagihan);
            }

            $sisaTagihanSetelahDeposit = max(0, $sisaTagihan - $depositAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahDeposit);
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);
            $totalPembayaranAllocated = $depositAkanDigunakan + $danaInputAkanDigunakan;

            if ($totalPembayaranAllocated <= 0.01 && $sisaDanaInput <= 0.01 && $sisaTagihan > 0.01) {
                throw new \Exception("Tidak ada dana (input/deposit) yang dialokasikan.");
            }

            $apAccountId = $this->accountingSettings->getAccountsPayableId();
            $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();
            
            if (!$apAccountId || !$supplierDepositAccountId) {
                throw new \Exception("Akun Hutang (AP) atau Deposit Supplier belum diatur.");
            }

            $cashBankAccount = null;
            if ($danaDariInput > 0) {
                $cashBankAccount = CompanyBankAccount::find($validated['company_bank_account_id']);
                if (!$cashBankAccount || !$cashBankAccount->chart_of_account_id) {
                    throw new \Exception("Akun Bank tidak valid atau belum terhubung ke COA.");
                }
            }

            $newPaymentStatus = 'completed'; 
            if ($paymentMethod) {
                $newPaymentStatus = $paymentMethod->internal_status_default; 
            }

            $proofPath = null;
            if ($request->hasFile('proof_of_payment')) {
                $proofPath = $request->file('proof_of_payment')->store('payment_proofs', 'public');
            }

            $payment = $purchaseOrder->payments()->create([
                'payment_date' => $validated['payment_date'],
                'amount' => $totalPembayaranAllocated, 
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                'status' => $newPaymentStatus,
                'notes' => $catatanLog,
                'received_by_user_id' => Auth::id(), 
                'reference_number' => $validated['reference_number'] ?? null,
                'proof_of_payment_path' => $proofPath,
            ]);

            if ($depositAkanDigunakan > 0) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => PurchaseOrderPayment::class,
                    'reference_id' => $payment->id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit', 
                    'amount' => -$depositAkanDigunakan,
                    'status' => 'available',
                    'description' => 'Digunakan untuk membayar PO #' . $purchaseOrder->po_number,
                    'user_id' => Auth::id(),
                ]);
                $catatanLog .= ' | Deposit digunakan: ' . number_format($depositAkanDigunakan);
            }

            if ($sisaDanaInput > 0.01) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => PurchaseOrderPayment::class,
                    'reference_id' => $payment->id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit', 
                    'amount' => $sisaDanaInput,
                    'status' => 'available',
                    'description' => 'Kelebihan bayar dari PO #' . $purchaseOrder->po_number,
                    'user_id' => Auth::id(),
                ]);
                $catatanLog .= ' | Sisa dana Rp ' . number_format($sisaDanaInput) . ' masuk ke Deposit.';
            }

            if ($depositAkanDigunakan > 0 || $sisaDanaInput > 0.01) {
                $payment->update(['notes' => $catatanLog]);
            }
            if ($newPaymentStatus == 'completed') {
                $journalGroupId = "PO-PAY-" . $payment->id;
                $description = "Pembayaran PO #" . $purchaseOrder->po_number;
                $debitEntries = [];
                $creditEntries = [];

                if ($totalPembayaranAllocated > 0) {
                    $debitEntries[] = [$apAccountId, $totalPembayaranAllocated, "Pelunasan hutang PO #" . $purchaseOrder->po_number];
                }
                if ($sisaDanaInput > 0) {
                    $debitEntries[] = [$supplierDepositAccountId, $sisaDanaInput, "Kelebihan bayar masuk Deposit"];
                }
                if ($danaDariInput > 0 && $cashBankAccount) {
                    $creditEntries[] = [$cashBankAccount->chart_of_account_id, $danaDariInput, "Pembayaran dari " . $cashBankAccount->account_name];
                }
                if ($depositAkanDigunakan > 0) {
                    $creditEntries[] = [$supplierDepositAccountId, $depositAkanDigunakan, "Potong deposit lama"];
                }

                $this->accountingService->postJournal(
                    $journalGroupId,
                    $validated['payment_date'],
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $payment
                );
            }

            $purchaseOrder->updatePaymentStatus();

            DB::commit();

            return back()->with('success', 'Pembayaran berhasil. Total dialokasikan: Rp ' . number_format($totalPembayaranAllocated) . '. Sisa masuk deposit: Rp ' . number_format($sisaDanaInput));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mencatat pembayaran PO: ' . $e->getMessage());
            return back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(PurchaseOrderPayment $payment): \Illuminate\Http\RedirectResponse
    {
        $journalGroupId = "PO-PAY-" . $payment->id;

        if ($error = $this->checkTransactionLock($payment->payment_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus Pembayaran: " . $error);
        }

        DB::beginTransaction();
        try {
            $purchaseOrder = $payment->purchaseOrder;

            SupplierLedger::where('reference_type', PurchaseOrderPayment::class)
                          ->where('reference_id', $payment->id)
                          ->delete();

            if ($payment->status == 'completed') {
                $originalEntries = GeneralLedger::where('journal_group_id', $journalGroupId)->get();
                
                $debitEntries = [];
                $creditEntries = [];

                foreach ($originalEntries as $entry) {
                    if ($entry->debit > 0) {
                        $creditEntries[] = [$entry->chart_of_account_id, $entry->debit, "Reversal: " . $entry->description];
                    }
                    if ($entry->credit > 0) {
                        $debitEntries[] = [$entry->chart_of_account_id, $entry->credit, "Reversal: " . $entry->description];
                    }
                }

                if (!empty($debitEntries)) {
                    $this->accountingService->postJournal(
                        "PO-PAY-REV-" . $payment->id,
                        now(),
                        "Reversal Pembayaran PO #" . ($purchaseOrder->po_number ?? '-'),
                        $debitEntries,
                        $creditEntries,
                        $payment
                    );
                }

                GeneralLedger::where('journal_group_id', $journalGroupId)->delete();
            }

            $payment->delete();

            if ($purchaseOrder) {
                $purchaseOrder->updatePaymentStatus();
            }

            DB::commit();
            return back()->with('success', 'Pembayaran berhasil dibatalkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal hapus pembayaran: ' . $e->getMessage());
            return back()->with('error', 'Gagal membatalkan pembayaran: ' . $e->getMessage());
        }
    }
}