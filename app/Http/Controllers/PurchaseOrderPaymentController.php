<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\SupplierLedger; // ✅ Pastikan ini ada
use App\Models\PurchaseOrderPayment; // ✅ Pastikan ini ada

class PurchaseOrderPaymentController extends Controller
{
     public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        // Validasi
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0', // Boleh 0 jika bayar pakai deposit
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

        $supplier = $purchaseOrder->supplier;
        $danaDariInput = (float)($validated['amount'] ?? 0);
        $pakaiDeposit = $validated['use_debit_balance'] ?? false;
        
        // ✅ 1. BACA SALDO AVAILABLE
        $depositAwalSupplier = $supplier->balance; // <-- Menggunakan accessor 'balance'
        
        $totalRetur = $purchaseOrder->total_returned;
        $sisaTagihan = $purchaseOrder->total_amount - $purchaseOrder->amount_paid - $totalRetur;
        
        $depositAkanDigunakan = 0;
        $danaInputAkanDigunakan = 0;
        $sisaDanaInput = 0;
        $metodeLog = $validated['payment_method'] ?? 'N/A';
        $catatanLog = $validated['notes'] ?? '';

        DB::beginTransaction();
        try {
            // 1. Hitung alokasi dana (Logika Anda sudah benar)
            if ($pakaiDeposit && $depositAwalSupplier > 0) {
                $depositAkanDigunakan = min($depositAwalSupplier, $sisaTagihan);
            }

            $sisaTagihanSetelahDeposit = max(0, $sisaTagihan - $depositAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahDeposit);
            $totalPembayaran = $depositAkanDigunakan + $danaInputAkanDigunakan;
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan); // Overpayment

            if ($totalPembayaran <= 0.01 && $sisaDanaInput <= 0.01) {
                if ($sisaTagihan > 0.01) {
                    throw new \Exception("Tidak ada dana (input/deposit) yang dialokasikan.");
                }
            }

            // 2. Tentukan log metode pembayaran
            if ($depositAkanDigunakan > 0) {
                $metodeLog = ($danaInputAkanDigunakan > 0) ? 'Deposit + ' . $validated['payment_method'] : 'Deposit Supplier';
            }
            if (!empty($catatanLog)) $catatanLog .= " | ";
            // Catatan log akan di-update di bawah

            // 3. Catat pembayaran baru
            $payment = $purchaseOrder->payments()->create([
                'payment_date' => $validated['payment_date'],
                'amount' => $totalPembayaran, // Catat total yg dialokasikan
                'payment_method' => $metodeLog,
                'notes' => $catatanLog,
                'received_by_user_id' => Auth::id(),
            ]);

            // 4. Proses Database (Ledger)
            if ($depositAkanDigunakan > 0) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => PurchaseOrderPayment::class,
                    'reference_id' => $payment->id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit',
                    'amount' => -$depositAkanDigunakan,
                    'status' => 'available', // Menggunakan saldo pasti 'available'
                    'description' => 'Digunakan untuk membayar PO #' . $purchaseOrder->po_number,
                    'user_id' => Auth::id(),
                ]);
                $catatanLog .= " Deposit used: " . number_format($depositAkanDigunakan);
            }

            // ✅ 2. BUAT OVERPAYMENT SEBAGAI 'AVAILABLE'
            if ($sisaDanaInput > 0.01) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => PurchaseOrderPayment::class,
                    'reference_id' => $payment->id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit',
                    'amount' => $sisaDanaInput,
                    'status' => 'available', // Kelebihan bayar selalu 'available'
                    'description' => 'Kelebihan bayar dari PO #' . $purchaseOrder->po_number,
                    'user_id' => Auth::id(),
                ]);
                $catatanLog .= ". Overpayment: " . number_format($sisaDanaInput) . " returned to deposit.";
            }
            
            $payment->update(['notes' => $catatanLog]);

            // 5. Hitung ulang total yang sudah dibayar
            $totalPaid = $purchaseOrder->payments()->sum('amount');
            $totalReturned = $purchaseOrder->total_returned;

            // 6. Update status pembayaran di Purchase Order
            $newStatus = 'unpaid';
            $sisaUtangBaru = $purchaseOrder->total_amount - $totalReturned - $totalPaid;
            
            if ($sisaUtangBaru <= 0.01) { // Toleransi pembulatan
                $newStatus = 'paid';
            } elseif ($totalPaid > 0) {
                $newStatus = 'partially_paid';
            }

            $purchaseOrder->update([
                'amount_paid' => $totalPaid,
                'payment_status' => $newStatus
            ]);
            
            // ======================================================
            // ✅ 3. LEPASKAN DEPOSIT PENDING JIKA LUNAS
            // ======================================================
            if ($newStatus == 'paid') {
                SupplierLedger::where('purchase_order_id', $purchaseOrder->po_id)
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'available',
                                'description' => DB::raw("REPLACE(description, ' (Ditahan)', '')")
                            ]);
            }
            // ======================================================
            
            DB::commit();
            return back()->with('success', 'Pembayaran berhasil dicatat. Total: Rp ' . number_format($totalPembayaran));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
    }
}