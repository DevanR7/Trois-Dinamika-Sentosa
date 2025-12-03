<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\User;
use App\Models\Tax;
use App\Models\PaymentMethod;
use App\Models\CompanyBankAccount;
use App\Services\PurchaseOrderCalculator;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;
// 1. Import Trait
use App\Traits\ValidatesAccountingPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrderController extends Controller
{
    // 2. Gunakan Trait
    use ValidatesAccountingPeriod;

    protected $accountingService;
    protected $accountingSettings;

    public function __construct(
        AccountingService $accountingService, 
        AccountingSettingService $accountingSettingService
    ) {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettingService;
    }

    /**
     * Menampilkan daftar purchase order
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $query = PurchaseOrder::with(['supplier', 'requester'])
            ->latest('order_date');

        // Filter Pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('supplier_invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($qSupplier) use ($search) {
                      $qSupplier->where('supplier_name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter Tanggal & Status
        if ($request->filled('order_date')) {
            $query->whereDate('order_date', $request->order_date);
        }
        if ($request->filled('due_date')) {
            $query->whereDate('due_date', $request->due_date);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Sorting
        $sort = $request->get('sort', 'terbaru');
        switch ($sort) {
            case 'terlama':
                $query->orderBy('order_date', 'asc');
                break;
            case 'supplier_az':
                $query->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.supplier_id')
                      ->orderBy('suppliers.supplier_name', 'asc')
                      ->select('purchase_orders.*');
                break;
            case 'supplier_za':
                $query->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.supplier_id')
                      ->orderBy('suppliers.supplier_name', 'desc')
                      ->select('purchase_orders.*');
                break;
            default:
                $query->orderBy('order_date', 'desc')->orderBy('po_id', 'desc');
                break;
        }

        $purchaseOrders = $query->paginate(15)->appends($request->query());

        return view('admin.purchase_orders.index', compact('purchaseOrders'));
    }

    /**
     * Form buat PO baru
     */
    public function create(): View
    {
        $this->authorize('create', PurchaseOrder::class);

        $suppliers = Supplier::orderBy('supplier_name')->get();
        $products  = Product::orderBy('product_name')->get();
        $users     = User::orderBy('full_name')->get();

        return view('admin.purchase_orders.create', compact('suppliers', 'products', 'users'));
    }

    /**
     * Simpan PO baru
     * ✅ DIPERBARUI: Menambahkan validasi periode akuntansi
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PurchaseOrder::class);

        // --- 🔒 VALIDASI TANGGAL (TRAIT) ---
        if ($this->isDateClosed($request->order_date)) {
            return back()->with('error', 'Gagal: Tanggal pesanan berada dalam periode tahun buku yang sudah ditutup.')->withInput();
        }
        // -----------------------------------

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
            // Hitung Subtotal Item
            $itemSubtotal = 0.0;
            foreach ($validated['products'] as $p) {
                $finalPrice = (float) $p['price_per_unit'];
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) $finalPrice *= (1 - ((float) $d / 100));
                    }
                }
                $itemSubtotal += ((float) $p['quantity']) * $finalPrice;
            }

            // Kalkulasi Total
            $options = $request->all();
            $options['subtotal'] = $itemSubtotal;
            $calc = PurchaseOrderCalculator::calculate($options);

            // Create Header PO
            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePoNumber(),
                'supplier_id' => $validated['supplier_id'],
                'order_date' => $validated['order_date'],
                'due_date' => $request->input('due_date'),
                'requester_user_id' => $request->input('requester_user_id'),
                'user_id_admin' => Auth::id(),
                'notes' => $request->input('notes'),
                'status' => 'draft',
                'payment_status' => 'unpaid',
                'subtotal' => $calc['subtotal'],
                'tax_id' => $request->input('tax_id'),
                'apply_disc_fee' => $request->boolean('apply_disc_fee'),
                'disc_fee_percent' => $request->input('disc_fee_percent'),
                'disc_fee_amount' => $calc['disc_fee_amount'],
                'apply_rounding_discount' => $request->boolean('apply_rounding_discount'),
                'rounding_discount_amount' => $calc['rounding_discount_amount'],
                'use_custom_dpp_factor' => $request->boolean('use_custom_dpp_factor'),
                'custom_dpp_factor' => $request->input('custom_dpp_factor'),
                'shipping_amount' => $calc['shipping_amount'],
                'taxable_amount' => $calc['taxable_base'],
                'dpp' => $calc['dpp'],
                'ppn' => $calc['ppn'],
                'total_amount' => $calc['grand_total'],
                'grand_total' => $calc['grand_total'],
            ]);

            // Create Items
            foreach ($validated['products'] as $p) {
                $finalPrice = (float) $p['price_per_unit'];
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) $finalPrice *= (1 - ((float) $d / 100));
                    }
                }

                $subtotalItem = ((float) $p['quantity']) * $finalPrice;

                $item = $po->items()->create([
                    'product_id' => $p['product_id'],
                    'quantity' => $p['quantity'],
                    'price_per_unit' => $p['price_per_unit'],
                    'subtotal' => $subtotalItem,
                ]);

                // Simpan diskon per item
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) {
                            $item->discounts()->create(['percentage' => (float) $d]);
                        }
                    }
                }

                // Update Harga Beli Master (Jika dicentang)
                if (!empty($p['update_master_price'])) {
                    $product = Product::find($p['product_id']);
                    if ($product) {
                        $product->update(['purchase_price' => $p['price_per_unit']]);
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.purchase-orders.show', $po->po_id)
                ->with('success', 'Pesanan berhasil dibuat: ' . $po->po_number);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Detail PO
     */
    public function show(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load([
            'supplier', 'requester', 'items.product.unit', 'items.discounts',
            'adjustments.user', 'returns', 'payments.receivedBy', 'payments.paymentMethod', 'tax',
        ]);

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->whereIn('type', ['direct', 'pending'])
            ->orderBy('name')->get();

        $companyBankAccounts = CompanyBankAccount::where('is_active', true)
            ->orderBy('bank_name')->get();

        return view('admin.purchase_orders.show', compact('purchaseOrder', 'paymentMethods', 'companyBankAccounts'));
    }

    /**
     * Form edit PO
     */
    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('update', $purchaseOrder);

        $purchaseOrder->load('items.product');
        $suppliers = Supplier::orderBy('supplier_name')->get();
        $products  = Product::orderBy('product_name')->get();
        $users     = User::orderBy('full_name')->get();

        return view('admin.purchase_orders.edit', compact('purchaseOrder', 'suppliers', 'products', 'users'));
    }

    /**
     * Update PO
     * ✅ DIPERBARUI: Menambahkan validasi periode akuntansi
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('update', $purchaseOrder);

        // --- 🔒 VALIDASI KEAMANAN (TRAIT) ---
        // 1. Cek Transaksi Lama (Jika sudah Completed/Jurnal Posted, tidak boleh diedit sembarangan)
        // Jika status masih draft/ordered, jurnal biasanya belum ada.
        // Tapi jika 'completed', jurnal PO-{no} sudah ada.
        $journalGroupId = ($purchaseOrder->status === 'completed') ? "PO-" . $purchaseOrder->po_number : null;
        
        if ($error = $this->checkTransactionLock($purchaseOrder->order_date, $journalGroupId)) {
            return back()->with('error', "Gagal Update: " . $error);
        }

        // 2. Cek Tanggal Baru (Apakah user memindahkan tanggal ke tahun yg ditutup?)
        if ($request->filled('order_date') && $this->isDateClosed($request->order_date)) {
            return back()->with('error', 'Gagal Update: Tanggal baru berada dalam periode tahun buku yang sudah ditutup.')->withInput();
        }
        // ------------------------------------

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
            // Hitung ulang subtotal
            $itemSubtotal = 0.0;
            foreach ($validated['products'] as $p) {
                $finalPrice = (float) $p['price_per_unit'];
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) $finalPrice *= (1 - ((float) $d / 100));
                    }
                }
                $itemSubtotal += ((float) $p['quantity']) * $finalPrice;
            }

            $options = $request->all();
            $options['subtotal'] = $itemSubtotal;
            $calc = PurchaseOrderCalculator::calculate($options);

            // Update PO
            $purchaseOrder->update([
                'supplier_id' => $validated['supplier_id'],
                'order_date' => $validated['order_date'],
                'due_date' => $request->input('due_date'),
                'requester_user_id' => $request->input('requester_user_id'),
                'notes' => $request->input('notes'),
                'tax_id' => $request->input('tax_id'),
                'subtotal' => $calc['subtotal'],
                'apply_disc_fee' => $request->boolean('apply_disc_fee'),
                'disc_fee_percent' => $request->input('disc_fee_percent'),
                'disc_fee_amount' => $calc['disc_fee_amount'],
                'apply_rounding_discount' => $request->boolean('apply_rounding_discount'),
                'rounding_discount_amount' => $calc['rounding_discount_amount'],
                'use_custom_dpp_factor' => $request->boolean('use_custom_dpp_factor'),
                'custom_dpp_factor' => $request->input('custom_dpp_factor'),
                'shipping_amount' => $calc['shipping_amount'],
                'taxable_amount' => $calc['taxable_base'],
                'dpp' => $calc['dpp'],
                'ppn' => $calc['ppn'],
                'total_amount' => $calc['grand_total'],
                'grand_total' => $calc['grand_total'],
            ]);

            // Hapus item lama (Cascade delete discounts)
            foreach ($purchaseOrder->items as $oldItem) {
                $oldItem->discounts()->delete();
                $oldItem->delete();
            }

            // Buat item baru
            foreach ($validated['products'] as $p) {
                $finalPrice = (float) $p['price_per_unit'];
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) $finalPrice *= (1 - ((float) $d / 100));
                    }
                }
                $subtotalItem = ((float) $p['quantity']) * $finalPrice;

                $item = $purchaseOrder->items()->create([
                    'product_id' => $p['product_id'],
                    'quantity' => $p['quantity'],
                    'price_per_unit' => $p['price_per_unit'],
                    'subtotal' => $subtotalItem,
                ]);

                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) {
                            $item->discounts()->create(['percentage' => (float) $d]);
                        }
                    }
                }

                if (!empty($p['update_master_price'])) {
                    $product = Product::find($p['product_id']);
                    if ($product) {
                        $product->update(['purchase_price' => $p['price_per_unit']]);
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.purchase-orders.show', $purchaseOrder->po_id)
                ->with('success', 'Pesanan Pembelian berhasil diupdate.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Terima Barang (Receive)
     * POSTING JURNAL AKUNTANSI
     * ✅ DIPERBARUI: Menambahkan validasi periode akuntansi
     */
    public function receive(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('receive', $purchaseOrder);

        if (!in_array($purchaseOrder->status, ['draft', 'ordered'])) {
            return back()->with('error', 'Pesanan ini sudah diproses sebelumnya.');
        }

        // --- 🔒 VALIDASI TANGGAL (TRAIT) ---
        if ($this->isDateClosed($purchaseOrder->order_date)) {
             return back()->with('error', 'Gagal Terima Barang: Tanggal PO berada di periode akuntansi yang sudah ditutup.');
        }
        // -----------------------------------

        // Validasi akun default
        $inventoryAccountId = $this->accountingSettings->getInventoryId();
        $apAccountId = $this->accountingSettings->getAccountsPayableId();

        if (!$inventoryAccountId || !$apAccountId) {
            return back()->with('error', 'Gagal: Akun default Persediaan (Inventory) atau Hutang Dagang (AP) belum diatur.');
        }

        DB::beginTransaction();
        try {
            // 1. Update stok & Avg Cost
            foreach ($purchaseOrder->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if (! $product) continue;

                $netPricePerUnit = $item->quantity > 0 ? ($item->subtotal / $item->quantity) : 0.0;

                $oldStock = (float) $product->stock_quantity;
                $oldAvgCost = (float) $product->average_cost;
                $newStock = (float) $item->quantity;
                $newPurchaseCost = (float) $netPricePerUnit;

                $totalStock = $oldStock + $newStock;
                $newAvgCost = 0.0;
                
                // ✅ KOREKSI MATEMATIKA: Mencegah Division by Zero
                if ($totalStock > 0) {
                    // Jika total stok positif, hitung Weighted Average
                    // Gunakan max(0, $oldStock) untuk mencegah nilai minus merusak harga rata-rata
                    $effectiveOldStock = max(0, $oldStock); 
        
                    $newAvgCost = (($effectiveOldStock * $oldAvgCost) + ($newStock * $newPurchaseCost)) / ($effectiveOldStock + $newStock);
                } else {
                    // Jika stok masih 0 atau minus (sangat jarang), gunakan harga beli terakhir
                    $newAvgCost = $newPurchaseCost;
                }

                $product->stock_quantity = $totalStock;
                $product->average_cost = $newAvgCost;
                $product->save();
            }

            // 2. Update Status
            $purchaseOrder->status = 'completed';
            $purchaseOrder->save();

            // 3. ✅ POST JURNAL AKUNTANSI
            $journalGroupId = "PO-" . $purchaseOrder->po_number;
            $description = "Penerimaan barang PO #" . $purchaseOrder->po_number . " (Supplier: " . $purchaseOrder->supplier->supplier_name . ")";
            
            $amount = $purchaseOrder->grand_total;

            // (D) Persediaan
            $debitEntries = [
                [$inventoryAccountId, $amount]
            ];
            // (K) Hutang Dagang
            $creditEntries = [
                [$apAccountId, $amount]
            ];

            $this->accountingService->postJournal(
                $journalGroupId,
                $purchaseOrder->order_date,
                $description,
                $debitEntries,
                $creditEntries,
                $purchaseOrder,
                Auth::id()
            );

            DB::commit();

            return redirect()
                ->route('admin.purchase-orders.index')
                ->with('success', 'Barang diterima. Stok, HPP, dan Jurnal Akuntansi diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal receive PO: " . $e->getMessage());
            return back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan PO
     * JURNAL REVERSAL
     * ✅ DIPERBARUI: Menambahkan validasi periode akuntansi
     */
    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('cancel', $purchaseOrder);
        
        // --- 🔒 VALIDASI KEAMANAN (TRAIT) ---
        // Cek apakah PO ini sudah pernah dijurnal (status completed)
        $journalGroupId = ($purchaseOrder->status === 'completed') ? "PO-" . $purchaseOrder->po_number : null;
        
        if ($error = $this->checkTransactionLock($purchaseOrder->order_date, $journalGroupId)) {
            return back()->with('error', "Gagal Membatalkan: " . $error);
        }
        // ------------------------------------

        $wasCompleted = ($purchaseOrder->status === 'completed');

        DB::beginTransaction();
        try {
            $purchaseOrder->update(['status' => 'cancelled']);

            // Jika PO sudah completed, buat reversal & kembalikan stok
            if ($wasCompleted) {
                
                $inventoryAccountId = $this->accountingSettings->getInventoryId();
                $apAccountId = $this->accountingSettings->getAccountsPayableId();
                if (!$inventoryAccountId || !$apAccountId) {
                    throw new \Exception("Akun default akuntansi belum diatur.");
                }

                // Kembalikan Stok (Logika sederhana: kurangi stok yg ditambah)
                // Catatan: Ini tidak mengembalikan Average Cost ke semula karena rumit, 
                // tapi setidaknya jumlah stok dikoreksi.
                foreach ($purchaseOrder->items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->decrement('stock_quantity', $item->quantity);
                    }
                }

                // ✅ POST JURNAL REVERSAL
                $journalGroupId = "PO-REVERSAL-" . $purchaseOrder->po_number;
                $description = "Reversal/Pembatalan PO #" . $purchaseOrder->po_number;
                $amount = $purchaseOrder->grand_total;

                // Balik: (D) Hutang, (K) Persediaan
                $debitEntries = [
                    [$apAccountId, $amount]
                ];
                $creditEntries = [
                    [$inventoryAccountId, $amount]
                ];

                $this->accountingService->postJournal(
                    $journalGroupId,
                    now(), // Reversal dilakukan hari ini
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $purchaseOrder,
                    Auth::id()
                );

                // Hapus Jurnal Asli dari General Ledger (Clean up)
                DB::table('general_ledgers')->where('journal_group_id', "PO-" . $purchaseOrder->po_number)->delete();
            }
            
            DB::commit();
            return redirect()->route('admin.purchase-orders.index')->with('success', 'Pesanan Pembelian berhasil dibatalkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan PO: ' . $e->getMessage());
        }
    }

    /**
     * Tandai Lunas (Simple Status Update)
     */
    public function markAsPaid(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('pay', $purchaseOrder);

        if ($purchaseOrder->payment_status === 'paid') {
            return back()->with('info', 'Pesanan ini sudah lunas.');
        }

        $purchaseOrder->update(['payment_status' => 'paid']);

        return redirect()
            ->route('admin.purchase-orders.show', $purchaseOrder->po_id)
            ->with('success', 'Pesanan berhasil ditandai LUNAS.');
    }

    /**
     * Download PDF
     */
    public function downloadPDF(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);
        $purchaseOrder->load(['supplier', 'items.product.unit', 'items.discounts', 'tax']);

        $pdf = Pdf::loadView('purchase_orders.pdf_template', compact('purchaseOrder'));
        $pdf->setPaper([0, 0, 684, 396]);

        $fileName = 'PO_' . str_replace('/', '-', $purchaseOrder->po_number) . '.pdf';
        return $pdf->download($fileName);
    }

    /**
     * Tambah No Faktur Supplier
     */
    public function addSupplierInvoice(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
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