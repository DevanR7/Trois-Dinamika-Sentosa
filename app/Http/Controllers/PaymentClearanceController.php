<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PurchaseOrderPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class PaymentClearanceController extends Controller
{
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

            DB::commit();
            return back()->with('success', 'Kliring piutang berhasil disetujui.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal setujui kliring piutang: ' . $e->getMessage());
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

            DB::commit();
            return back()->with('success', 'Kliring hutang berhasil disetujui.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal setujui kliring hutang: ' . $e->getMessage());
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