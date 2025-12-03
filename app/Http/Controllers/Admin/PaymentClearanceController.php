<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PurchaseOrderPayment;
use App\Models\ClientLedger;
use App\Models\SupplierLedger;
use App\Models\BulkPurchasePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;

class PaymentClearanceController extends Controller
{
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
     * Menampilkan daftar pembayaran yang menunggu kliring
     */
    public function index(): View
    {
        $salesPayments = Payment::where('status', 'pending_clearance')
            ->with(['salesInvoice.client', 'paymentMethod', 'companyBankAccount'])
            ->get();

        $purchasePayments = PurchaseOrderPayment::where('status', 'pending_clearance')
            ->with(['purchaseOrder.supplier', 'paymentMethod', 'companyBankAccount'])
            ->get();

        $combined = $salesPayments->map(function ($item) {
            $item->payment_type = 'Piutang';
            return $item;
        })->concat($purchasePayments->map(function ($item) {
            $item->payment_type = 'Hutang';
            return $item;
        }));

        $pendingPayments = $combined->sortBy('payment_date');

        return view('admin.payment_clearance.index', compact('pendingPayments'));
    }

    /**
     * Menyetujui Kliring Piutang
     */
    public function approveSalesPayment(Payment $payment): RedirectResponse
    {
        if ($payment->status !== 'pending_clearance') {
            return back()->with('error', 'Status pembayaran ini bukan pending kliring.');
        }

        try {
            DB::beginTransaction();

            $payment->update(['status' => 'completed']);
            $payment->salesInvoice->updatePaymentStatus();

            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            $cashBankAccountId = $payment->companyBankAccount?->chart_of_account_id;

            if (!$arAccountId || !$clientDepositAccountId) {
                throw new \Exception("Akun AR atau Deposit Klien belum diatur.");
            }
            if (!$cashBankAccountId) {
                 throw new \Exception("Akun Bank tidak terhubung ke Chart of Account.");
            }

            $ledgerEntries = ClientLedger::where('reference_type', Payment::class)
                                ->where('reference_id', $payment->payment_id)
                                ->get();

            $kreditAkanDigunakan = abs($ledgerEntries->where('type', 'debit')->sum('amount'));
            $sisaDanaInput = $ledgerEntries->where('type', 'credit')->sum('amount');
            $totalPembayaran = $payment->amount;
            $danaDariInput = ($totalPembayaran - $kreditAkanDigunakan) + $sisaDanaInput;

            $journalGroupId = "PAY-" . $payment->payment_id;
            $description = "Kliring Pembayaran Inv #" . $payment->salesInvoice->invoice_number;

            $debitEntries = [];
            $creditEntries = [];

            if ($danaDariInput > 0) {
                $debitEntries[] = [$cashBankAccountId, $danaDariInput, "Kliring ke bank"];
            }

            if ($kreditAkanDigunakan > 0) {
                $debitEntries[] = [$clientDepositAccountId, $kreditAkanDigunakan, "Penggunaan deposit klien"];
            }

            if ($totalPembayaran > 0) {
                $creditEntries[] = [$arAccountId, $totalPembayaran, "Pelunasan Piutang"];
            }

            if ($sisaDanaInput > 0) {
                $creditEntries[] = [$clientDepositAccountId, $sisaDanaInput, "Kelebihan bayar"];
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
            return back()->with('success', 'Kliring piutang berhasil disetujui.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal setujui kliring piutang: '.$e->getMessage());
            return back()->with('error', 'Gagal memproses: '.$e->getMessage());
        }
    }

    /**
     * Menolak Kliring Piutang
     * Perbaikan: Kembalikan saldo deposit klien
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

            ClientLedger::where('reference_type', Payment::class)
                ->where('reference_id', $payment->payment_id)
                ->delete();

            DB::commit();
            return back()->with('success', 'Kliring piutang ditolak. Deposit klien sudah dikembalikan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal tolak kliring piutang: '.$e->getMessage());
            return back()->with('error', 'Gagal memproses: '.$e->getMessage());
        }
    }

    /**
     * Menyetujui Kliring Hutang
     */
    public function approvePurchasePayment(PurchaseOrderPayment $purchaseOrderPayment): RedirectResponse
    {
        if ($purchaseOrderPayment->status !== 'pending_clearance') {
            return back()->with('error', 'Status pembayaran ini bukan pending kliring.');
        }

        try {
            DB::beginTransaction();

            $purchaseOrderPayment->update(['status' => 'completed']);
            $purchaseOrderPayment->purchaseOrder->updatePaymentStatus();

            $payment = $purchaseOrderPayment;

            $apAccountId = $this->accountingSettings->getAccountsPayableId();
            $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();
            $cashBankAccountId = $payment->companyBankAccount?->chart_of_account_id;

            if (!$apAccountId || !$supplierDepositAccountId) {
                throw new \Exception("Akun AP atau Deposit Supplier belum diatur.");
            }
            if (!$cashBankAccountId) {
                 throw new \Exception("Akun Bank tidak terhubung ke Chart of Account.");
            }

            $ledgerEntries = SupplierLedger::where(function($q) use ($payment) {
                                $q->where('reference_type', PurchaseOrderPayment::class)
                                  ->where('reference_id', $payment->id);
                            })->orWhere(function($q) use ($payment) {
                                $q->where('reference_type', BulkPurchasePayment::class)
                                  ->where('reference_id', $payment->bulk_purchase_payment_id);
                            })->get();

            $pakaiDepositNominal = abs($ledgerEntries->where('type', 'debit')->sum('amount'));
            $sisaDana = $ledgerEntries->where('type', 'credit')->sum('amount');
            $totalAlokasi = $payment->amount;
            $danaInput = ($totalAlokasi - $pakaiDepositNominal) + $sisaDana;

            $journalGroupId = $payment->bulk_purchase_payment_id 
                ? "BPO-PAY-" . $payment->bulk_purchase_payment_id
                : "PO-PAY-" . $payment->id;

            $description = "Kliring Pembayaran PO #" . $payment->purchaseOrder->po_number;

            $debitEntries = [];
            $creditEntries = [];

            if ($totalAlokasi > 0) {
                $debitEntries[] = [$apAccountId, $totalAlokasi, "Pelunasan hutang"];
            }

            if ($sisaDana > 0) {
                $debitEntries[] = [$supplierDepositAccountId, $sisaDana, "Kelebihan bayar"];
            }

            if ($danaInput > 0) {
                $creditEntries[] = [$cashBankAccountId, $danaInput, "Pembayaran bank"];
            }

            if ($pakaiDepositNominal > 0) {
                $creditEntries[] = [$supplierDepositAccountId, $pakaiDepositNominal, "Penggunaan deposit"];
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
            return back()->with('success', 'Kliring hutang berhasil disetujui.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal setujui kliring hutang: '.$e->getMessage());
            return back()->with('error', 'Gagal memproses: '.$e->getMessage());
        }
    }

    /**
     * Menolak Kliring Hutang
     * Perbaikan: Kembalikan deposit supplier
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

            SupplierLedger::where('reference_type', PurchaseOrderPayment::class)
                ->where('reference_id', $purchaseOrderPayment->id)
                ->delete();

            DB::commit();
            return back()->with('success', 'Kliring hutang ditolak. Deposit supplier telah dikembalikan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal tolak kliring hutang: '.$e->getMessage());
            return back()->with('error', 'Gagal memproses: '.$e->getMessage());
        }
    }
}
