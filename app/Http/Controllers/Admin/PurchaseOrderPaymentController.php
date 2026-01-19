<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderPayment;
use App\Models\PaymentMethod;
use App\Models\SupplierLedger;
use App\Models\GeneralLedger;
use App\Models\CompanyBankAccount;
use App\Models\PurchaseOrderAdjustment;
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

    /**
     * Simpan Pembayaran Baru
     */
    public function store(Request $request, PurchaseOrder $purchaseOrder): \Illuminate\Http\RedirectResponse
    {
        // 1. Validasi Input
        $rules = [
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method_id' => [
                Rule::requiredIf(fn () => $request->input('amount', 0) > 0),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
            'company_bank_account_id' => [
                Rule::requiredIf(fn () => $request->input('amount', 0) > 0),
                'nullable',
                'exists:company_bank_accounts,company_bank_account_id',
            ],
            'notes' => 'nullable|string',
            'use_debit_balance' => 'nullable|boolean',
        ];

        // Validasi Proof & Reference
        $paymentMethod = null;
        if ($request->filled('payment_method_id')) {
            $paymentMethod = PaymentMethod::find($request->input('payment_method_id'));
        }

        if ($paymentMethod && $request->input('amount', 0) > 0) {
            $config = $paymentMethod->internal_input_config;
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

        if ($this->isDateClosed($request->payment_date)) {
            return back()->with('error', 'Gagal: Tanggal pembayaran masuk periode tutup buku.')->withInput();
        }

        DB::beginTransaction();
        try {
            $poLocked = PurchaseOrder::lockForUpdate()->find($purchaseOrder->po_id);
            
            // --- [FIX BUG DOUBLE COUNTING] ---
            // grand_total di DB sudah merupakan nilai final setelah adjustment (karena storeAuto mengupdate grand_total).
            // Jadi kita TIDAK PERLU lagi menghitung debit/credit note manual di sini.
            
            // Hitung Total yang sudah dibayar (completed)
            $totalPaid = $poLocked->payments()->where('status', 'completed')->sum('amount');
            
            // Rumus Kewajiban Bersih: Grand Total (Terkini) - Retur (yang potong tagihan)
            $kewajibanBersih = $poLocked->grand_total - $poLocked->total_returned;
            
            // Sisa Hutang Real
            $sisaHutang = max(0, $kewajibanBersih - $totalPaid);
            
            // --- [END FIX] ---

            $supplier = $poLocked->supplier;
            $danaDariInput = (float) ($validated['amount'] ?? 0);
            $pakaiDeposit = (bool) ($validated['use_debit_balance'] ?? false);
            $depositAwalSupplier = $supplier->balance;

            // Validasi penggunaan deposit
            if ($pakaiDeposit && $depositAwalSupplier <= 0.01) {
                $pakaiDeposit = false; 
            }

            // Alokasi Dana
            $depositAkanDigunakan = 0;
            $danaInputAkanDigunakan = 0;
            $sisaDanaInput = 0; // Ini akan menjadi Deposit Baru (Overpayment)

            // 1. Prioritaskan Deposit Lama
            if ($pakaiDeposit) {
                $depositAkanDigunakan = min($depositAwalSupplier, $sisaHutang);
            }

            // 2. Hitung sisa yang harus dibayar
            $sisaHutangSetelahDeposit = max(0, $sisaHutang - $depositAkanDigunakan);

            // 3. Gunakan Dana Input
            $danaInputAkanDigunakan = min($danaDariInput, $sisaHutangSetelahDeposit);
            
            // 4. Hitung Overpayment (Jika input lebih besar dari sisa hutang)
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            $totalPembayaranAllocated = $depositAkanDigunakan + $danaInputAkanDigunakan;

            if ($totalPembayaranAllocated <= 0.01 && $sisaDanaInput <= 0.01) {
                throw new \Exception("Jumlah pembayaran 0 atau tidak ada alokasi dana.");
            }

            // Ambil Akun Akuntansi
            $apAccountId = $this->accountingSettings->getAccountsPayableId();
            $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();
            
            if (!$apAccountId || !$supplierDepositAccountId) {
                throw new \Exception("Akun Hutang (AP) atau Deposit Supplier belum diatur di Pengaturan.");
            }

            // Validasi Akun Bank
            $cashBankAccountId = null;
            $cashBankAccount = null;
            if ($danaDariInput > 0) {
                $cashBankAccount = CompanyBankAccount::find($validated['company_bank_account_id']);
                if (!$cashBankAccount || !$cashBankAccount->chart_of_account_id) {
                    throw new \Exception("Akun Bank tidak valid atau belum terhubung ke COA.");
                }
                $cashBankAccountId = $cashBankAccount->chart_of_account_id;
            }

            // Simpan File Bukti
            $proofPath = null;
            if ($request->hasFile('proof_of_payment')) {
                $proofPath = $request->file('proof_of_payment')->store('payment_proofs', 'public');
            }

            // Buat Catatan Log
            $catatanLog = $validated['notes'] ?? '';
            if ($depositAkanDigunakan > 0) $catatanLog .= " | Potong Deposit: " . number_format($depositAkanDigunakan);
            if ($sisaDanaInput > 0) $catatanLog .= " | Kelebihan Bayar -> Deposit: " . number_format($sisaDanaInput);

            $newPaymentStatus = $paymentMethod ? $paymentMethod->internal_status_default : 'completed';

            // 1. Simpan Data Pembayaran
            $payment = $poLocked->payments()->create([
                'payment_date' => $validated['payment_date'],
                'amount' => $totalPembayaranAllocated, // Hanya mencatat yang dialokasikan ke hutang PO
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                'status' => $newPaymentStatus,
                'notes' => $catatanLog,
                'received_by_user_id' => Auth::id(), 
                'reference_number' => $validated['reference_number'] ?? null,
                'proof_of_payment_path' => $proofPath,
            ]);

            // 2. Handle Ledger (Deposit)
            // A. Deposit Terpakai (Debit Ledger)
            if ($depositAkanDigunakan > 0) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => PurchaseOrderPayment::class,
                    'reference_id' => $payment->id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit', // Mengurangi saldo deposit
                    'amount' => -$depositAkanDigunakan, 
                    'status' => 'available',
                    'description' => 'Digunakan untuk melunasi PO #' . $poLocked->po_number,
                    'user_id' => Auth::id(),
                ]);
            }

            // B. Overpayment Menjadi Deposit Baru (Credit Ledger)
            if ($sisaDanaInput > 0.01) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => PurchaseOrderPayment::class,
                    'reference_id' => $payment->id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit', // Menambah saldo deposit
                    'amount' => $sisaDanaInput,
                    'status' => 'available',
                    'description' => 'Kelebihan bayar PO #' . $poLocked->po_number,
                    'user_id' => Auth::id(),
                ]);
            }

            // 3. Jurnal Akuntansi
            if ($newPaymentStatus == 'completed') {
                $journalGroupId = "PO-PAY-" . $payment->id;
                $description = "Pembayaran PO #" . $poLocked->po_number;
                
                $debitEntries = [];
                $creditEntries = [];

                // Debit: Hutang Dagang (Berkurang sebesar alokasi)
                if ($totalPembayaranAllocated > 0) {
                    $debitEntries[] = [$apAccountId, $totalPembayaranAllocated, "Pelunasan hutang PO #" . $poLocked->po_number];
                }

                // Debit: Deposit Supplier (Bertambah karena Overpayment)
                if ($sisaDanaInput > 0) {
                    $debitEntries[] = [$supplierDepositAccountId, $sisaDanaInput, "Kelebihan bayar (Masuk Deposit)"];
                }

                // Kredit: Bank (Uang Tunai Keluar Total)
                if ($danaDariInput > 0 && $cashBankAccountId) {
                    $creditEntries[] = [$cashBankAccountId, $danaDariInput, "Keluar dari " . $cashBankAccount->account_name];
                }

                // Kredit: Deposit Supplier (Berkurang karena Dipakai)
                if ($depositAkanDigunakan > 0) {
                    $creditEntries[] = [$supplierDepositAccountId, $depositAkanDigunakan, "Potong deposit lama"];
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

            // 4. Update Status PO
            $poLocked->updatePaymentStatus();

            DB::commit();

            // Notifikasi Pintar
            $msgParts = [];
            $msgParts[] = 'Pembayaran PO berhasil';
            
            if ($totalPembayaranAllocated > 0) {
                $msgParts[] = 'Dialokasikan ke hutang: Rp ' . number_format($totalPembayaranAllocated);
            }
            if ($depositAkanDigunakan > 0) {
                $msgParts[] = 'Dipotong dari Deposit: Rp ' . number_format($depositAkanDigunakan);
            }
            if ($sisaDanaInput > 0) {
                $msgParts[] = 'Masuk Deposit Supplier (Kelebihan Bayar): Rp ' . number_format($sisaDanaInput);
            }

            $pendingReturns = $poLocked->returns()->where('return_handling_type', 'deduct_invoice')->count();
            if ($pendingReturns > 0) {
                $msgParts[] = "(Catatan: Ada {$pendingReturns} retur potong tagihan)";
            }

            return back()->with('success', implode('. ', $msgParts) . '.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mencatat pembayaran PO: ' . $e->getMessage());
            return back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Hapus Pembayaran
     */
    public function destroy(PurchaseOrderPayment $payment): \Illuminate\Http\RedirectResponse
    {
        $journalGroupId = "PO-PAY-" . $payment->id;

        if ($error = $this->checkTransactionLock($payment->payment_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus Pembayaran: " . $error);
        }

        DB::beginTransaction();
        try {
            $purchaseOrder = $payment->purchaseOrder;

            // 1. Hapus Ledger terkait pembayaran ini (Deposit terpakai/terbentuk)
            SupplierLedger::where('reference_type', PurchaseOrderPayment::class)
                          ->where('reference_id', $payment->id)
                          ->delete();

            // 2. Reversal Jurnal Pembayaran
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

                // Hapus Jurnal Lama
                GeneralLedger::where('journal_group_id', $journalGroupId)->delete();
            }

            // 3. Hapus Record Payment
            $payment->delete();

            // 4. [AUTO-CLEANUP] Validasi ulang deposit otomatis
            // Jika setelah hapus pembayaran ini, hutang PO kembali muncul (atau impas),
            // maka "Auto Debit Note" (Deposit Sistem) yang mungkin terbentuk harus dihapus.
            if ($purchaseOrder) {
                $this->revalidateAutoDeposits($purchaseOrder);
                $purchaseOrder->updatePaymentStatus();
            }

            DB::commit();
            return back()->with('success', 'Pembayaran berhasil dibatalkan. Deposit penyesuaian (jika ada) telah divalidasi ulang.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal hapus pembayaran: ' . $e->getMessage());
            return back()->with('error', 'Gagal membatalkan pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Helper: Membersihkan Deposit Otomatis jika Hutang Muncul Kembali atau Impas
     */
    private function revalidateAutoDeposits(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->refresh();

        // 1. Hitung Kewajiban Bersih (Tanpa memperhitungkan Auto Debit Note dari sistem)
        $baseTotal = $purchaseOrder->grand_total - $purchaseOrder->total_returned;
        
        // Kewajiban Real saat ini
        $realObligation = $baseTotal;

        // 2. Ambil Total yang Sudah Dibayar
        $totalPaid = $purchaseOrder->payments()->sum('amount');

        // 3. LOGIKA FIX: 
        // Jika Real Obligation >= Total Paid (Berarti TIDAK Overpaid / Impas), 
        // maka SEMUA "Auto Debit Note" (Deposit System) harus dihapus karena tidak valid lagi.
        
        // Toleransi 0.01 untuk float
        if ($realObligation >= ($totalPaid - 0.01)) {
            
            // Cari Auto Adjustment (Debit Note penyeimbang) yang dibuat sistem
            $autoAdjustments = $purchaseOrder->adjustments()
                ->where('type', 'debit_note')
                ->where(function($q) {
                    $q->where('reason', 'like', 'System:%')
                      ->orWhere('reason', 'like', 'Otomatis:%');
                })
                ->get();

            foreach ($autoAdjustments as $adj) {
                // Parse Ledger ID dari reason string
                if (preg_match('/Ledger #(\d+)/', $adj->reason, $matches)) {
                    $ledgerId = $matches[1];
                    // Hapus Ledger (Deposit Supplier)
                    SupplierLedger::where('ledger_id', $ledgerId)->delete();
                }

                // Hapus Jurnal Auto Adj
                $this->reverseJournal("PO-ADJ-" . $adj->adjustment_id, "System Cleanup (Payment Deleted/Balanced)");

                // Hapus Adjustment Record
                $adj->delete();
                
                Log::info("Auto Deposit Cleanup: Deleted Adjustment #{$adj->adjustment_id} for PO #{$purchaseOrder->po_number}");
            }
        }
    }

    private function reverseJournal($groupId, $desc) {
        $entries = GeneralLedger::where('journal_group_id', $groupId)->get();
        if ($entries->isEmpty()) return;
        
        // Hapus langsung karena ini koreksi sistem atas data yang invalid (Cleanup)
        GeneralLedger::where('journal_group_id', $groupId)->delete();
    }
}