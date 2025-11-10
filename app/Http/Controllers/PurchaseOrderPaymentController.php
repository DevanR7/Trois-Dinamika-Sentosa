<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\PurchaseOrderPayment;
use App\Models\PaymentMethod;
use App\Models\SupplierLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class PurchaseOrderPaymentController extends Controller
{
    /**
     * Menyimpan pembayaran untuk purchase order tertentu.
     */
    public function store(Request $request, PurchaseOrder $purchaseOrder): \Illuminate\Http\RedirectResponse
    {
        // --- Validasi Dasar ---
        $rules = [
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method_id' => [
                Rule::requiredIf(fn () => $request->input('amount', 0) > 0 || !$request->has('use_debit_balance')),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
            'company_bank_account_id' => [
                Rule::requiredIf(fn () => $request->input('amount', 0) > 0 || !$request->has('use_debit_balance')),
                'nullable',
                'exists:company_bank_accounts,company_bank_account_id',
            ],
            'notes' => 'nullable|string',
            'use_debit_balance' => 'nullable|boolean',
        ];

        // --- Validasi Dinamis Berdasarkan Metode Pembayaran ---
        $paymentMethod = null;
        if ($request->filled('payment_method_id')) {
            $paymentMethod = PaymentMethod::find($request->input('payment_method_id'));
        }

        if ($paymentMethod) {
            $config = $paymentMethod->required_fields_config;

            $rules['proof_of_payment'] = ($config === 'proof_only' || $config === 'proof_and_reference')
                ? 'required|image|mimes:jpeg,png,jpg|max:2048'
                : 'nullable|image|mimes:jpeg,png,jpg|max:2048';

            $rules['reference_number'] = ($config === 'reference_only' || $config === 'proof_and_reference')
                ? 'required|string|max:255'
                : 'nullable|string|max:255';
        } else {
            $rules['proof_of_payment'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048';
            $rules['reference_number'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        // --- Persiapan Data ---
        $supplier = $purchaseOrder->supplier;
        $danaDariInput = (float) ($validated['amount'] ?? 0);
        $pakaiDeposit = (bool) ($validated['use_debit_balance'] ?? false);
        $depositAwalSupplier = $supplier->balance;
        $sisaTagihan = $purchaseOrder->remaining_balance;

        $depositAkanDigunakan = 0;
        $danaInputAkanDigunakan = 0;
        $sisaDanaInput = 0;
        $catatanLog = $validated['notes'] ?? '';

        DB::beginTransaction();
        try {
            // --- Hitung Alokasi Dana ---
            if ($pakaiDeposit && $depositAwalSupplier > 0) {
                $depositAkanDigunakan = min($depositAwalSupplier, $sisaTagihan);
            }

            $sisaTagihanSetelahDeposit = max(0, $sisaTagihan - $depositAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahDeposit);
            $totalPembayaran = $depositAkanDigunakan + $danaInputAkanDigunakan;
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            if ($totalPembayaran <= 0.01 && $sisaDanaInput <= 0.01 && $sisaTagihan > 0.01) {
                throw new \Exception("Tidak ada dana (input/deposit) yang dialokasikan.");
            }

            // --- Persiapan Metode dan Status Pembayaran ---
            $paymentMethodName = 'N/A';
            $paymentMethodType = 'direct';

            if (!empty($validated['payment_method_id'])) {
                $method = PaymentMethod::find($validated['payment_method_id']);
                if ($method) {
                    $paymentMethodName = $method->name;
                    $paymentMethodType = $method->type;
                }
            }

            $newPaymentStatus = ($paymentMethodType === 'pending') ? 'pending_clearance' : 'completed';

            // --- Unggah Bukti Pembayaran ---
            $proofPath = null;
            if ($request->hasFile('proof_of_payment')) {
                $proofPath = $request->file('proof_of_payment')->store('payment_proofs', 'public');
            }

            // --- Simpan Entri Pembayaran ---
            $payment = $purchaseOrder->payments()->create([
                'payment_date' => $validated['payment_date'],
                'amount' => $totalPembayaran,
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                'status' => $newPaymentStatus,
                'notes' => $catatanLog,
                'received_by_user_id' => Auth::id(),
                'reference_number' => $validated['reference_number'] ?? null,
                'proof_of_payment_path' => $proofPath,
            ]);

            // --- Update Ledger Supplier ---
            if ($depositAkanDigunakan > 0) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => PurchaseOrderPayment::class,
                    'reference_id' => $payment->id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit',
                    'amount' => -$depositAkanDigunakan,
                    'status' => 'available',
                    'description' => 'Digunakan untuk membayar PO #' . $purchaseOrder->po_number,
                    'user_id' => Auth::id(),
                ]);
                $catatanLog .= ' | Deposit digunakan: ' . number_format($depositAkanDigunakan);
            }

            if ($sisaDanaInput > 0.01) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => PurchaseOrderPayment::class,
                    'reference_id' => $payment->id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit',
                    'amount' => $sisaDanaInput,
                    'status' => 'available',
                    'description' => 'Kelebihan bayar dari PO #' . $purchaseOrder->po_number,
                    'user_id' => Auth::id(),
                ]);
                $catatanLog .= ' | Kelebihan bayar: ' . number_format($sisaDanaInput) . ' disimpan ke deposit.';
            }

            // Perbarui catatan jika ada tambahan info
            if ($depositAkanDigunakan > 0 || $sisaDanaInput > 0.01) {
                $payment->update(['notes' => $catatanLog]);
            }

            // --- Perbarui Status Pembayaran PO ---
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