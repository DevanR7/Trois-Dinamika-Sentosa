<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\BulkPurchasePayment;
use App\Models\PaymentMethod;
use App\Models\CompanyBankAccount;
use App\Models\SupplierLedger;
use App\Models\GeneralLedger;
use App\Models\PurchaseOrderPayment; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;
use Illuminate\Support\Facades\Log;
use App\Traits\ValidatesAccountingPeriod;
use Illuminate\Validation\Rule;

class BulkPurchasePaymentController extends Controller
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
        
        $this->middleware('can:pay-purchase-orders');
    }

    public function create(): View
    {
        $suppliers = Supplier::orderBy('supplier_name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)
            ->whereIn('type', ['direct', 'pending'])
            ->orderBy('name')
            ->get();
        $companyBankAccounts = CompanyBankAccount::where('is_active', true)
            ->orderBy('bank_name')
            ->get();
            
        return view('admin.bulk_purchase_payments.create', compact('suppliers', 'paymentMethods', 'companyBankAccounts'));
    }

    public function getUnpaidPurchaseOrdersApi(Supplier $supplier): JsonResponse
    {
        $purchaseOrders = $supplier->purchaseOrders()
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->where('status', '!=', 'cancelled') 
            ->with(['deductingReturns', 'adjustments'])
            ->orderBy('due_date', 'asc')
            ->get();

        $posWithBalance = $purchaseOrders->map(function ($po) {
            $paid = $po->payments()->where('status', 'completed')->sum('amount');
            
            $po->load('adjustments');
            $totalAdj = $po->adjustments->reduce(function ($carry, $adj) {
                return $carry + ($adj->type === 'debit_note' ? $adj->amount : -$adj->amount);
            }, 0);
            
            $sisaHutang = max(0, round($po->grand_total - $paid - $po->total_returned, 2));

            return [
                'po_id' => $po->po_id,
                'po_number' => $po->po_number,
                'due_date_formatted' => optional($po->due_date)->format('d M Y') ?? 'N/A',
                'sisa_tagihan' => $sisaHutang,
            ];
        })->filter(fn($po) => $po['sisa_tagihan'] > 0.01);

        return response()->json($posWithBalance->values());
    }

    public function store(Request $request): RedirectResponse
    {
        // 1. Validasi Input
        $rules = [
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'payment_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0', 
            'payment_method_id' => [
                Rule::requiredIf(fn() => $request->input('total_amount', 0) > 0),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
            'company_bank_account_id' => [
                Rule::requiredIf(fn() => $request->input('total_amount', 0) > 0),
                'nullable',
                'exists:company_bank_accounts,company_bank_account_id',
            ],
            'notes' => 'nullable|string|max:1000',
            'po_ids' => 'required|array|min:1',
            'po_ids.*' => 'required|exists:purchase_orders,po_id',
            'use_debit_balance' => 'nullable|boolean', 
        ];

        // Validasi Proof & Reference
        $paymentMethod = null;
        if ($request->input('total_amount', 0) > 0 && $request->filled('payment_method_id')) {
            $paymentMethod = PaymentMethod::find($request->payment_method_id);
            if ($paymentMethod) {
                $config = $paymentMethod->internal_input_config;
                $rules['proof_of_payment'] = in_array($config, ['proof_only', 'proof_and_reference']) 
                    ? 'required|image|mimes:jpeg,png,jpg|max:2048' 
                    : 'nullable|image|mimes:jpeg,png,jpg|max:2048';
                $rules['reference_number'] = in_array($config, ['reference_only', 'proof_and_reference']) 
                    ? 'required|string|max:255' 
                    : 'nullable|string|max:255';
            }
        } else {
            $rules['proof_of_payment'] = 'nullable';
            $rules['reference_number'] = 'nullable';
        }

        $validated = $request->validate($rules);

        if ($this->isDateClosed($request->payment_date)) {
            return back()->with('error', 'Gagal: Tanggal pembayaran masuk periode tutup buku.')->withInput();
        }

        DB::beginTransaction();
        try {
            $supplier = Supplier::lockForUpdate()->findOrFail($validated['supplier_id']);
            
            $danaCashInput = round((float)($validated['total_amount'] ?? 0), 2);
            $pakaiDeposit = $request->boolean('use_debit_balance');
            $saldoDepositTersedia = $supplier->balance; 

            // 2. Ambil PO yang dipilih
            $posDipilih = PurchaseOrder::whereIn('po_id', $validated['po_ids'])
                ->lockForUpdate() 
                ->orderBy('due_date', 'asc')
                ->get();

            // 3. Hitung Sisa Hutang Real-time
            $totalTagihanValid = 0;
            $posValid = $posDipilih->filter(function($po) use (&$totalTagihanValid) {
                $kewajiban = $po->grand_total - $po->total_returned;
                $sudahBayar = $po->payments()->where('status', 'completed')->sum('amount');
                
                $sisa = max(0, round($kewajiban - $sudahBayar, 2));
                
                // Set property sementara untuk perhitungan
                $po->temp_remaining = $sisa;
                
                if ($sisa > 0.01) {
                    $totalTagihanValid += $sisa;
                    return true;
                }
                return false;
            });

            if ($posValid->isEmpty()) {
                throw new \Exception("Semua PO yang dipilih sudah lunas.");
            }

            // 4. Kalkulasi Alokasi
            $nominalDepositDipakai = 0;
            if ($pakaiDeposit && $saldoDepositTersedia > 0) {
                $nominalDepositDipakai = min($saldoDepositTersedia, $totalTagihanValid);
            }
            
            $sisaTagihan = max(0, $totalTagihanValid - $nominalDepositDipakai);
            $nominalCashTerpakai = min($danaCashInput, $sisaTagihan);
            $sisaCashOverpayment = max(0, $danaCashInput - $nominalCashTerpakai);
            $totalAlokasiKeHutang = $nominalDepositDipakai + $nominalCashTerpakai;

            if ($totalAlokasiKeHutang <= 0.01 && $sisaCashOverpayment <= 0.01) {
                throw new \Exception("Tidak ada dana yang diproses (Deposit 0 & Cash 0).");
            }

            // 5. Validasi Akun
            $apAccountId = $this->accountingSettings->getAccountsPayableId();
            $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();
            
            if (!$apAccountId || !$supplierDepositAccountId) throw new \Exception("Akun AP atau Deposit belum diatur.");
            
            $cashBankAccountId = null;
            $cashBankAccount = null;
            if ($danaCashInput > 0) {
                $cashBankAccount = CompanyBankAccount::find($validated['company_bank_account_id']);
                if (!$cashBankAccount || !$cashBankAccount->chart_of_account_id) throw new \Exception("Akun Bank tidak valid.");
                $cashBankAccountId = $cashBankAccount->chart_of_account_id;
            }

            // 6. Simpan File Proof
            $proofPath = $request->hasFile('proof_of_payment')
                ? $request->file('proof_of_payment')->store('payment_proofs', 'public')
                : null;

            // 7. Buat Header Bulk Payment
            $newStatus = ($paymentMethod && $paymentMethod->type === 'pending') ? 'pending_clearance' : 'completed';

            $bulkPayment = BulkPurchasePayment::create([
                'payment_number' => BulkPurchasePayment::generateNumber(),
                'supplier_id' => $supplier->supplier_id,
                'processed_by_user_id' => Auth::id(),
                'payment_date' => $validated['payment_date'],
                'total_amount' => $danaCashInput,
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                'status' => $newStatus,
                'notes' => $validated['notes'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'proof_of_payment_path' => $proofPath,
            ]);

            // JIKA COMPLETED, JALANKAN LOGIC
            if ($newStatus == 'completed') {

                // A. Debit Ledger Supplier (Pakai Deposit)
                if ($nominalDepositDipakai > 0) {
                    SupplierLedger::create([
                        'supplier_id' => $supplier->supplier_id,
                        'reference_type' => BulkPurchasePayment::class,
                        'reference_id' => $bulkPayment->bulk_purchase_payment_id,
                        'transaction_date' => $validated['payment_date'],
                        'type' => 'debit', 
                        'amount' => -$nominalDepositDipakai,
                        'status' => 'available',
                        'description' => 'Digunakan untuk Bulk Payment #' . $bulkPayment->payment_number,
                        'user_id' => Auth::id(),
                    ]);
                }

                // B. Kredit Ledger Supplier (Overpayment)
                if ($sisaCashOverpayment > 0) {
                    SupplierLedger::create([
                        'supplier_id' => $supplier->supplier_id,
                        'reference_type' => BulkPurchasePayment::class,
                        'reference_id' => $bulkPayment->bulk_purchase_payment_id,
                        'transaction_date' => $validated['payment_date'],
                        'type' => 'credit',
                        'amount' => $sisaCashOverpayment,
                        'status' => 'available',
                        'description' => 'Kelebihan bayar Bulk Payment #' . $bulkPayment->payment_number,
                        'user_id' => Auth::id(),
                    ]);
                }

                // C. Loop Alokasi ke PO
                $poolDeposit = $nominalDepositDipakai;
                $poolCash = $nominalCashTerpakai;
                $alokasiLog = [];

                foreach ($posValid as $po) {
                    if (($poolDeposit + $poolCash) <= 0.01) break;

                    // Ambil nilai sisa tagihan dari property sementara
                    $tagihanPO = $po->temp_remaining;

                    // --- [BUG FIX: HAPUS ATTRIBUTE TEMPORARY] ---
                    // Sangat Penting! Hapus 'temp_remaining' dari model agar Eloquent tidak mencoba menyimpannya ke DB
                    unset($po['temp_remaining']); 
                    // --------------------------------------------
                    
                    // Ambil dari Deposit dulu
                    $bayarDariDeposit = min($tagihanPO, $poolDeposit);
                    $poolDeposit -= $bayarDariDeposit;
                    $tagihanPO -= $bayarDariDeposit;

                    // Ambil dari Cash
                    $bayarDariCash = min($tagihanPO, $poolCash);
                    $poolCash -= $bayarDariCash;

                    $totalBayarPO = round($bayarDariDeposit + $bayarDariCash, 2);

                    if ($totalBayarPO > 0) {
                        $po->payments()->create([
                            'bulk_purchase_payment_id' => $bulkPayment->bulk_purchase_payment_id,
                            'payment_date' => $validated['payment_date'],
                            'amount' => $totalBayarPO,
                            'payment_method_id' => $validated['payment_method_id'] ?? null,
                            'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                            'status' => 'completed',
                            'received_by_user_id' => Auth::id(),
                            'reference_number' => $validated['reference_number'] ?? null,
                            'notes' => 'Bulk #' . $bulkPayment->payment_number . " (Dep: " . number_format($bayarDariDeposit) . ", Cash: " . number_format($bayarDariCash) . ")",
                        ]);

                        $po->updatePaymentStatus();
                        $alokasiLog[] = $po->po_number;
                    }
                }

                // D. JURNAL AKUNTANSI
                $journalGroupId = "BLK-PO-" . $bulkPayment->bulk_purchase_payment_id;
                $description = "Pembayaran Massal ke " . $supplier->supplier_name;
                
                $debitEntries = [];
                $creditEntries = [];

                if ($totalAlokasiKeHutang > 0) {
                    $debitEntries[] = [$apAccountId, $totalAlokasiKeHutang, "Pelunasan Hutang Bulk"];
                }
                if ($sisaCashOverpayment > 0) {
                    $debitEntries[] = [$supplierDepositAccountId, $sisaCashOverpayment, "Kelebihan Bayar (Masuk Deposit)"];
                }
                if ($danaCashInput > 0 && $cashBankAccountId) {
                    $creditEntries[] = [$cashBankAccountId, $danaCashInput, "Keluar dari " . $cashBankAccount->account_name];
                }
                if ($nominalDepositDipakai > 0) {
                    $creditEntries[] = [$supplierDepositAccountId, $nominalDepositDipakai, "Potong Deposit Lama"];
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
            
            $msg = ($newStatus == 'completed') 
                ? 'Pembayaran Bulk Berhasil! ' . count($alokasiLog) . ' PO terbayar.' 
                : 'Pembayaran Bulk disimpan (Menunggu Verifikasi).';

            return redirect()->route('admin.purchase-orders.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal Bulk Purchase: ' . $e->getMessage());
            return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(BulkPurchasePayment $bulkPayment): RedirectResponse
    {
        $journalGroupId = "BLK-PO-" . $bulkPayment->bulk_purchase_payment_id;
        
        if ($error = $this->checkTransactionLock($bulkPayment->payment_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus: " . $error);
        }

        DB::beginTransaction();
        try {
            SupplierLedger::where('reference_type', BulkPurchasePayment::class)
                          ->where('reference_id', $bulkPayment->bulk_purchase_payment_id)
                          ->delete();

            foreach ($bulkPayment->payments as $payment) {
                $po = $payment->purchaseOrder;
                $payment->delete();
                if ($po) $po->updatePaymentStatus();
            }

            GeneralLedger::where('journal_group_id', $journalGroupId)->delete();
            $bulkPayment->delete();

            DB::commit();
            return redirect()->route('admin.purchase-orders.index')
                ->with('success', 'Bulk pembayaran berhasil dihapus dan jurnal dibersihkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan bulk: ' . $e->getMessage());
        }
    }
}