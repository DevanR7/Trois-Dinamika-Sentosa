<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderPaymentController extends Controller
{
     public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        // 1. Catat pembayaran baru
        $purchaseOrder->payments()->create([
            'payment_date' => $request->payment_date,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'notes' => $request->notes,
            'received_by_user_id' => Auth::id(),
        ]);

        // 2. Hitung ulang total yang sudah dibayar
        $totalPaid = $purchaseOrder->payments()->sum('amount');

        // 3. Update status pembayaran di Purchase Order
        $newStatus = 'unpaid';
        if ($totalPaid > 0) {
            $newStatus = ($totalPaid >= $purchaseOrder->total_amount) ? 'paid' : 'partially_paid';
        }

        $purchaseOrder->update([
            'amount_paid' => $totalPaid,
            'payment_status' => $newStatus
        ]);

        return back()->with('success', 'Pembayaran berhasil dicatat.');
    }
}
