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

class PurchaseOrderController extends Controller
{
    public function __construct()
    {
        // Jika Anda ingin menambahkan middleware atau policy global, letakkan di sini.
        // Contoh: $this->middleware('permission:manage-purchase-orders');
    }

    /**
     * Display a listing of purchase orders.
     *
     * Supports search, date filters (order_date, due_date), payment status filter and sorting.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $query = PurchaseOrder::with(['supplier', 'requester'])
            ->latest('order_date');

        // General search across po_number, supplier_invoice_number, supplier name
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

        // Filter by exact order_date
        if ($request->filled('order_date')) {
            $query->whereDate('order_date', $request->order_date);
        }

        // Filter by exact due_date
        if ($request->filled('due_date')) {
            $query->whereDate('due_date', $request->due_date);
        }

        // Filter by payment_status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Sorting options
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

        return view('purchase_orders.index', compact('purchaseOrders'));
    }

    /**
     * Show the form for creating a new purchase order.
     */
    public function create(): View
    {
        $this->authorize('create', PurchaseOrder::class);

        $suppliers = Supplier::orderBy('supplier_name')->get();
        $products  = Product::orderBy('product_name')->get();
        $users     = User::orderBy('full_name')->get();

        return view('purchase_orders.create', compact('suppliers', 'products', 'users'));
    }

