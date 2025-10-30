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

class BatchPurchasePaymentController extends Controller
{
    /**
     * Tampilkan halaman form untuk membuat batch payment.
     */
    public function create(): View
    {
        $this->authorize('create-batch-purchase-payments'); // Proteksi dengan permission
        $suppliers = Supplier::orderBy('supplier_name')->get();
        return view('batch_purchase_payments.create', compact('suppliers'));
    }

    /**
     * [API] Ambil data PO yang belum lunas milik supplier.
     */
    public function getUnpaidPurchaseOrdersApi(Supplier $supplier): JsonResponse
    {
        // Pastikan user boleh melihat ini (atau tambahkan middleware)
        // $this->authorize('create-batch-purchase-payments');

        $purchaseOrders = $supplier->purchaseOrders()
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->withSum('returns', 'total_amount') // Hitung total retur
            ->orderBy('due_date', 'asc') // Urutkan dari paling lama
            ->get();

        // Hitung sisa tagihan untuk setiap PO
        $posWithBalance = $purchaseOrders->map(function ($po) {
            $totalRetur = $po->returns_sum_total_amount ?? 0;
            $sisaTagihan = $po->total_amount - $po->amount_paid - $totalRetur;

            return [
                'po_id' => $po->po_id,
                'po_number' => $po->po_number,
                'due_date_formatted' => optional($po->due_date)->format('d M Y') ?? 'N/A',
                'sisa_tagihan' => max(0, $sisaTagihan), // Pastikan tidak negatif
            ];
        })->filter(fn($po) => $po['sisa_tagihan'] > 0.01); // Filter yg sisa 0

        return response()->json($posWithBalance);
    }

    /**
     * Simpan batch payment dan alokasikan ke PO.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create-batch-purchase-payments');
        
        // Validasi
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'payment_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0', // Boleh 0
            'payment_method' => [
                'required_unless:total_amount,0', // Wajib diisi KECUALI total_amount adalah 0
                'nullable',
                'string',
            ],
            'notes' => 'nullable|string',
            'po_ids' => 'required|array|min:1',
            'po_ids.*' => 'required|exists:purchase_orders,po_id',
            'use_debit_balance' => 'nullable|boolean', // Untuk deposit
        ]);

        DB::beginTransaction();
        try {
            $supplier = Supplier::findOrFail($validated['supplier_id']);
            $danaDariInput = (float)($validated['total_amount'] ?? 0);
            $pakaiDeposit = $validated['use_debit_balance'] ?? false;
            $depositAwalSupplier = (float)($supplier->debit_balance ?? 0);

            // Ambil PO terpilih untuk hitung total tagihan
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
                 throw new \Exception("Tidak ada tagihan yang dipilih atau semua PO terpilih sudah lunas.");
            }

            // Tentukan berapa deposit yang AKAN digunakan
            $depositAkanDigunakan = 0;
            if ($pakaiDeposit && $depositAwalSupplier > 0) {
                $depositAkanDigunakan = min($depositAwalSupplier, $totalTagihanTerpilih);
            }

            // Tentukan berapa dana input yang AKAN digunakan
            $sisaTagihanSetelahDeposit = max(0, $totalTagihanTerpilih - $depositAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahDeposit);

            // Hitung total dana yang akan dialokasikan
            $totalDanaAlokasi = $depositAkanDigunakan + $danaInputAkanDigunakan;

            if ($totalDanaAlokasi <= 0.01) {
                 throw new \Exception("Tidak ada dana (input/deposit) yang cukup untuk dialokasikan.");
            }

            // Hitung sisa dana input (overpayment) YANG TIDAK TERPAKAI
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            // --- Mulai Proses Database ---

            // 1. Kurangi deposit supplier (jika dipakai)
            if ($depositAkanDigunakan > 0) {
                $supplier->decrement('debit_balance', $depositAkanDigunakan);
            }

            // 2. Buat record BatchPurchasePayment
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

            // 3. Alokasikan dana ke PO
            $sisaDepositUntukAlokasi = $depositAkanDigunakan;
            $sisaInputUntukAlokasi = $danaInputAkanDigunakan;
            $alokasiLog = [];
            if ($depositAkanDigunakan > 0) $alokasiLog[] = "Menggunakan deposit Rp " . number_format($depositAkanDigunakan);
            if ($danaInputAkanDigunakan > 0) $alokasiLog[] = "Menggunakan dana input Rp " . number_format($danaInputAkanDigunakan);


            foreach ($posDipilih as $po) {
                if ($sisaDepositUntukAlokasi <= 0.01 && $sisaInputUntukAlokasi <= 0.01) break;

                $totalRetur = $po->returns_sum_total_amount ?? $po->returns->sum('total_amount');
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

                // Buat record PurchaseOrderPayment
                $po->payments()->create([
                    'batch_purchase_payment_id' => $batchPayment->batch_payment_id,
                    'payment_date' => $validated['payment_date'],
                    'amount' => $jumlahUntukPOIni,
                    'payment_method' => $metodePayment,
                    'received_by_user_id' => Auth::id(), // User yg memproses
                ]);

                // Update status PurchaseOrder
                $poCurrent = PurchaseOrder::find($po->po_id); // Ambil data terbaru
                $totalPaidBaru = ($poCurrent->amount_paid ?? 0) + $jumlahUntukPOIni;
                $sisaTagihanBaru = $poCurrent->total_amount - ($poCurrent->total_returned ?? 0) - $totalPaidBaru;

                $poCurrent->update([
                    'amount_paid' => $totalPaidBaru,
                    'payment_status' => ($sisaTagihanBaru <= 0.01) ? 'paid' : 'partially_paid',
                ]);

                $sisaDepositUntukAlokasi -= $bayarDariDeposit;
                $sisaInputUntukAlokasi -= $bayarDariInput;
                $alokasiLog[] = "Rp " . number_format($jumlahUntukPOIni) . " dialokasikan ke " . $po->po_number;
            }

            // 4. Tambahkan sisa dana INPUT (jika ada) kembali ke deposit supplier
            if ($sisaDanaInput > 0.01) {
                $supplier->increment('debit_balance', $sisaDanaInput);
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