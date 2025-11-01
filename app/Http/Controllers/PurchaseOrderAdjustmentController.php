<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseOrderAdjustmentController extends Controller
{
    public function __construct()
    {
        // (Kita akan tambahkan permission nanti di Tahap 8)
        // $this->middleware('permission:create-purchase-adjustments')
        //      ->only(['create', 'store']);
    }

    /**
     * Menampilkan form untuk membuat penyesuaian baru.
     */
    public function create(): View
    {
        $purchaseOrders = PurchaseOrder::where('status', '!=', 'cancelled')
            ->orderBy('order_date', 'desc')
            ->get();
            
        return view('purchase_order_adjustments.create', compact('purchaseOrders'));
    }

    /**
     * Menyimpan penyesuaian baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,po_id',
            'adjustment_date' => 'required|date',
            'type' => 'required|in:credit_note,debit_note',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $purchaseOrder = PurchaseOrder::findOrFail($validated['purchase_order_id']);
            $amount = (float) $validated['amount'];

            PurchaseOrderAdjustment::create([
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'adjustment_date' => $validated['adjustment_date'],
                'type' => $validated['type'],
                'amount' => $amount,
                'reason' => $validated['reason'],
            ]);

            // Tidak ada logika SupplierLedger.
            // Sisa utang akan otomatis dihitung ulang oleh accessor.
            
            $typeString = ($validated['type'] == 'credit_note') ? 'Kredit' : 'Debit';

            return redirect()->route('purchase-orders.show', $purchaseOrder->po_id)
                         ->with('success', "Penyesuaian (Nota $typeString) berhasil disimpan.");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyimpan penyesuaian: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Membatalkan penyesuaian.
     */
    public function destroy(PurchaseOrderAdjustment $purchaseOrderAdjustment)
    {
        // $this->authorize('delete', $purchaseOrderAdjustment);

        try {
            $po_id = $purchaseOrderAdjustment->purchase_order_id;
            $purchaseOrderAdjustment->delete();

            return redirect()->route('purchase-orders.show', $po_id)
                         ->with('success', 'Penyesuaian PO berhasil dibatalkan.');
                         
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membatalkan penyesuaian: ' . $e->getMessage());
        }
    }
}