<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\User;
use App\Models\Setting;
use App\Models\PaymentMethod;
use App\Models\CompanyBankAccount;
use App\Services\PurchaseOrderCalculator;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;
use App\Traits\ValidatesAccountingPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    use ValidatesAccountingPeriod;

    protected $accountingService;
    protected $accountingSettings;

    public function __construct(
        AccountingService $accountingService,
        AccountingSettingService $accountingSettingService
    ) {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettingService;

        $this->middleware('can:view-purchase-orders')->only(['index', 'show', 'downloadPDF']);
        $this->middleware('can:create-purchase-orders')->only(['create', 'store']);
        $this->middleware('can:edit-purchase-orders')->only(['edit', 'update', 'addSupplierInvoice']);
        $this->middleware('can:receive-purchase-orders')->only(['receive']);
        $this->middleware('can:pay-purchase-orders')->only(['markAsPaid']);
        $this->middleware('can:cancel-purchase-orders')->only(['cancel']);
        $this->middleware('can:delete-purchase-orders')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $query = PurchaseOrder::with(['supplier', 'requester', 'items', 'tax'])
            ->latest('order_date');

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

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('order_date', '<=', $request->end_date);
        }

        $purchaseOrders = $query->paginate(15)->appends($request->query());
        $suppliers = Supplier::orderBy('supplier_name')->get();

        return view('admin.purchase_orders.index', compact('purchaseOrders', 'suppliers'));
    }

    public function create(): View
    {
        $suppliers = Supplier::orderBy('supplier_name')->get();
        $products  = Product::where('is_active', true)->orderBy('product_name')->get();
        $users     = User::orderBy('full_name')->get();
        $taxes     = \App\Models\Tax::where('is_active', true)->get();
        return view('admin.purchase_orders.create', compact('suppliers', 'products', 'users', 'taxes'));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->isDateClosed($request->order_date)) {
            return back()->with('error', 'Gagal: Tanggal pesanan berada dalam periode tahun buku yang sudah ditutup.')->withInput();
        }

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
            $itemSubtotal = 0.0;

            foreach ($validated['products'] as $p) {
                $productCheck = Product::find($p['product_id']);
                if ($productCheck->supplier_id != $validated['supplier_id']) {
                    throw ValidationException::withMessages([
                        'products' => "Produk '{$productCheck->product_name}' bukan milik supplier yang dipilih."
                    ]);
                }

                $finalPrice = round((float) $p['price_per_unit'], 2);
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) $finalPrice *= (1 - ((float) $d / 100));
                    }
                }
                $finalPrice = round($finalPrice, 2);
                $rowTotal = round(((float) $p['quantity']) * $finalPrice, 2);
                $itemSubtotal = round($itemSubtotal + $rowTotal, 2);
            }

            $options = $request->all();
            $options['subtotal'] = $itemSubtotal;
            $calc = PurchaseOrderCalculator::calculate($options);

            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePoNumber($validated['supplier_id']),
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

            foreach ($validated['products'] as $p) {
                $finalPrice = round((float) $p['price_per_unit'], 2);
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) $finalPrice *= (1 - ((float) $d / 100));
                    }
                }
                $finalPrice = round($finalPrice, 2);
                $subtotalItem = round(((float) $p['quantity']) * $finalPrice, 2);

                $item = $po->items()->create([
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

                // Update Master Price HANYA jika dicentang di sini
                if (!empty($p['update_master_price'])) {
                    Product::where('product_id', $p['product_id'])
                        ->update(['purchase_price' => $p['price_per_unit']]);
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

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load([
            'supplier', 'requester', 'items.product.unit', 'items.discounts',
            'adjustments.user', 'returns', 'payments.receivedBy', 'payments.paymentMethod', 'tax',
        ]);
        
        $paymentMethods = PaymentMethod::where('is_active', true)
            ->whereIn('type', ['direct', 'pending'])
            ->orderBy('name')->get();

        $companyBankAccounts = CompanyBankAccount::where('is_active', true)->orderBy('bank_name')->get();

        return view('admin.purchase_orders.show', compact('purchaseOrder', 'paymentMethods', 'companyBankAccounts'));
    }

    public function edit(PurchaseOrder $purchaseOrder): View|RedirectResponse
    {
        if (!in_array($purchaseOrder->status, ['draft', 'ordered'])) {
            return redirect()
                ->route('admin.purchase-orders.show', $purchaseOrder)
                ->with('error', 'PO dengan status ' . ucfirst($purchaseOrder->status) . ' tidak dapat diedit.');
        }

        $purchaseOrder->load('items.product', 'items.discounts');
        $suppliers = Supplier::orderBy('supplier_name')->get();
        $products  = Product::where('supplier_id', $purchaseOrder->supplier_id)
                        ->orderBy('product_name')
                        ->get();
        $users     = User::orderBy('full_name')->get();
        $taxes     = \App\Models\Tax::where('is_active', true)->get();

        return view('admin.purchase_orders.edit', compact('purchaseOrder', 'suppliers', 'products', 'users', 'taxes'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $journalGroupId = ($purchaseOrder->status === 'completed') ? "PO-" . $purchaseOrder->po_number : null;

        if ($error = $this->checkTransactionLock($purchaseOrder->order_date, $journalGroupId)) {
            return back()->with('error', "Gagal Update: " . $error);
        }

        if ($request->filled('order_date') && $this->isDateClosed($request->order_date)) {
            return back()->with('error', 'Gagal Update: Tanggal baru berada dalam periode tahun buku yang sudah ditutup.')->withInput();
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'order_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:order_date',
            'requester_user_id' => 'nullable|exists:users,user_id',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.item_id' => 'nullable|exists:purchase_order_items,item_id',
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
            $itemSubtotal = 0.0;
            
            // 1. Kumpulkan ID item yang dikirim
            $submittedItemIds = collect($validated['products'])
                ->pluck('item_id')
                ->filter()
                ->toArray();

            // 2. Hapus item di DB yang tidak ada di form submission
            $itemsToDelete = $purchaseOrder->items()->whereNotIn('item_id', $submittedItemIds)->get();
            foreach ($itemsToDelete as $delItem) {
                $delItem->discounts()->delete(); 
                $delItem->delete();
            }

            // 3. Loop Create / Update
            foreach ($validated['products'] as $p) {
                $productCheck = Product::find($p['product_id']);
                if ($productCheck->supplier_id != $validated['supplier_id']) {
                    throw ValidationException::withMessages(['products' => "Produk '{$productCheck->product_name}' salah supplier."]);
                }

                $finalPrice = round((float) $p['price_per_unit'], 2);
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) $finalPrice *= (1 - ((float) $d / 100));
                    }
                }
                $finalPrice = round($finalPrice, 2);
                $subtotalItem = round(((float) $p['quantity']) * $finalPrice, 2);
                $itemSubtotal = round($itemSubtotal + $subtotalItem, 2);

                if (isset($p['item_id']) && $p['item_id']) {
                    // UPDATE
                    $existingItem = $purchaseOrder->items()->where('item_id', $p['item_id'])->first();
                    if ($existingItem) {
                        $existingItem->update([
                            'product_id' => $p['product_id'],
                            'quantity' => $p['quantity'],
                            'price_per_unit' => $p['price_per_unit'],
                            'subtotal' => $subtotalItem,
                        ]);
                        $currentItem = $existingItem;
                    }
                } else {
                    // CREATE
                    $currentItem = $purchaseOrder->items()->create([
                        'product_id' => $p['product_id'],
                        'quantity' => $p['quantity'],
                        'price_per_unit' => $p['price_per_unit'],
                        'subtotal' => $subtotalItem,
                    ]);
                }

                // Sync Diskon
                $currentItem->discounts()->delete();
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) {
                            $currentItem->discounts()->create(['percentage' => (float) $d]);
                        }
                    }
                }

                if (!empty($p['update_master_price'])) {
                    Product::where('product_id', $p['product_id'])->update(['purchase_price' => $p['price_per_unit']]);
                }
            }

            $options = $request->all();
            $options['subtotal'] = $itemSubtotal;
            $calc = PurchaseOrderCalculator::calculate($options);

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

            DB::commit();
            return redirect()->route('admin.purchase-orders.show', $purchaseOrder->po_id)->with('success', 'Pesanan Pembelian berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function receive(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('receive', $purchaseOrder);

        if (!in_array($purchaseOrder->status, ['draft', 'ordered'])) {
            return back()->with('error', 'Gagal: Pesanan ini sudah diproses atau dibatalkan sebelumnya.');
        }

        if ($this->isDateClosed($purchaseOrder->order_date)) {
             return back()->with('error', 'Gagal Terima Barang: Tanggal PO berada di periode akuntansi yang sudah ditutup.');
        }

        $inventoryAccountId = $this->accountingSettings->getInventoryId();
        $apAccountId = $this->accountingSettings->getAccountsPayableId();

        if (!$inventoryAccountId || !$apAccountId) {
            return back()->with('error', 'Gagal: Akun default Persediaan (Inventory) atau Hutang Dagang (AP) belum diatur di menu Pengaturan.');
        }

        DB::beginTransaction();
        try {
            $purchaseOrder->load('items.product');

            foreach ($purchaseOrder->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                
                if (! $product) {
                    Log::warning("Produk ID {$item->product_id} tidak ditemukan saat receive PO #{$purchaseOrder->po_number}");
                    continue;
                }

                $qtyReceived = (float) $item->quantity;
                if ($qtyReceived <= 0) continue;

                // --- LOGIKA HPP ---
                // Harga Bersih Transaksi (untuk HPP)
                $pricePerUnit = $item->subtotal / $qtyReceived; 
                
                $oldStock = (float) $product->stock_quantity;
                $oldAvgCost = (float) $product->average_cost;

                $totalNewStock = $oldStock + $qtyReceived;
                $newAvgCost = $oldAvgCost;

                if ($totalNewStock > 0) {
                    if ($oldStock < 0) {
                        $newAvgCost = $pricePerUnit;
                    } else {
                        // Weighted Average
                        $totalOldValue = $oldStock * $oldAvgCost;
                        $totalNewValue = $qtyReceived * $pricePerUnit;
                        $newAvgCost = ($totalOldValue + $totalNewValue) / $totalNewStock;
                    }
                } else {
                    $newAvgCost = $pricePerUnit; 
                }

                $newAvgCost = round($newAvgCost, 4);

                $product->stock_quantity = $totalNewStock;
                $product->average_cost = $newAvgCost;
                
                // ==============================================================
                // BUG FIX: JANGAN UPDATE MASTER PRICE DI SINI
                // Kode lama yang dihapus: $product->purchase_price = $pricePerUnit;
                // ==============================================================

                $product->save();
            }

            $purchaseOrder->status = 'completed';
            $purchaseOrder->save();

            // Jurnal Akuntansi
            $journalGroupId = "PO-" . $purchaseOrder->po_number;
            $description = "Penerimaan barang PO #" . $purchaseOrder->po_number . " (" . $purchaseOrder->supplier->supplier_name . ")";
            
            $amount = $purchaseOrder->grand_total;

            $debitEntries = [[$inventoryAccountId, $amount, "Persediaan Masuk PO #" . $purchaseOrder->po_number]];
            $creditEntries = [[$apAccountId, $amount, "Hutang Dagang PO #" . $purchaseOrder->po_number]];

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

            return redirect()->route('admin.purchase-orders.show', $purchaseOrder->po_id)
                ->with('success', 'Barang berhasil diterima. Stok telah bertambah dan status diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal receive PO #{$purchaseOrder->po_number}: " . $e->getMessage());
            return back()->with('error', 'Gagal memproses penerimaan barang: ' . $e->getMessage());
        }
    }

    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('cancel', $purchaseOrder);

        $journalGroupId = ($purchaseOrder->status === 'completed') ? "PO-" . $purchaseOrder->po_number : null;
        if ($error = $this->checkTransactionLock($purchaseOrder->order_date, $journalGroupId)) return back()->with('error', $error);

        $wasCompleted = ($purchaseOrder->status === 'completed');

        DB::beginTransaction();
        try {
            $purchaseOrder->update(['status' => 'cancelled']);

            if ($wasCompleted) {
                $inventoryAccountId = $this->accountingSettings->getInventoryId();
                $apAccountId = $this->accountingSettings->getAccountsPayableId();

                if (!$inventoryAccountId || !$apAccountId) throw new \Exception("Akun default belum diatur.");

                foreach ($purchaseOrder->items as $item) {
                    $product = Product::withTrashed()->where('product_id', $item->product_id)->lockForUpdate()->first();
                    if ($product) {
                        // FIX: Hanya kurangi stok, jangan ubah HPP (konservatif)
                        $product->decrement('stock_quantity', $item->quantity);
                    }
                }

                $journalGroupId = "PO-REVERSAL-" . $purchaseOrder->po_number;
                $this->accountingService->postJournal(
                    $journalGroupId,
                    now(),
                    "Reversal PO #".$purchaseOrder->po_number,
                    [[$apAccountId, $purchaseOrder->grand_total]],
                    [[$inventoryAccountId, $purchaseOrder->grand_total]],
                    $purchaseOrder,
                    Auth::id()
                );

                // Hapus jurnal asli
                DB::table('general_ledgers')->where('journal_group_id', "PO-" . $purchaseOrder->po_number)->delete();
            }

            DB::commit();
            return redirect()->route('admin.purchase-orders.index')->with('success', 'Pesanan Pembelian berhasil dibatalkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan: ' . $e->getMessage());
        }
    }

    public function markAsPaid(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('pay', $purchaseOrder);
        if ($purchaseOrder->payment_status === 'paid') return back()->with('info', 'Pesanan ini sudah lunas.');

        $purchaseOrder->update(['payment_status' => 'paid']);
        return redirect()->route('admin.purchase-orders.show', $purchaseOrder->po_id)->with('success', 'Pesanan ditandai LUNAS.');
    }

    public function downloadPDF(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);
        $purchaseOrder->load(['supplier', 'items.product.unit', 'items.discounts', 'tax', 'requester']);

        $settings = \App\Models\Setting::getAllSettings();

        $pdf = Pdf::loadView('admin.purchase_orders.pdf_template', compact('purchaseOrder', 'settings'))
                  ->setPaper('a4', 'portrait');
        
        $cleanPoNumber = str_replace('/', '-', $purchaseOrder->po_number);
        return $pdf->download('PO-' . $cleanPoNumber . '.pdf');
    }

    public function addSupplierInvoice(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $request->validate(['supplier_invoice_number' => 'required|string|max:255']);
        $purchaseOrder->update(['supplier_invoice_number' => $request->supplier_invoice_number]);
        return back()->with('success', 'Nomor Faktur Supplier berhasil disimpan.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if ($purchaseOrder->status !== 'draft') return back()->with('error', 'Hanya PO DRAFT yang bisa dihapus.');
        if ($error = $this->checkTransactionLock($purchaseOrder->order_date)) return back()->with('error', $error);

        if ($purchaseOrder->payments()->exists() || $purchaseOrder->returns()->exists()) return back()->with('error', 'PO memiliki data pembayaran/retur.');

        try {
            DB::beginTransaction();
            foreach ($purchaseOrder->items as $item) {
                $item->discounts()->delete();
                $item->delete();
            }
            $purchaseOrder->adjustments()->delete();
            $purchaseOrder->delete();
            DB::commit();

            return redirect()->route('admin.purchase-orders.index')->with('success', 'Draft PO dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal hapus: ' . $e->getMessage());
        }
    }

    public function markAsOrdered(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Pesanan tidak dapat diproses karena status bukan Draft.');
        }

        if ($purchaseOrder->items()->count() === 0) {
            return back()->with('error', 'Harap tambahkan item barang terlebih dahulu sebelum memproses pesanan.');
        }

        $purchaseOrder->update([
            'status' => 'ordered',
        ]);

        return back()->with('success', 'Pesanan berhasil diproses menjadi Ordered. Menunggu barang datang.');
    }
}