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

    public function index(Request $request): View
    {
        $viewMode = $request->input('view', 'pending');

        if ($viewMode === 'history') {
            $statuses = ['completed', 'failed'];
        } else {
            $statuses = ['pending_clearance', 'pending_verification'];
        }

        $salesPayments = Payment::whereIn('status', $statuses)
            ->with(['salesInvoice.client', 'paymentMethod', 'companyBankAccount'])
            ->orderBy('payment_date', 'desc')
            ->get();

        $purchasePayments = PurchaseOrderPayment::whereIn('status', $statuses)
            ->with(['purchaseOrder.supplier', 'paymentMethod', 'companyBankAccount'])
            ->orderBy('payment_date', 'desc')
            ->get();

        $combined = $salesPayments->map(function ($item) {
            $item->payment_type = 'Piutang';
            return $item;
        })->concat($purchasePayments->map(function ($item) {
            $item->payment_type = 'Hutang';
            return $item;
        }));

        $pendingPayments = $combined->sortByDesc('payment_date');

        return view('admin.payment_clearance.index', compact('pendingPayments', 'viewMode'));
    }

    public function approveSalesPayment(Payment $payment): RedirectResponse
    {
        if (!in_array($payment->status, ['pending_clearance', 'pending_verification'])) {
            return back()->with('error', 'Status pembayaran ini bukan pending kliring atau verifikasi.');
        }

        try {
            DB::beginTransaction();

            $payment->update(['status' => 'completed']);
            
            $invoice = $payment->salesInvoice;
            if ($invoice) {
                $invoice->updatePaymentStatus();
                if ($invoice->payment_status === 'paid' || $invoice->status === 'paid') {
                    $duplicatePayments = Payment::where('invoice_id', $invoice->invoice_id)
                        ->where('payment_id', '!=', $payment->payment_id) // Kecuali yang sedang diproses ini
                        ->whereIn('status', ['pending_clearance', 'pending_verification'])
                        ->get();

                    foreach ($duplicatePayments as $dup) {
                        $dup->update([
                            'status' => 'failed',
                            'notes' => $dup->notes . ' | Auto-rejected: Invoice sudah dilunasi oleh pembayaran lain.',
                            'received_by_user_id' => auth()->id()
                        ]);

                        ClientLedger::where('reference_type', Payment::class)
                            ->where('reference_id', $dup->payment_id)
                            ->delete();
                    }
                }
            }

            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            $cashBankAccountId = $payment->companyBankAccount?->chart_of_account_id;

            if (!$cashBankAccountId) {
                $cashBankAccountId = $this->accountingSettings->getGatewayAccountId();
            }
            if (!$arAccountId || !$clientDepositAccountId) {
                throw new \Exception("Akun AR atau Deposit Klien belum diatur di Pengaturan Akuntansi.");
            }
            if (!$cashBankAccountId) {
                 throw new \Exception("Akun Bank Penerima tidak valid/kosong. Pastikan 'Akun Gateway Default' diatur di menu Pengaturan.");
            }

            $ledgerEntries = ClientLedger::where('reference_type', Payment::class)
                                        ->where('reference_id', $payment->payment_id)
                                        ->get();

            $kreditAkanDigunakan = abs($ledgerEntries->where('type', 'debit')->sum('amount'));
            $sisaDanaInput = $ledgerEntries->where('type', 'credit')->sum('amount');
            $totalPembayaranAllocated = $payment->amount; 
            $danaDariInput = ($totalPembayaranAllocated - $kreditAkanDigunakan) + $sisaDanaInput;

            $journalGroupId = "PAY-" . $payment->payment_id;
            $description = "Penerimaan Pembayaran Inv #" . ($invoice->invoice_number ?? '-');

            $debitEntries = [];
            $creditEntries = [];

            if ($danaDariInput > 0) {
                $bankName = $payment->companyBankAccount->account_name ?? 'Kas/Gateway';
                $debitEntries[] = [$cashBankAccountId, $danaDariInput, "Penerimaan ke " . $bankName];
            }
            if ($kreditAkanDigunakan > 0) {
                $debitEntries[] = [$clientDepositAccountId, $kreditAkanDigunakan, "Potong Deposit Klien"];
            }
            if ($totalPembayaranAllocated > 0) {
                $creditEntries[] = [$arAccountId, $totalPembayaranAllocated, "Pelunasan Piutang"];
            }
            if ($sisaDanaInput > 0) {
                $creditEntries[] = [$clientDepositAccountId, $sisaDanaInput, "Kelebihan bayar (Deposit)"];
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
            return back()->with('success', 'Pembayaran berhasil disetujui. Pengajuan pending lainnya untuk invoice ini (jika ada) telah ditolak otomatis.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal setujui kliring piutang: '.$e->getMessage());
            return back()->with('error', 'Gagal memproses: '.$e->getMessage());
        }
    }

    public function rejectSalesPayment(Payment $payment): RedirectResponse
    {
        if (!in_array($payment->status, ['pending_clearance', 'pending_verification'])) {
            return back()->with('error', 'Status pembayaran ini tidak valid untuk ditolak.');
        }

        try {
            DB::beginTransaction();

            $payment->update(['status' => 'failed']);
            
            if ($payment->salesInvoice) {
                $payment->salesInvoice->updatePaymentStatus();
            }

            ClientLedger::where('reference_type', Payment::class)
                ->where('reference_id', $payment->payment_id)
                ->delete();

            DB::commit();
            return back()->with('success', 'Pembayaran ditolak. Deposit (jika ada) telah dibatalkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal tolak kliring piutang: '.$e->getMessage());
            return back()->with('error', 'Gagal memproses: '.$e->getMessage());
        }
    }

    public function approvePurchasePayment(PurchaseOrderPayment $purchaseOrderPayment): RedirectResponse
    {
        if (!in_array($purchaseOrderPayment->status, ['pending_clearance', 'pending_verification'])) {
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
            
            if (!$cashBankAccountId) {
                $cashBankAccountId = $this->accountingSettings->getGatewayAccountId();
            }
            if (!$apAccountId || !$supplierDepositAccountId) {
                throw new \Exception("Akun AP atau Deposit Supplier belum diatur.");
            }
            if (!$cashBankAccountId) {
                 throw new \Exception("Akun Bank Sumber Dana tidak valid/kosong.");
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

            $description = "Pembayaran PO #" . ($payment->purchaseOrder->po_number ?? '-');
            $debitEntries = [];
            $creditEntries = [];

            if ($totalAlokasi > 0) {
                $debitEntries[] = [$apAccountId, $totalAlokasi, "Pelunasan hutang"];
            }
            if ($sisaDana > 0) {
                $debitEntries[] = [$supplierDepositAccountId, $sisaDana, "Kelebihan bayar"];
            }
            if ($danaInput > 0) {
                $creditEntries[] = [$cashBankAccountId, $danaInput, "Pembayaran Bank"];
            }
            if ($pakaiDepositNominal > 0) {
                $creditEntries[] = [$supplierDepositAccountId, $pakaiDepositNominal, "Potong deposit"];
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

    public function rejectPurchasePayment(PurchaseOrderPayment $purchaseOrderPayment): RedirectResponse
    {
        if (!in_array($purchaseOrderPayment->status, ['pending_clearance', 'pending_verification'])) {
            return back()->with('error', 'Status pembayaran ini tidak valid untuk ditolak.');
        }

        try {
            DB::beginTransaction();

            $purchaseOrderPayment->update(['status' => 'failed']);
            $purchaseOrderPayment->purchaseOrder->updatePaymentStatus();

            SupplierLedger::where('reference_type', PurchaseOrderPayment::class)
                ->where('reference_id', $purchaseOrderPayment->id)
                ->delete();

            DB::commit();
            
            return back()->with('success', 'Kliring hutang ditolak.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal tolak kliring hutang: '.$e->getMessage());
            return back()->with('error', 'Gagal memproses: '.$e->getMessage());
        }
    }
}