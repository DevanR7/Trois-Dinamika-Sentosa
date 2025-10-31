<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\SupplierLedger;
use App\Models\PurchaseOrderPayment;

class PurchaseOrderPaymentController extends Controller
{
    public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        // ✅ 1. Validasi Input
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0', // boleh 0 jika bayar pakai deposit
            'payment_date' => 'required|date',
            'payment_method' => [
                Rule::requiredIf(function () use ($request) {
                    return $request->input('amount', 0) > 0 || !$request->has('use_debit_balance');
                }),
                'string',
                'nullable',
            ],
            'notes' => 'nullable|string',
            'use_debit_balance' => 'nullable|boolean',
        ]);

        // ✅ 2. Variabel Awal
        $supplier = $purchaseOrder->supplier;
        $danaDariInput = (float)($validated['amount'] ?? 0);
        $pakaiDeposit = $validated['use_debit_balance'] ?? false;
        $depositAwalSupplier = $supplier->balance; // ambil dari accessor Supplier
        $totalRetur = $purchaseOrder->total_returned;
        $sisaTagihan = $purchaseOrder->total_amount - $purchaseOrder->amount_paid - $totalRetur;

        $depositAkanDigunakan = 0;
        $danaInputAkanDigunakan = 0;
        $sisaDanaInput = 0;
        $metodeLog = $validated['payment_method'] ?? 'N/A';
        $catatanLog = $validated['notes'] ?? '';

        DB::beginTransaction();
        try {
            // ✅ 3. Hitung alokasi dana
            if ($pakaiDeposit && $depositAwalSupplier > 0) {
                $depositAkanDigunakan = min($depositAwalSupplier, $sisaTagihan);
            }

            $sisaTagihanSetelahDeposit = max(0, $sisaTagihan - $depositAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahDeposit);
            $totalPembayaran = $depositAkanDigunakan + $danaInputAkanDigunakan;
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan); // kelebihan bayar

            if ($totalPembayaran <= 0.01) {
                throw new \Exception("Tidak ada dana (input/deposit) yang dialokasikan.");
            }

            // ✅ 4. Tentukan metode pembayaran untuk log
            if ($depositAkanDigunakan > 0) {
                $metodeLog = ($danaInputAkanDigunakan > 0)
                    ? 'Deposit + ' . $validated['payment_method']
                    : 'Deposit Supplier';
            }
            if (!empty($catatanLog)) $catatanLog .= " | ";

            // ✅ 5. Catat pembayaran di tabel purchase_order_payments
            $payment = $purchaseOrder->payments()->create([
                'payment_date' => $validated['payment_date'],
                'amount' => $totalPembayaran,
                'payment_method' => $metodeLog,
                'notes' => $validated['notes'],
                'received_by_user_id' => Auth::id(),
            ]);

            // ✅ 6. Ledger Supplier (Ganti debit_balance manual → ledger otomatis)
            if ($depositAkanDigunakan > 0) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => PurchaseOrderPayment::class,
                    'reference_id' => $payment->id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit', // dana keluar dari deposit
                    'amount' => -$depositAkanDigunakan,
                    'description' => 'Digunakan untuk membayar PO #' . $purchaseOrder->po_number,
                    'user_id' => Auth::id(),
                ]);
                $catatanLog .= "Deposit used: " . number_format($depositAkanDigunakan);
            }

            // ✅ Kelebihan bayar → masuk ke deposit (ledger kredit)
            if ($sisaDanaInput > 0.01) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => PurchaseOrderPayment::class,
                    'reference_id' => $payment->id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit',
                    'amount' => $sisaDanaInput,
                    'description' => 'Kelebihan bayar dari PO #' . $purchaseOrder->po_number,
                    'user_id' => Auth::id(),
                ]);
                $catatanLog .= ". Overpayment: " . number_format($sisaDanaInput) . " returned to deposit.";
            }

            // ✅ Update catatan log di payment
            $payment->update(['notes' => $catatanLog]);

            // ✅ 7. Hitung ulang total pembayaran dan status PO
            $totalPaid = $purchaseOrder->payments()->sum('amount');
            $totalReturned = $purchaseOrder->total_returned;
            $sisaUtangBaru = $purchaseOrder->total_amount - $totalReturned - $totalPaid;

            $newStatus = 'unpaid';
            if ($sisaUtangBaru <= 0.01) {
                $newStatus = 'paid';
            } elseif ($totalPaid > 0) {
                $newStatus = 'partially_paid';
            }

            $purchaseOrder->update([
                'amount_paid' => $totalPaid,
                'payment_status' => $newStatus,
            ]);

            DB::commit();
            return back()->with('success', 'Pembayaran berhasil dicatat. Total: Rp ' . number_format($totalPembayaran));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
    }
}
