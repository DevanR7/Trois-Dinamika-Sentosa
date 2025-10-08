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
    public function index(): View
    {
        $salesReturns = SalesReturn::with(['client', 'salesInvoice'])
            ->latest('return_date')
            ->paginate(15);
            
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
        'items.*.product_id' => 'required|exists:products,product_id',
        'items.*.quantity' => 'nullable|integer|min:1', // Boleh kosong, tapi jika diisi harus minimal 1
        'items.*.price_per_unit' => 'required|numeric',
    ]);

    DB::beginTransaction();
    try {
        $invoice = SalesInvoice::findOrFail($validated['sales_invoice_id']);
        $totalReturnValue = 0;
        $hasReturnedItems = false; // Penanda apakah ada item yang diretur

        // 1. Buat data utama retur
        $salesReturn = SalesReturn::create([
            'return_number' => 'SR/' . date('Y/m/') . time(), // Ganti dengan generator nomor Anda nanti
            'client_id' => $invoice->client_id,
            'sales_invoice_id' => $invoice->invoice_id,
            'user_id' => Auth::id(),
            'return_date' => $validated['return_date'],
            'notes' => $validated['notes'],
            'total_amount' => 0, // Akan diupdate nanti
        ]);

        // 2. Loop melalui item yang dikirim dari form
        foreach ($validated['items'] as $itemData) {
            // Hanya proses jika kuantitas diisi dan lebih dari 0
            if (!empty($itemData['quantity']) && $itemData['quantity'] > 0) {
                $originalItem = InvoiceItem::find($itemData['item_id']);
                $maxQty = $originalItem->quantity - $originalItem->quantity_returned;
                if ($itemData['quantity'] > $maxQty) {
                    throw new \Exception("Jumlah retur untuk produk '{$originalItem->product->product_name}' melebihi batas.");
                }
                $hasReturnedItems = true; // Set penanda menjadi true
                $subtotal = $itemData['quantity'] * $itemData['price_per_unit'];
                $totalReturnValue += $subtotal;

                // Simpan item retur
                $salesReturn->items()->create([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'price_per_unit' => $itemData['price_per_unit'],
                    'subtotal' => $subtotal,
                ]);

                // Tambah kembali kuantitas yang diretur pada item asli
                $originalItem->increment('quantity_returned', $itemData['quantity']);

                // Tambah kembali stok produk
                $product = Product::find($itemData['product_id']);
                if ($product) {
                    $product->increment('stock_quantity', $itemData['quantity']);
                }
            }
        }

        // Jika tidak ada item yang diretur sama sekali, batalkan proses
        if (!$hasReturnedItems) {
            throw new \Exception("Tidak ada item yang dipilih untuk diretur. Harap isi kuantitas retur minimal pada satu barang.");
        }
        
        // 3. Update total nilai retur
        $salesReturn->update(['total_amount' => $totalReturnValue]);

        // 4. (Opsional) Sesuaikan sisa tagihan pada invoice asli
        $invoice->amount_paid -= $totalReturnValue; 
        
        // Cek ulang status invoice
        if ($invoice->amount_paid >= $invoice->total_amount) {
            $invoice->status = 'paid';
        } elseif ($invoice->amount_paid > 0) {
            $invoice->status = 'partially_paid';
        } else {
             $invoice->status = 'unpaid';
        }
        $invoice->save();

        DB::commit();

        return redirect()->route('sales-returns.index')->with('success', 'Retur penjualan berhasil disimpan dan stok telah diperbarui.');

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
        // 1. Ambil invoice asli
        $invoice = $salesReturn->salesInvoice;

        // 2. Loop melalui item retur untuk mengembalikan stok
        foreach ($salesReturn->items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                // Kurangi kembali stok produk
                $product->decrement('stock_quantity', $item->quantity);
            }

            // [BARU] Kurangi kembali jumlah yang sudah diretur di invoice_items
            $originalItem = InvoiceItem::where('invoice_id', $salesReturn->sales_invoice_id)
                                       ->where('product_id', $item->product_id)
                                       ->first();
            if ($originalItem) {
                $originalItem->decrement('quantity_returned', $item->quantity);
            }
        }

        // 3. (Opsional) Kembalikan penyesuaian pada invoice asli
        if ($invoice) {
            $invoice->amount_paid += $salesReturn->total_amount; // Tambah lagi jumlah terbayar
            // Cek ulang status
            if ($invoice->amount_paid >= $invoice->total_amount) {
                $invoice->status = 'paid';
            } elseif ($invoice->amount_paid > 0) {
                $invoice->status = 'partially_paid';
            } else {
                $invoice->status = 'unpaid';
            }
            $invoice->save();
        }

        // 4. Hapus data retur
        $salesReturn->delete();

        DB::commit();

        return redirect()->route('sales-returns.index')->with('success', 'Retur penjualan berhasil dibatalkan dan stok telah disesuaikan kembali.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal membatalkan retur: ' . $e->getMessage());
    }
}
}