<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\InvoiceItem;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ClientOrderReviewController extends Controller
{
    /**
     * === INDEX ===
     * Menampilkan daftar pesanan klien yang menunggu review oleh admin/sales.
     * Mendukung filter berdasarkan status, pencarian, tanggal, dan pengurutan.
     */
    public function index(Request $request): View
    {
        $this->authorize('review-client-orders');

        // 1. Tentukan jenis tampilan: pending atau history
        $view = $request->input('view', 'pending');

        // 2. Query utama: hanya pesanan dari klien
        $query = Order::with(['client'])->where('order_source', 'client');

        // 3. Filter utama berdasarkan view
        if ($view === 'pending') {
            $query->where('status', 'pending_review');
        } else {
            $query->where('status', '!=', 'pending_review');
        }

        // 4. Filter pencarian (nomor pesanan atau nama klien)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($subQ) use ($search) {
                      $subQ->where('client_name', 'like', "%{$search}%");
                  });
            });
        }

        // 5. Filter tanggal berdasarkan bulan dan tahun
        if ($request->filled('date_filter')) {
            $yearMonth = $request->date_filter;
            try {
                $date = Carbon::createFromFormat('Y-m', $yearMonth);
                $query->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
            } catch (\Exception $e) {
                // Abaikan jika format tanggal salah
            }
        }

        // 6. Filter tambahan untuk tampilan history
        if ($view === 'history' && $request->filled('status_filter') && in_array($request->status_filter, ['approved', 'rejected', 'invoiced'])) {
            $query->where('status', $request->status_filter);
        }

        // 7. Pengurutan hasil
        $sort = $request->get('sort', 'terbaru');
        if ($sort === 'terlama') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // 8. Ambil data hasil query dengan pagination
        $clientOrders = $query->paginate(15)->appends($request->query());

        // 9. Ambil daftar bulan unik untuk filter tanggal
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

        // 10. Tampilkan view daftar
        return view('client_order_reviews.index', compact('clientOrders', 'view', 'uniqueDates'));
    }

    /**
     * === SHOW ===
     * Menampilkan detail pesanan klien yang sedang menunggu review.
     */
    public function show(Order $order): View|RedirectResponse
    {
        $this->authorize('review-client-orders');

        // Pastikan hanya pesanan klien yang pending_review yang dapat ditampilkan
        if ($order->order_source !== 'client' || $order->status !== 'pending_review') {
            return redirect()->route('client-order-reviews.index')
                ->with('error', 'Pesanan ini tidak valid atau sudah diproses.');
        }

        // Muat data klien dan produk pesanan
        $order->load(['client', 'items.product']);

        return view('client_order_reviews.show', compact('order'));
    }

    /**
     * === APPROVE ===
     * Menyetujui pesanan klien dan otomatis membuat invoice.
     */
    public function approve(Order $order): RedirectResponse
    {
        $this->authorize('approve-client-orders');

        // Validasi status pesanan
        if ($order->order_source !== 'client' || $order->status !== 'pending_review') {
            return back()->with('error', 'Pesanan ini tidak dapat disetujui.');
        }

        try {
            DB::beginTransaction();

            // 1. Validasi stok produk (pastikan stok tidak negatif)
            $order->load('items.product');
            foreach ($order->items as $item) {
                $product = $item->product()->lockForUpdate()->first();
                if (!$product) {
                    throw new \Exception("Produk '{$item->product_id}' tidak ditemukan.");
                }
                if ($product->stock_quantity < 0) {
                    throw new \Exception("Stok produk '{$product->product_name}' tidak valid (negatif).");
                }
            }

            // 2. Hitung subtotal dan persiapkan item invoice
            $subtotalInvoice = 0;
            $invoiceItemsToSave = [];
            foreach ($order->items as $orderItem) {
                $price = $orderItem->price_per_unit;
                $quantity = $orderItem->quantity;
                $itemSubtotal = $quantity * $price;
                $subtotalInvoice += $itemSubtotal;

                $invoiceItemsToSave[] = [
                    'product_id' => $orderItem->product_id,
                    'quantity' => $quantity,
                    'price_per_unit' => $price,
                    'subtotal' => $itemSubtotal,
                ];
            }

            // 3. Hitung diskon dan pajak (opsional, default 0)
            $discountPercentage = 0;
            $discountAmount = 0;
            $subtotalAfterDiscount = $subtotalInvoice;
            $totalTaxAmount = 0;
            $taxesToAttach = [];

            // 4. Hitung total akhir invoice
            $totalAmountInvoice = $subtotalAfterDiscount + $totalTaxAmount;

            // 5. Buat invoice baru
            $invoice = SalesInvoice::create([
                'client_id' => $order->client_id,
                'invoice_number' => SalesInvoice::generateInvoiceNumber(null, $order->order_source),
                'order_date' => $order->order_date,
                'due_date' => $order->order_date->addDays(30),
                'subtotal' => $subtotalInvoice,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmountInvoice,
                'status' => 'unpaid',
                'user_id_sales' => null,
                'amount_paid' => 0,
                'notes' => $order->notes
                    ? "Berdasarkan Pesanan Klien #{$order->order_number}:\n{$order->notes}"
                    : "Berdasarkan Pesanan Klien #{$order->order_number}",
            ]);

            // 6. Tambahkan pajak dan item ke invoice
            if (!empty($taxesToAttach)) {
                $invoice->taxes()->attach($taxesToAttach);
            }
            $invoice->items()->createMany($invoiceItemsToSave);

            // 7. Update status order menjadi invoiced
            $order->update([
                'status' => 'invoiced',
                'invoice_id' => $invoice->invoice_id,
            ]);

            // 8. Stok tidak perlu diubah karena sudah dikurangi saat order dibuat

            DB::commit();

            return redirect()->route('invoices.show', $invoice->invoice_id)
                ->with('success', "Pesanan {$order->order_number} berhasil disetujui dan invoice #{$invoice->invoice_number} telah dibuat.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyetujui pesanan: ' . $e->getMessage());
        }
    }

    /**
     * === REJECT ===
     * Menolak pesanan klien dan mengembalikan stok produk.
     */
    public function reject(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('reject-client-orders');

        if ($order->order_source !== 'client' || $order->status !== 'pending_review') {
            return back()->with('error', 'Pesanan ini tidak dapat ditolak.');
        }

        $validated = $request->validate(['rejection_notes' => 'nullable|string|max:500']);

        try {
            DB::beginTransaction();

            // 1. Ubah status order menjadi rejected dan tambahkan alasan
            $order->status = 'rejected';
            if ($request->filled('rejection_notes')) {
                $order->notes = ($order->notes ? $order->notes . "\n\n" : '') .
                    "Alasan Penolakan: " . $validated['rejection_notes'];
            }
            $order->save();

            // 2. Kembalikan stok produk yang sudah dikurangi
            $order->loadMissing('items');
            foreach ($order->items as $item) {
                $product = Product::where('product_id', $item->product_id)->lockForUpdate()->first();
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                }
            }

            DB::commit();

            return redirect()->route('client-order-reviews.index')
                ->with('success', "Pesanan {$order->order_number} berhasil ditolak dan stok dikembalikan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menolak pesanan: ' . $e->getMessage());
        }
    }
}
