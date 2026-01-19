<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Tax;
use App\Models\PaymentMethod;
use App\Models\CompanyBankAccount;
use App\Models\User;
use App\Models\ClientLedger;
use App\Models\InvoiceAdditionalCost;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;
use App\Traits\ValidatesAccountingPeriod;
use App\Http\Requests\StoreSalesInvoiceRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class SalesInvoiceController extends Controller
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

        $this->middleware('can:view-invoices')->only(['index', 'show', 'downloadPDF']);
        $this->middleware('can:create-invoices')->only(['create', 'store', 'createFromOrder']);
        $this->middleware('can:edit-invoices')->only(['edit', 'update', 'confirm']);
        $this->middleware('can:cancel-invoices')->only(['cancel']);
        $this->middleware('can:delete-invoices')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalesInvoice::class);
        $query = SalesInvoice::with(['client', 'sales', 'returns', 'items.product']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($q) => $q->where('client_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('order_date', '<=', $request->end_date);
        }

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
            default:
                $query->orderBy('order_date', 'desc')->orderBy('invoice_id', 'desc');
                break;
        }

        $invoices = $query->paginate(15)->appends($request->query());

        return view('admin.invoices.index', ['invoices' => $invoices]);
    }

    public function show(SalesInvoice $invoice): View
    {
        $this->authorize('view', $invoice);
        
        $invoice->load([
            'client',
            'sales',
            'payments.receivedBy',
            'payments.paymentMethod',
            'items.product' => function ($query) {
                $query->withTrashed();
            },
            'taxes',
            'adjustments',
            'returns',
            'additionalCosts'
        ]);

        $paymentMethods = PaymentMethod::where('is_active', true)
                                       ->whereIn('type', ['direct', 'pending'])
                                       ->orderBy('name')
                                       ->get();

        $companyBankAccounts = CompanyBankAccount::where('is_active', true)->orderBy('bank_name')->get();

        $gatewayMethod = PaymentMethod::where('is_active', true)
            ->where('type', 'gateway')
            ->first();

        return view('admin.invoices.show', compact(
            'invoice',
            'paymentMethods',
            'companyBankAccounts',
            'gatewayMethod'
        ));
    }

    public function create(): View
    {
        $this->authorize('create', SalesInvoice::class);
        
        $clients = Client::orderBy('client_name')->get();
        $products = Product::where('is_active', true)
                           ->orderBy('product_name')
                           ->get(); // Tampilkan semua produk aktif (stok 0 tetap tampil untuk info)

        $taxes = Tax::where('is_active', true)->get();
        $salesUsers = User::role('sales')->get();
        
        return view('admin.invoices.create', compact('clients', 'products', 'taxes', 'salesUsers'));
    }

    public function createFromOrder(Order $order): View
    {
        $this->authorize('create', SalesInvoice::class);
        $order->load('items.product');
        $clients = Client::all();
        $products = Product::where('is_active', true)->orderBy('product_name')->get();
        $taxes = Tax::where('is_active', true)->get();
        $salesUsers = User::role('sales')->get();
        return view('admin.invoices.create', compact('clients', 'products', 'order', 'taxes', 'salesUsers'));
    }

    public function store(StoreSalesInvoiceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($this->isDateClosed($validated['order_date'])) {
            return back()->with('error', 'Gagal: Tanggal invoice berada di periode akuntansi yang sudah ditutup.')->withInput();
        }

        try {
            DB::beginTransaction();

            $originOrder = isset($validated['sales_order_id']) ? Order::find($validated['sales_order_id']) : null;
            $subtotalProducts = 0;
            $productsToSave = [];

            // 1. Hitung Item
            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']);

                $price = round((float) $productData['custom_price'], 2);
                $quantity = (float) $productData['quantity'];

                $subtotalItem = round($quantity * $price, 2);
                $subtotalProducts = round($subtotalProducts + $subtotalItem, 2);

                if (!empty($productData['update_master_price'])) {
                    $product->update(['selling_price' => $price]);
                }

                $productsToSave[] = [
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'price_per_unit' => $price,
                    'hpp' => $product->average_cost ?? 0, // Snapshot HPP saat ini
                    'subtotal' => $subtotalItem,
                ];
            }

            // 2. Hitung Biaya Tambahan
            $totalAdditionalCosts = 0;
            $additionalCostsToSave = [];
            if (!empty($validated['additional_costs'])) {
                foreach ($validated['additional_costs'] as $cost) {
                    $amount = round((float) $cost['amount'], 2);
                    $totalAdditionalCosts = round($totalAdditionalCosts + $amount, 2);
                    $additionalCostsToSave[] = [
                        'description' => $cost['description'],
                        'amount' => $amount
                    ];
                }
            }

            // 3. Hitung Diskon & Pajak
            $discountPercentage = $validated['discount_percentage'] ?? 0;
            $discountAmount = round($subtotalProducts * ($discountPercentage / 100), 2);
            $subtotalAfterDiscount = round($subtotalProducts - $discountAmount, 2);

            $totalTaxAmount = 0;
            $taxesToAttach = [];
            if (!empty($validated['taxes'])) {
                $selectedTaxes = Tax::find($validated['taxes']);
                foreach ($selectedTaxes as $tax) {
                    $taxAmountForItem = round($subtotalAfterDiscount * ($tax->rate / 100), 2);
                    $totalTaxAmount = round($totalTaxAmount + $taxAmountForItem, 2);

                    $taxesToAttach[$tax->id] = [
                        'name' => $tax->name,
                        'rate' => $tax->rate,
                        'amount' => $taxAmountForItem,
                    ];
                }
            }

            $totalAmount = round($subtotalAfterDiscount + $totalTaxAmount + $totalAdditionalCosts, 2);

            $salesUserId = $validated['user_id_sales'] ?? null;
            $orderSource = 'sales';
            if ($originOrder) {
                $orderSource = $originOrder->order_source;
                if (empty($salesUserId) && $originOrder->user_id_sales) {
                    $salesUserId = $originOrder->user_id_sales;
                }
            }

            // 4. Simpan Invoice
            $invoice = SalesInvoice::create([
                'client_id' => $validated['client_id'],
                'invoice_number' => SalesInvoice::generateInvoiceNumber($salesUserId, $orderSource),
                'order_date' => $validated['order_date'],
                'due_date' => $validated['due_date'],
                'subtotal' => $subtotalProducts,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'user_id_sales' => $salesUserId,
                'amount_paid' => 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoice->taxes()->attach($taxesToAttach);
            $invoice->items()->createMany($productsToSave);

            if (!empty($additionalCostsToSave)) {
                $invoice->additionalCosts()->createMany($additionalCostsToSave);
            }

            if ($originOrder) {
                $originOrder->status = 'invoiced';
                $originOrder->invoice_id = $invoice->invoice_id;
                $originOrder->save();
            }

            DB::commit();
            return redirect()->route('admin.invoices.show', $invoice->invoice_id)->with('success', 'Invoice berhasil dibuat (Status: Draft).');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating invoice: ' . $e->getMessage());
            return back()->with('error', 'Gagal membuat invoice: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(SalesInvoice $invoice): View|RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->status !== 'draft') {
            return redirect()->route('admin.invoices.show', $invoice->invoice_id)
                ->with('error', 'Peringatan: Invoice yang sudah dikonfirmasi (Posted) TIDAK DAPAT diedit. Silakan Batalkan (Cancel) lalu buat baru.');
        }

        $invoice->load(['items.product', 'taxes', 'additionalCosts']);
        $clients = Client::all();
        $products = Product::where('is_active', true)->orderBy('product_name')->get();
        $taxes = Tax::where('is_active', true)->get();
        $salesUsers = User::role('sales')->get();

        return view('admin.invoices.edit', compact('invoice', 'clients', 'products', 'taxes', 'salesUsers'));
    }

    public function update(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Gagal: Hanya invoice berstatus Draft yang bisa diubah.');
        }

        if ($error = $this->checkTransactionLock($invoice->order_date)) {
            return back()->with('error', "Gagal Update: " . $error);
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'order_date' => 'required|date',
            'due_date' => 'required|date',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.item_id' => 'nullable|exists:invoice_items,item_id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.custom_price' => 'required|numeric|min:0',
            'products.*.update_master_price' => 'nullable|boolean',
            'additional_costs' => 'nullable|array',
            'additional_costs.*.description' => 'required_with:additional_costs|string|max:255',
            'additional_costs.*.amount' => 'required_with:additional_costs|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'taxes' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        if ($this->isDateClosed($request->order_date)) {
            return back()->with('error', 'Gagal Update: Tanggal baru berada dalam periode yang ditutup.')->withInput();
        }

        try {
            DB::beginTransaction();

            $subtotalProducts = 0;

            // 1. Kumpulkan ID item yang dikirim dari form
            $submittedItemIds = collect($validated['products'])
                ->pluck('item_id')
                ->filter()
                ->toArray();

            // 2. Hapus item di DB yang tidak ada di form submission (Deleted by user)
            $invoice->items()->whereNotIn('item_id', $submittedItemIds)->delete();

            // 3. Loop Create / Update
            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']);
                $price = round((float) $productData['custom_price'], 2);
                $quantity = (float) $productData['quantity'];
                $itemSubtotal = round($quantity * $price, 2);
                $subtotalProducts = round($subtotalProducts + $itemSubtotal, 2);

                if (isset($productData['update_master_price']) && $productData['update_master_price']) {
                    $product->update(['selling_price' => $price]);
                }

                if (isset($productData['item_id']) && $productData['item_id']) {
                    // UPDATE
                    $existingItem = $invoice->items()
                        ->where('item_id', $productData['item_id'])
                        ->where('invoice_id', $invoice->invoice_id)
                        ->first();

                    if ($existingItem) {
                        $existingItem->update([
                            'product_id' => $product->product_id,
                            'quantity' => $quantity,
                            'price_per_unit' => $price,
                            'subtotal' => $itemSubtotal,
                            'hpp' => $product->average_cost ?? 0
                        ]);
                    }
                } else {
                    // CREATE NEW ITEM
                    $invoice->items()->create([
                        'product_id' => $product->product_id,
                        'quantity' => $quantity,
                        'price_per_unit' => $price,
                        'subtotal' => $itemSubtotal,
                        'hpp' => $product->average_cost ?? 0
                    ]);
                }
            }

            // 4. Update Biaya Tambahan
            $totalAdditionalCosts = 0;
            $invoice->additionalCosts()->delete(); 

            if (!empty($validated['additional_costs'])) {
                $costsToSave = [];
                foreach ($validated['additional_costs'] as $cost) {
                    $amount = round((float) $cost['amount'], 2);
                    $totalAdditionalCosts = round($totalAdditionalCosts + $amount, 2);
                    $costsToSave[] = [
                        'description' => $cost['description'],
                        'amount' => $amount
                    ];
                }
                $invoice->additionalCosts()->createMany($costsToSave);
            }

            $discountPercentage = $request->input('discount_percentage', 0);
            $discountAmount = round($subtotalProducts * ($discountPercentage / 100), 2);
            $subtotalAfterDiscount = round($subtotalProducts - $discountAmount, 2);

            $totalTaxAmount = 0;
            $taxesToSync = [];
            if (!empty($validated['taxes'])) {
                $selectedTaxes = Tax::find($validated['taxes']);
                foreach ($selectedTaxes as $tax) {
                    $taxAmountForItem = round($subtotalAfterDiscount * ($tax->rate / 100), 2);
                    $totalTaxAmount = round($totalTaxAmount + $taxAmountForItem, 2);
                    $taxesToSync[$tax->id] = [
                        'name' => $tax->name,
                        'rate' => $tax->rate,
                        'amount' => $taxAmountForItem,
                    ];
                }
            }

            $totalAmount = round($subtotalAfterDiscount + $totalTaxAmount + $totalAdditionalCosts, 2);

            $invoice->update([
                'client_id' => $validated['client_id'],
                'order_date' => $validated['order_date'],
                'due_date' => $validated['due_date'],
                'subtotal' => $subtotalProducts,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'notes' => $request->input('notes'),
            ]);

            $invoice->taxes()->sync($taxesToSync);

            DB::commit();
            return redirect()->route('admin.invoices.show', $invoice->invoice_id)->with('success', 'Invoice Draft berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update: ' . $e->getMessage())->withInput();
        }
    }

    public function confirm(SalesInvoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Hanya invoice berstatus DRAFT yang bisa dikonfirmasi.');
        }

        if ($this->isDateClosed($invoice->order_date)) {
             return back()->with('error', 'Gagal: Tanggal invoice berada di periode akuntansi yang sudah ditutup.');
        }

        $arAccountId = $this->accountingSettings->getAccountsReceivableId();
        $salesRevenueAccountId = $this->accountingSettings->getSalesRevenueId();
        $cogsAccountId = $this->accountingSettings->getCogsId();
        $inventoryAccountId = $this->accountingSettings->getInventoryId();

        if (!$arAccountId || !$salesRevenueAccountId || !$cogsAccountId || !$inventoryAccountId) {
            return back()->with('error', 'Gagal: Akun default (Piutang, Pendapatan, HPP, Persediaan) belum diatur.');
        }

        DB::beginTransaction();
        try {
            $invoice->load('items');
            $totalHpp = 0;

            foreach ($invoice->items as $item) {
                // FIX: LOCKING Product agar stok akurat saat dikurangi (Mencegah Race Condition)
                $product = Product::lockForUpdate()->find($item->product_id);

                if (!$product) {
                    throw new \Exception("Produk ID '{$item->product_id}' tidak ditemukan (mungkin terhapus).");
                }

                if (!$product->is_active) {
                    throw new \Exception("Gagal: Produk '{$product->product_name}' sedang dinonaktifkan.");
                }

                // FIX: Validasi Stok Ketat
                if ($product->stock_quantity < $item->quantity) {
                    throw new \Exception("Stok '{$product->product_name}' tidak mencukupi. Sisa: {$product->stock_quantity}, Diminta: {$item->quantity}.");
                }

                $product->decrement('stock_quantity', $item->quantity);

                // Snapshot HPP (Simpan nilai HPP saat barang keluar)
                $currentHpp = $product->average_cost;
                $item->update(['hpp' => $currentHpp]);

                $itemHppTotal = round($item->quantity * $currentHpp, 2);
                $totalHpp = round($totalHpp + $itemHppTotal, 2);
            }

            $invoice->update(['status' => 'unpaid']);

            // JURNAL AKUNTANSI
            $journalGroupId = "INV-" . $invoice->invoice_number;
            $description = "Penjualan Invoice #" . $invoice->invoice_number . " (" . $invoice->client->client_name . ")";

            $totalAdditionalCosts = $invoice->additionalCosts()->sum('amount');
            $totalTaxJournal = $invoice->taxes()->sum('invoice_tax.amount');
            $revenueFromProducts = round($invoice->total_amount - $totalAdditionalCosts - $totalTaxJournal, 2);

            $debitEntries = [
                [$arAccountId, $invoice->total_amount, "Piutang atas " . $invoice->client->client_name],
                [$cogsAccountId, $totalHpp, "HPP Invoice #" . $invoice->invoice_number]
            ];

            $creditEntries = [
                [$salesRevenueAccountId, $revenueFromProducts, "Pendapatan Penjualan"],
                [$inventoryAccountId, $totalHpp, "Pengurangan Stok (COGS)"]
            ];

            if ($totalAdditionalCosts > 0) {
                 $creditEntries[] = [$salesRevenueAccountId, $totalAdditionalCosts, "Pendapatan Biaya Tambahan"];
            }

            if ($totalTaxJournal > 0) {
                 $creditEntries[] = [$salesRevenueAccountId, $totalTaxJournal, "Hutang Pajak Keluaran (PPN)"];
            }

            $this->accountingService->postJournal(
                $journalGroupId,
                $invoice->order_date,
                $description,
                $debitEntries,
                $creditEntries,
                $invoice,
                Auth::id()
            );

            DB::commit();
            return redirect()->route('admin.invoices.show', $invoice->invoice_id)
                ->with('success', 'Invoice dikonfirmasi. Stok dikurangi & Jurnal diposting.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal konfirmasi invoice: " . $e->getMessage());
            return back()->with('error', 'Gagal konfirmasi invoice: ' . $e->getMessage());
        }
    }

    public function destroy(SalesInvoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        if ($invoice->status !== 'draft') {
             return back()->with('error', 'Gagal: Hanya invoice berstatus DRAFT yang bisa dihapus.');
        }

        if ($error = $this->checkTransactionLock($invoice->order_date)) {
            return back()->with('error', "Gagal Hapus: " . $error);
        }

        try {
            DB::beginTransaction();

            $invoice->items()->delete();
            $invoice->taxes()->detach();
            $invoice->additionalCosts()->delete();
            ClientLedger::where('sales_invoice_id', $invoice->invoice_id)->delete();
            $invoice->delete();

            DB::commit();
            return redirect()->route('admin.invoices.index')->with('success', 'Invoice draft berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal hapus: ' . $e->getMessage());
        }
    }

    public function cancel(SalesInvoice $invoice): RedirectResponse
    {
        $this->authorize('cancel', $invoice);

        $journalGroupId = "INV-" . $invoice->invoice_number;
        if ($error = $this->checkTransactionLock($invoice->order_date, $journalGroupId)) {
            return back()->with('error', "Gagal Batal: " . $error);
        }

        if ($invoice->amount_paid > 0) {
             return back()->with('error', 'Gagal: Invoice ini memiliki pembayaran yang tercatat. Silakan hapus/batalkan pembayaran terkait terlebih dahulu.');
        }

        if ($invoice->status === 'cancelled') {
             return back()->with('error', 'Invoice sudah dibatalkan sebelumnya.');
        }

        if ($invoice->status !== 'draft') {
            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $salesRevenueAccountId = $this->accountingSettings->getSalesRevenueId();
            $cogsAccountId = $this->accountingSettings->getCogsId();
            $inventoryAccountId = $this->accountingSettings->getInventoryId();

            if (!$arAccountId || !$salesRevenueAccountId || !$cogsAccountId || !$inventoryAccountId) {
                return back()->with('error', 'Gagal: Akun default belum diatur sepenuhnya di Pengaturan.');
            }
        }

        try {
            DB::beginTransaction();

            $relatedOrders = Order::where('invoice_id', $invoice->invoice_id)->get();
            foreach ($relatedOrders as $order) {
                $order->update([
                    'status' => 'approved',
                    'invoice_id' => null
                ]);
            }

            if ($invoice->status !== 'draft') {
                $invoice->load('items');
                $totalHppReversal = 0;

                foreach ($invoice->items as $item) {
                    $product = Product::withTrashed()->where('product_id', $item->product_id)->lockForUpdate()->first();

                    if ($product) {
                        $product->stock_quantity += $item->quantity;
                        $product->save();
                    }

                    $hppSnapshot = $item->hpp ?? 0;
                    $totalHppReversal += round($item->quantity * $hppSnapshot, 2);
                }

                $journalGroupId = "INV-REV-" . $invoice->invoice_number;
                $description = "Reversal/Pembatalan Invoice #" . $invoice->invoice_number;

                $debitEntries = [
                    [$salesRevenueAccountId, $invoice->total_amount, "Reversal Pendapatan Inv#" . $invoice->invoice_number],
                    [$inventoryAccountId, $totalHppReversal, "Reversal HPP (Stok Kembali)"]
                ];

                $creditEntries = [
                    [$arAccountId, $invoice->total_amount, "Reversal Piutang Inv#" . $invoice->invoice_number],
                    [$cogsAccountId, $totalHppReversal, "Reversal HPP Inv#" . $invoice->invoice_number]
                ];

                $this->accountingService->postJournal(
                    $journalGroupId,
                    now(),
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $invoice,
                    Auth::id()
                );
            }

            $invoice->update([
                'status' => 'cancelled',
                'notes' => $invoice->notes . "\n[Dibatalkan pada " . now()->format('d-m-Y H:i') . " oleh " . Auth::user()->full_name . "]"
            ]);

            DB::commit();

            $msg = ($invoice->status === 'draft')
                ? 'Invoice draft berhasil dibatalkan.'
                : 'Invoice berhasil dibatalkan. Stok dikembalikan dan Jurnal Pembalik dibuat.';

            return redirect()->route('admin.invoices.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal batalkan invoice #{$invoice->invoice_number}: " . $e->getMessage());
            return back()->with('error', 'Gagal membatalkan: ' . $e->getMessage());
        }
    }

    public function downloadPDF(SalesInvoice $invoice)
    {
        $this->authorize('view', $invoice);
        
        $invoice->load(['client', 'items.product.unit', 'taxes', 'sales', 'additionalCosts']);

        $settings = \App\Models\Setting::getAllSettings();
        $bankAccounts = \App\Models\CompanyBankAccount::where('is_active', true)->get();

        $customPaper = [0, 0, 684, 396]; 

        $pdf = Pdf::loadView('admin.invoices.pdf_template', compact('invoice', 'settings', 'bankAccounts'));
        $pdf->setPaper($customPaper);
        
        $cleanInvoiceNumber = str_replace('/', '-', $invoice->invoice_number);
        $fileName = 'Faktur-' . $cleanInvoiceNumber . '.pdf';

        return $pdf->stream($fileName); 
    }

    /**
     * Generate Snap Token dengan dukungan Split Payment (Deposit + Gateway)
     * Dipanggil via AJAX dari halaman show invoice.
     */
    public function getSnapToken(Request $request, SalesInvoice $invoice)
    {
        // 1. Hitung Sisa Tagihan Aktual
        $sisaTagihan = $invoice->remaining_balance; // Menggunakan accessor dari Model
        
        if ($sisaTagihan <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Invoice sudah lunas.']);
        }

        // 2. Cek Saldo Klien
        $client = $invoice->client;
        $saldoDeposit = $client->balance;
        
        // Gunakan boolean untuk konsistensi
        $useCredit = filter_var($request->input('use_credit'), FILTER_VALIDATE_BOOLEAN);

        // 3. Kalkulasi Split
        $creditUsed = 0;
        $amountToPay = $sisaTagihan;

        if ($useCredit && $saldoDeposit > 0) {
            $creditUsed = min($saldoDeposit, $sisaTagihan);
            $amountToPay = max(0, $sisaTagihan - $creditUsed);
        }

        // Case A: Lunas Full Pakai Deposit (Tidak perlu Midtrans)
        if ($amountToPay <= 0) {
            return response()->json([
                'status' => 'full_credit', 
                'message' => 'Saldo deposit mencukupi untuk pelunasan penuh. Silakan gunakan tombol Simpan Pembayaran Manual.'
            ]);
        }

        // Case B: Perlu Bayar via Midtrans (Full atau Sisa)
        try {
            // Setup Midtrans Params
            \Midtrans\Config::$serverKey = config('midtrans.server_key');
            \Midtrans\Config::$isProduction = config('midtrans.is_production');
            \Midtrans\Config::$isSanitized = true;
            \Midtrans\Config::$is3ds = true;

            // Buat Order ID Unik yang membawa informasi kredit
            // Format: INV-{ID}-T{TIMESTAMP}-C{CREDIT_AMOUNT}
            $customOrderId = "INV-{$invoice->invoice_id}-T" . time() . "-C" . $creditUsed;

            $params = [
                'transaction_details' => [
                    'order_id' => $customOrderId,
                    'gross_amount' => round($amountToPay), // Midtrans butuh integer
                ],
                'customer_details' => [
                    'first_name' => $client->client_name,
                    'email' => $client->email,
                    'phone' => $client->phone_number,
                ],
                'item_details' => [
                    [
                        'id' => 'INV-' . $invoice->invoice_number,
                        'price' => round($amountToPay),
                        'quantity' => 1,
                        'name' => "Pelunasan Invoice #{$invoice->invoice_number}" . ($creditUsed > 0 ? " (Sisa)" : "")
                    ]
                ]
            ];

            $snapToken = \Midtrans\Snap::getSnapToken($params);

            return response()->json([
                'status' => 'success',
                'snap_token' => $snapToken,
                'amount_to_pay' => $amountToPay,
                'credit_used' => $creditUsed
            ]);

        } catch (\Exception $e) {
            Log::error("Midtrans Snap Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Proses Refund untuk Invoice yang Overpaid
     */
    public function processRefund(Request $request, SalesInvoice $invoice)
    {
        $request->validate([
            'refund_amount' => 'required|numeric|min:0.01',
            'company_bank_account_id' => 'required|exists:company_bank_accounts,company_bank_account_id',
            'refund_date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        if ($this->isDateClosed($request->refund_date)) {
            return back()->with('error', 'Gagal: Tanggal refund masuk periode tutup buku.');
        }

        // Hitung Sisa Lebih Bayar (Harus negatif untuk menunjukkan overpayment)
        $currentBalance = $invoice->remaining_balance; 
        
        // Validasi: Tidak boleh refund lebih dari kelebihan bayar
        if ($currentBalance >= 0) {
            return back()->with('error', 'Tidak ada kelebihan bayar yang perlu direfund.');
        }

        if ($request->refund_amount > abs($currentBalance)) {
            return back()->with('error', 'Nominal refund melebihi jumlah kelebihan bayar.');
        }

        $arAccountId = $this->accountingSettings->getAccountsReceivableId();
        $bankAccount = \App\Models\CompanyBankAccount::find($request->company_bank_account_id);

        if (!$arAccountId || !$bankAccount->chart_of_account_id) {
            return back()->with('error', 'Konfigurasi Akun COA belum lengkap.');
        }

        DB::beginTransaction();
        try {
            // 1. Update Invoice
            $invoice->increment('total_refunded', $request->refund_amount);
            
            // 2. Buat Jurnal Akuntansi Otomatis
            // Debit: Piutang Usaha (Menetralkan posisi kredit/minus di AR)
            // Kredit: Bank (Uang Keluar)
            
            $journalGroupId = "REFUND-INV-" . $invoice->invoice_number . "-" . time();
            
            $debitEntries = [[$arAccountId, $request->refund_amount, "Refund Tunai Retur Inv #" . $invoice->invoice_number]];
            $creditEntries = [[$bankAccount->chart_of_account_id, $request->refund_amount, "Keluar ke Klien (" . $invoice->client->client_name . ")"]];

            $this->accountingService->postJournal(
                $journalGroupId,
                $request->refund_date,
                "Refund Kelebihan Bayar Inv #" . $invoice->invoice_number,
                $debitEntries,
                $creditEntries,
                $invoice,
                Auth::id()
            );

            // 3. Update Status (Cek apakah sekarang jadi 0/Lunas murni)
            $invoice->updatePaymentStatus();

            DB::commit();
            return back()->with('success', 'Refund berhasil diproses. Jurnal pengeluaran kas telah tercatat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal refund: ' . $e->getMessage());
        }
    }
}