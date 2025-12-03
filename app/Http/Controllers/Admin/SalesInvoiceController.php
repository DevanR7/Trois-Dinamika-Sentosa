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
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf; 

// Service & Traits
use App\Services\AccountingService;
use App\Services\AccountingSettingService;
use App\Traits\ValidatesAccountingPeriod;

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
    }
    
    /**
     * Menampilkan daftar invoice
     */
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

        // Filter tanggal
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

        // Sorting
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

        return view('admin.invoices.index', ['invoices' => $invoices]);
    } 

    /**
     * Menampilkan detail invoice
     */
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

        return view('admin.invoices.show', compact('invoice', 'paymentMethods', 'companyBankAccounts'));
    }

    /**
     * Form Invoice Baru
     */
    public function create(): View
    {
        $this->authorize('create', SalesInvoice::class);
        
        // Load Data untuk Dropdown
        $clients = Client::all();
        $products = Product::all();
        $taxes = Tax::where('is_active', true)->get();
        $salesUsers = User::role('sales')->get();

        return view('admin.invoices.create', compact('clients', 'products', 'taxes', 'salesUsers'));
    }
    
    /**
     * Form Invoice dari Order
     */
    public function createFromOrder(Order $order): View
    {
        $this->authorize('create', SalesInvoice::class);
        $order->load('items.product'); 
        
        $clients = Client::all();
        $products = Product::all();
        $taxes = Tax::where('is_active', true)->get();
        $salesUsers = User::role('sales')->get();
        
        return view('admin.invoices.create', compact('clients', 'products', 'order', 'taxes', 'salesUsers'));
    }

    /**
     * Simpan Invoice Baru (Status: DRAFT)
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SalesInvoice::class);
        
        if ($this->isDateClosed($request->order_date)) {
            return back()->with('error', 'Gagal: Tanggal invoice berada dalam periode tahun buku yang sudah ditutup.')->withInput();
        }
        
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'order_date' => 'required|date',
            'due_date' => 'required|date',
            'sales_order_id' => 'nullable|exists:orders,order_id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.custom_price' => 'required|numeric|min:0', 
            'products.*.update_master_price' => 'nullable|boolean', 
            'additional_costs' => 'nullable|array',
            'additional_costs.*.description' => 'required_with:additional_costs|string|max:255',
            'additional_costs.*.amount' => 'required_with:additional_costs|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'taxes' => 'nullable|array',
            'taxes.*' => 'exists:taxes,id',
            'notes' => 'nullable|string',
            'user_id_sales' => 'nullable|exists:users,user_id',
        ]);

        try {
            DB::beginTransaction();
            
            $originOrder = $request->filled('sales_order_id') ? Order::find($request->sales_order_id) : null;
            $subtotalProducts = 0;
            $productsToSave = [];

            // 1. Proses Produk
            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']); 
                $price = (float) $productData['custom_price'];
                $quantity = $productData['quantity'];
                $subtotalItem = $quantity * $price;
                $subtotalProducts += $subtotalItem;
                
                // Update Harga Master
                if (isset($productData['update_master_price']) && $productData['update_master_price']) {
                    $product->update(['selling_price' => $price]);
                }
                
                $productsToSave[] = [
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'price_per_unit' => $price,
                    'hpp' => $product->average_cost ?? 0,
                    'subtotal' => $subtotalItem,
                ];
            }

            // 2. Proses Biaya Tambahan
            $totalAdditionalCosts = 0;
            $additionalCostsToSave = [];
            if (!empty($validated['additional_costs'])) {
                foreach ($validated['additional_costs'] as $cost) {
                    $totalAdditionalCosts += (float) $cost['amount'];
                    $additionalCostsToSave[] = [
                        'description' => $cost['description'],
                        'amount' => $cost['amount']
                    ];
                }
            }

            // 3. Kalkulasi
            $discountPercentage = $request->input('discount_percentage', 0);
            $discountAmount = $subtotalProducts * ($discountPercentage / 100);
            $subtotalAfterDiscount = $subtotalProducts - $discountAmount;
            
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
            
            $totalAmount = $subtotalAfterDiscount + $totalTaxAmount + $totalAdditionalCosts;

            // 4. Simpan Header Invoice
            $salesUserId = $request->input('user_id_sales');
            $orderSource = 'sales'; 
            if ($originOrder) {
                $orderSource = $originOrder->order_source;
                if (empty($salesUserId) && $originOrder->user_id_sales) {
                    $salesUserId = $originOrder->user_id_sales;
                }
            }

            $invoice = SalesInvoice::create([
                'client_id' => $validated['client_id'],
                'invoice_number' => SalesInvoice::generateInvoiceNumber($salesUserId, $orderSource),
                'order_date' => $validated['order_date'],
                'due_date' => $validated['due_date'],
                'subtotal' => $subtotalProducts,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'status' => 'draft', // Selalu DRAFT di awal
                'user_id_sales' => $salesUserId,
                'amount_paid' => 0,
                'notes' => $request->input('notes'),
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
            return back()->with('error', 'Gagal membuat invoice: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Konfirmasi Invoice (Posting Jurnal & Kurangi Stok)
     */
    public function confirm(SalesInvoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Hanya invoice DRAFT yang bisa dikonfirmasi.');
        }

        // Cek Akun Default
        $arAccountId = $this->accountingSettings->getAccountsReceivableId();
        $salesRevenueAccountId = $this->accountingSettings->getSalesRevenueId();
        $cogsAccountId = $this->accountingSettings->getCogsId();
        $inventoryAccountId = $this->accountingSettings->getInventoryId();

        if (!$arAccountId || !$salesRevenueAccountId || !$cogsAccountId || !$inventoryAccountId) {
            return back()->with('error', 'Gagal: Akun default (Piutang, Pendapatan, HPP, Persediaan) belum diatur.');
        }

        try {
            DB::beginTransaction();
            
            // 1. Kurangi Stok
            $itemsToDecrement = [];
            foreach ($invoice->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if (!$product) {
                    throw new \Exception("Produk '{$item->product_name}' tidak ditemukan.");
                }
                $itemsToDecrement[] = [
                    'product_id' => $product->product_id,
                    'quantity' => $item->quantity
                ];
            }

            foreach ($itemsToDecrement as $item) {
                Product::where('product_id', $item['product_id'])->decrement('stock_quantity', $item['quantity']);
            }

            // 2. Update Status
            $invoice->update(['status' => 'unpaid']);

            // 3. Post Jurnal Akuntansi
            $journalGroupId = "INV-" . $invoice->invoice_number;
            $description = "Penjualan Invoice #" . $invoice->invoice_number . " ke " . $invoice->client->client_name;
            
            $totalHpp = $invoice->items()->sum(DB::raw('quantity * hpp'));
            $totalAdditionalCosts = $invoice->additionalCosts()->sum('amount');
            $revenueFromProducts = $invoice->total_amount - $totalAdditionalCosts;

            // Sisi DEBIT
            $debitEntries = [
                [$arAccountId, $invoice->total_amount, "Piutang atas " . $invoice->client->client_name],
                [$cogsAccountId, $totalHpp, "HPP atas Invoice #" . $invoice->invoice_number]
            ];

            // Sisi KREDIT
            $creditEntries = [
                [$salesRevenueAccountId, $revenueFromProducts, "Pendapatan Penjualan #" . $invoice->invoice_number],
                [$inventoryAccountId, $totalHpp, "Pengurangan Persediaan"]
            ];

            if ($totalAdditionalCosts > 0) {
                $creditEntries[] = [$salesRevenueAccountId, $totalAdditionalCosts, "Pendapatan Biaya Tambahan #" . $invoice->invoice_number];
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
            return redirect()->route('admin.invoices.show', $invoice->invoice_id)->with('success', 'Invoice dikonfirmasi. Stok dikurangi & Jurnal diposting.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal konfirmasi invoice: " . $e->getMessage());
            return back()->with('error', 'Gagal konfirmasi invoice: ' . $e->getMessage());
        }
    }

    /**
     * Edit Invoice (HANYA DRAFT)
     */
    public function edit(SalesInvoice $invoice): View|RedirectResponse
    {
        $this->authorize('update', $invoice);

        // ✅ REVISI AKUNTANSI: Strict Check
        if ($invoice->status !== 'draft') {
            // Redirect ini mengembalikan 'RedirectResponse', bukan 'View'
            // Makanya return type di atas harus ditambahkan "|RedirectResponse"
            return redirect()->route('admin.invoices.show', $invoice->invoice_id)->with('error', 'Peringatan: Invoice yang sudah dikonfirmasi (Posted) TIDAK DAPAT diedit untuk menjaga integritas akuntansi. Silakan Batalkan (Cancel) invoice ini lalu buat baru.');
        }

        $invoice->load(['items.product', 'taxes', 'additionalCosts']);
        
        $clients = Client::all();
        $products = Product::all();
        $taxes = Tax::where('is_active', true)->get();
        $salesUsers = User::role('sales')->get();

        return view('admin.invoices.edit', compact('invoice', 'clients', 'products', 'taxes', 'salesUsers'));
    }

    /**
     * Update Invoice (HANYA DRAFT)
     */
    public function update(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        // ✅ REVISI AKUNTANSI: Strict Check
        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Gagal: Hanya invoice berstatus Draft yang bisa diubah.');
        }

        // Cek Lock Tanggal
        if ($error = $this->checkTransactionLock($invoice->order_date)) {
            return back()->with('error', "Gagal Update: " . $error);
        }

        if ($request->filled('order_date') && $this->isDateClosed($request->order_date)) {
            return back()->with('error', 'Gagal Update: Tanggal baru berada dalam periode yang ditutup.')->withInput();
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'order_date' => 'required|date',
            'due_date' => 'required|date',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
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

        try {
            DB::beginTransaction();

            // 1. Kalkulasi Ulang (Tanpa logika stok, karena masih draft)
            $subtotalProducts = 0;
            $productsToSave = [];
            
            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']);
                $price = (float) $productData['custom_price'];
                $quantity = $productData['quantity'];
                $itemSubtotal = $quantity * $price;
                $subtotalProducts += $itemSubtotal;

                if (isset($productData['update_master_price']) && $productData['update_master_price']) {
                    $product->update(['selling_price' => $price]);
                }

                $productsToSave[] = [
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'price_per_unit' => $price,
                    'subtotal' => $itemSubtotal,
                    'hpp' => $product->average_cost ?? 0
                ];
            }

            // 2. Biaya Tambahan
            $totalAdditionalCosts = 0;
            $additionalCostsToSave = [];
            if (!empty($validated['additional_costs'])) {
                foreach ($validated['additional_costs'] as $cost) {
                    $totalAdditionalCosts += (float) $cost['amount'];
                    $additionalCostsToSave[] = [
                        'description' => $cost['description'],
                        'amount' => $cost['amount']
                    ];
                }
            }

            $discountPercentage = $request->input('discount_percentage', 0);
            $discountAmount = $subtotalProducts * ($discountPercentage / 100);
            $subtotalAfterDiscount = $subtotalProducts - $discountAmount;

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
            
            $totalAmount = $subtotalAfterDiscount + $totalTaxAmount + $totalAdditionalCosts;

            // 3. Update Data Header
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

            // 4. Reset & Recreate Items (Draft bebas di-reset)
            $invoice->items()->delete();
            $invoice->items()->createMany($productsToSave);
            
            $invoice->additionalCosts()->delete();
            if (!empty($additionalCostsToSave)) {
                $invoice->additionalCosts()->createMany($additionalCostsToSave);
            }
            
            $invoice->taxes()->sync($taxesToSync);

            DB::commit();
            return redirect()->route('admin.invoices.show', $invoice->invoice_id)->with('success', 'Invoice Draft berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Hapus Invoice (Hanya Draft/Cancelled)
     */
    public function destroy(SalesInvoice $invoice): RedirectResponse
    { 
        $this->authorize('delete', $invoice); 

        if ($error = $this->checkTransactionLock($invoice->order_date)) {
            return back()->with('error', "Gagal Hapus: " . $error);
        }

        if (!in_array($invoice->status, ['draft', 'cancelled'])) {
             return back()->with('error', 'Invoice aktif tidak bisa dihapus permanen. Gunakan fitur Batalkan.');
        }

        if ($invoice->payments()->exists() || $invoice->returns()->exists()) {
             return back()->with('error', 'Invoice memiliki data terkait (pembayaran/retur) dan tidak bisa dihapus.');
        }

        try {
            DB::beginTransaction();
            $invoice->items()->delete();
            $invoice->taxes()->detach();
            $invoice->additionalCosts()->delete();
            $invoice->delete();
            DB::commit();
            return redirect()->route('admin.invoices.index')->with('success', 'Invoice draft berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal hapus: ' . $e->getMessage());
        }
    }

    /**
     * Membatalkan Invoice (Reversal Journal)
     */
    public function cancel(SalesInvoice $invoice): RedirectResponse
    {
        $this->authorize('cancel', $invoice);

        $journalGroupId = "INV-" . $invoice->invoice_number;
        if ($error = $this->checkTransactionLock($invoice->order_date, $journalGroupId)) {
            return back()->with('error', "Gagal Batal: " . $error);
        }

        if (in_array($invoice->status, ['paid', 'partially_paid'])) {
             return back()->with('error', 'Invoice yang sudah dibayar tidak bisa dibatalkan.');
        }
        
        if ($invoice->status === 'draft') {
            $invoice->status = 'cancelled';
            $invoice->save();
            return redirect()->route('admin.invoices.index')->with('success', 'Invoice draft dibatalkan.');
        }

        // Logic Reversal
        $arAccountId = $this->accountingSettings->getAccountsReceivableId();
        $salesRevenueAccountId = $this->accountingSettings->getSalesRevenueId();
        $cogsAccountId = $this->accountingSettings->getCogsId();
        $inventoryAccountId = $this->accountingSettings->getInventoryId();

        if (!$arAccountId || !$salesRevenueAccountId || !$cogsAccountId || !$inventoryAccountId) {
            return back()->with('error', 'Gagal: Akun default belum diatur.');
        }

        try {
            DB::beginTransaction();
            
            // 1. Kembalikan Stok
            foreach ($invoice->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                }
            }

            // 2. Update Status
            $invoice->status = 'cancelled';
            $invoice->save();

            // 3. Post Jurnal Reversal (BALIKAN DEBIT & KREDIT)
            // ✅ REVISI AKUNTANSI: Jurnal asli TETAP DISIMPAN, kita buat jurnal penyeimbang (Reversal)
            
            $journalGroupId = "INV-REV-" . $invoice->invoice_number;
            $description = "Pembatalan/Reversal Invoice #" . $invoice->invoice_number;
            $totalHpp = $invoice->items()->sum(DB::raw('quantity * hpp'));

            $debitEntries = [
                [$salesRevenueAccountId, $invoice->total_amount, "Reversal Pendapatan Inv#".$invoice->invoice_number],
                [$inventoryAccountId, $totalHpp, "Reversal HPP/Stok Inv#".$invoice->invoice_number]
            ];
            $creditEntries = [
                [$arAccountId, $invoice->total_amount, "Reversal Piutang Inv#".$invoice->invoice_number],
                [$cogsAccountId, $totalHpp, "Reversal HPP Inv#".$invoice->invoice_number]
            ];

            $this->accountingService->postJournal(
                $journalGroupId,
                now(), // Gunakan waktu saat pembatalan terjadi
                $description,
                $debitEntries,
                $creditEntries,
                $invoice,
                Auth::id()
            );
            
            DB::commit();
            return redirect()->route('admin.invoices.index')->with('success', 'Invoice dibatalkan. Stok dikembalikan dan Jurnal Pembalik telah dibuat.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal batalkan invoice: " . $e->getMessage());
            return back()->with('error', 'Gagal membatalkan: ' . $e->getMessage());
        }
    }

    /**
     * Download PDF
     */
    public function downloadPDF(SalesInvoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load(['client', 'items.product.unit', 'taxes', 'sales', 'additionalCosts']);

        $paperSize = [0, 0, 684, 396];
        $pdf = Pdf::loadView('invoices.pdf_template', compact('invoice'));
        $pdf->setPaper($paperSize);
        $cleanInvoiceNumber = str_replace('/', '-', $invoice->invoice_number);
        $fileName = 'Invoice-' . $cleanInvoiceNumber . '.pdf';

        return $pdf->download($fileName);
    }
}