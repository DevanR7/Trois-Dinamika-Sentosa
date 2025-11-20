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
use App\Services\AccountingService;
use App\Services\AccountingSettingService;
use Illuminate\Support\Facades\Log;
// 1. Import Trait
use App\Traits\ValidatesAccountingPeriod;

class SalesInvoiceController extends Controller
{
    // 2. Gunakan Trait
    use ValidatesAccountingPeriod;

    /**
     * ✅ Inject Service Akuntansi
     */
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
     * Menampilkan daftar invoice dengan filter dan sorting
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

        return view('invoices.index', ['invoices' => $invoices]);
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
            'additionalCosts' // ✅ Load biaya tambahan
        ]);

        $paymentMethods = PaymentMethod::where('is_active', true)
                            ->whereIn('type', ['direct', 'pending'])
                            ->orderBy('name')
                            ->get();

        $companyBankAccounts = CompanyBankAccount::where('is_active', true)->orderBy('bank_name')->get();

        return view('invoices.show', compact('invoice', 'paymentMethods', 'companyBankAccounts'));
    }

    /**
     * Menampilkan form buat invoice baru
     */
    public function create(): View
    {
        $this->authorize('create', SalesInvoice::class);
        $clients = Client::all();
        $products = Product::all();
        $taxes = Tax::where('is_active', true)->get();
        $salesUsers = User::role('sales')->get();

        return view('invoices.create', compact('clients', 'products', 'taxes', 'salesUsers'));
    }
    
    /**
     * Menampilkan form buat invoice dari order
     */
    public function createFromOrder(Order $order): View
    {
        $this->authorize('create', SalesInvoice::class);
        $order->load('items.product'); 
        
        $clients = Client::all();
        $products = Product::all();
        $taxes = Tax::where('is_active', true)->get();
        $salesUsers = User::role('sales')->get();
        
        return view('invoices.create', compact('clients', 'products', 'order', 'taxes', 'salesUsers'));
    }

    /**
     * Menyimpan invoice baru
     * ✅ DIPERBARUI: Menambahkan fitur Custom Price, Update Master Price, dan Additional Costs
     * ✅ DIPERBARUI: Menambahkan validasi periode akuntansi
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SalesInvoice::class);
        
        // ✅ VALIDASI PERIODE AKUNTANSI
        if ($this->isDateClosed($request->order_date)) {
            return back()->with('error', 'Gagal: Tanggal invoice berada dalam periode tahun buku yang sudah ditutup.')->withInput();
        }
        
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'order_date' => 'required|date',
            'due_date' => 'required|date',
            'sales_order_id' => 'nullable|exists:orders,order_id',
            
            // Produk
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.custom_price' => 'required|numeric|min:0', // ✅ Harga Custom
            'products.*.update_master_price' => 'nullable|boolean', // ✅ Checkbox Update Master
            
            // Biaya Tambahan
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
            $itemsToDecrementStock = [];

            // 1. Proses Produk
            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']); 
                
                // ✅ Gunakan harga dari input user (Custom Price)
                $price = (float) $productData['custom_price'];
                $quantity = $productData['quantity'];
                $subtotalItem = $quantity * $price;
                $subtotalProducts += $subtotalItem;
                
                // ✅ Update Harga Master jika dicentang
                if (isset($productData['update_master_price']) && $productData['update_master_price']) {
                    $product->update(['selling_price' => $price]);
                }
                
                // Logika stok (tetap sama)
                $isFromClientOrder = $originOrder && $originOrder->order_source === 'client';
                if (!$isFromClientOrder) {
                    $itemsToDecrementStock[] = [
                        'product_id' => $product->product_id,
                        'quantity' => $quantity
                    ];
                }

                $productsToSave[] = [
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'price_per_unit' => $price,
                    'hpp' => $product->average_cost ?? 0,
                    'subtotal' => $subtotalItem,
                ];
            }

            // Kurangi stok untuk produk yang bukan dari client order
            foreach ($itemsToDecrementStock as $item) {
                Product::where('product_id', $item['product_id'])->decrement('stock_quantity', $item['quantity']);
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

            // 3. Kalkulasi Akhir
            $discountPercentage = $request->input('discount_percentage', 0);
            $discountAmount = $subtotalProducts * ($discountPercentage / 100);
            $subtotalAfterDiscount = $subtotalProducts - $discountAmount;
            
            $totalTaxAmount = 0;
            $taxesToAttach = [];
            if (!empty($validated['taxes'])) {
                $selectedTaxes = Tax::find($validated['taxes']);
                foreach ($selectedTaxes as $tax) {
                    // Pajak biasanya dikenakan ke (Produk - Diskon)
                    $taxAmountForItem = $subtotalAfterDiscount * ($tax->rate / 100);
                    $totalTaxAmount += $taxAmountForItem;
                    $taxesToAttach[$tax->id] = [
                        'name' => $tax->name,
                        'rate' => $tax->rate,
                        'amount' => $taxAmountForItem,
                    ];
                }
            }
            
            // Total Akhir = (Produk - Diskon) + Pajak + Biaya Tambahan
            $totalAmount = $subtotalAfterDiscount + $totalTaxAmount + $totalAdditionalCosts;

            // 4. Simpan Invoice
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
                'subtotal' => $subtotalProducts, // Subtotal murni produk
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'user_id_sales' => $salesUserId,
                'amount_paid' => 0,
                'notes' => $request->input('notes'),
            ]);
            
            $invoice->taxes()->attach($taxesToAttach);
            $invoice->items()->createMany($productsToSave);
            
            // ✅ Simpan Biaya Tambahan ke tabel baru
            if (!empty($additionalCostsToSave)) {
                $invoice->additionalCosts()->createMany($additionalCostsToSave);
            }

            if ($originOrder) {
                $originOrder->status = 'invoiced';
                $originOrder->invoice_id = $invoice->invoice_id;
                $originOrder->save();
            }

            DB::commit();
            return redirect()->route('invoices.show', $invoice->invoice_id)->with('success', 'Invoice berhasil dibuat!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat invoice: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Konfirmasi invoice draft
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi
     */
    public function confirm(SalesInvoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Hanya invoice DRAFT yang bisa dikonfirmasi.');
        }

        // ✅ Validasi Akun Default
        $arAccountId = $this->accountingSettings->getAccountsReceivableId();
        $salesRevenueAccountId = $this->accountingSettings->getSalesRevenueId();
        $cogsAccountId = $this->accountingSettings->getCogsId();
        $inventoryAccountId = $this->accountingSettings->getInventoryId();

        if (!$arAccountId || !$salesRevenueAccountId || !$cogsAccountId || !$inventoryAccountId) {
            return back()->with('error', 'Gagal: Akun default (Piutang, Pendapatan, HPP, Persediaan) belum diatur di Pengaturan Akuntansi.');
        }

        try {
            DB::beginTransaction();
            
            // Logika Stok
            $itemsToDecrement = [];
            foreach ($invoice->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if (!$product) {
                    throw new \Exception("Produk '{$item->product_name}' tidak ditemukan lagi.");
                }

                $itemsToDecrement[] = [
                    'product_id' => $product->product_id,
                    'quantity' => $item->quantity
                ];
            }

            foreach ($itemsToDecrement as $item) {
                Product::where('product_id', $item['product_id'])->decrement('stock_quantity', $item['quantity']);
            }

            // Update Status Invoice
            $invoice->update(['status' => 'unpaid']);

            // ✅ Post Jurnal Akuntansi (Jurnal Penjualan & HPP)
            $journalGroupId = "INV-" . $invoice->invoice_number;
            $description = "Penjualan Invoice #" . $invoice->invoice_number . " ke " . $invoice->client->client_name;
            
            // Ambil total HPP dari item invoice
            $totalHpp = $invoice->items()->sum(DB::raw('quantity * hpp'));
            
            $debitEntries = [
                // [Akun Piutang, Total Invoice]
                [$arAccountId, $invoice->total_amount, "Piutang atas " . $invoice->client->client_name],
                // [Akun HPP, Total HPP]
                [$cogsAccountId, $totalHpp, "HPP atas Invoice #" . $invoice->invoice_number]
            ];
            $creditEntries = [
                // [Akun Pendapatan, Total Invoice]
                [$salesRevenueAccountId, $invoice->total_amount, "Pendapatan atas Invoice #" . $invoice->invoice_number],
                // [Akun Persediaan, Total HPP]
                [$inventoryAccountId, $totalHpp, "Pengurangan Persediaan"]
            ];

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
            return redirect()->route('invoices.show', $invoice->invoice_id)->with('success', 'Invoice berhasil dikonfirmasi. Stok telah dikurangi dan jurnal telah diposting.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal konfirmasi invoice: " . $e->getMessage());
            return back()->with('error', 'Gagal konfirmasi invoice: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan form edit invoice
     */
    public function edit(SalesInvoice $invoice): View
    {
        $this->authorize('update', $invoice);
        $invoice->load(['items.product', 'taxes', 'additionalCosts']); // ✅ Load additional costs
        $clients = Client::all();
        $products = Product::all();
        $taxes = Tax::where('is_active', true)->get();
        $salesUsers = User::role('sales')->get();

        return view('invoices.edit', compact('invoice', 'clients', 'products', 'taxes', 'salesUsers'));
    }

    /**
     * Mengupdate invoice
     * ✅ DIPERBARUI: Menambahkan fitur Custom Price, Update Master Price, dan Additional Costs
     * ✅ DIPERBARUI: Menambahkan validasi periode akuntansi
     */
    public function update(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        // --- 🔒 VALIDASI KEAMANAN (TRAIT) ---
        // 1. Cek Invoice Lama (Apakah terkunci?)
        $journalGroupId = "INV-" . $invoice->invoice_number;
        if ($error = $this->checkTransactionLock($invoice->order_date, $journalGroupId)) {
            return back()->with('error', "Gagal Update: " . $error);
        }

        // 2. Cek Tanggal Baru (Jika tanggal diubah ke tahun yang ditutup)
        if ($request->filled('order_date') && $this->isDateClosed($request->order_date)) {
            return back()->with('error', 'Gagal Update: Tanggal baru berada dalam periode tahun buku yang sudah ditutup.')->withInput();
        }
        // ------------------------------------

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'order_date' => 'required|date',
            'due_date' => 'required|date',
            
            // Produk
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.custom_price' => 'required|numeric|min:0', // ✅ Harga Custom
            'products.*.update_master_price' => 'nullable|boolean', // ✅ Checkbox Update Master
            
            // Biaya Tambahan
            'additional_costs' => 'nullable|array',
            'additional_costs.*.description' => 'required_with:additional_costs|string|max:255',
            'additional_costs.*.amount' => 'required_with:additional_costs|numeric|min:0',
            
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

            // Kembalikan stok lama (hanya jika status bukan draft)
            if ($invoice->status !== 'draft') {
                foreach ($invoice->items as $oldItem) {
                    $product = Product::find($oldItem->product_id);
                    if ($product) {
                        $product->increment('stock_quantity', $oldItem->quantity);
                    }
                }
            }

            // Kalkulasi ulang dengan fitur baru
            $subtotalProducts = 0;
            $productsToSave = [];
            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']);
                
                // ✅ Gunakan harga dari input user (Custom Price)
                $price = (float) $productData['custom_price'];
                $quantity = $productData['quantity'];
                $itemSubtotal = $quantity * $price;
                $subtotalProducts += $itemSubtotal;
                
                // ✅ Update Harga Master jika dicentang
                if (isset($productData['update_master_price']) && $productData['update_master_price']) {
                    $product->update(['selling_price' => $price]);
                }

                $productsToSave[] = [
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'price_per_unit' => $price,
                    'subtotal' => $itemSubtotal,
                    'hpp' => $product->average_cost ?? 0 // ✅ Pastikan HPP di-update juga
                ];
                
                // Kurangi stok baru (hanya jika status bukan draft)
                if ($invoice->status !== 'draft') {
                    $product->decrement('stock_quantity', $quantity);
                }
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

            // Update invoice
            $invoice->update([
                'client_id' => $validated['client_id'],
                'order_date' => $validated['order_date'],
                'due_date' => $validated['due_date'],
                'subtotal' => $subtotalProducts,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'notes' => $request->input('notes'),
                'amount_paid' => 0,
                'status' => 'unpaid',
            ]);

            $invoice->items()->delete();
            $invoice->payments()->delete();
            $invoice->items()->createMany($productsToSave);
            $invoice->taxes()->sync($taxesToSync);
            
            // ✅ Update Biaya Tambahan
            $invoice->additionalCosts()->delete();
            if (!empty($additionalCostsToSave)) {
                $invoice->additionalCosts()->createMany($additionalCostsToSave);
            }

            DB::commit();

            return redirect()->route('invoices.show', $invoice->invoice_id)->with('success', 'Invoice berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate invoice: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menghapus invoice
     * ✅ DIPERBARUI: Lebih aman, hanya izinkan hapus 'draft' atau 'cancelled'
     * ✅ DIPERBARUI: Menambahkan validasi periode akuntansi
     */
    public function destroy(SalesInvoice $invoice): RedirectResponse
    { 
        $this->authorize('delete', $invoice); 

        // --- 🔒 VALIDASI KEAMANAN (TRAIT) ---
        // Cek Invoice Draft sekalipun (jika tanggalnya ada di tahun tertutup)
        if ($error = $this->checkTransactionLock($invoice->order_date)) { // Draft belum punya Jurnal, jadi cek tanggal saja
            return back()->with('error', "Gagal Hapus: " . $error);
        }
        // ------------------------------------

        // ✅ Cek status. Jangan hapus invoice yg sudah terkonfirmasi.
        if (!in_array($invoice->status, ['draft', 'cancelled'])) {
             return back()->with('error', 'Invoice yang sudah aktif (unpaid/paid) tidak bisa dihapus permanen. Gunakan fitur "Batalkan".');
        }

        // Cek relasi lain jika perlu
        if ($invoice->payments()->exists() || $invoice->returns()->exists() || $invoice->adjustments()->exists()) {
             return back()->with('error', 'Invoice ini tidak bisa dihapus karena memiliki data terkait (pembayaran/retur/penyesuaian).');
        }

        try {
            DB::beginTransaction();
            // Hapus item dan pajak terkait
            $invoice->items()->delete();
            $invoice->taxes()->detach();
            $invoice->additionalCosts()->delete(); // ✅ Hapus biaya tambahan
            // Hapus invoice
            $invoice->delete();
            DB::commit();
            return redirect()->route('invoices.index')->with('success', 'Invoice draft berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus invoice: ' . $e->getMessage());
        }
    }

    /**
     * Membatalkan invoice
     * ✅ DIPERBARUI: Menambahkan Jurnal Reversal dan Kembalikan Stok
     * ✅ DIPERBARUI: Menambahkan validasi periode akuntansi
     */
    public function cancel(SalesInvoice $invoice): RedirectResponse
    {
        $this->authorize('cancel', $invoice);

        // --- 🔒 VALIDASI KEAMANAN (TRAIT) ---
        $journalGroupId = "INV-" . $invoice->invoice_number;
        if ($error = $this->checkTransactionLock($invoice->order_date, $journalGroupId)) {
            return back()->with('error', "Gagal Membatalkan: " . $error);
        }
        // ------------------------------------

        // Cek status
        if (in_array($invoice->status, ['paid', 'partially_paid'])) {
             return back()->with('error', 'Invoice yang sudah lunas atau dicicil tidak bisa dibatalkan.');
        }
        
        // Jika statusnya 'draft', batalkan saja tanpa jurnal
        if ($invoice->status === 'draft') {
            $invoice->status = 'cancelled';
            $invoice->save();
            return redirect()->route('invoices.index')->with('success', 'Invoice draft berhasil dibatalkan.');
        }

        // --- Jika status 'unpaid' atau 'overdue', lakukan Jurnal Reversal ---

        // ✅ Validasi Akun Default
        $arAccountId = $this->accountingSettings->getAccountsReceivableId();
        $salesRevenueAccountId = $this->accountingSettings->getSalesRevenueId();
        $cogsAccountId = $this->accountingSettings->getCogsId();
        $inventoryAccountId = $this->accountingSettings->getInventoryId();

        if (!$arAccountId || !$salesRevenueAccountId || !$cogsAccountId || !$inventoryAccountId) {
            return back()->with('error', 'Gagal: Akun default (Piutang, Pendapatan, HPP, Persediaan) belum diatur.');
        }

        try {
            DB::beginTransaction();
            
            // ✅ Kembalikan Stok
            foreach ($invoice->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                }
            }

            // Update Status Invoice
            $invoice->status = 'cancelled';
            $invoice->save();

            // ✅ Post Jurnal Reversal (Jurnal dibalik)
            $journalGroupId = "INV-REV-" . $invoice->invoice_number;
            $description = "Reversal/Pembatalan Invoice #" . $invoice->invoice_number;
            $totalHpp = $invoice->items()->sum(DB::raw('quantity * hpp'));

            $debitEntries = [
                // [Akun Pendapatan, Total Invoice]
                [$salesRevenueAccountId, $invoice->total_amount, "Reversal pendapatan"],
                // [Akun Persediaan, Total HPP]
                [$inventoryAccountId, $totalHpp, "Reversal persediaan"]
            ];
            $creditEntries = [
                // [Akun Piutang, Total Invoice]
                [$arAccountId, $invoice->total_amount, "Reversal piutang"],
                // [Akun HPP, Total HPP]
                [$cogsAccountId, $totalHpp, "Reversal HPP"]
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
            
            // ✅ Hapus Jurnal Asli (INV-...)
            DB::table('general_ledgers')->where('journal_group_id', "INV-" . $invoice->invoice_number)->delete();

            DB::commit();
            return redirect()->route('invoices.index')->with('success', 'Invoice berhasil dibatalkan. Stok telah dikembalikan dan jurnal telah dibalik.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal batalkan invoice: " . $e->getMessage());
            return back()->with('error', 'Gagal membatalkan invoice: ' . $e->getMessage());
        }
    }

    /**
     * Download PDF invoice
     */
    public function downloadPDF(SalesInvoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load(['client', 'items.product.unit', 'taxes', 'sales', 'additionalCosts']); // ✅ Load additional costs

        $paperSize = [0, 0, 684, 396];
        $pdf = Pdf::loadView('invoices.pdf_template', compact('invoice'));
        $pdf->setPaper($paperSize);
        $cleanInvoiceNumber = str_replace('/', '-', $invoice->invoice_number);
        $fileName = 'Invoice-' . $cleanInvoiceNumber . '.pdf';

        return $pdf->download($fileName);
    }
}