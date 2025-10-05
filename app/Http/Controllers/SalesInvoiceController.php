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

    // Filter tanggal
    if ($request->filled('start_date')) {
        $query->whereDate('invoice_date', '>=', $request->start_date);
    }
    if ($request->filled('end_date')) {
        $query->whereDate('invoice_date', '<=', $request->end_date);
    }

    // Filter status
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    $sort = $request->get('sort', 'terbaru');
    switch ($sort) {
        case 'terlama':
            $query->orderBy('invoice_date', 'asc');
            break;
        case 'klien_az':
            $query->join('clients', 'sales_invoices.client_id', '=', 'clients.client_id')
                  ->orderBy('clients.client_name', 'asc');
            break;
        case 'klien_za':
            $query->join('clients', 'sales_invoices.client_id', '=', 'clients.client_id')
                  ->orderBy('clients.client_name', 'desc');
            break;
        default: // 'terbaru'
            $query->orderBy('invoice_date', 'desc');
            break;
    }

    $invoices = $query->latest('invoice_date')->paginate(15)->appends($request->query());

    return view('invoices.index', ['invoices' => $invoices]);
}    
    public function show($id): View
    {
        $invoice = SalesInvoice::with(['client', 'items.product' => function ($query) {
            $query->withTrashed();
        }])->findOrFail($id);
        return view('invoices.show', compact('invoice'));
    }

    public function create(): View
    {
        $clients = Client::all();
        $products = Product::all();
        $taxes = Tax::where('is_active', true)->get();
        return view('invoices.create', compact('clients', 'products','taxes'));
    }
    
    public function createFromOrder(SalesOrder $salesOrder): View
    {
        $salesOrder->load('items.product');
        $clients = Client::all();
        $products = Product::all();
        $taxes = Tax::where('is_active', true)->get();
        return view('invoices.create', compact('clients', 'products', 'salesOrder', 'taxes'));
    }

    /**
     * Menyimpan invoice baru dengan multi-pajak.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'due_date' => 'required|date', 
            'sales_order_id' => 'nullable|exists:sales_orders,order_id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
            'taxes' => 'nullable|array', // Validasi untuk pajak yang dipilih
            'taxes.*' => 'exists:taxes,id',
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
            
            // 1. Hitung Subtotal dari Produk
            $subtotal = 0;
            foreach ($validated['products'] as $productData) {
                $subtotal += $productData['quantity'] * $productData['price'];
            }

            // 2. Hitung Total Pajak dari checkbox yang dipilih
            $totalTaxAmount = 0;
            $taxesToAttach = [];
            if (!empty($validated['taxes'])) {
                $selectedTaxes = Tax::find($validated['taxes']);
                foreach ($selectedTaxes as $tax) {
                    $taxAmountForItem = $subtotal * ($tax->rate / 100);
                    $totalTaxAmount += $taxAmountForItem;
                    
                    // Siapkan data untuk disimpan ke tabel pivot 'invoice_tax'
                    $taxesToAttach[$tax->id] = [
                        'name' => $tax->name,
                        'rate' => $tax->rate,
                        'amount' => $taxAmountForItem,
                    ];
                }
            }
            
            $totalAmount = $subtotal + $totalTaxAmount;

            // 3. Simpan data utama ke tabel sales_invoices
            $invoice = new SalesInvoice();
            $invoice->client_id = $validated['client_id'];
            $invoice->invoice_number = 'INV-' . time();
            $invoice->invoice_date = now();
            $invoice->due_date = $validated['due_date'];
            $invoice->subtotal = $subtotal;
            $invoice->total_amount = $totalAmount; // total_amount baru (subtotal + semua pajak)
            $invoice->status = 'unpaid';
            $invoice->user_id_sales = Auth::id() ?? 1;
            $invoice->amount_paid = 0;
            $invoice->save();

            // 4. Lampirkan Pajak ke Invoice (Simpan ke tabel pivot 'invoice_tax')
            if (!empty($taxesToAttach)) {
                $invoice->taxes()->attach($taxesToAttach);
            }

            // 5. Simpan setiap item ke tabel invoice_items
            foreach ($validated['products'] as $productData) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->invoice_id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'price_per_unit' => $productData['price'],
                    'subtotal' => $productData['quantity'] * $productData['price'],
                ]);

                $product = Product::find($productData['product_id']);
        if ($product) {
            $product->stock_quantity -= $productData['quantity'];
            $product->save();
        }
            }

            // 6. Update status Sales Order jika ada
            if ($request->filled('sales_order_id')) {
                $salesOrder = SalesOrder::find($request->sales_order_id);
                if ($salesOrder) {
                    $salesOrder->status = 'invoiced';
                    $salesOrder->invoice_id = $invoice->invoice_id;
                    $salesOrder->save();
                }
            }

            DB::commit();

            return redirect()->route('invoices.index')->with('success', 'Invoice berhasil dibuat!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat invoice: ' . $e->getMessage());
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

        return view('invoices.edit', compact('invoice', 'clients', 'products', 'taxes'));
    }

    /**
     * Mengupdate invoice di database.
     */
    public function update(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'due_date' => 'required|date',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
            'taxes' => 'nullable|array',
            'taxes.*' => 'exists:taxes,id',
        ]);

        try {
            DB::beginTransaction();

            // 1. Hitung ulang Subtotal dari data form
            $subtotal = 0;
            foreach ($validated['products'] as $productData) {
                $subtotal += $productData['quantity'] * $productData['price'];
            }

            // 2. Hitung ulang Total Pajak dari data form
            $totalTaxAmount = 0;
            $taxesToSync = []; // Menggunakan sync untuk update pivot table
            if (!empty($validated['taxes'])) {
                $selectedTaxes = Tax::find($validated['taxes']);
                foreach ($selectedTaxes as $tax) {
                    $taxAmountForItem = $subtotal * ($tax->rate / 100);
                    $totalTaxAmount += $taxAmountForItem;
                    $taxesToSync[$tax->id] = [
                        'name' => $tax->name,
                        'rate' => $tax->rate,
                        'amount' => $taxAmountForItem,
                    ];
                }
            }
            
            $totalAmount = $subtotal + $totalTaxAmount;

            // 3. Update data utama Invoice
            $invoice->update([
                'client_id' => $validated['client_id'],
                'due_date' => $validated['due_date'],
                'subtotal' => $subtotal,
                'total_amount' => $totalAmount,
            ]);

            // 4. Hapus item lama, lalu buat ulang item baru
            $invoice->items()->delete();
            foreach ($validated['products'] as $productData) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->invoice_id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'price_per_unit' => $productData['price'],
                    'subtotal' => $productData['quantity'] * $productData['price'],
                ]);
            }

            // 5. Sinkronkan data pajak di tabel pivot
            $invoice->taxes()->sync($taxesToSync);

            DB::commit();

            return redirect()->route('invoices.index')->with('success', 'Invoice berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate invoice: ' . $e->getMessage());
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