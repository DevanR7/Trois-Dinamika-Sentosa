<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\InvoiceItem;
use App\Models\SalesOrder;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;

class SalesInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-invoices');
    }
    
    public function index(Request $request): View
{
    $query = SalesInvoice::with('client', 'sales');

     // Filter pencarian
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('invoice_number', 'like', "%{$search}%")
              ->orWhereHas('client', function($q) use ($search) {
                  $q->where('client_name', 'like', "%{$search}%");
              });
        });
    }

    // Filter tanggal (menggunakan order_date)
    if ($request->filled('start_date')) {
        $query->whereDate('order_date', '>=', $request->start_date);
    }
    if ($request->filled('end_date')) {
        $query->whereDate('order_date', '<=', $request->end_date);
    }

    // Filter status
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    $sort = $request->get('sort', 'terbaru');
    switch ($sort) {
        case 'terlama':
            $query->orderBy('order_date', 'asc');
            break;
        case 'klien_az':
            $query->join('clients', 'sales_invoices.client_id', '=', 'clients.client_id')
                  ->orderBy('clients.client_name', 'asc')
                  ->select('sales_invoices.*'); // [FIX 1] Menghindari 'ambiguous column'
            break;
        case 'klien_za':
            $query->join('clients', 'sales_invoices.client_id', '=', 'clients.client_id')
                  ->orderBy('clients.client_name', 'desc')
                  ->select('sales_invoices.*'); // [FIX 1] Menghindari 'ambiguous column'
            break;
        default: // 'terbaru'
            $query->orderBy('order_date', 'desc')->orderBy('invoice_id', 'desc'); // Ditambah order by ID untuk konsistensi
            break;
    }

    // [FIX 2] Hapus ->latest() agar tidak menimpa logika sorting di atas
    $invoices = $query->paginate(15)->appends($request->query());

    return view('invoices.index', ['invoices' => $invoices]);
} 
    public function show(SalesInvoice $invoice): View
{
    // Laravel sudah otomatis melakukan findOrFail($id) untuk Anda.
    // Kita hanya perlu me-load relasi yang dibutuhkan.
    $invoice->load(['client', 'sales', 'payments.receivedBy', 'items.product' => function ($query) {
        $query->withTrashed();
    }, 'taxes']);

    return view('invoices.show', compact('invoice'));
}

    public function create(): View
    {
        $clients = Client::all();
        $products = Product::all();
        $taxes = Tax::where('is_active', true)->get();
        $salesUsers = User::where('role', 'sales')->get(); 

    return view('invoices.create', compact('clients', 'products', 'taxes', 'salesUsers'));
    }
    
    public function createFromOrder(SalesOrder $salesOrder): View
    {
        $salesOrder->load('items.product');
        $clients = Client::all();
        $products = Product::all();
        $taxes = Tax::where('is_active', true)->get();
        $salesUsers = User::where('role', 'sales')->get();
        return view('invoices.create', compact('clients', 'products', 'salesOrder', 'taxes', 'salesUsers'));
    }

    /**
     * Menyimpan invoice baru dengan multi-pajak.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'order_date' => 'required|date',
            'due_date' => 'required|date', 
            'sales_order_id' => 'nullable|exists:sales_orders,order_id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|integer|min:1',
            'discount_percentage' => 'nullable|numeric|min:0|max:100', 
            'taxes' => 'nullable|array',
            'taxes.*' => 'exists:taxes,id',
            'notes' => 'nullable|string',
            'user_id_sales' => 'nullable|exists:users,user_id',
        ]);

        // --- LOGIKA BARU: PENGECEKAN STOK ---
    /*foreach ($validated['products'] as $productData) {
        $product = Product::find($productData['product_id']);
        if ($product->stock_quantity < $productData['quantity']) {
            // Jika stok tidak cukup, kembalikan dengan pesan error
            return back()
                ->with('error', "Stok untuk produk '{$product->product_name}' tidak mencukupi. Sisa stok: {$product->stock_quantity}.")
                ->withInput();
        }
    }*/

        try {
        DB::beginTransaction();
        
        // 1. Hitung Subtotal dari Produk berdasarkan HARGA BELI
        $subtotal = 0;
        $productsToSave = [];
        foreach ($validated['products'] as $productData) {
            $product = Product::find($productData['product_id']);
            if (!$product) {
                // Handle jika produk tidak ditemukan
                throw new \Exception("Produk dengan ID {$productData['product_id']} tidak ditemukan.");
            }
            $price = $product->purchase_price ?? 0; // Menggunakan purchase_price
            $quantity = $productData['quantity'];
            $itemSubtotal = $quantity * $price;
            $subtotal += $itemSubtotal;

            // Simpan data untuk disimpan nanti
            $productsToSave[] = [
                'product_id' => $product->product_id,
                'quantity' => $quantity,
                'price_per_unit' => $price,
                'subtotal' => $itemSubtotal,
            ];

            // Kurangi stok
            $product->decrement('stock_quantity', $quantity);
        }

        // 2. Hitung Diskon Global
        $discountPercentage = $request->input('discount_percentage', 0);
        $discountAmount = $subtotal * ($discountPercentage / 100);

        // Subtotal setelah diskon
        $subtotalAfterDiscount = $subtotal - $discountAmount;

        // 3. Hitung Total Pajak dari subtotal setelah diskon
        $totalTaxAmount = 0;
        $taxesToAttach = [];
        if (!empty($validated['taxes'])) {
            $selectedTaxes = Tax::find($validated['taxes']);
            foreach ($selectedTaxes as $tax) {
                $taxAmountForItem = $subtotalAfterDiscount * ($tax->rate / 100);
                $totalTaxAmount += $taxAmountForItem;
                
                $taxesToAttach[$tax->id] = [
                    'name' => $tax->name,
                    'rate' => $tax->rate,
                    'amount' => $taxAmountForItem,
                ];
            }
        }
        
        // 4. Hitung Total Akhir
        $totalAmount = $subtotalAfterDiscount + $totalTaxAmount;

        // 5. Simpan data utama ke tabel sales_invoices
        $invoice = SalesInvoice::create([
            'client_id' => $validated['client_id'],
            'invoice_number' => SalesInvoice::generateInvoiceNumber($request->input('user_id_sales')),
            'order_date' => $validated['order_date'],
            'due_date' => $validated['due_date'],
            'subtotal' => $subtotal,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'status' => 'unpaid',
            'payment_status' => 'unpaid',
            'user_id_sales' => $request->input('user_id_sales', Auth::id()), 
            'amount_paid' => 0,
            'notes' => $request->input('notes'),
        ]);

        // 6. Lampirkan Pajak dan Item
        $invoice->taxes()->attach($taxesToAttach);
        $invoice->items()->createMany($productsToSave);

        if ($request->filled('sales_order_id')) {
            $salesOrder = SalesOrder::find($request->sales_order_id);
            if ($salesOrder) {
                $salesOrder->status = 'invoiced'; // Ubah status menjadi 'invoiced'
                $salesOrder->invoice_id = $invoice->invoice_id; // Simpan referensi ID invoice
                $salesOrder->save();
            }
        }
        
        DB::commit();

        return redirect()->route('invoices.show', $invoice->invoice_id)->with('success', 'Invoice berhasil dibuat!');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal membuat invoice: ' . $e->getMessage())->withInput();
    }
    }

    /**
     * Menampilkan form untuk mengedit invoice.
     */
    public function edit(SalesInvoice $invoice): View
    {
        $invoice->load(['items.product', 'taxes']);
        $clients = Client::all();
        $products = Product::all();
        $taxes = Tax::where('is_active', true)->get();
         $salesUsers = User::where('role', 'sales')->get();

        return view('invoices.edit', compact('invoice', 'clients', 'products', 'taxes', 'salesUsers'));
    }

    /**
     * Mengupdate invoice di database.
     */
    public function update(Request $request, SalesInvoice $invoice): RedirectResponse
{
    // 1. Validasi input (disamakan dengan method store)
    $validated = $request->validate([
        'client_id' => 'required|exists:clients,client_id',
        'order_date' => 'required|date',
        'due_date' => 'required|date',
        'products' => 'required|array|min:1',
        'products.*.product_id' => 'required|exists:products,product_id',
        'products.*.quantity' => 'required|integer|min:1',
        'discount_percentage' => 'nullable|numeric|min:0|max:100',
        'taxes' => 'nullable|array',
        'taxes.*' => 'exists:taxes,id',
        'notes' => 'nullable|string',
    ]);

    // Jangan izinkan edit jika invoice sudah lunas atau dibatalkan
    if (in_array($invoice->status, ['paid', 'cancelled'])) {
        return back()->with('error', 'Invoice yang sudah lunas atau dibatalkan tidak bisa di-edit.');
    }

    try {
        DB::beginTransaction();

        // 2. Kembalikan stok barang dari item-item lama sebelum dihapus
        foreach ($invoice->items as $oldItem) {
            $product = Product::find($oldItem->product_id);
            if ($product) {
                $product->increment('stock_quantity', $oldItem->quantity);
            }
        }

        // 3. Hitung ulang semua dari awal, sama seperti di method store
        $subtotal = 0;
        $productsToSave = [];
        foreach ($validated['products'] as $productData) {
            $product = Product::find($productData['product_id']);
            $price = $product->purchase_price ?? 0;
            $quantity = $productData['quantity'];
            $itemSubtotal = $quantity * $price;
            $subtotal += $itemSubtotal;

            $productsToSave[] = [
                'product_id' => $product->product_id,
                'quantity' => $quantity,
                'price_per_unit' => $price,
                'subtotal' => $itemSubtotal,
            ];
            
            // Kurangi stok dengan data yang baru
            $product->decrement('stock_quantity', $quantity);
        }

        $discountPercentage = $request->input('discount_percentage', 0);
        $discountAmount = $subtotal * ($discountPercentage / 100);
        $subtotalAfterDiscount = $subtotal - $discountAmount;

        $totalTaxAmount = 0;
        $taxesToSync = [];
        if (!empty($validated['taxes'])) {
            $selectedTaxes = Tax::find($validated['taxes']);
            foreach ($selectedTaxes as $tax) {
                $taxAmountForItem = $subtotalAfterDiscount * ($tax->rate / 100);
                $totalTaxAmount += $taxAmountForItem;
                $taxesToSync[$tax->id] = [
                    'name' => $tax->name,
                    'rate' => $tax->rate,
                    'amount' => $taxAmountForItem,
                ];
            }
        }
        
        $totalAmount = $subtotalAfterDiscount + $totalTaxAmount;

        // 4. Update data utama Invoice
        $invoice->update([
            'client_id' => $validated['client_id'],
            'order_date' => $validated['order_date'],
            'due_date' => $validated['due_date'],
            'subtotal' => $subtotal,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'notes' => $request->input('notes'),
            // Reset status pembayaran karena total tagihan berubah
            'amount_paid' => 0, 
            'status' => 'unpaid',
        ]);

        // 5. Hapus item dan pembayaran lama, lalu buat ulang item baru
        $invoice->items()->delete();
        $invoice->payments()->delete(); // Hapus riwayat pembayaran lama
        $invoice->items()->createMany($productsToSave);

        // 6. Sinkronkan data pajak di tabel pivot
        $invoice->taxes()->sync($taxesToSync);

        DB::commit();

        return redirect()->route('invoices.show', $invoice->invoice_id)->with('success', 'Invoice berhasil diupdate!');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal mengupdate invoice: ' . $e->getMessage())->withInput();
    }
    }

    /**
     * Menghapus invoice dari database (atau membatalkan).
     */
    public function destroy(SalesInvoice $invoice): RedirectResponse
    {     
        // Sebaiknya gunakan Soft Deletes atau ubah status menjadi 'cancelled'
        $invoice->delete();
        
        return redirect()->route('invoices.index')->with('success', 'Invoice berhasil dihapus.');
    }

    public function cancel(SalesInvoice $invoice): RedirectResponse
{
    // Logika bisnis: Invoice yang sudah lunas tidak boleh dibatalkan.
    if ($invoice->status == 'paid') {
        return back()->with('error', 'Invoice yang sudah lunas tidak bisa dibatalkan.');
    }

    $invoice->status = 'cancelled';
    $invoice->save();

    return redirect()->route('invoices.index')->with('success', 'Invoice berhasil dibatalkan.');
}

public function downloadPDF(SalesInvoice $invoice)
    {
        // Pastikan semua relasi yang dibutuhkan sudah ter-load
        $invoice->load(['client', 'items.product', 'taxes']);

        // Render view ke dalam PDF
        $pdf = Pdf::loadView('invoices.pdf_template', compact('invoice'));

        // Download file PDF dengan nama yang dinamis
        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }
}