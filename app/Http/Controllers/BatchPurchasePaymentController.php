<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\BatchPurchasePayment;
use App\Models\PaymentMethod;
use App\Models\CompanyBankAccount;
use App\Models\SupplierLedger;
use App\Models\GeneralLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;
use Illuminate\Support\Facades\Log;

class BatchPurchasePaymentController extends Controller
{
    /**
     * ✅ Inject Service Akuntansi
     */
    protected $accountingService;
    protected $accountingSettings;

    public function __construct(
        AccountingService $accountingService, 
        AccountingSettingService $accountingSettingService
    ) {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettingService;
    }

    /**
     * =========================================================
     * FORM PEMBUATAN PEMBAYARAN HUTANG (BATCH)
     * =========================================================
     */
    public function create(): View
    {
        // $this->authorize('create-batch-purchase-payments');

        $suppliers = Supplier::orderBy('supplier_name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)
            ->whereIn('type', ['direct', 'pending'])
            ->orderBy('name')
            ->get();

        $companyBankAccounts = CompanyBankAccount::where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        return view('batch_purchase_payments.create', compact('suppliers', 'paymentMethods', 'companyBankAccounts'));
    }

    /**
     * =========================================================
     * API: AMBIL DAFTAR PURCHASE ORDER YANG BELUM LUNAS
     * =========================================================
     */
    public function getUnpaidPurchaseOrdersApi(Supplier $supplier): JsonResponse
    {
        $purchaseOrders = $supplier->purchaseOrders()
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->with(['deductingReturns', 'adjustments'])
            ->orderBy('due_date', 'asc')
            ->get();

        $posWithBalance = $purchaseOrders->map(function ($po) {
            $sisaTagihan = $po->remaining_balance;
            return [
                'po_id' => $po->po_id,
                'po_number' => $po->po_number,
                'due_date_formatted' => optional($po->due_date)->format('d M Y') ?? 'N/A',
                'sisa_tagihan' => $sisaTagihan,
            ];
        })->filter(fn($po) => $po['sisa_tagihan'] > 0.01);

        return response()->json($posWithBalance);
    }

    /**
     * =========================================================
     * SIMPAN PEMBAYARAN BATCH (PROSES UTAMA)
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi Agregat
     * =========================================================
     */
    public function store(Request $request): RedirectResponse
    {
        // $this->authorize('create-batch-purchase-payments');

        $rules = [
            'supplier_id' => 'required|exists:suppliers,supplier_id',
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
            'notes' => 'nullable|string|max:1000',
            'po_ids' => 'required|array|min:1',
            'po_ids.*' => 'required|exists:purchase_orders,po_id',
            'use_debit_balance' => 'nullable|boolean',
        ];

        // Validasi tambahan berdasarkan konfigurasi metode pembayaran
        $paymentMethod = $request->filled('payment_method_id')
            ? PaymentMethod::find($request->payment_method_id)
            : null;

        if ($paymentMethod) {
            $config = $paymentMethod->required_fields_config;
            if (in_array($config, ['proof_only', 'proof_and_reference'])) {
                $rules['proof_of_payment'] = 'required|image|mimes:jpeg,png,jpg|max:2048';
            } else {
                $rules['proof_of_payment'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048';
            }

            if (in_array($config, ['reference_only', 'proof_and_reference'])) {
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
            $supplier = Supplier::findOrFail($validated['supplier_id']);
            $danaInput = (float)($validated['total_amount'] ?? 0);
            $pakaiDeposit = $validated['use_debit_balance'] ?? false;
            $depositAwal = $supplier->balance;

            $posDipilih = PurchaseOrder::whereIn('po_id', $validated['po_ids'])
                ->with(['deductingReturns', 'adjustments'])
                ->orderBy('due_date', 'asc')
                ->get();

            $totalTagihan = $posDipilih->sum(fn($po) => $po->remaining_balance);

            if ($totalTagihan <= 0.01) {
                throw new \Exception("Semua PO yang dipilih sudah lunas.");
            }

            $pakaiDepositNominal = ($pakaiDeposit && $depositAwal > 0)
                ? min($depositAwal, $totalTagihan)
                : 0;

            $sisaTagihan = max(0, $totalTagihan - $pakaiDepositNominal);
            $pakaiInputNominal = min($danaInput, $sisaTagihan);
            $totalAlokasi = $pakaiDepositNominal + $pakaiInputNominal;
            $sisaDana = max(0, $danaInput - $pakaiInputNominal);

            if ($totalAlokasi <= 0.01) {
                throw new \Exception("Dana tidak cukup untuk dialokasikan.");
            }

            // --- ✅ Validasi Akun Akuntansi ---
            $apAccountId = $this->accountingSettings->getAccountsPayableId();
            $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();
            if (!$apAccountId || !$supplierDepositAccountId) {
                throw new \Exception("Akun Hutang Dagang (AP) atau Akun Deposit Supplier belum diatur di Pengaturan Akuntansi.");
            }
            
            $cashBankAccount = null;
            if ($danaInput > 0) {
                $cashBankAccount = CompanyBankAccount::find($validated['company_bank_account_id']);
                if (!$cashBankAccount || !$cashBankAccount->chart_of_account_id) {
                    throw new \Exception("Akun Bank Perusahaan yang dipilih belum terhubung ke Chart of Account.");
                }
            }

            $paymentMethodType = $paymentMethod?->type ?? 'direct';
            $newStatus = $paymentMethodType === 'pending' ? 'pending_clearance' : 'completed';

            $proofPath = $request->hasFile('proof_of_payment')
                ? $request->file('proof_of_payment')->store('payment_proofs', 'public')
                : null;

            // Buat entri induk BatchPayment
            $batchPayment = BatchPurchasePayment::create([
                'supplier_id' => $supplier->supplier_id,
                'processed_by_user_id' => Auth::id(),
                'payment_date' => $validated['payment_date'],
                'total_amount' => $totalAlokasi,
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                'status' => $newStatus,
                'notes' => $validated['notes'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'proof_of_payment_path' => $proofPath,
            ]);

            $alokasiLog = [];

            // Catat penggunaan deposit supplier
            if ($pakaiDepositNominal > 0) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => BatchPurchasePayment::class,
                    'reference_id' => $batchPayment->batch_payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit',
                    'amount' => -$pakaiDepositNominal,
                    'status' => 'available',
                    'description' => 'Digunakan untuk pembayaran hutang batch #' . $batchPayment->batch_payment_id,
                    'user_id' => Auth::id(),
                ]);
                $alokasiLog[] = "Deposit digunakan Rp " . number_format($pakaiDepositNominal);
            }

            // Proses alokasi dana ke setiap PO
            $sisaDeposit = $pakaiDepositNominal;
            $sisaInput = $pakaiInputNominal;

            foreach ($posDipilih as $po) {
                if ($sisaDeposit <= 0.01 && $sisaInput <= 0.01) break;

                $sisaTagihanPO = $po->remaining_balance;
                if ($sisaTagihanPO <= 0.01) continue;

                $dariDeposit = min($sisaTagihanPO, $sisaDeposit);
                $sisaTagihanPO -= $dariDeposit;
                $dariInput = min($sisaTagihanPO, $sisaInput);
                $dibayar = $dariDeposit + $dariInput;

                if ($dibayar <= 0.01) continue;

                $po->payments()->create([
                    'batch_purchase_payment_id' => $batchPayment->batch_payment_id,
                    'payment_date' => $validated['payment_date'],
                    'amount' => $dibayar,
                    'payment_method_id' => $validated['payment_method_id'] ?? null,
                    'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                    'status' => $newStatus,
                    'received_by_user_id' => Auth::id(),
                    'reference_number' => $validated['reference_number'] ?? null,
                    'proof_of_payment_path' => $proofPath,
                ]);

                // Update status PO hanya jika status completed
                if ($newStatus == 'completed') {
                    $po->updatePaymentStatus();
                }

                $alokasiLog[] = "Rp " . number_format($dibayar) . " dialokasikan ke " . $po->po_number;

                $sisaDeposit -= $dariDeposit;
                $sisaInput -= $dariInput;
            }

            // Simpan kelebihan dana input
            if ($sisaDana > 0.01) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => BatchPurchasePayment::class,
                    'reference_id' => $batchPayment->batch_payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit',
                    'amount' => $sisaDana,
                    'status' => 'available',
                    'description' => 'Kelebihan dana dari pembayaran hutang batch #' . $batchPayment->batch_payment_id,
                    'user_id' => Auth::id(),
                ]);
                $alokasiLog[] = "Kelebihan dana Rp " . number_format($sisaDana) . " disimpan sebagai deposit supplier.";
            }

