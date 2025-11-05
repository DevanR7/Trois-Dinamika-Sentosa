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
use App\Models\ClientLedger;
use Illuminate\Support\Facades\Log;

class SalesReturnController extends Controller
{
     public function index(Request $request): View
    {
        // [AUTH] Panggil policy untuk memeriksa permission 'view-sales-returns'
        $this->authorize('viewAny', SalesReturn::class);

        $query = SalesReturn::with(['client', 'salesInvoice']);

        // Logika untuk Pencarian Umum (No. Retur / Klien / No. Invoice)
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
        // [AUTH] Panggil policy untuk memeriksa permission 'create-sales-returns'
        $this->authorize('create', SalesReturn::class);

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
        $this->authorize('create', SalesReturn::class);

        $validated = $request->validate([
            'sales_invoice_id' => 'required|exists:sales_invoices,invoice_id',
            'return_date' => 'required|date',
            'return_handling_type' => 'required|in:deduct_invoice,store_as_credit',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:invoice_items,item_id',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $invoice = SalesInvoice::with('client')->findOrFail($validated['sales_invoice_id']);
            $discountRate = $invoice->discount_percentage / 100;
            $totalReturnValue = 0;
            $hasReturnedItems = false;
            $handlingType = $validated['return_handling_type'];

            // Hitung total nilai retur DULU
            foreach ($validated['items'] as $itemData) {
                if (!empty($itemData['quantity']) && $itemData['quantity'] > 0) {
                    $hasReturnedItems = true;
                    $originalItem = InvoiceItem::find($itemData['item_id']);

                    $maxQty = $originalItem->quantity - $originalItem->quantity_returned;
                    if ($itemData['quantity'] > $maxQty) {
                        throw new \Exception("Jumlah retur melebihi batas untuk produk " . $originalItem->product->product_name);
                    }

                    $priceAfterDiscount = $originalItem->price_per_unit * (1 - $discountRate);
                    $subtotal = $itemData['quantity'] * $priceAfterDiscount;
                    $totalReturnValue += $subtotal;
                }
            }

            if (!$hasReturnedItems) throw new \Exception("Tidak ada item yang dipilih untuk diretur.");
            
            // Logika koreksi tipe handling (Sudah benar)
            $sisaTagihanInvoice = $invoice->remaining_balance; 
            if ($handlingType == 'deduct_invoice' && $totalReturnValue > $sisaTagihanInvoice) {
                $handlingType = 'store_as_credit';
            }
            
            $salesReturn = SalesReturn::create([
                'return_number' => SalesReturn::generateReturnNumber(),
                'client_id' => $invoice->client_id,
                'sales_invoice_id' => $invoice->invoice_id,
                'user_id' => Auth::id(),
                'return_date' => $validated['return_date'],
                'return_handling_type' => $handlingType,
                'notes' => $validated['notes'],
                'total_amount' => $totalReturnValue,
            ]);

            // Loop kedua untuk simpan item, update stok, dan qty_returned
            foreach ($validated['items'] as $itemData) {
                if (!empty($itemData['quantity']) && $itemData['quantity'] > 0) {
                    $originalItem = InvoiceItem::find($itemData['item_id']);
                    
                    $priceAfterDiscount = $originalItem->price_per_unit * (1 - $discountRate);
                    $subtotal = $itemData['quantity'] * $priceAfterDiscount;

                    $salesReturn->items()->create([
                        'product_id' => $originalItem->product_id,
                        'quantity' => $itemData['quantity'],
                        'price_per_unit' => $priceAfterDiscount,
                        'subtotal' => $subtotal,
                    ]);

                    $originalItem->increment('quantity_returned', $itemData['quantity']);

                    $product = Product::find($originalItem->product_id);
                    if ($product) {
                        $product->increment('stock_quantity', $itemData['quantity']);
                    }
                }
            }

            // Logika aksi berdasarkan tipe handling (Sudah benar)
            if ($handlingType == 'store_as_credit') {
                $ledgerStatus = ($invoice->status == 'paid') ? 'available' : 'pending';
                ClientLedger::create([
                    'client_id' => $invoice->client_id,
                    'sales_invoice_id' => $invoice->invoice_id,
                    'reference_type' => SalesReturn::class,
                    'reference_id' => $salesReturn->return_id,
                    'transaction_date' => $validated['return_date'],
                    'type' => 'credit',
                    'amount' => $totalReturnValue,
                    'status' => $ledgerStatus,
                    'description' => 'Kredit dari Retur Penjualan #' . $salesReturn->return_number . ($ledgerStatus == 'pending' ? ' (Ditahan)' : ''),
                    'user_id' => Auth::id(),
                ]);
            }
            
            // ======================================================
            // ✅ TAMBAHKAN PEMANGGILAN UPDATE STATUS DI SINI
            // ======================================================
            // Panggil fungsi ini SETELAH semua logika retur selesai
            $invoice->updatePaymentStatus();
            // ======================================================

            DB::commit();
            return redirect()->route('sales-returns.index')->with('success', 'Retur penjualan berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan retur: ' . $e->getMessage())->withInput();
        }
    }

    public function show(SalesReturn $salesReturn): View
{
    // [AUTH] Panggil policy untuk memeriksa permission 'view-sales-returns'
    $this->authorize('view', $salesReturn);

    // Load semua relasi yang dibutuhkan oleh view
    $salesReturn->load(['client', 'salesInvoice', 'user', 'items.product.unit']);
    
    return view('sales_returns.show', compact('salesReturn'));
}

    public function destroy(SalesReturn $salesReturn): RedirectResponse
    {
        $this->authorize('delete', $salesReturn);
        
        DB::beginTransaction();
        try {
            
            // Ambil invoice ID SEBELUM $salesReturn dihapus
            $invoice_id = $salesReturn->sales_invoice_id;

            if ($salesReturn->return_handling_type == 'store_as_credit') {
                ClientLedger::where('reference_type', SalesReturn::class)
                            ->where('reference_id', $salesReturn->return_id)
                            ->delete();
            } else {
                // Logika cek status Anda sudah benar
            }

            // Kembalikan stok dan kuantitas
            foreach ($salesReturn->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->decrement('stock_quantity', $item->quantity);
                }
                $originalItem = InvoiceItem::where('invoice_id', $salesReturn->sales_invoice_id)
                                        ->where('product_id', $item->product_id)
                                        ->first();
                if ($originalItem) {
                    $originalItem->decrement('quantity_returned', $item->quantity);
                }
            }
            
            $salesReturn->delete(); // Hapus retur
            
            // ======================================================
            // ✅ TAMBAHKAN PEMANGGILAN UPDATE STATUS DI SINI
            // ======================================================
            $invoice = SalesInvoice::find($invoice_id);
            if ($invoice) {
                $invoice->updatePaymentStatus();
            }
            // ======================================================
            
            DB::commit();
            return redirect()->route('sales-returns.index')->with('success', 'Retur penjualan berhasil dibatalkan.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan retur: ' . $e->getMessage());
        }
    }
}