<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\BatchPurchasePayment;
use App\Models\PurchaseOrderPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\SupplierLedger;

class BatchPurchasePaymentController extends Controller
{
    public function create(): View
    {
        $this->authorize('create-batch-purchase-payments');
        $suppliers = Supplier::orderBy('supplier_name')->get();
        return view('batch_purchase_payments.create', compact('suppliers'));
    }

    public function getUnpaidPurchaseOrdersApi(Supplier $supplier): JsonResponse
    {
        $purchaseOrders = $supplier->purchaseOrders()
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            // ✅ Eager load relasi yang diperlukan untuk accessor
            ->with(['deductingReturns', 'adjustments'])
            ->orderBy('due_date', 'asc')
            ->get();

        $posWithBalance = $purchaseOrders->map(function ($po) {
            // ✅ Gunakan accessor 'remaining_balance'
            $sisaTagihan = $po->remaining_balance;

            return [
                'po_id' => $po->po_id,
                'po_number' => $po->po_number,
                'due_date_formatted' => optional($po->due_date)->format('d M Y') ?? 'N/A',
                'sisa_tagihan' => $sisaTagihan,
            ];
        })->filter(fn($po) => $po['sisa_tagihan'] > 0.01);

        return response()->json($posWithBalance);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create-batch-purchase-payments');
        
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'payment_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'payment_method' => [
                'required_unless:total_amount,0',
                'nullable',
                'string',
            ],
            'notes' => 'nullable|string',
            'po_ids' => 'required|array|min:1',
            'po_ids.*' => 'required|exists:purchase_orders,po_id',
            'use_debit_balance' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $supplier = Supplier::findOrFail($validated['supplier_id']);
            $danaDariInput = (float)($validated['total_amount'] ?? 0);
            $pakaiDeposit = $validated['use_debit_balance'] ?? false;
            
            $depositAwalSupplier = $supplier->balance; // <-- Sudah benar (menggunakan accessor)

            $posDipilih = PurchaseOrder::whereIn('po_id', $validated['po_ids'])
                                // ✅ Eager load relasi
                                ->with(['deductingReturns', 'adjustments'])
                                ->orderBy('due_date', 'asc')
                                ->get();

            $totalTagihanTerpilih = $posDipilih->reduce(function ($carry, $po) {
                // ✅ Gunakan accessor
                $sisa = $po->remaining_balance;
                return $carry + $sisa;
            }, 0.0);

            if ($totalTagihanTerpilih <= 0.01) {
                 throw new \Exception("Tidak ada tagihan yang dipilih atau semua PO terpilih sudah lunas.");
            }

            // ... (Logika $depositAkanDigunakan, $danaInputAkanDigunakan, dll. Anda sudah benar) ...
            $depositAkanDigunakan = 0;
            if ($pakaiDeposit && $depositAwalSupplier > 0) {
                $depositAkanDigunakan = min($depositAwalSupplier, $totalTagihanTerpilih);
            }
            $sisaTagihanSetelahDeposit = max(0, $totalTagihanTerpilih - $depositAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahDeposit);
            $totalDanaAlokasi = $depositAkanDigunakan + $danaInputAkanDigunakan;
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            if ($totalDanaAlokasi <= 0.01 && $sisaDanaInput <= 0.01) {
                 if ($totalTagihanTerpilih > 0.01) {
                    throw new \Exception("Tidak ada dana (input/deposit) yang cukup untuk dialokasikan.");
                 }
            }

            // --- Mulai Proses Database ---

            // 1. Buat BatchPurchasePayment (Logika ini sudah benar)
            // ... (Logika $metodeBatch) ...
            $metodeBatch = '';
            if ($depositAkanDigunakan > 0) $metodeBatch .= 'Deposit Supplier';
            if ($danaInputAkanDigunakan > 0) {
                 if (!empty($metodeBatch)) $metodeBatch .= ' + ';
                 $metodeBatch .= $validated['payment_method'];
            }
            if (empty($metodeBatch)) $metodeBatch = 'N/A';

            $batchPayment = BatchPurchasePayment::create([
                'supplier_id' => $validated['supplier_id'],
                'processed_by_user_id' => Auth::id(),
                'payment_date' => $validated['payment_date'],
                'total_amount' => $totalDanaAlokasi,
                'payment_method' => $metodeBatch,
                'notes' => $validated['notes'],
            ]);

