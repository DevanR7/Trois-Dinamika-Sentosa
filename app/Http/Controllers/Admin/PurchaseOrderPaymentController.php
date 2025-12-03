<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
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
// 1. Import Trait
use App\Traits\ValidatesAccountingPeriod;

class PurchaseOrderPaymentController extends Controller
{
    /**
     * ✅ Inject Service Akuntansi
     */
    protected $accountingService;
    protected $accountingSettings;

    // 2. Gunakan Trait
    use ValidatesAccountingPeriod;

    public function __construct(
        AccountingService $accountingService, 
        AccountingSettingService $accountingSettingService
    ) {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettingService;
    }

    /**
     * Menyimpan pembayaran untuk purchase order tertentu.
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi.
     * ✅ DIPERBARUI: Menambahkan validasi periode akuntansi
     */
    public function store(Request $request, PurchaseOrder $purchaseOrder): \Illuminate\Http\RedirectResponse
    {
        // --- Validasi Dasar & Dinamis ---
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

        // --- Validasi Dinamis Berdasarkan Metode Pembayaran ---
        $paymentMethod = null;
        if ($request->filled('payment_method_id')) {
            $paymentMethod = PaymentMethod::find($request->input('payment_method_id'));
        }

        if ($paymentMethod) {
            $config = $paymentMethod->required_fields_config;

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

        // --- 🔒 VALIDASI PERIODE AKUNTANSI ---
        if ($this->isDateClosed($request->payment_date)) {
            return back()->with('error', 'Gagal: Tanggal pembayaran masuk periode tutup buku.')->withInput();
        }
        // -------------------------------------

        // --- Persiapan Data ---
        $supplier = $purchaseOrder->supplier;
        $danaDariInput = (float) ($validated['amount'] ?? 0);
        $pakaiDeposit = (bool) ($validated['use_debit_balance'] ?? false);
        $depositAwalSupplier = $supplier->balance;
        $sisaTagihan = $purchaseOrder->remaining_balance;

        $depositAkanDigunakan = 0;
        $danaInputAkanDigunakan = 0;
        $sisaDanaInput = 0;
        $catatanLog = $validated['notes'] ?? '';

        DB::beginTransaction();
        try {
            // --- Hitung Alokasi Dana ---
            if ($pakaiDeposit && $depositAwalSupplier > 0) {
                $depositAkanDigunakan = min($depositAwalSupplier, $sisaTagihan);
            }

            $sisaTagihanSetelahDeposit = max(0, $sisaTagihan - $depositAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahDeposit);
            $totalPembayaran = $depositAkanDigunakan + $danaInputAkanDigunakan;
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            if ($totalPembayaran <= 0.01 && $sisaDanaInput <= 0.01 && $sisaTagihan > 0.01) {
                throw new \Exception("Tidak ada dana (input/deposit) yang dialokasikan.");
            }

            // --- ✅ Validasi Akun Akuntansi ---
            $apAccountId = $this->accountingSettings->getAccountsPayableId();
            $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();
            if (!$apAccountId || !$supplierDepositAccountId) {
                throw new \Exception("Akun Hutang Dagang (AP) atau Akun Deposit Supplier belum diatur di Pengaturan Akuntansi.");
            }
            
            $cashBankAccount = null;
            if ($danaDariInput > 0) {
                $cashBankAccount = CompanyBankAccount::find($validated['company_bank_account_id']);
                if (!$cashBankAccount || !$cashBankAccount->chart_of_account_id) {
                    throw new \Exception("Akun Bank Perusahaan yang dipilih belum terhubung ke Chart of Account.");
                }
            }

            // --- Persiapan Metode dan Status Pembayaran ---
            $paymentMethodName = 'N/A';
            $paymentMethodType = 'direct';

            if (!empty($validated['payment_method_id'])) {
                $method = PaymentMethod::find($validated['payment_method_id']);
                if ($method) {
                    $paymentMethodName = $method->name;
                    $paymentMethodType = $method->type;
                }
            }

            $newPaymentStatus = ($paymentMethodType === 'pending') ? 'pending_clearance' : 'completed';

            // --- Unggah Bukti Pembayaran ---
            $proofPath = null;
            if ($request->hasFile('proof_of_payment')) {
                $proofPath = $request->file('proof_of_payment')->store('payment_proofs', 'public');
            }

            // --- Simpan Entri Pembayaran ---
            $payment = $purchaseOrder->payments()->create([
                'payment_date' => $validated['payment_date'],
                'amount' => $totalPembayaran,
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                'status' => $newPaymentStatus,
                'notes' => $catatanLog,
                'received_by_user_id' => Auth::id(),
                'reference_number' => $validated['reference_number'] ?? null,
                'proof_of_payment_path' => $proofPath,
            ]);

            // --- Update Ledger Supplier ---
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
                $catatanLog .= ' | Kelebihan bayar: ' . number_format($sisaDanaInput) . ' disimpan ke deposit.';
            }

            // Perbarui catatan jika ada tambahan info
            if ($depositAkanDigunakan > 0 || $sisaDanaInput > 0.01) {
                $payment->update(['notes' => $catatanLog]);
            }

            // --- ✅ Post Jurnal Akuntansi ---
            if ($newPaymentStatus == 'completed') {
                $journalGroupId = "PO-PAY-" . $payment->id;
                $description = "Pembayaran PO #" . $purchaseOrder->po_number;

                // Jurnal seimbang:
                // (D) Hutang Dagang      (Total AP Lunas) : $totalPembayaran
                // (D) Deposit Supplier  (Kelebihan Bayar) : $sisaDanaInput
                // (K) Kas/Bank         (Total Kas Keluar) : $danaDariInput
                // (K) Deposit Supplier (Deposit Terpakai) : $depositAkanDigunakan
                
                $debitEntries = [];
                $creditEntries = [];

                // (D) Hutang Dagang
                if ($totalPembayaran > 0) {
                    $debitEntries[] = [$apAccountId, $totalPembayaran, "Pelunasan hutang PO #" . $purchaseOrder->po_number];
                }
                // (D) Deposit Supplier (Kelebihan bayar jadi deposit baru)
                if ($sisaDanaInput > 0) {
                    $debitEntries[] = [$supplierDepositAccountId, $sisaDanaInput, "Kelebihan bayar PO #" . $purchaseOrder->po_number];
                }
                
                // (K) Kas/Bank
                if ($danaDariInput > 0 && $cashBankAccount) {
                    $creditEntries[] = [$cashBankAccount->chart_of_account_id, $danaDariInput, "Pembayaran dari " . $cashBankAccount->account_name];
                }
                // (K) Deposit Supplier (Deposit terpakai)
                if ($depositAkanDigunakan > 0) {
                    $creditEntries[] = [$supplierDepositAccountId, $depositAkanDigunakan, "Penggunaan deposit untuk PO #" . $purchaseOrder->po_number];
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

            // --- Perbarui Status Pembayaran PO ---
            $purchaseOrder->updatePaymentStatus();

            DB::commit();

            return back()->with('success', 'Pembayaran berhasil dicatat. Total: Rp ' . number_format($totalPembayaran));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mencatat pembayaran PO: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * ✅ (BARU) Menghapus/Membatalkan Pembayaran
     * Ini penting untuk akuntansi.
     * ✅ DIPERBARUI: Menambahkan validasi periode akuntansi
     */
    public function destroy(PurchaseOrderPayment $payment): \Illuminate\Http\RedirectResponse
    {
        // $this->authorize('delete', $payment); // Anda bisa tambahkan Policy nanti
        
        // --- 🔒 VALIDASI PERIODE AKUNTANSI ---
        $journalGroupId = "PO-PAY-" . $payment->id;
        
        if ($error = $this->checkTransactionLock($payment->payment_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus Pembayaran: " . $error);
        }
        // -------------------------------------

        DB::beginTransaction();
        try {
            $purchaseOrder = $payment->purchaseOrder;
            $supplier = $purchaseOrder->supplier;
            $paymentDate = $payment->payment_date;
            
            // 1. Rollback SupplierLedger (JIKA ADA)
            SupplierLedger::where('reference_type', PurchaseOrderPayment::class)
                          ->where('reference_id', $payment->id)
                          ->where('type', 'debit')
                          ->delete();
            
            SupplierLedger::where('reference_type', PurchaseOrderPayment::class)
                          ->where('reference_id', $payment->id)
                          ->where('type', 'credit')
                          ->delete();

            // 2. ✅ Post Jurnal Reversal (Pembalikan)
            $apAccountId = $this->accountingSettings->getAccountsPayableId();
            $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();
            $cashBankAccount = $payment->companyBankAccount;

            if (!$apAccountId || !$supplierDepositAccountId) {
                throw new \Exception("Akun AP atau Deposit Supplier belum diatur.");
            }

            // Jurnal Reversal adalah kebalikan dari Jurnal Store
            $journalGroupId = "PO-PAY-REV-" . $payment->id;
            $description = "Reversal Pembayaran PO #" . $purchaseOrder->po_number;
            
            // Ambil data dari jurnal aslinya
            $originalJournalEntries = GeneralLedger::where('journal_group_id', "PO-PAY-" . $payment->id)->get();
            
            $debitEntries = [];
            $creditEntries = [];
            
            foreach ($originalJournalEntries as $entry) {
                /** @var \App\Models\GeneralLedger $entry */
                // Balikkan Debit jadi Kredit
                if ($entry->debit > 0) {
                    $creditEntries[] = [$entry->chart_of_account_id, $entry->debit, "Reversal: " . $entry->description];
                }
                // Balikkan Kredit jadi Debit
                if ($entry->credit > 0) {
                    $debitEntries[] = [$entry->chart_of_account_id, $entry->credit, "Reversal: " . $entry->description];
                }
            }
            
            if (!empty($debitEntries) || !empty($creditEntries)) {
                $this->accountingService->postJournal(
                    $journalGroupId,
                    now(),
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $payment
                );
            }

            // 3. Hapus Jurnal Asli
            GeneralLedger::where('journal_group_id', "PO-PAY-" . $payment->id)->delete();

            // 4. Hapus data pembayaran
            $payment->delete();

            // 5. Update status PO
            if ($purchaseOrder) {
                $purchaseOrder->updatePaymentStatus();
            }

            DB::commit();
            return back()->with('success', 'Pembayaran berhasil dibatalkan (Rollback).');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menghapus pembayaran PO: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal membatalkan pembayaran: ' . $e->getMessage());
        }
    }
}