<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Tax;
use App\Models\PaymentMethod;
use App\Models\CompanyBankAccount;
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
       
    }
    
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalesInvoice::class);
        $query = SalesInvoice::with(['client', 'sales', 'returns']);

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
                      ->select('sales_invoices.*'); 
                break;
            case 'klien_za':
                $query->join('clients', 'sales_invoices.client_id', '=', 'clients.client_id')
                      ->orderBy('clients.client_name', 'desc')
                      ->select('sales_invoices.*'); 
                break;
            default: // 'terbaru'
                $query->orderBy('order_date', 'desc')->orderBy('invoice_id', 'desc'); 
                break;
        }

        $invoices = $query->paginate(15)->appends($request->query());

        return view('invoices.index', ['invoices' => $invoices]);
    } 

    public function show(SalesInvoice $invoice): View
    {
        $this->authorize('view', $invoice);
        $invoice->load(['client', 'sales', 'payments.receivedBy', 'payments.paymentMethod', 'items.product' => function ($query) { // ✅ 'payments.paymentMethod'
            $query->withTrashed();
        }, 'taxes', 'adjustments', 'returns']); // ✅ 'adjustments' & 'returns'

        // ✅ TAMBAHKAN INI
        $paymentMethods = PaymentMethod::where('is_active', true)
                            ->whereIn('type', ['direct', 'pending'])
                            ->orderBy('name')
                            ->get();

       $companyBankAccounts = CompanyBankAccount::where('is_active', true)->orderBy('bank_name')->get();

       return view('invoices.show', compact('invoice', 'paymentMethods', 'companyBankAccounts'));
    }

    public function create(): View
    {
        $this->authorize('create', SalesInvoice::class);
        $clients = Client::all();
        $products = Product::all();
        $taxes = Tax::where('is_active', true)->get();
        $salesUsers = User::role('sales')->get();

        return view('invoices.create', compact('clients', 'products', 'taxes', 'salesUsers'));
    }
    
    public function createFromOrder(Order $order): View
    {
        $this->authorize('create', SalesInvoice::class);
        
        // ✅ BERUBAH: Menggunakan variabel $order
        $order->load('items.product'); 
        
        $clients = Client::all();
        $products = Product::all();
        $taxes = Tax::where('is_active', true)->get();
        $salesUsers = User::role('sales')->get();
        
        // ✅ BERUBAH: Mengirim $order (bukan $salesOrder) ke view
        return view('invoices.create', compact('clients', 'products', 'order', 'taxes', 'salesUsers'));
    }

    /**
     * Menyimpan invoice baru dengan multi-pajak.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SalesInvoice::class);
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'order_date' => 'required|date',
            'due_date' => 'required|date',
            'sales_order_id' => 'nullable|exists:orders,order_id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|integer|min:1',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'taxes' => 'nullable|array',
            'taxes.*' => 'exists:taxes,id',
            'notes' => 'nullable|string',
            'user_id_sales' => 'nullable|exists:users,user_id',
        ]);

        $itemsToDecrementStock = [];

        try {
            DB::beginTransaction();

            $originOrder = $request->filled('sales_order_id') ? Order::find($request->sales_order_id) : null;

            // 1. Hitung Subtotal dan siapkan item
            $subtotal = 0;
            $productsToSave = [];
            foreach ($validated['products'] as $productData) {
                // Kunci produk untuk memastikan data stok & HPP akurat
                $product = Product::find($productData['product_id']); 
                if (!$product) {
                    throw new \Exception("Produk dengan ID {$productData['product_id']} tidak ditemukan.");
                }

                $quantity = $productData['quantity'];
                
                // Cek Stok:
                $isFromClientOrder = $originOrder && $originOrder->order_source === 'client';

                if (!$isFromClientOrder) {
                    /*
                    if ($product->stock_quantity < $quantity) {
                        throw new \Exception("Stok untuk produk '{$product->product_name}' tidak mencukupi. Sisa stok: {$product->stock_quantity}.");
                    }
                    */
                    $itemsToDecrementStock[] = [
                        'product_id' => $product->product_id,
                        'quantity' => $quantity
                    ];
                }

                // Ambil harga jual (berdasarkan purchase_price)
                $price = $product->selling_price ?? 0;
                $itemSubtotal = $quantity * $price;
                $subtotal += $itemSubtotal;

                // =============================================
                // ✅ LOGIKA BARU: Simpan HPP saat penjualan
                // =============================================
                $hppSaatIni = $product->average_cost ?? 0;

                $productsToSave[] = [
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'price_per_unit' => $price, // Ini harga jual per unit
                    'hpp' => $hppSaatIni,       // Ini HPP (modal) per unit
                    'subtotal' => $itemSubtotal,
                ];
            }

            // 2. Hitung Diskon Global
            $discountPercentage = $request->input('discount_percentage', 0);
            $discountAmount = $subtotal * ($discountPercentage / 100);
            $subtotalAfterDiscount = $subtotal - $discountAmount;

            // 3. Hitung Total Pajak
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

            // 5. Logika Penentuan Nomor Invoice
            $salesUserId = $request->input('user_id_sales');
            $orderSource = 'sales'; 
            if ($originOrder) {
                $orderSource = $originOrder->order_source;
                if (empty($salesUserId) && $originOrder->user_id_sales) {
                    $salesUserId = $originOrder->user_id_sales;
                }
            }

            // 6. Simpan data utama ke tabel sales_invoices
            $invoice = SalesInvoice::create([
                'client_id' => $validated['client_id'],
                'invoice_number' => SalesInvoice::generateInvoiceNumber($salesUserId, $orderSource),
                'order_date' => $validated['order_date'],
                'due_date' => $validated['due_date'],
                'subtotal' => $subtotal,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'status' => 'draft', // ✅ PERUBAHAN UTAMA DI SINI
                'user_id_sales' => $salesUserId,
                'amount_paid' => 0,
                'notes' => $request->input('notes'),
            ]);
            // 7. Lampirkan Pajak dan Item (yang sekarang sudah berisi HPP)
            $invoice->taxes()->attach($taxesToAttach);
            $invoice->items()->createMany($productsToSave);

            // 8. Update status Order jika dibuat dari order
            if ($originOrder) {
                $originOrder->status = 'invoiced';
                $originOrder->invoice_id = $invoice->invoice_id;
                $originOrder->save();
            }

            // 9. Kurangi Stok (HANYA jika perlu)
            /*foreach ($itemsToDecrementStock as $item) {
                // Kita tidak perlu lock lagi karena sudah di-lock di atas
                Product::where('product_id', $item['product_id'])->decrement('stock_quantity', $item['quantity']);
            }*/

            DB::commit();

            return redirect()->route('invoices.show', $invoice->invoice_id)->with('success', 'Invoice berhasil dibuat!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat invoice: ' . $e->getMessage())->withInput();
        }
    }

    public function confirm(SalesInvoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice); // Gunakan permission 'update' yang ada

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Hanya invoice DRAFT yang bisa dikonfirmasi.');
        }

        try {
            DB::beginTransaction();
            
            // 1. Kunci dan Cek Stok (LOGIKA PINDAHAN DARI STORE)
            $itemsToDecrement = [];
            foreach ($invoice->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if (!$product) {
                    throw new \Exception("Produk '{$item->product_name}' tidak ditemukan lagi.");
                }

                // Ini adalah pengecekan stok yang sebenarnya, terjadi saat konfirmasi
                /*
                // Ini adalah pengecekan stok yang sebenarnya, terjadi saat konfirmasi
                if ($product->stock_quantity < $item->quantity) {
                    throw new \Exception("Stok untuk produk '{$product->product_name}' tidak mencukupi. Sisa stok: {$product->stock_quantity}.");
                }
                */
                
                $itemsToDecrement[] = [
                    'product_id' => $product->product_id,
                    'quantity' => $item->quantity
                ];
            }

            // 2. Jika semua stok aman, kurangi stok
            foreach ($itemsToDecrement as $item) {
                Product::where('product_id', $item['product_id'])->decrement('stock_quantity', $item['quantity']);
            }

            // 3. Ubah status invoice
            $invoice->update(['status' => 'unpaid']);

            DB::commit();
            return redirect()->route('invoices.show', $invoice->invoice_id)->with('success', 'Invoice berhasil dikonfirmasi. Stok telah dikurangi.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal konfirmasi invoice: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan form untuk mengedit invoice.
     */
    public function edit(SalesInvoice $invoice): View
    {
        $this->authorize('update', $invoice);
        $invoice->load(['items.product', 'taxes']);
        $clients = Client::all();
        $products = Product::all();
        $taxes = Tax::where('is_active', true)->get();
        $salesUsers = User::role('sales')->get();

        return view('invoices.edit', compact('invoice', 'clients', 'products', 'taxes', 'salesUsers'));
    }

    /**
     * Mengupdate invoice di database.
     */
    public function update(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);
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

        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return back()->with('error', 'Invoice yang sudah lunas atau dibatalkan tidak bisa di-edit.');
        }

        try {
            DB::beginTransaction();

            // 2. Kembalikan stok barang
            foreach ($invoice->items as $oldItem) {
                $product = Product::find($oldItem->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $oldItem->quantity);
                }
            }

            // 3. Hitung ulang semua
            $subtotal = 0;
            $productsToSave = [];
            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']);
                
                // ✅ Sesuai permintaan Anda: Tetap menggunakan purchase_price
                $price = $product->selling_price ?? 0;
                $quantity = $productData['quantity'];
                $itemSubtotal = $quantity * $price;
                $subtotal += $itemSubtotal;

                $productsToSave[] = [
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'price_per_unit' => $price,
                    'subtotal' => $itemSubtotal,
                ];
                
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
                'amount_paid' => 0, 
                'status' => 'unpaid',
            ]);

            // 5. Hapus item dan pembayaran lama, lalu buat ulang item baru
            $invoice->items()->delete();
            $invoice->payments()->delete(); 
            $invoice->items()->createMany($productsToSave);

            // 6. Sinkronkan data pajak
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
        $this->authorize('delete', $invoice); 
        $invoice->delete();
        
        return redirect()->route('invoices.index')->with('success', 'Invoice berhasil dihapus.');
    }

    public function cancel(SalesInvoice $invoice): RedirectResponse
    {
        $this->authorize('cancel', $invoice);

        // ✅ PERBAIKAN: Cek status 'paid' ATAU 'partially_paid'
        if (in_array($invoice->status, ['paid', 'partially_paid'])) {
             return back()->with('error', 'Invoice yang sudah lunas atau dicicil tidak bisa dibatalkan.');
        }
        
        // Alternatif (lebih aman): Cek langsung ke 'amount_paid'
        // if ($invoice->amount_paid > 0) {
        //     return back()->with('error', 'Invoice yang sudah memiliki pembayaran (meski cicil) tidak bisa dibatalkan.');
        // }

        $invoice->status = 'cancelled';
        $invoice->save();

        return redirect()->route('invoices.index')->with('success', 'Invoice berhasil dibatalkan.');
    }

    public function downloadPDF(SalesInvoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load(['client', 'items.product.unit', 'taxes', 'sales']);

        $paperSize = [0, 0, 684, 396];
        $pdf = Pdf::loadView('invoices.pdf_template', compact('invoice'));
        $pdf->setPaper($paperSize);
        $cleanInvoiceNumber = str_replace('/', '-', $invoice->invoice_number);
        $fileName = 'Invoice-' . $cleanInvoiceNumber . '.pdf';

        return $pdf->download($fileName);
    }
}