            // --- ✅ Post Jurnal Akuntansi (Agregat) ---
            if ($newStatus == 'completed') {
                $journalGroupId = "BPO-PAY-" . $batchPayment->batch_payment_id;
                $description = "Pembayaran Hutang Batch #" . $batchPayment->batch_payment_id . " ke " . $supplier->supplier_name;

                // Jurnal seimbang:
                // (D) Hutang Dagang      (Total AP Lunas) : $totalAlokasi
                // (D) Deposit Supplier  (Kelebihan Bayar) : $sisaDana
                // (K) Kas/Bank         (Total Kas Keluar) : $danaInput
                // (K) Deposit Supplier (Deposit Terpakai) : $pakaiDepositNominal
                
                $debitEntries = [];
                $creditEntries = [];

                // (D) Hutang Dagang
                if ($totalAlokasi > 0) {
                    $debitEntries[] = [$apAccountId, $totalAlokasi, "Pelunasan hutang batch ke " . $supplier->supplier_name];
                }
                // (D) Deposit Supplier (Kelebihan bayar jadi deposit baru)
                if ($sisaDana > 0) {
                    $debitEntries[] = [$supplierDepositAccountId, $sisaDana, "Kelebihan bayar batch"];
                }
                
                // (K) Kas/Bank
                if ($danaInput > 0 && $cashBankAccount) {
                    $creditEntries[] = [$cashBankAccount->chart_of_account_id, $danaInput, "Pembayaran dari " . $cashBankAccount->account_name];
                }
                // (K) Deposit Supplier (Deposit terpakai)
                if ($pakaiDepositNominal > 0) {
                    $creditEntries[] = [$supplierDepositAccountId, $pakaiDepositNominal, "Penggunaan deposit untuk batch"];
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

            $message = 'Batch pembayaran berhasil. ' . implode('. ', $alokasiLog);
            return redirect()->route('batch-purchase-payments.create')
            ->with('success', 'Pembayaran Batch Berhasil Disimpan. ' . count($alokasiLog) . ' alokasi diproses.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan batch pembayaran PO: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal menyimpan pembayaran: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * =========================================================
     * ✅ (BARU) MENGHAPUS/MEMBATALKAN BATCH PEMBAYARAN
     * =========================================================
     */
    public function destroy(BatchPurchasePayment $batchPayment): RedirectResponse
    {
        // $this->authorize('delete-batch-purchase-payments');
        
        DB::beginTransaction();
        try {
            $supplier = $batchPayment->supplier;
            $paymentDate = $batchPayment->payment_date;
            
            // 1. Rollback SupplierLedger terkait batch payment ini
            SupplierLedger::where('reference_type', BatchPurchasePayment::class)
                          ->where('reference_id', $batchPayment->batch_payment_id)
                          ->delete();

            // 2. Hapus semua payment individual yang terkait
            $individualPayments = $batchPayment->individualPayments;
            foreach ($individualPayments as $payment) {
                // Hapus payment individual
                $payment->delete();
                
                // Update status PO yang terkait
                $po = $payment->purchaseOrder;
                if ($po) {
                    $po->updatePaymentStatus();
                }
            }

            // 3. ✅ Post Jurnal Reversal (Pembalikan)
            $apAccountId = $this->accountingSettings->getAccountsPayableId();
            $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();

            if (!$apAccountId || !$supplierDepositAccountId) {
                throw new \Exception("Akun AP atau Deposit Supplier belum diatur.");
            }

            // Jurnal Reversal adalah kebalikan dari Jurnal Store
            $journalGroupId = "BPO-PAY-REV-" . $batchPayment->batch_payment_id;
            $description = "Reversal Pembayaran Batch #" . $batchPayment->batch_payment_id;
            
            // Ambil data dari jurnal aslinya
            $originalJournalEntries = GeneralLedger::where('journal_group_id', "BPO-PAY-" . $batchPayment->batch_payment_id)->get();
            
            $debitEntries = [];
            $creditEntries = [];
            
            foreach ($originalJournalEntries as $entry) {
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
                    $batchPayment
                );
            }

            // 4. Hapus Jurnal Asli
            GeneralLedger::where('journal_group_id', "BPO-PAY-" . $batchPayment->batch_payment_id)->delete();

            // 5. Hapus data batch payment
            $batchPayment->delete();

            DB::commit();
            return redirect()->route('purchase-orders.index')->with('success', 'Batch pembayaran berhasil dibatalkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menghapus batch pembayaran: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal membatalkan batch pembayaran: ' . $e->getMessage());
        }
    }
}