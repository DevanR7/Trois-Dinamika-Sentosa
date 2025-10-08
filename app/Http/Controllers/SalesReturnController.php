<?php

namespace App\Http\Controllers;

use App\Models\SalesReturn;
use App\Models\SalesInvoice;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\InvoiceItem;

class SalesReturnController extends Controller
{
     public function index(Request $request): View
    {
        $query = SalesReturn::with(['client', 'salesInvoice']);

        // Logika untuk Pencarian Umum
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function($q_client) use ($search) {
                      $q_client->where('client_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('salesInvoice', function($q_invoice) use ($search) {
                      $q_invoice->where('invoice_number', 'like', "%{$search}%");
                  });
            });
        }

        // Logika untuk Filter Tanggal
        if ($request->filled('return_date')) {
            $query->whereDate('return_date', $request->return_date);
        }

        $salesReturns = $query->latest('return_date')->paginate(15)->appends($request->query());
            
        return view('sales_returns.index', compact('salesReturns'));
    }

    public function create(): View
    {
        $invoices = SalesInvoice::whereNotIn('status', ['draft', 'cancelled'])
            ->orderBy('order_date', 'desc')
            ->get();
            
        return view('sales_returns.create', compact('invoices'));
    }

    /**
     * Menyimpan data retur penjualan baru.
     */
    public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'sales_invoice_id' => 'required|exists:sales_invoices,invoice_id',
        'return_date' => 'required|date',
        'notes' => 'nullable|string',
        'items' => 'required|array',
        'items.*.item_id' => 'required|exists:invoice_items,item_id', // Menggunakan item_id asli
        'items.*.quantity' => 'nullable|integer|min:1',
    ]);

    DB::beginTransaction();
    try {
        $invoice = SalesInvoice::findOrFail($validated['sales_invoice_id']);
        $discountRate = $invoice->discount_percentage / 100; // Ambil rate diskon global dari invoice
        $totalReturnValue = 0;
        $hasReturnedItems = false;

        $salesReturn = SalesReturn::create([
            'return_number' => SalesReturn::generateReturnNumber(),
            'client_id' => $invoice->client_id,
            'sales_invoice_id' => $invoice->invoice_id,
            'user_id' => Auth::id(),
            'return_date' => $validated['return_date'],
            'notes' => $validated['notes'],
            'total_amount' => 0,
        ]);

        foreach ($validated['items'] as $itemData) {
            if (!empty($itemData['quantity']) && $itemData['quantity'] > 0) {
                $hasReturnedItems = true;
                $originalItem = InvoiceItem::find($itemData['item_id']);

                // Validasi agar tidak bisa retur lebih dari sisa
                $maxQty = $originalItem->quantity - $originalItem->quantity_returned;
                if ($itemData['quantity'] > $maxQty) {
                    throw new \Exception("Jumlah retur melebihi batas untuk produk " . $originalItem->product->product_name);
                }

                // [LOGIKA BARU] Hitung harga satuan setelah diskon
                $priceAfterDiscount = $originalItem->price_per_unit * (1 - $discountRate);
                $subtotal = $itemData['quantity'] * $priceAfterDiscount;
                $totalReturnValue += $subtotal;

                // Simpan item retur dengan harga setelah diskon
                $salesReturn->items()->create([
                    'product_id' => $originalItem->product_id,
                    'quantity' => $itemData['quantity'],
                    'price_per_unit' => $priceAfterDiscount, // Simpan harga net
                    'subtotal' => $subtotal,
                ]);

                // Update catatan retur di item invoice asli
                $originalItem->increment('quantity_returned', $itemData['quantity']);

                // Tambah kembali stok produk
                $product = Product::find($originalItem->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $itemData['quantity']);
                }
            }
        }

        if (!$hasReturnedItems) throw new \Exception("Tidak ada item yang dipilih untuk diretur.");

        $salesReturn->update(['total_amount' => $totalReturnValue]);

        DB::commit();
        return redirect()->route('sales-returns.index')->with('success', 'Retur penjualan berhasil disimpan.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal menyimpan retur: ' . $e->getMessage())->withInput();
    }

}

    public function show(SalesReturn $salesReturn): View
{
    // Load semua relasi yang dibutuhkan oleh view
    $salesReturn->load(['client', 'salesInvoice', 'user', 'items.product.unit']);
    
    return view('sales_returns.show', compact('salesReturn'));
}

public function destroy(SalesReturn $salesReturn): RedirectResponse
{
    DB::beginTransaction();
    try {
        foreach ($salesReturn->items as $item) {
            // Kurangi kembali stok produk
            $product = Product::find($item->product_id);
            if ($product) {
                $product->decrement('stock_quantity', $item->quantity);
            }

            // Kurangi kembali jumlah yang sudah diretur di invoice_items
            $originalItem = InvoiceItem::where('invoice_id', $salesReturn->sales_invoice_id)
                                       ->where('product_id', $item->product_id)
                                       ->first();
            if ($originalItem) {
                $originalItem->decrement('quantity_returned', $item->quantity);
            }
        }
        $salesReturn->delete();
        DB::commit();
        return redirect()->route('sales-returns.index')->with('success', 'Retur penjualan berhasil dibatalkan.');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal membatalkan retur: ' . $e->getMessage());
    }

}
}