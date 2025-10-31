<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\BatchPurchasePayment;
use App\Models\PurchaseOrderPayment;
use App\Models\SupplierLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class BatchPurchasePaymentController extends Controller
{
    /**
     * Tampilkan halaman form untuk membuat batch payment.
     */
    public function create(): View
    {
        $this->authorize('create-batch-purchase-payments');
        $suppliers = Supplier::orderBy('supplier_name')->get();
        return view('batch_purchase_payments.create', compact('suppliers'));
    }

    /**
     * [API] Ambil data PO yang belum lunas milik supplier.
     */
    public function getUnpaidPurchaseOrdersApi(Supplier $supplier): JsonResponse
    {
        $purchaseOrders = $supplier->purchaseOrders()
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->withSum('returns', 'total_amount')
            ->orderBy('due_date', 'asc')
            ->get();

        $posWithBalance = $purchaseOrders->map(function ($po) {
            $totalRetur = $po->returns_sum_total_amount ?? 0;
            $sisaTagihan = $po->total_amount - $po->amount_paid - $totalRetur;

            return [
                'po_id' => $po->po_id,
                'po_number' => $po->po_number,
                'due_date_formatted' => optional($po->due_date)->format('d M Y') ?? 'N/A',
                'sisa_tagihan' => max(0, $sisaTagihan),
            ];
        })->filter(fn($po) => $po['sisa_tagihan'] > 0.01);

        return response()->json($posWithBalance);
    }

    /**
     * Simpan batch payment dan alokasikan ke beberapa PO.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create-batch-purchase-payments');

        // ✅ Validasi tetap sama
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

            // ✅ Ganti ke accessor baru
            $depositAwalSupplier = $supplier->balance;

            // Ambil semua PO yang dipilih
            $posDipilih = PurchaseOrder::whereIn('po_id', $validated['po_ids'])
                ->withSum('returns', 'total_amount')
                ->orderBy('due_date', 'asc')
                ->get();

            $totalTagihanTerpilih = $posDipilih->reduce(function ($carry, $po) {
                $retur = $po->returns_sum_total_amount ?? 0;
                $sisa = max(0, $po->total_amount - $po->amount_paid - $retur);
                return $carry + $sisa;
            }, 0.0);

            if ($totalTagihanTerpilih <= 0.01) {
                throw new \Exception("Semua PO terpilih sudah lunas atau tidak valid.");
            }

            // ✅ Hitung alokasi dana
            $depositAkanDigunakan = ($pakaiDeposit && $depositAwalSupplier > 0)
                ? min($depositAwalSupplier, $totalTagihanTerpilih)
                : 0;

            $sisaTagihanSetelahDeposit = max(0, $totalTagihanTerpilih - $depositAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahDeposit);
            $totalDanaAlokasi = $depositAkanDigunakan + $danaInputAkanDigunakan;
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            if ($totalDanaAlokasi <= 0.01) {
                throw new \Exception("Tidak ada dana yang dialokasikan (deposit atau input).");
            }

            // ✅ Buat record BatchPurchasePayment
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

            $alokasiLog = [];

            // ✅ 1. Catat penggunaan DEPOSIT via SupplierLedger
            if ($depositAkanDigunakan > 0) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => BatchPurchasePayment::class,
                    'reference_id' => $batchPayment->batch_payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit',
                    'amount' => -$depositAkanDigunakan,
                    'description' => 'Digunakan untuk Batch Payment #' . $batchPayment->batch_payment_id,
                    'user_id' => Auth::id(),
                ]);
                $alokasiLog[] = "Menggunakan deposit Rp " . number_format($depositAkanDigunakan);
            }

            // ✅ 2. Alokasi ke setiap PO
            $sisaDepositUntukAlokasi = $depositAkanDigunakan;
            $sisaInputUntukAlokasi = $danaInputAkanDigunakan;

            foreach ($posDipilih as $po) {
                if ($sisaDepositUntukAlokasi <= 0.01 && $sisaInputUntukAlokasi <= 0.01) break;

                $totalRetur = $po->returns_sum_total_amount ?? 0;
                $sisaTagihanPO = max(0, $po->total_amount - $po->amount_paid - $totalRetur);
                if ($sisaTagihanPO <= 0.01) continue;

                $bayarDariDeposit = min($sisaTagihanPO, $sisaDepositUntukAlokasi);
                $sisaTagihanSetelahDepositPO = max(0, $sisaTagihanPO - $bayarDariDeposit);
                $bayarDariInput = min($sisaTagihanSetelahDepositPO, $sisaInputUntukAlokasi);
                $jumlahUntukPOIni = $bayarDariDeposit + $bayarDariInput;
                if ($jumlahUntukPOIni <= 0.01) continue;

                $metodePayment = '';
                if ($bayarDariDeposit > 0) $metodePayment .= 'Deposit Supplier';
                if ($bayarDariInput > 0) {
                    if (!empty($metodePayment)) $metodePayment .= ' + ';
                    $metodePayment .= $validated['payment_method'] ?? 'N/A';
                }

                // Simpan payment PO
                $po->payments()->create([
                    'batch_purchase_payment_id' => $batchPayment->batch_payment_id,
                    'payment_date' => $validated['payment_date'],
                    'amount' => $jumlahUntukPOIni,
                    'payment_method' => $metodePayment,
                    'received_by_user_id' => Auth::id(),
                ]);

                // Update status PO
                $totalPaidBaru = $po->payments()->sum('amount');
                $sisaTagihanBaru = $po->total_amount - $totalPaidBaru - $po->total_returned;
                $statusBaru = ($sisaTagihanBaru <= 0.01) ? 'paid' : 'partially_paid';
                $po->update(['amount_paid' => $totalPaidBaru, 'payment_status' => $statusBaru]);

                $sisaDepositUntukAlokasi -= $bayarDariDeposit;
                $sisaInputUntukAlokasi -= $bayarDariInput;
                $alokasiLog[] = "Rp " . number_format($jumlahUntukPOIni) . " dialokasikan ke PO " . $po->po_number;
            }

            // ✅ 3. Catat overpayment (sisa dana input) via SupplierLedger
            if ($sisaDanaInput > 0.01) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->supplier_id,
                    'reference_type' => BatchPurchasePayment::class,
                    'reference_id' => $batchPayment->batch_payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit',
                    'amount' => $sisaDanaInput,
                    'description' => 'Kelebihan dana dari Batch Payment #' . $batchPayment->batch_payment_id,
                    'user_id' => Auth::id(),
                ]);
                $alokasiLog[] = "Sisa dana input Rp " . number_format($sisaDanaInput) . " disimpan sebagai deposit supplier.";
            }

            DB::commit();
            return redirect()->route('purchase-orders.index')
                ->with('success', 'Batch payment berhasil. Detail: ' . implode('. ', $alokasiLog));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan pembayaran hutang: ' . $e->getMessage())->withInput();
        }
    }
}
