<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\PurchaseOrderPayment;
use App\Models\PaymentMethod; // ✅ Pastikan ini ada
use App\Models\SupplierLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log; // ✅ Pastikan ini ada

class PurchaseOrderPaymentController extends Controller
{
     public function store(Request $request, PurchaseOrder $purchaseOrder)
     {
        // 1. Validasi
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method_id' => [
                Rule::requiredIf(function () use ($request) {
                    return $request->input('amount', 0) > 0 || !$request->has('use_debit_balance');
                }),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
            // TAMBAHKAN VALIDASI INI (gunakan rule yang sama)
            'company_bank_account_id' => [
                Rule::requiredIf(function () use ($request) {
                    return $request->input('amount', 0) > 0 || !$request->has('use_debit_balance');
                }),
                'nullable',
                'exists:company_bank_accounts,company_bank_account_id',
            ],
            'notes' => 'nullable|string',
            'use_debit_balance' => 'nullable|boolean',
        ]);

        $supplier = $purchaseOrder->supplier;
        $danaDariInput = (float)($validated['amount'] ?? 0);
        $pakaiDeposit = $validated['use_debit_balance'] ?? false;
        $depositAwalSupplier = $supplier->balance; // Menggunakan accessor 'balance'
        
        // ✅ Gunakan accessor 'remaining_balance' yang sudah menghitung retur & penyesuaian
        $sisaTagihan = $purchaseOrder->remaining_balance; 
        
        $depositAkanDigunakan = 0;
        $danaInputAkanDigunakan = 0;
        $sisaDanaInput = 0; // Overpayment
        $catatanLog = $validated['notes'] ?? '';

        DB::beginTransaction();
        try {
            // 2. Hitung alokasi dana
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

            // 3. Tentukan log metode pembayaran & status
            $paymentMethodName = 'N/A';
            $paymentMethodType = 'direct';
            if (!empty($validated['payment_method_id'])) {
                $method = PaymentMethod::find($validated['payment_method_id']);
                if ($method) {
                    $paymentMethodName = $method->name;
                    $paymentMethodType = $method->type;
                }
            }
            
            // Tentukan status baru (untuk Giro/Cek)
            $newPaymentStatus = ($paymentMethodType == 'pending') ? 'pending_clearance' : 'completed';

            // 4. Catat pembayaran baru
            $payment = $purchaseOrder->payments()->create([
                'payment_date' => $validated['payment_date'],
                'amount' => $totalPembayaran,
                'payment_method_id' => $validated['payment_method_id'],
                'company_bank_account_id' => $validated['company_bank_account_id'] ?? null, // ✅ 2. Simpan ID
                'status' => $newPaymentStatus,
                'notes' => $catatanLog,
                'received_by_user_id' => Auth::id(),
            ]);

            // 5. Proses Database (Ledger)
            if ($depositAkanDigunakan > 0) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => PurchaseOrderPayment::class,
                    'reference_id' => $payment->id, // Gunakan ID payment baru
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit',
                    'amount' => -$depositAkanDigunakan,
                    'status' => 'available',
                    'description' => 'Digunakan untuk membayar PO #' . $purchaseOrder->po_number,
                    'user_id' => Auth::id(),
                ]);
                $catatanLog .= " | Deposit digunakan: " . number_format($depositAkanDigunakan);
            }

            if ($sisaDanaInput > 0.01) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => PurchaseOrderPayment::class,
                    'reference_id' => $payment->id, // Gunakan ID payment baru
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit',
                    'amount' => $sisaDanaInput,
                    'status' => 'available', // Kelebihan bayar selalu 'available'
                    'description' => 'Kelebihan bayar dari PO #' . $purchaseOrder->po_number,
                    'user_id' => Auth::id(),
                ]);
                $catatanLog .= " | Kelebihan bayar: " . number_format($sisaDanaInput) . " disimpan ke deposit.";
            }
            
            // Update catatan log di payment (jika ada tambahan info)
            if ($depositAkanDigunakan > 0 || $sisaDanaInput > 0.01) {
                 $payment->update(['notes' => $catatanLog]);
            }

            // ======================================================
            // ✅ 6. Panggil fungsi update status dari Model PO
            // ======================================================
            $purchaseOrder->updatePaymentStatus();
            
            DB::commit();
            return back()->with('success', 'Pembayaran berhasil dicatat. Total: Rp ' . number_format($totalPembayaran));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mencatat pembayaran PO: ' . $e->getMessage());
            return back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
     }
}