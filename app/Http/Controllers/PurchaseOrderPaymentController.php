<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseOrderPaymentController extends Controller
{
     public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        // Validasi
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0', // Boleh 0 jika bayar pakai deposit
            'payment_date' => 'required|date',
            'payment_method' => [
                // Wajib KECUALI jika amount 0 DAN use_debit_balance dicentang
                Rule::requiredIf(function () use ($request) {
                    return $request->input('amount', 0) > 0 || !$request->has('use_debit_balance');
                }),
                'string',
                'nullable',
            ],
            'notes' => 'nullable|string',
            'use_debit_balance' => 'nullable|boolean', // Ambil data checkbox
        ]);

        $supplier = $purchaseOrder->supplier;
        $danaDariInput = (float)($validated['amount'] ?? 0);
        $pakaiDeposit = $validated['use_debit_balance'] ?? false;
        $depositAwalSupplier = (float)($supplier->debit_balance ?? 0);
        $totalRetur = $purchaseOrder->total_returned;
        $sisaTagihan = $purchaseOrder->total_amount - $purchaseOrder->amount_paid - $totalRetur;
        
        $depositAkanDigunakan = 0;
        $danaInputAkanDigunakan = 0;
        $sisaDanaInput = 0;
        $metodeLog = $validated['payment_method'] ?? 'N/A';
        $catatanLog = $validated['notes'] ?? '';

        DB::beginTransaction();
        try {
            // 1. Hitung alokasi dana
            if ($pakaiDeposit && $depositAwalSupplier > 0) {
                $depositAkanDigunakan = min($depositAwalSupplier, $sisaTagihan);
            }

            $sisaTagihanSetelahDeposit = max(0, $sisaTagihan - $depositAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahDeposit);
            $totalPembayaran = $depositAkanDigunakan + $danaInputAkanDigunakan;
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan); // Overpayment

            if ($totalPembayaran <= 0.01) {
                 throw new \Exception("Tidak ada dana (input/deposit) yang dialokasikan.");
            }

            // 2. Tentukan log metode pembayaran
            if ($depositAkanDigunakan > 0) {
                $metodeLog = ($danaInputAkanDigunakan > 0) ? 'Deposit + ' . $validated['payment_method'] : 'Deposit Supplier';
            }
            if (!empty($catatanLog)) $catatanLog .= " | ";
            $catatanLog .= "Auto-processed. Deposit: " . number_format($depositAkanDigunakan) . ". Input: " . number_format($danaInputAkanDigunakan);

            // 3. Proses Database
            if ($depositAkanDigunakan > 0) {
                $supplier->decrement('debit_balance', $depositAkanDigunakan);
            }
            if ($sisaDanaInput > 0.01) {
                $supplier->increment('debit_balance', $sisaDanaInput);
                 $catatanLog .= ". Overpayment: " . number_format($sisaDanaInput) . " returned to deposit.";
            }

            // 4. Catat pembayaran baru
            $purchaseOrder->payments()->create([
                'payment_date' => $validated['payment_date'],
                'amount' => $totalPembayaran, // Catat total yg dialokasikan
                'payment_method' => $metodeLog,
                'notes' => $catatanLog,
                'received_by_user_id' => Auth::id(),
            ]);

            // 5. Hitung ulang total yang sudah dibayar
            $totalPaid = $purchaseOrder->payments()->sum('amount');
            $totalReturned = $purchaseOrder->returns()->sum('total_amount'); // Ambil ulang

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
            
            DB::commit();
            return back()->with('success', 'Pembayaran berhasil dicatat. Total: Rp ' . number_format($totalPembayaran));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
    }
}
