<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class ClientOrderReviewController extends Controller
{   
    public function __construct()
    {
        $this->middleware('can:approve-client-orders');
    }
    
    public function index(Request $request): View
    {
        $this->authorize('review-client-orders');
        $view = $request->input('view', 'pending');
        $query = Order::with(['client'])->where('order_source', 'client');

        if ($view === 'pending') {
            $query->where('status', 'pending_review');
        } else {
            $query->where('status', '!=', 'pending_review');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($subQ) use ($search) {
                      $subQ->where('client_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date_filter')) {
            $yearMonth = $request->date_filter;
            try {
                $date = Carbon::createFromFormat('Y-m', $yearMonth);
                $query->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
            } catch (\Exception $e) {
            }
        }

        if ($view === 'history' && $request->filled('status_filter') && in_array($request->status_filter, ['approved', 'rejected', 'invoiced'])) {
            $query->where('status', $request->status_filter);
        }

        $sort = $request->get('sort', 'terbaru');
        if ($sort === 'terlama') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $clientOrders = $query->paginate(15)->appends($request->query());

        $uniqueDatesQuery = Order::where('order_source', 'client');
        if ($view === 'pending') {
            $uniqueDatesQuery->where('status', 'pending_review');
        } else {
            $uniqueDatesQuery->where('status', '!=', 'pending_review');
        }

        $uniqueDates = $uniqueDatesQuery
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"))
            ->distinct()
            ->orderBy('ym', 'desc')
            ->get()
            ->mapWithKeys(fn($item) => [
                $item->ym => Carbon::createFromFormat('Y-m', $item->ym)->isoFormat('MMMM YYYY')
            ]);

        return view('admin.client_order_reviews.index', compact('clientOrders', 'view', 'uniqueDates'));
    }

    public function show(Order $order): View|RedirectResponse
    {
        $this->authorize('review-client-orders');

        if ($order->order_source !== 'client' || $order->status !== 'pending_review') {
            return redirect()->route('admin.client-order-reviews.index')
                ->with('error', 'Pesanan ini tidak valid atau sudah diproses.');
        }

        $order->load(['client', 'items.product']);

        return view('admin.client_order_reviews.show', compact('order'));
    }

    public function approve(Order $order): RedirectResponse
    {
        $this->authorize('approve-client-orders');

        if ($order->order_source !== 'client' || $order->status !== 'pending_review') {
            return back()->with('error', 'Pesanan ini tidak dapat disetujui.');
        }

        try {
            DB::beginTransaction();

            $order->load('items');
            
            // 1. Validasi Stok & Pengurangan Stok (CRITICAL FIX)
            foreach ($order->items as $item) {
                // Lock produk untuk mencegah race condition
                $product = Product::lockForUpdate()->find($item->product_id);
                
                if (!$product) {
                    throw new \Exception("Produk '{$item->product_id}' tidak ditemukan.");
                }
                
                if (!$product->is_active) {
                    throw new \Exception("Produk '{$product->product_name}' sedang tidak aktif.");
                }

                // Cek ketersediaan stok
                if ($product->stock_quantity < $item->quantity) {
                    throw new \Exception("Stok produk '{$product->product_name}' tidak mencukupi. Sisa: {$product->stock_quantity}, Diminta: {$item->quantity}.");
                }

                // Kurangi stok karena invoice akan dibuat status 'unpaid' (Confirmed)
                $product->decrement('stock_quantity', $item->quantity);
            }

            // 2. Pembuatan Invoice
            $subtotalInvoice = 0;
            $invoiceItemsToSave = [];
            
            // Re-fetch items with products to get snapshot data if needed (e.g. HPP)
            foreach ($order->items as $orderItem) {
                $product = $orderItem->product; // Already loaded/cached
                $price = $orderItem->price_per_unit;
                $quantity = $orderItem->quantity;
                $itemSubtotal = $quantity * $price;
                $subtotalInvoice += $itemSubtotal;

                $invoiceItemsToSave[] = [
                    'product_id' => $orderItem->product_id,
                    'quantity' => $quantity,
                    'price_per_unit' => $price,
                    'subtotal' => $itemSubtotal,
                    'hpp' => $product->average_cost ?? 0, // Snapshot HPP
                ];
            }

            // Asumsi diskon/pajak 0 dulu untuk order dari klien (bisa diedit di invoice nanti)
            $discountPercentage = 0;
            $discountAmount = 0;
            $subtotalAfterDiscount = $subtotalInvoice;
            $totalTaxAmount = 0;
            $totalAmountInvoice = $subtotalAfterDiscount + $totalTaxAmount;

            $invoice = SalesInvoice::create([
                'client_id' => $order->client_id,
                'invoice_number' => SalesInvoice::generateInvoiceNumber(null, $order->order_source),
                'order_date' => $order->order_date,
                'due_date' => $order->order_date->addDays(30), // Default term
                'subtotal' => $subtotalInvoice,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmountInvoice,
                'status' => 'unpaid', // Status Unpaid = Stok sudah dibooking/dikirim
                'user_id_sales' => null,
                'amount_paid' => 0,
                'notes' => $order->notes
                    ? "Berdasarkan Pesanan Klien #{$order->order_number}:\n{$order->notes}"
                    : "Berdasarkan Pesanan Klien #{$order->order_number}",
            ]);

            $invoice->items()->createMany($invoiceItemsToSave);

            // 3. Update Order Status
            $order->update([
                'status' => 'invoiced',
                'invoice_id' => $invoice->invoice_id,
            ]);

            DB::commit();

            return redirect()->route('admin.invoices.show', $invoice->invoice_id)
                ->with('success', "Pesanan {$order->order_number} berhasil disetujui. Invoice #{$invoice->invoice_number} telah dibuat dan stok dikurangi.");
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyetujui pesanan: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('reject-client-orders');

        if ($order->order_source !== 'client' || $order->status !== 'pending_review') {
            return back()->with('error', 'Pesanan ini tidak dapat ditolak.');
        }

        $validated = $request->validate(['rejection_notes' => 'nullable|string|max:500']);

        try {
            DB::beginTransaction();

            $order->status = 'rejected';
            
            if ($request->filled('rejection_notes')) {
                $order->notes = ($order->notes ? $order->notes . "\n\n" : '') .
                    "Alasan Penolakan: " . $validated['rejection_notes'];
            }

            $order->save();
            
            // Karena status masih pending_review, stok BELUM berkurang.
            // Jadi saat reject, TIDAK PERLU mengembalikan stok.
            // Cukup ubah status saja.

            DB::commit();

            return redirect()->route('admin.client-order-reviews.index')
                ->with('success', "Pesanan {$order->order_number} berhasil ditolak.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menolak pesanan: ' . $e->getMessage());
        }
    }
}