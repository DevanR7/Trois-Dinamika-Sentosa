<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PurchaseOrderPayment;
use App\Models\ClientLedger;
use App\Models\SupplierLedger;
use App\Models\BatchPurchasePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;

class PaymentClearanceController extends Controller
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
        // $this->middleware('permission:manage-payment-clearance');
    }

    /**
     * Menampilkan daftar semua pembayaran yang menunggu kliring
     */
    public function index(): View
    {
        // Ambil piutang (sales payments) yang menunggu kliring
        $salesPayments = Payment::where('status', 'pending_clearance')
            ->with(['salesInvoice.client', 'paymentMethod', 'companyBankAccount'])
            ->get();

        // Ambil hutang (purchase payments) yang menunggu kliring
        $purchasePayments = PurchaseOrderPayment::where('status', 'pending_clearance')
            ->with(['purchaseOrder.supplier', 'paymentMethod', 'companyBankAccount'])
            ->get();

        // Gabungkan dan tambahkan identifier type
        $combined = $salesPayments->map(function ($item) {
            $item->payment_type = 'Piutang';
            return $item;
        })->concat($purchasePayments->map(function ($item) {
            $item->payment_type = 'Hutang';
            return $item;
        }));

        // Urutkan berdasarkan tanggal
        $pendingPayments = $combined->sortBy('payment_date');

        return view('payment_clearance.index', compact('pendingPayments'));
    }

    /**
     * Menyetujui kliring Piutang (Sales Payment)
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi
     */
    public function approveSalesPayment(Payment $payment): RedirectResponse
    {
        if ($payment->status !== 'pending_clearance') {
            return back()->with('error', 'Status pembayaran ini bukan pending kliring.');
        }

        try {
            DB::beginTransaction();
            
            // 1. Update status
            $payment->update(['status' => 'completed']);
            $payment->salesInvoice->updatePaymentStatus();

            // 2. ✅ Post Jurnal Akuntansi
            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            $cashBankAccount = $payment->companyBankAccount;
            $cashBankAccountId = $cashBankAccount?->chart_of_account_id;

            if (!$arAccountId || !$clientDepositAccountId) {
                throw new \Exception("Akun Piutang Usaha (AR) atau Akun Deposit Klien belum diatur.");
            }
            if (!$cashBankAccountId) {
                 throw new \Exception("Akun Bank di pembayaran ini tidak terhubung ke Chart of Account.");
            }

            // Hitung alokasi dari ClientLedger
            $ledgerEntries = ClientLedger::where('reference_type', Payment::class)
                                ->where('reference_id', $payment->payment_id)
                                ->get();
            
            $kreditAkanDigunakan = abs($ledgerEntries->where('type', 'debit')->sum('amount'));
            $sisaDanaInput = $ledgerEntries->where('type', 'credit')->sum('amount');
            $totalPembayaran = $payment->amount; // Piutang yang dilunasi
            $danaDariInput = ($totalPembayaran - $kreditAkanDigunakan) + $sisaDanaInput; // Total kas masuk

            $journalGroupId = "PAY-" . $payment->payment_id;
            $description = "Kliring Pembayaran Inv #" . $payment->salesInvoice->invoice_number;

            $debitEntries = [];
            $creditEntries = [];

            // (D) Kas/Bank (Total Kas Masuk)
            if ($danaDariInput > 0) {
                $debitEntries[] = [$cashBankAccountId, $danaDariInput, "Kliring ke " . $cashBankAccount->account_name];
            }
            
            // (D) Deposit Klien (Deposit Terpakai)
            if ($kreditAkanDigunakan > 0) {
                $debitEntries[] = [$clientDepositAccountId, $kreditAkanDigunakan, "Penggunaan deposit klien (Kliring)"];
            }
            
            // (K) Piutang Usaha (Piutang Lunas)
            if ($totalPembayaran > 0) {
                $creditEntries[] = [$arAccountId, $totalPembayaran, "Pelunasan Piutang (Kliring)"];
            }
            
            // (K) Deposit Klien (Kelebihan Bayar)
            if ($sisaDanaInput > 0) {
                $creditEntries[] = [$clientDepositAccountId, $sisaDanaInput, "Kelebihan bayar (Kliring)"];
            }

            $this->accountingService->postJournal(
                $journalGroupId,
                $payment->payment_date,
                $description,
                $debitEntries,
                $creditEntries,
                $payment
            );

            DB::commit();
            return back()->with('success', 'Kliring piutang berhasil disetujui dan jurnal telah diposting.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal setujui kliring piutang: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }

    /**
     * Menolak kliring Piutang (Sales Payment)
     */
    public function rejectSalesPayment(Payment $payment): RedirectResponse
    {
        if ($payment->status !== 'pending_clearance') {
            return back()->with('error', 'Status pembayaran ini bukan pending kliring.');
        }

        try {
            DB::beginTransaction();
            
            $payment->update(['status' => 'failed']);
            $payment->salesInvoice->updatePaymentStatus();

            DB::commit();
            return back()->with('success', 'Kliring piutang berhasil ditolak.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal tolak kliring piutang: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }

    /**
     * Menyetujui kliring Hutang (Purchase Order Payment)
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi
     */
    public function approvePurchasePayment(PurchaseOrderPayment $purchaseOrderPayment): RedirectResponse
    {
        if ($purchaseOrderPayment->status !== 'pending_clearance') {
            return back()->with('error', 'Status pembayaran ini bukan pending kliring.');
        }

        try {
            DB::beginTransaction();
            
            // 1. Update status
            $purchaseOrderPayment->update(['status' => 'completed']);
            $purchaseOrderPayment->purchaseOrder->updatePaymentStatus();

            // 2. ✅ Post Jurnal Akuntansi
            $payment = $purchaseOrderPayment;
            $purchaseOrder = $payment->purchaseOrder;
            
            $apAccountId = $this->accountingSettings->getAccountsPayableId();
            $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();
            $cashBankAccount = $payment->companyBankAccount;
            $cashBankAccountId = $cashBankAccount?->chart_of_account_id;

            if (!$apAccountId || !$supplierDepositAccountId) {
                throw new \Exception("Akun Hutang Dagang (AP) atau Akun Deposit Supplier belum diatur.");
            }
            if (!$cashBankAccountId) {
                 throw new \Exception("Akun Bank di pembayaran ini tidak terhubung ke Chart of Account.");
            }

            // Hitung alokasi dari SupplierLedger
            $ledgerEntries = SupplierLedger::where(function($q) use ($payment) {
                                $q->where('reference_type', PurchaseOrderPayment::class)
                                  ->where('reference_id', $payment->id);
                            })->orWhere(function($q) use ($payment) {
                                $q->where('reference_type', BatchPurchasePayment::class)
                                  ->where('reference_id', $payment->batch_purchase_payment_id);
                            })->get();

            $pakaiDepositNominal = abs($ledgerEntries->where('type', 'debit')->sum('amount'));
            $sisaDana = $ledgerEntries->where('type', 'credit')->sum('amount');
            $totalAlokasi = $payment->amount; // Hutang yang dilunasi
            $danaInput = ($totalAlokasi - $pakaiDepositNominal) + $sisaDana; // Total kas keluar

            // Tentukan journal group ID
            $journalGroupId = $payment->batch_purchase_payment_id 
                ? "BPO-PAY-" . $payment->batch_purchase_payment_id 
                : "PO-PAY-" . $payment->id;
                
            $description = "Kliring Pembayaran PO #" . $purchaseOrder->po_number;

            $debitEntries = [];
            $creditEntries = [];

            // (D) Hutang Dagang (Total AP Lunas)
            if ($totalAlokasi > 0) {
                $debitEntries[] = [$apAccountId, $totalAlokasi, "Pelunasan hutang (Kliring)"];
            }
            
            // (D) Deposit Supplier (Kelebihan Bayar)
            if ($sisaDana > 0) {
                $debitEntries[] = [$supplierDepositAccountId, $sisaDana, "Kelebihan bayar (Kliring)"];
            }
            
            // (K) Kas/Bank (Total Kas Keluar)
            if ($danaInput > 0) {
                $creditEntries[] = [$cashBankAccountId, $danaInput, "Pembayaran dari " . $cashBankAccount->account_name];
            }
            
            // (K) Deposit Supplier (Deposit Terpakai)
            if ($pakaiDepositNominal > 0) {
                $creditEntries[] = [$supplierDepositAccountId, $pakaiDepositNominal, "Penggunaan deposit (Kliring)"];
            }

            $this->accountingService->postJournal(
                $journalGroupId,
                $payment->payment_date,
                $description,
                $debitEntries,
                $creditEntries,
                $payment
            );
            
            DB::commit();
            return back()->with('success', 'Kliring hutang berhasil disetujui dan jurnal telah diposting.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal setujui kliring hutang: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }

    /**
     * Menolak kliring Hutang (Purchase Order Payment)
     */
    public function rejectPurchasePayment(PurchaseOrderPayment $purchaseOrderPayment): RedirectResponse
    {
        if ($purchaseOrderPayment->status !== 'pending_clearance') {
            return back()->with('error', 'Status pembayaran ini bukan pending kliring.');
        }

        try {
            DB::beginTransaction();
            
            $purchaseOrderPayment->update(['status' => 'failed']);
            $purchaseOrderPayment->purchaseOrder->updatePaymentStatus();

            DB::commit();
            return back()->with('success', 'Kliring hutang berhasil ditolak.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal tolak kliring hutang: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }
}