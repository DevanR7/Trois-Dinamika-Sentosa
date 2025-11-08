<?php

    namespace App\Http\Controllers;

    use App\Models\PurchaseOrder;
    use App\Models\PurchaseOrderItem;
    use App\Models\Supplier;
    use App\Models\Product;
    use App\Models\User;
    use App\Models\PurchaseOrderItemDiscount;
    use App\Models\CompanyBankAccount;
    use App\Services\PurchaseOrderCalculator;
    use App\Models\PaymentMethod;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\View\View;
    use Illuminate\Http\RedirectResponse;
    use Barryvdh\DomPDF\Facade\Pdf;
    use Illuminate\Support\Facades\Gate;

    class PurchaseOrderController extends Controller
    {

        public function __construct()
        {
           
        }
        
        public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);
        $query = PurchaseOrder::with(['supplier', 'requester'])->latest('order_date');

        // [DIUBAH] Logika pencarian umum sekarang mencakup 3 kolom
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('po_number', 'like', "%{$search}%")
              ->orWhere('supplier_invoice_number', 'like', "%{$search}%") // Ditambahkan
              ->orWhereHas('supplier', function($q_supplier) use ($search) {
                  $q_supplier->where('supplier_name', 'like', "%{$search}%");
              });
        });
    }

        // LOGIKA BARU: Filter berdasarkan Tanggal Pesanan (spesifik)
        if ($request->filled('order_date')) {
            $query->whereDate('order_date', $request->order_date);
        }

        // LOGIKA BARU: Filter berdasarkan Tanggal Jatuh Tempo (spesifik)
        if ($request->filled('due_date')) {
            $query->whereDate('due_date', $request->due_date);
        }

        // Logika untuk filter status pembayaran (tetap sama)
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Logika untuk Pengurutan (tetap sama)
        $sort = $request->get('sort', 'terbaru');
        switch ($sort) {
            case 'terlama':
                $query->orderBy('order_date', 'asc');
                break;
            case 'supplier_az':
                $query->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.supplier_id')
                    ->orderBy('suppliers.supplier_name', 'asc')->select('purchase_orders.*');
                break;
            case 'supplier_za':
                $query->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.supplier_id')
                    ->orderBy('suppliers.supplier_name', 'desc')->select('purchase_orders.*');
                break;
            default:
                $query->orderBy('order_date', 'desc')->orderBy('po_id', 'desc');
            break;
        }

        // Ambil data dengan paginasi
        $purchaseOrders = $query->paginate(15)->appends($request->query());

        return view('purchase_orders.index', compact('purchaseOrders'));
    }

        public function create()
        {
            $this->authorize('create', PurchaseOrder::class);

            $suppliers = Supplier::all();
            $products = Product::all();
            $users = User::all();
            return view('purchase_orders.create', compact('suppliers', 'products','users'));
        }

        public function store(Request $request): RedirectResponse
        {
            $this->authorize('create', PurchaseOrder::class);
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,supplier_id',
                'order_date' => 'required|date',
                'due_date' => 'nullable|date|after_or_equal:order_date',
                'requester_user_id' => 'nullable|exists:users,user_id',
                'notes' => 'nullable|string',
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|exists:products,product_id',
                'products.*.quantity' => 'required|numeric|min:0.01',
                'products.*.price_per_unit' => 'required|numeric|min:0',
                'products.*.discounts' => 'nullable|array',
                'products.*.discounts.*' => 'nullable|numeric|min:0|max:100',
                'products.*.update_master_price' => 'nullable|boolean',
                'apply_disc_fee' => 'sometimes|boolean',
                'disc_fee_percent' => 'nullable|numeric|min:0|max:100',
                'disc_fee_amount' => 'nullable|numeric|min:0',
                'apply_rounding_discount' => 'sometimes|boolean',
                'rounding_discount_amount' => 'nullable|numeric|min:0',
                'use_custom_dpp_factor' => 'sometimes|boolean',
                'custom_dpp_factor' => 'nullable|numeric|min:0',
                'tax_id' => 'nullable|exists:taxes,id',
                'shipping_amount' => 'nullable|numeric|min:0',
            ]);

            DB::beginTransaction();
            try {
                // LANGKAH 1: Lakukan semua kalkulasi di backend sebagai sumber kebenaran
                $itemSubtotal = 0;
                foreach ($validated['products'] as $p) {
                    $finalPrice = floatval($p['price_per_unit']);
                    if (!empty($p['discounts']) && is_array($p['discounts'])) {
                        foreach ($p['discounts'] as $d) {
                            if (is_numeric($d)) $finalPrice *= (1 - (floatval($d) / 100));
                        }
                    }
                    $itemSubtotal += (floatval($p['quantity']) * $finalPrice);
                }

                // LANGKAH 2: Panggil service kalkulator dengan data yang sudah bersih
                $options = $request->all();
                $options['subtotal'] = $itemSubtotal;
                $calc = PurchaseOrderCalculator::calculate($options);

                // LANGKAH 3: Buat Purchase Order dengan SEMUA data yang sudah dihitung dalam satu perintah
                $po = PurchaseOrder::create([
                    'po_number'           => PurchaseOrder::generatePoNumber(), // Pastikan Anda punya fungsi ini di Model
                    'supplier_id'         => $validated['supplier_id'],
                    'order_date'          => $validated['order_date'],
                    'due_date'            => $request->input('due_date'),
                    'requester_user_id'   => $request->input('requester_user_id'),
                    'user_id_admin'       => Auth::id(),
                    'notes'               => $request->input('notes'),
                    'status'              => 'draft',
                    'payment_status'      => 'unpaid',
                    // Mengisi semua kolom kalkulasi dari hasil service kalkulator
                    'subtotal'                  => $calc['subtotal'],
                    'tax_id'                    => $request->input('tax_id'),
                    'apply_disc_fee'            => $request->boolean('apply_disc_fee'),
                    'disc_fee_percent'          => $request->input('disc_fee_percent'),
                    'disc_fee_amount'           => $calc['disc_fee_amount'],
                    'apply_rounding_discount'   => $request->boolean('apply_rounding_discount'),
                    'rounding_discount_amount'  => $calc['rounding_discount_amount'],
                    'use_custom_dpp_factor'     => $request->boolean('use_custom_dpp_factor'),
                    'custom_dpp_factor'         => $request->input('custom_dpp_factor'),
                    'shipping_amount'           => $calc['shipping_amount'],
                    'taxable_amount'            => $calc['taxable_base'],
                    'dpp'                       => $calc['dpp'],
                    'ppn'                       => $calc['ppn'],
                    'total_amount'              => $calc['grand_total'],
                    'grand_total'               => $calc['grand_total'], // Mengisi kedua kolom agar konsisten
                ]);

                // LANGKAH 4: Simpan item-item yang dibeli
                foreach ($validated['products'] as $p) {
                    $finalPrice = floatval($p['price_per_unit']);
                    if (!empty($p['discounts']) && is_array($p['discounts'])) {
                        foreach ($p['discounts'] as $d) {
                            if (is_numeric($d)) $finalPrice *= (1 - (floatval($d) / 100));
                        }
                    }
                    $subtotal_item = floatval($p['quantity']) * $finalPrice;

                    $item = $po->items()->create([
                        'product_id' => $p['product_id'],
                        'quantity' => $p['quantity'],
                        'price_per_unit' => $p['price_per_unit'],
                        'subtotal' => $subtotal_item,
                    ]);

                    if (!empty($p['discounts']) && is_array($p['discounts'])) {
                        foreach ($p['discounts'] as $d) {
                            if (is_numeric($d)) $item->discounts()->create(['percentage' => floatval($d)]);
                        }
                    }

                    if (isset($p['update_master_price']) && $p['update_master_price']) {
            $product = Product::find($p['product_id']);
            if ($product) {
                $product->update(['purchase_price' => $p['price_per_unit']]);
            }
        }
                }

                DB::commit();
                return redirect()->route('purchase-orders.show', $po->po_id)->with('success', 'Pesanan berhasil dibuat: ' . $po->po_number);

            } catch (\Exception $e) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }
        
    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);
        
        $purchaseOrder->load([
            'supplier', 
            'requester', 
            'items.product.unit', 
            'adjustments.user', // Muat penyesuaian & user pembuatnya
            'returns',          // Muat retur (untuk info retur deposit)
            'payments.receivedBy',
            'payments.paymentMethod', // ✅ PERBAIKAN: Eager load relasi ini
        ]);

        $paymentMethods = PaymentMethod::where('is_active', true)
                            ->whereIn('type', ['direct', 'pending'])
                            ->orderBy('name')
                            ->get();
        $companyBankAccounts = CompanyBankAccount::where('is_active', true)->orderBy('bank_name')->get();
        // ✅ PERBAIKAN: Tambahkan 'paymentMethods' ke compact()
        return view('purchase_orders.show', compact('purchaseOrder', 'paymentMethods', 'companyBankAccounts'));
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $purchaseOrder->load('items.product');
        $suppliers = Supplier::all();
        $products = Product::all();
        $users = User::all();

        return view('purchase_orders.edit', compact('purchaseOrder', 'suppliers', 'products', 'users'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        // Otorisasi, pastikan user boleh mengupdate PO ini
        $this->authorize('update', $purchaseOrder);

        // Validasi input, sama seperti di method store
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'order_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:order_date',
            'requester_user_id' => 'nullable|exists:users,user_id',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.price_per_unit' => 'required|numeric|min:0',
            'products.*.discounts' => 'nullable|array',
            'products.*.discounts.*' => 'nullable|numeric|min:0|max:100',
            'products.*.update_master_price' => 'nullable|boolean',
            'apply_disc_fee' => 'sometimes|boolean',
            'disc_fee_percent' => 'nullable|numeric|min:0|max:100',
            'disc_fee_amount' => 'nullable|numeric|min:0',
            'apply_rounding_discount' => 'sometimes|boolean',
            'rounding_discount_amount' => 'nullable|numeric|min:0',
            'use_custom_dpp_factor' => 'sometimes|boolean',
            'custom_dpp_factor' => 'nullable|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'shipping_amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // LANGKAH 1: Kalkulasi ulang subtotal barang di server
            $itemSubtotal = 0;
            foreach ($validated['products'] as $p) {
                $finalPrice = floatval($p['price_per_unit']);
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) $finalPrice *= (1 - (floatval($d) / 100));
                    }
                }
                $itemSubtotal += (floatval($p['quantity']) * $finalPrice);
            }

            // LANGKAH 2: Panggil service kalkulator Anda
            $options = $request->all();
            $options['subtotal'] = $itemSubtotal;
            $calc = PurchaseOrderCalculator::calculate($options);

            // LANGKAH 3: Update data utama Purchase Order dengan hasil kalkulasi yang benar
            $purchaseOrder->update([
                'supplier_id'         => $validated['supplier_id'],
                'order_date'          => $validated['order_date'],
                'due_date'            => $request->input('due_date'),
                'requester_user_id'   => $request->input('requester_user_id'),
                'notes'               => $request->input('notes'),
                'tax_id'              => $request->input('tax_id'),
                
                // Mengupdate semua kolom kalkulasi dari hasil service
                'subtotal'                  => $calc['subtotal'],
                'apply_disc_fee'            => $request->boolean('apply_disc_fee'),
                'disc_fee_percent'          => $request->input('disc_fee_percent'),
                'disc_fee_amount'           => $calc['disc_fee_amount'],
                'apply_rounding_discount'   => $request->boolean('apply_rounding_discount'),
                'rounding_discount_amount'  => $calc['rounding_discount_amount'],
                'use_custom_dpp_factor'     => $request->boolean('use_custom_dpp_factor'),
                'custom_dpp_factor'         => $request->input('custom_dpp_factor'),
                'shipping_amount'           => $calc['shipping_amount'],
                'taxable_amount'            => $calc['taxable_base'],
                'dpp'                       => $calc['dpp'],
                'ppn'                       => $calc['ppn'],
                'total_amount'              => $calc['grand_total'],
                'grand_total'               => $calc['grand_total'],
            ]);

            // LANGKAH 4: Hapus item-item lama beserta diskonnya
            $purchaseOrder->items()->each(function ($item) {
                $item->discounts()->delete(); // Hapus relasi diskon dulu
                $item->delete();
            });

            // LANGKAH 5: Buat ulang item-item berdasarkan data baru dari form
            foreach ($validated['products'] as $p) {
                $finalPrice = floatval($p['price_per_unit']);
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) $finalPrice *= (1 - (floatval($d) / 100));
                    }
                }
                $subtotal_item = floatval($p['quantity']) * $finalPrice;

                $item = $purchaseOrder->items()->create([
                    'product_id' => $p['product_id'],
                    'quantity' => $p['quantity'],
                    'price_per_unit' => $p['price_per_unit'],
                    'subtotal' => $subtotal_item,
                ]);

                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) $item->discounts()->create(['percentage' => floatval($d)]);
                    }
                }

                if (isset($p['update_master_price']) && $p['update_master_price']) {
            $product = Product::find($p['product_id']);
            if ($product) {
                $product->update(['purchase_price' => $p['price_per_unit']]);
            }
        }
            }

            DB::commit();

            return redirect()->route('purchase-orders.show', $purchaseOrder->po_id)->with('success', 'Pesanan Pembelian berhasil diupdate.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal mengupdate Pesanan Pembelian: ' . $e->getMessage());
        }
    }


    public function receive(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('receive', $purchaseOrder);

        if ($purchaseOrder->status !== 'draft' && $purchaseOrder->status !== 'ordered') {
            return back()->with('error', 'Pesanan ini sudah diproses sebelumnya.');
        }

        try {
            DB::beginTransaction();

            // Loop melalui setiap item di dalam PO
            foreach ($purchaseOrder->items as $item) {
                
                // Kunci produk untuk update (mencegah race condition)
                $product = Product::lockForUpdate()->find($item->product_id);
                
                if ($product) {
                    
                    // --- LOGIKA HPP RATA-RATA (AVERAGE COST) ---
                    
                    // 1. Dapatkan harga beli bersih (HPP) per unit dari item PO ini
                    // Kita ambil dari subtotal item / quantity item
                    // Ini adalah HPP paling akurat karena sudah termasuk diskon berlipat
                    $netPricePerUnit = $item->subtotal / $item->quantity;
                    
                    // 2. Dapatkan data stok & HPP saat ini
                    $oldStock = $product->stock_quantity;
                    $oldAvgCost = $product->average_cost;
                    
                    // 3. Dapatkan data barang yang masuk
                    $newStock = $item->quantity;
                    $newPurchaseCost = $netPricePerUnit;
                    
                    // 4. Hitung total stok baru
                    $totalStock = $oldStock + $newStock;
                    
                    // 5. Hitung HPP rata-rata baru (Weighted Average)
                    // Rumus: ((StokLama * HppLama) + (StokBaru * HppBaru)) / TotalStok
                    $newAvgCost = 0;
                    if ($totalStock > 0) {
                        $newAvgCost = (($oldStock * $oldAvgCost) + ($newStock * $newPurchaseCost)) / $totalStock;
                    }
                    
                    // 6. Update stok dan HPP rata-rata baru
                    $product->stock_quantity = $totalStock;
                    $product->average_cost = $newAvgCost;
                    $product->save();
                }
            }

            // Ubah status pesanan pembelian menjadi 'completed'
            $purchaseOrder->status = 'completed';
            $purchaseOrder->save();

            DB::commit();
            return redirect()->route('purchase-orders.index')
                ->with('success', 'Barang untuk pesanan #' . ($purchaseOrder->po_number ?? $purchaseOrder->po_id) . ' telah diterima. Stok dan HPP rata-rata diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses penerimaan barang: ' . $e->getMessage());
        }
    }

    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
        {
            $this->authorize('cancel', $purchaseOrder); // Menggunakan method cancel dari Policy

            $purchaseOrder->status = 'cancelled';
            $purchaseOrder->save();

            return redirect()->route('purchase-orders.index')->with('success', 'Pesanan Pembelian berhasil dibatalkan.');
        }

        public function markAsPaid(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('pay', $purchaseOrder);
        if ($purchaseOrder->payment_status === 'paid') {
            return back()->with('info', 'Pesanan ini sudah ditandai lunas sebelumnya.');
        }

        $purchaseOrder->update(['payment_status' => 'paid']);

        return redirect()->route('purchase-orders.show', $purchaseOrder->po_id)
                        ->with('success', 'Pesanan berhasil ditandai LUNAS.');
    }
    public function downloadPDF(PurchaseOrder $purchaseOrder)
{
    $this->authorize('view', $purchaseOrder);
    $purchaseOrder->load(['supplier', 'items.product.unit', 'items.discounts', 'tax']);

    // [PERBAIKAN] Mengubah ukuran kertas menjadi 9.5" x 5.5"
    $paperSize = [0, 0, 684, 396]; 

    $pdf = Pdf::loadView('purchase_orders.pdf_template', compact('purchaseOrder'));
    
    $pdf->setPaper($paperSize);

    $fileName = 'PO_' . str_replace('/', '-', $purchaseOrder->po_number) . '.pdf';
    return $pdf->download($fileName);
}

    public function addSupplierInvoice(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);
        $request->validate([
            'supplier_invoice_number' => 'required|string|max:255',
        ]);

        $purchaseOrder->update([
            'supplier_invoice_number' => $request->supplier_invoice_number,
        ]);

        return back()->with('success', 'Nomor Faktur Supplier berhasil disimpan.');
    }

    
}