            // 2. Buat entri Ledger (Logika ini sudah benar)
            $alokasiLog = [];
            if ($depositAkanDigunakan > 0) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => BatchPurchasePayment::class,
                    'reference_id' => $batchPayment->batch_payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit',
                    'amount' => -$depositAkanDigunakan,
                    'status' => 'available',
                    'description' => 'Digunakan untuk Pembayaran Hutang Batch #' . $batchPayment->batch_payment_id,
                    'user_id' => Auth::id(),
                ]);
                $alokasiLog[] = "Menggunakan deposit Rp " . number_format($depositAkanDigunakan);
            }
            if ($danaInputAkanDigunakan > 0) $alokasiLog[] = "Menggunakan dana input Rp " . number_format($danaInputAkanDigunakan);


            // 3. Alokasikan dana ke PO
            $sisaDepositUntukAlokasi = $depositAkanDigunakan;
            $sisaInputUntukAlokasi = $danaInputAkanDigunakan;

            foreach ($posDipilih as $po) {
                if ($sisaDepositUntukAlokasi <= 0.01 && $sisaInputUntukAlokasi <= 0.01) break;

                // ✅ Gunakan accessor
                $sisaTagihanPO = $po->remaining_balance;

                if ($sisaTagihanPO <= 0.01) continue;

                $bayarDariDeposit = min($sisaTagihanPO, $sisaDepositUntukAlokasi);
                $sisaTagihanSetelahDepositPO = max(0, $sisaTagihanPO - $bayarDariDeposit);
                $bayarDariInput = min($sisaTagihanSetelahDepositPO, $sisaInputUntukAlokasi);
                $jumlahUntukPOIni = $bayarDariDeposit + $bayarDariInput;

                if ($jumlahUntukPOIni <= 0.01) continue;

                // ... (Logika $metodePayment Anda sudah benar) ...
                $metodePayment = '';
                if ($bayarDariDeposit > 0) $metodePayment .= 'Deposit Supplier';
                if ($bayarDariInput > 0) {
                     if (!empty($metodePayment)) $metodePayment .= ' + ';
                     $metodePayment .= $validated['payment_method'] ?? 'N/A';
                }

                $po->payments()->create([
                    'batch_purchase_payment_id' => $batchPayment->batch_payment_id,
                    'payment_date' => $validated['payment_date'],
                    'amount' => $jumlahUntukPOIni,
                    'payment_method' => $metodePayment,
                    'received_by_user_id' => Auth::id(),
                ]);

                // Update status PurchaseOrder
                $poCurrent = PurchaseOrder::find($po->po_id);
                $totalPaidBaru = ($poCurrent->amount_paid ?? 0) + $jumlahUntukPOIni;
                
                // ✅ Hitung sisa tagihan baru dengan benar
                $totalRetur = $poCurrent->deductingReturns()->sum('total_amount');
                $totalAdjustments = $poCurrent->adjustments()->sum(DB::raw("CASE WHEN type = 'debit_note' THEN amount ELSE -amount END"));
                $totalTagihanAktual = $poCurrent->total_amount + $totalAdjustments - $totalRetur;

                $sisaTagihanBaru = $totalTagihanAktual - $totalPaidBaru;

                $newStatus = ($sisaTagihanBaru <= 0.01) ? 'paid' : 'partially_paid';

                $poCurrent->update([
                    'amount_paid' => $totalPaidBaru,
                    'payment_status' => $newStatus,
                ]);

                // ✅ Lepaskan kredit pending (Logika ini sudah benar)
                if ($newStatus == 'paid') {
                    SupplierLedger::where('purchase_order_id', $poCurrent->po_id)
                                ->where('status', 'pending')
                                ->update([
                                    'status' => 'available',
                                    'description' => DB::raw("REPLACE(description, ' (Ditahan)', '')")
                                ]);
                }

                $sisaDepositUntukAlokasi -= $bayarDariDeposit;
                $sisaInputUntukAlokasi -= $bayarDariInput;
                $alokasiLog[] = "Rp " . number_format($jumlahUntukPOIni) . " dialokasikan ke " . $po->po_number;
            }

            // 4. Catat overpayment (Logika ini sudah benar)
            if ($sisaDanaInput > 0.01) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => BatchPurchasePayment::class,
                    'reference_id' => $batchPayment->batch_payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit',
                    'amount' => $sisaDanaInput,
                    'status' => 'available',
                    'description' => 'Kelebihan dana dari Pembayaran Hutang Batch #' . $batchPayment->batch_payment_id,
                    'user_id' => Auth::id(),
                ]);
                $alokasiLog[] = "Sisa dana input Rp " . number_format($sisaDanaInput) . " disimpan sebagai deposit supplier.";
            }

            DB::commit();
            $message = 'Pembayaran hutang berhasil. Detail: ' . implode('. ', $alokasiLog);
            return redirect()->route('purchase-orders.index')->with('success', $message); // Redirect ke daftar PO

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan pembayaran hutang: ' . $e->getMessage())->withInput();
        }
    }
}