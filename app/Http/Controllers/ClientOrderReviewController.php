<?php

namespace App\Http\Controllers; // Sesuaikan namespace jika Anda menaruhnya di subfolder Admin

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
     * Menampilkan daftar pesanan klien yang menunggu review.
     */
    public function index(Request $request): View
    {
        // $this->authorize('review-client-orders');

        // --- 1. Get View Filter ---
        $view = $request->input('view', 'pending'); // 'pending' atau 'history'

        $query = Order::with(['client'])
                    ->where('order_source', 'client'); // Selalu filter order dari klien

        // --- 2. Apply View Filter ---
        if ($view === 'pending') {
            $query->where('status', 'pending_review');
        } else { // 'history'
            // Tampilkan semua yang sudah diproses (selain pending_review)
            $query->where('status', '!=', 'pending_review'); 
        }

        // --- 3. Apply Other Filters ---
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($subQ) use ($search) {
                        $subQ->where('client_name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter Tanggal (berdasarkan created_at)
        if ($request->filled('date_filter')) {
            $yearMonth = $request->date_filter;
            try {
                $date = Carbon::createFromFormat('Y-m', $yearMonth);
                $query->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
            } catch (\Exception $e) { /* Abaikan format tanggal salah */ }
        }

        // Filter berdasarkan status akhir (hanya untuk view history)
        if ($view === 'history' && $request->filled('status_filter') && in_array($request->status_filter, ['approved', 'rejected', 'invoiced'])) {
            $query->where('status', $request->status_filter);
        }

        // --- 4. Apply Sorting ---
        $sort = $request->get('sort', 'terbaru');
        switch ($sort) {
            case 'terlama':
                $query->orderBy('created_at', 'asc');
                break;
            case 'terbaru':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // --- 5. Get Data ---
        $clientOrders = $query->paginate(15)->appends($request->query());

        // --- 6. Get Data untuk Dropdown Filter Tanggal ---
        // Ambil data tanggal unik berdasarkan view yang aktif
        $uniqueDatesQuery = Order::where('order_source', 'client');
        if ($view === 'pending') {
            $uniqueDatesQuery->where('status', 'pending_review');
        } else {
            $uniqueDatesQuery->where('status', '!=', 'pending_review');
        }
        $uniqueDates = $uniqueDatesQuery->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"))
            ->distinct()->orderBy('ym', 'desc')->get()->mapWithKeys(function ($item) {
                return [$item->ym => Carbon::createFromFormat('Y-m', $item->ym)->isoFormat('MMMM YYYY')];
            });


        return view('client_order_reviews.index', compact(
            'clientOrders', // Ganti nama variabel
            'view',
            'uniqueDates'
        ));
    }

    /**
     * Menampilkan detail pesanan klien untuk direview.
     */
    public function show(Order $order): View|RedirectResponse
    {
         // $this->authorize('review-client-orders');

        if ($order->order_source !== 'client' || $order->status !== 'pending_review') {
             return redirect()->route('client-order-reviews.index') // Pastikan nama route benar
                ->with('error', 'Pesanan ini tidak valid atau sudah diproses.');
        }

        $order->load(['client', 'items.product']);

        return view('client_order_reviews.show', compact('order'));
    }

    /**
     * Menyetujui pesanan klien & Otomatis membuat Invoice.
     */
    public function approve(Order $order): RedirectResponse
    {
        // $this->authorize('approve-client-orders');

         if ($order->order_source !== 'client' || $order->status !== 'pending_review') {
             return back()->with('error', 'Pesanan ini tidak dapat disetujui.');
        }

         try {
            DB::beginTransaction();

            // 1. Validasi Stok Ulang (Opsional, tapi bagus)
            //    Hanya cek apakah stok tidak menjadi negatif (karena sudah dikurangi)
            $order->load('items.product');
            foreach ($order->items as $item) {
                $product = $item->product()->lockForUpdate()->first(); // Lock produk
                if (!$product) {
                    throw new \Exception("Produk '{$item->product_id}' tidak ditemukan lagi.");
                }
                if ($product->stock_quantity < 0) { 
                     throw new \Exception("Stok produk '{$product->product_name}' bermasalah (negatif) setelah pesanan ini.");
                }
            }

            // 2. Siapkan data item untuk Invoice
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

            // 3. Hitung Diskon & Pajak (Default: 0)
            $discountPercentage = 0;
            $discountAmount = 0;
            $subtotalAfterDiscount = $subtotalInvoice;
            $totalTaxAmount = 0;
            $taxesToAttach = [];
            // (Logika pajak default bisa ditambahkan di sini jika perlu)
            
            // 4. Hitung Total Akhir Invoice
            $totalAmountInvoice = $subtotalAfterDiscount + $totalTaxAmount;

            // 5. Buat Invoice Baru
            $invoice = SalesInvoice::create([
                'client_id' => $order->client_id,
                'invoice_number' => SalesInvoice::generateInvoiceNumber(null, $order->order_source), // Dibuat otomatis
                'order_date' => $order->order_date, // Pakai tanggal order klien
                'due_date' => $order->order_date->addDays(30), // Asumsi 30 hari
                'subtotal' => $subtotalInvoice,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmountInvoice,
                'status' => 'unpaid',
                'user_id_sales' => null, // Dibuat oleh sistem/admin
                'amount_paid' => 0,
                'notes' => $order->notes ? "Berdasarkan Pesanan Klien #" . $order->order_number . ":\n" . $order->notes : "Berdasarkan Pesanan Klien #" . $order->order_number,
            ]);

            // 6. Lampirkan Pajak (jika ada) dan Item ke Invoice
            if (!empty($taxesToAttach)) {
                $invoice->taxes()->attach($taxesToAttach);
            }
            $invoice->items()->createMany($invoiceItemsToSave);

            // 7. Update status order menjadi 'invoiced'
            $order->status = 'invoiced';
            $order->invoice_id = $invoice->invoice_id;
            $order->save();

            // 8. ❌ STOK TIDAK PERLU DIKURANGI LAGI
            // (karena sudah dikurangi di ClientOnlineOrderController@store)

            DB::commit();

            // Redirect ke halaman detail INVOICE admin
            return redirect()->route('invoices.show', $invoice->invoice_id)
                 ->with('success', "Pesanan {$order->order_number} berhasil disetujui dan Invoice #{$invoice->invoice_number} telah dibuat.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyetujui pesanan: ' . $e->getMessage());
        }
    }

    /**
     * Menolak pesanan klien.
     */
    public function reject(Request $request, Order $order): RedirectResponse
    {
        // $this->authorize('reject-client-orders');

        if ($order->order_source !== 'client' || $order->status !== 'pending_review') {
             return back()->with('error', 'Pesanan ini tidak dapat ditolak.');
        }

        $validated = $request->validate(['rejection_notes' => 'nullable|string|max:500']);

         try {
            DB::beginTransaction();

            // 1. Update status order menjadi 'rejected'
            $order->status = 'rejected';
             if ($request->filled('rejection_notes')) {
                $order->notes = ($order->notes ? $order->notes . "\n\n" : '') . "Alasan Penolakan: " . $validated['rejection_notes'];
            }
            $order->save();

            // 2. ✅ KEMBALIKAN STOK PRODUK
            //    (Karena stok dikurangi saat klien memesan)
            $order->loadMissing('items'); // Pastikan items di-load
            foreach ($order->items as $item) {
                $product = Product::where('product_id', $item->product_id)->lockForUpdate()->first();
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                }
            }
            // ===============================================

            DB::commit();

             return redirect()->route('client-order-reviews.index') // Pastikan nama route benar
                 ->with('success', "Pesanan {$order->order_number} berhasil ditolak dan stok telah dikembalikan.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menolak pesanan: ' . $e->getMessage());
        }
    }
}