    /**
     * Store a newly created purchase order in storage.
     *
     * Steps:
     *  - Validate request
     *  - Recalculate item subtotals server-side
     *  - Call PurchaseOrderCalculator service for full totals
     *  - Create PO and items (and item discounts)
     */
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
            // Recalculate item subtotal server-side (apply discounts multiplicatively)
            $itemSubtotal = 0.0;
            foreach ($validated['products'] as $p) {
                $finalPrice = (float) $p['price_per_unit'];
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) {
                            $finalPrice *= (1 - ((float) $d / 100));
                        }
                    }
                }
                $itemSubtotal += ((float) $p['quantity']) * $finalPrice;
            }

            // Prepare options for calculator service
            $options = $request->all();
            $options['subtotal'] = $itemSubtotal;
            $calc = PurchaseOrderCalculator::calculate($options);

            // Create Purchase Order master record
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

                // Calculation fields from service
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

            // Create PO items and discounts; optionally update master purchase price
            foreach ($validated['products'] as $p) {
                $finalPrice = (float) $p['price_per_unit'];
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) {
                            $finalPrice *= (1 - ((float) $d / 100));
                        }
                    }
                }

                $subtotalItem = ((float) $p['quantity']) * $finalPrice;

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

                if (!empty($p['update_master_price'])) {
                    $product = Product::find($p['product_id']);
                    if ($product) {
                        $product->update(['purchase_price' => $p['price_per_unit']]);
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('purchase-orders.show', $po->po_id)
                ->with('success', 'Pesanan berhasil dibuat: ' . $po->po_number);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified purchase order.
     *
     * Eager-load relations required by the view (supplier, items, payments, etc.)
     */
    public function show(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load([
            'supplier',
            'requester',
            'items.product.unit',
            'items.discounts',
            'adjustments.user',
            'returns',
            'payments.receivedBy',
            'payments.paymentMethod',
            'tax',
        ]);

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->whereIn('type', ['direct', 'pending'])
            ->orderBy('name')
            ->get();

        $companyBankAccounts = CompanyBankAccount::where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        return view('purchase_orders.show', compact('purchaseOrder', 'paymentMethods', 'companyBankAccounts'));
    }

    /**
     * Show the form for editing the specified purchase order.
     */
    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('update', $purchaseOrder);

        $purchaseOrder->load('items.product');

        $suppliers = Supplier::orderBy('supplier_name')->get();
        $products  = Product::orderBy('product_name')->get();
        $users     = User::orderBy('full_name')->get();

        return view('purchase_orders.edit', compact('purchaseOrder', 'suppliers', 'products', 'users'));
    }

    /**
     * Update the specified purchase order in storage.
     *
     * This mirrors store() but updates existing PO and replaces its items.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('update', $purchaseOrder);

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
            // Recalculate subtotal
            $itemSubtotal = 0.0;
            foreach ($validated['products'] as $p) {
                $finalPrice = (float) $p['price_per_unit'];
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) {
                            $finalPrice *= (1 - ((float) $d / 100));
                        }
                    }
                }
                $itemSubtotal += ((float) $p['quantity']) * $finalPrice;
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

            // Remove old items and their discounts
            foreach ($purchaseOrder->items as $oldItem) {
                $oldItem->discounts()->delete();
                $oldItem->delete();
            }

            // Recreate items from validated data
            foreach ($validated['products'] as $p) {
                $finalPrice = (float) $p['price_per_unit'];
                if (!empty($p['discounts']) && is_array($p['discounts'])) {
                    foreach ($p['discounts'] as $d) {
                        if (is_numeric($d)) {
                            $finalPrice *= (1 - ((float) $d / 100));
                        }
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
                ->route('purchase-orders.show', $purchaseOrder->po_id)
                ->with('success', 'Pesanan Pembelian berhasil diupdate.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal mengupdate Pesanan Pembelian: ' . $e->getMessage());
        }
    }

    /**
     * Receive goods for the given purchase order.
     *
     * Updates product stock and weighted average cost (average inventory cost).
     */
    public function receive(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('receive', $purchaseOrder);

        if (!in_array($purchaseOrder->status, ['draft', 'ordered'])) {
            return back()->with('error', 'Pesanan ini sudah diproses sebelumnya.');
        }

        DB::beginTransaction();
        try {
            foreach ($purchaseOrder->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if (! $product) {
                    continue;
                }

                // Net price per unit derived from item subtotal
                $netPricePerUnit = $item->quantity > 0 ? ($item->subtotal / $item->quantity) : 0.0;

                $oldStock = (float) $product->stock_quantity;
                $oldAvgCost = (float) $product->average_cost;
                $newStock = (float) $item->quantity;
                $newPurchaseCost = (float) $netPricePerUnit;

                $totalStock = $oldStock + $newStock;

                $newAvgCost = 0.0;
                if ($totalStock > 0) {
                    $newAvgCost = (($oldStock * $oldAvgCost) + ($newStock * $newPurchaseCost)) / $totalStock;
                }

                $product->stock_quantity = $totalStock;
                $product->average_cost = $newAvgCost;
                $product->save();
            }

            $purchaseOrder->status = 'completed';
            $purchaseOrder->save();

            DB::commit();

            return redirect()
                ->route('purchase-orders.index')
                ->with('success', 'Barang untuk pesanan #' . ($purchaseOrder->po_number ?? $purchaseOrder->po_id) . ' telah diterima. Stok dan HPP rata-rata diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses penerimaan barang: ' . $e->getMessage());
        }
    }

    /**
     * Mark a purchase order as cancelled.
     */
    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('cancel', $purchaseOrder);

        $purchaseOrder->update(['status' => 'cancelled']);

        return redirect()->route('purchase-orders.index')->with('success', 'Pesanan Pembelian berhasil dibatalkan.');
    }

    /**
     * Mark a purchase order as paid.
     */
    public function markAsPaid(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('pay', $purchaseOrder);

        if ($purchaseOrder->payment_status === 'paid') {
            return back()->with('info', 'Pesanan ini sudah ditandai lunas sebelumnya.');
        }

        $purchaseOrder->update(['payment_status' => 'paid']);

        return redirect()
            ->route('purchase-orders.show', $purchaseOrder->po_id)
            ->with('success', 'Pesanan berhasil ditandai LUNAS.');
    }

    /**
     * Download PDF for the purchase order.
     */
    public function downloadPDF(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier', 'items.product.unit', 'items.discounts', 'tax']);

        // Custom paper size (points): 9.5" x 5.5" ≈ 684 x 396 points
        $paperSize = [0, 0, 684, 396];

        $pdf = Pdf::loadView('purchase_orders.pdf_template', compact('purchaseOrder'));
        $pdf->setPaper($paperSize);

        $fileName = 'PO_' . str_replace('/', '-', $purchaseOrder->po_number) . '.pdf';
        return $pdf->download($fileName);
    }

    /**
     * Add supplier invoice number to the PO.
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
