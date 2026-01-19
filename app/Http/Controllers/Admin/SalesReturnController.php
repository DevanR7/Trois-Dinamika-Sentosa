<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesReturn;
use App\Models\SalesInvoice;
use App\Models\Product;
use App\Models\ClientLedger;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;
use App\Traits\ValidatesAccountingPeriod;

class SalesReturnController extends Controller
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

        $this->middleware('can:view-sales-returns')->only(['index', 'show']);
        $this->middleware('can:create-sales-returns')->only(['create', 'store']);
        $this->middleware('can:delete-sales-returns')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalesReturn::class);

        $query = SalesReturn::with(['client', 'salesInvoice']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($q) => $q->where('client_name', 'like', "%{$search}%"))
                  ->orWhereHas('salesInvoice', fn($q) => $q->where('invoice_number', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('return_date')) {
            $query->whereDate('return_date', $request->return_date);
        }

        $salesReturns = $query->latest('return_date')
                              ->paginate(15)
                              ->appends($request->query());

        return view('admin.sales_returns.index', compact('salesReturns'));
    }

    public function create(): View
    {
        $this->authorize('create', SalesReturn::class);
        
        // Hanya Invoice yang sudah ada statusnya (bukan draft/cancelled)
        $invoices = SalesInvoice::where('status', '!=', 'draft')
            ->where('status', '!=', 'cancelled')
            ->orderBy('order_date', 'desc')
            ->get();
            
        return view('admin.sales_returns.create', compact('invoices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SalesReturn::class);

        $validated = $request->validate([
            'sales_invoice_id' => 'required|exists:sales_invoices,invoice_id',
            'return_date' => 'required|date',
            'return_handling_type' => 'required|in:deduct_invoice,store_as_credit',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:invoice_items,item_id',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        if ($this->isDateClosed($request->return_date)) {
            return back()->with('error', 'Gagal: Tanggal retur masuk periode tutup buku.')->withInput();
        }

        // Setup Akun COA
        $arAccountId = $this->accountingSettings->getAccountsReceivableId();
        $salesReturnAccountId = $this->accountingSettings->getSalesReturnId();
        $inventoryAccountId = $this->accountingSettings->getInventoryId();
        $cogsAccountId = $this->accountingSettings->getCogsId();
        $clientDepositAccountId = $this->accountingSettings->getClientDepositId();

        if (!$arAccountId || !$salesReturnAccountId || !$inventoryAccountId || !$cogsAccountId || !$clientDepositAccountId) {
            return back()->with('error', 'Gagal: Akun default (Piutang, Retur Penjualan, Persediaan, HPP, Deposit Klien) belum lengkap.')->withInput();
        }

        DB::beginTransaction();
        try {
            $invoice = SalesInvoice::lockForUpdate()->with('items.product')->findOrFail($validated['sales_invoice_id']);

            $totalReturnValue = 0;
            $totalHppValue = 0;
            $hasReturnedItems = false;
            $handlingType = $validated['return_handling_type'];

            // 1. Buat Header Retur
            $salesReturn = SalesReturn::create([
                'return_number' => SalesReturn::generateReturnNumber(),
                'client_id' => $invoice->client_id,
                'sales_invoice_id' => $invoice->invoice_id,
                'user_id' => Auth::id(),
                'return_date' => $validated['return_date'],
                'return_handling_type' => $handlingType,
                'notes' => $validated['notes'],
                'total_amount' => 0, 
                'total_hpp_amount' => 0,
            ]);

            // 2. Loop Item
            foreach ($validated['items'] as $itemData) {
                if (empty($itemData['quantity']) || $itemData['quantity'] <= 0) continue;

                $originalItem = $invoice->items()->where('item_id', $itemData['item_id'])->first();
                if (!$originalItem) continue;

                // Cek Batas Retur
                $maxQty = $originalItem->quantity - $originalItem->quantity_returned;
                if ($itemData['quantity'] > $maxQty) {
                    throw new \Exception("Jumlah retur melebihi batas untuk produk " . ($originalItem->product->product_name ?? 'Item'));
                }

                $hasReturnedItems = true;
                
                // === [FIX PERHITUNGAN HARGA DENGAN DISKON GLOBAL] ===
                $basePrice = $originalItem->price_per_unit; // Harga satuan item di invoice (sebelum diskon global)
                
                // Ambil Diskon Global Invoice (%)
                $globalDisc = $invoice->discount_percentage;
                
                // Hitung Harga Netto Per Item setelah diskon global
                $netPricePerUnit = $basePrice;
                if ($globalDisc > 0) {
                    $netPricePerUnit = $basePrice * (1 - ($globalDisc / 100));
                }
                $netPricePerUnit = round($netPricePerUnit, 2);

                // Subtotal menggunakan harga setelah diskon
                $subtotal = round($itemData['quantity'] * $netPricePerUnit, 2);
                $totalReturnValue += $subtotal;
                // ====================================================

                $hppPerUnit = $originalItem->hpp; 
                $subtotalHpp = round($itemData['quantity'] * $hppPerUnit, 2);
                $totalHppValue += $subtotalHpp;

                // Simpan Item Retur
                $salesReturn->items()->create([
                    'product_id' => $originalItem->product_id,
                    'quantity' => $itemData['quantity'],
                    'price_per_unit' => $netPricePerUnit, // Simpan harga efektif
                    'subtotal' => $subtotal,
                ]);

                // Update Counter di Invoice Item
                $originalItem->increment('quantity_returned', $itemData['quantity']);

                // Kembalikan Stok ke Gudang
                $product = Product::lockForUpdate()->find($originalItem->product_id);
                if ($product) {
                    $currentStock = $product->stock_quantity;
                    $currentCost  = $product->average_cost;
                    $qtyReturned = $itemData['quantity'];
                    $costReturned = $hppPerUnit;

                    if ($currentStock < 0) {
                        $newAvgCost = $costReturned;
                        $totalQty = $currentStock + $qtyReturned;
                    } else {
                        // Weighted Average saat barang kembali
                        $totalValue = ($currentStock * $currentCost) + ($qtyReturned * $costReturned);
                        $totalQty   = $currentStock + $qtyReturned;
                        $newAvgCost = ($totalQty > 0) ? $totalValue / $totalQty : 0;
                    }

                    $product->stock_quantity = $totalQty;
                    $product->average_cost   = round($newAvgCost, 4);
                    $product->save();
                }
            }

            if (!$hasReturnedItems) {
                throw new \Exception("Tidak ada item valid untuk diretur.");
            }

            $salesReturn->update([
                'total_amount' => $totalReturnValue,
                'total_hpp_amount' => $totalHppValue
            ]);

            // 3. Jurnal Akuntansi Dasar (Apapun metodenya, jurnal retur harus terjadi)
            $journalGroupId = "SAL-RET-" . $salesReturn->return_number;
            $description = "Retur Penjualan #" . $salesReturn->return_number . " (Inv #" . $invoice->invoice_number . ")";

            $debitEntries = [
                [$salesReturnAccountId, $totalReturnValue, "Retur Penjualan Inv #" . $invoice->invoice_number],
                [$inventoryAccountId, $totalHppValue, "Persediaan Masuk (Retur)"]
            ];
            
            // Kredit ke AR dulu (mengurangi tagihan secara akuntansi)
            $creditEntries = [
                [$arAccountId, $totalReturnValue, "Potong Piutang Retur"],
                [$cogsAccountId, $totalHppValue, "Reversal HPP Retur"]
            ];

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['return_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $salesReturn,
                Auth::id()
            );

            // 4. Handling Deposit / Credit (FIX STATUS PENDING/AVAILABLE)
            if ($handlingType === 'store_as_credit') {
                
                // Cek Status Invoice saat ini
                $isInvoicePaid = ($invoice->status === 'paid');
                
                // Jika Invoice BELUM Lunas, Deposit harus PENDING (Tertahan)
                // Jika Invoice SUDAH Lunas, Deposit langsung AVAILABLE
                $ledgerStatus = $isInvoicePaid ? 'available' : 'pending';
                
                $descLedger = "Deposit dari Retur Penjualan #{$salesReturn->return_number}";
                if (!$isInvoicePaid) {
                    $descLedger .= " (Tertahan: Menunggu Pelunasan Invoice)";
                }

                // Buat Ledger Client
                $clientLedger = ClientLedger::create([
                    'client_id' => $invoice->client_id,
                    'sales_invoice_id' => $invoice->invoice_id,
                    'reference_type' => SalesReturn::class,
                    'reference_id' => $salesReturn->return_id,
                    'transaction_date' => $validated['return_date'],
                    'type' => 'credit', // Kredit = Menambah saldo deposit klien
                    'amount' => $totalReturnValue, // Nilai yang sudah didiskon
                    'status' => $ledgerStatus, 
                    'description' => $descLedger,
                    'user_id' => Auth::id(),
                ]);

                // Jurnal Pindah AR ke Deposit (Secara Akuntansi, kita pindahkan hutang kita dari AR ke Deposit Liability)
                $depGroupId = "CLI-DEP-" . $clientLedger->ledger_id;
                $depDebit = [[$arAccountId, $totalReturnValue, "Pindah Retur ke Deposit"]];
                $depCredit = [[$clientDepositAccountId, $totalReturnValue, "Deposit Klien (Retur)"]];

                $this->accountingService->postJournal(
                    $depGroupId,
                    $validated['return_date'],
                    "Deposit Retur Penjualan #" . $salesReturn->return_number,
                    $depDebit,
                    $depCredit,
                    $clientLedger,
                    Auth::id()
                );
            } 

            // 5. Update Invoice Status (Penting agar sinkron)
            $invoice->updatePaymentStatus();

            DB::commit();
            return redirect()->route('admin.sales-returns.index')
                ->with('success', 'Retur penjualan berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan retur penjualan: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan retur: ' . $e->getMessage())->withInput();
        }
    }

    public function show(SalesReturn $salesReturn): View
    {
        $this->authorize('view', $salesReturn);
        $salesReturn->load(['client', 'salesInvoice', 'user', 'items.product.unit']);
        return view('admin.sales_returns.show', compact('salesReturn'));
    }

    public function destroy(SalesReturn $salesReturn): RedirectResponse
    {
        $this->authorize('delete', $salesReturn);

        $journalGroupId = "SAL-RET-" . $salesReturn->return_number;

        if ($error = $this->checkTransactionLock($salesReturn->return_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus: " . $error);
        }

        DB::beginTransaction();
        try {
            // 1. Reversal Jurnal Utama
            $this->reverseAndClearJournal($journalGroupId, "Reversal Retur Jual #" . $salesReturn->return_number, $salesReturn);

            // 2. Reversal Deposit (Jika ada)
            if ($salesReturn->return_handling_type === 'store_as_credit') {
                $ledger = ClientLedger::where('reference_type', SalesReturn::class)
                    ->where('reference_id', $salesReturn->return_id)
                    ->first();
                
                if ($ledger) {
                    $this->reverseAndClearJournal("CLI-DEP-" . $ledger->ledger_id, "Reversal Deposit Retur Jual", $ledger);
                    $ledger->delete();
                }
            }

            // 3. Kembalikan Stok & Data Item
            foreach ($salesReturn->items as $item) {
                // Ambil produk (termasuk yang soft delete)
                $product = Product::withTrashed()->lockForUpdate()->find($item->product_id);
                
                if ($product) {
                    // Karena batal retur, stok harus keluar lagi
                    $product->decrement('stock_quantity', $item->quantity);
                }

                // Kembalikan counter quantity_returned di Invoice Item
                $invItem = InvoiceItem::where('invoice_id', $salesReturn->sales_invoice_id)
                    ->where('product_id', $item->product_id)
                    ->first();
                
                if ($invItem) {
                    $invItem->decrement('quantity_returned', $item->quantity);
                }
            }

            $invoice = $salesReturn->salesInvoice;
            
            // 4. Hapus Header Retur
            $salesReturn->delete();

            // 5. Update Status Invoice
            if ($invoice) {
                $invoice->updatePaymentStatus();
            }

            DB::commit();
            return redirect()->route('admin.sales-returns.index')
                ->with('success', 'Retur penjualan berhasil dibatalkan. Stok dikembalikan (dikurangi kembali).');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal hapus retur penjualan: ' . $e->getMessage());
            return back()->with('error', 'Gagal membatalkan: ' . $e->getMessage());
        }
    }

    private function reverseAndClearJournal(string $journalGroupId, string $reversalDescription, $referenceModel)
    {
        $originalEntries = DB::table('general_ledgers')
                             ->where('journal_group_id', $journalGroupId)
                             ->get();
        
        if ($originalEntries->isEmpty()) return;

        $debitEntries = [];
        $creditEntries = [];

        foreach ($originalEntries as $entry) {
            // Balik posisi: Debit jadi Kredit, Kredit jadi Debit
            if ($entry->debit > 0) {
                $creditEntries[] = [$entry->chart_of_account_id, $entry->debit, "Reversal: " . $entry->description];
            }
            if ($entry->credit > 0) {
                $debitEntries[] = [$entry->chart_of_account_id, $entry->credit, "Reversal: " . $entry->description];
            }
        }

        if (!empty($debitEntries) || !empty($creditEntries)) {
            $this->accountingService->postJournal(
                str_replace(['SAL-RET-', 'CLI-DEP-'], ['SAL-RET-REV-', 'CLI-DEP-REV-'], $journalGroupId), 
                now(), 
                $reversalDescription, 
                $debitEntries, 
                $creditEntries, 
                $referenceModel
            );
        }

        DB::table('general_ledgers')->where('journal_group_id', $journalGroupId)->delete();
    }
}