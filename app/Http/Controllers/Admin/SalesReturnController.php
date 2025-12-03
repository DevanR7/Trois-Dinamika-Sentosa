<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesReturn;
use App\Models\SalesInvoice;
use App\Models\Product;
use App\Models\InvoiceItem;
use App\Models\ClientLedger;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;

class SalesReturnController extends Controller
{
    /**
     * ===========================================================
     *  INJECT SERVICE AKUNTANSI
     * ===========================================================
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
     * ===========================================================
     *  MENAMPILKAN DAFTAR RETUR PENJUALAN
     * ===========================================================
     * - Menampilkan daftar retur dengan filter pencarian & tanggal
     * - Dapat diakses sesuai izin policy pengguna
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalesReturn::class);

        $query = SalesReturn::with(['client', 'salesInvoice']);

        // Filter pencarian berdasarkan nomor retur, nama klien, atau nomor invoice
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q_client) use ($search) {
                        $q_client->where('client_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('salesInvoice', function ($q_invoice) use ($search) {
                        $q_invoice->where('invoice_number', 'like', "%{$search}%");
                    });
            });
        }

        // Filter berdasarkan tanggal retur
        if ($request->filled('return_date')) {
            $query->whereDate('return_date', $request->return_date);
        }

        $salesReturns = $query->latest('return_date')
            ->paginate(15)
            ->appends($request->query());

        return view('admin.sales_returns.index', compact('salesReturns'));
    }

    /**
     * ===========================================================
     *  FORM PEMBUATAN RETUR PENJUALAN BARU
     * ===========================================================
     * - Menampilkan daftar invoice yang valid untuk diretur
     */
    public function create(): View
    {
        $this->authorize('create', SalesReturn::class);

        $invoices = SalesInvoice::whereNotIn('status', ['draft', 'cancelled'])
            ->orderBy('order_date', 'desc')
            ->get();

        return view('admin.sales_returns.create', compact('invoices'));
    }

    /**
     * ===========================================================
     *  MENYIMPAN RETUR PENJUALAN BARU
     * ===========================================================
     * - Validasi data retur
     * - Hitung nilai retur dan stok
     * - Hitung HPP dan posting jurnal akuntansi
     * - Simpan data secara transaksional
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SalesReturn::class);

        $validated = $request->validate([
            'sales_invoice_id' => 'required|exists:sales_invoices,invoice_id',
            'return_date' => 'required|date',
            'return_handling_type' => 'required|in:deduct_invoice,store_as_credit',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:invoice_items,item_id',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        // Validasi Akun Akuntansi
        $arAccountId = $this->accountingSettings->getAccountsReceivableId();
        $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
        $salesReturnAccountId = $this->accountingSettings->getSalesReturnId();
        $inventoryAccountId = $this->accountingSettings->getInventoryId();
        $cogsAccountId = $this->accountingSettings->getCogsId();

        if (!$arAccountId || !$clientDepositAccountId || !$salesReturnAccountId || !$inventoryAccountId || !$cogsAccountId) {
            return back()->with('error', 'Gagal: Akun default akuntansi (Piutang, Deposit, Retur, Persediaan, HPP) belum lengkap diatur.')->withInput();
        }

        DB::beginTransaction();

        try {
            $invoice = SalesInvoice::with('client')->findOrFail($validated['sales_invoice_id']);
            $discountRate = $invoice->discount_percentage / 100;
            $totalReturnValue = 0;
            $totalHppValue = 0; // ✅ BARU: Variabel untuk total HPP
            $hasReturnedItems = false;
            $handlingType = $validated['return_handling_type'];

            /**
             * -----------------------------------------------------------
             * Tahap 1: Validasi item dan hitung total nilai retur & HPP
             * -----------------------------------------------------------
             */
            foreach ($validated['items'] as $itemData) {
                if (!empty($itemData['quantity']) && $itemData['quantity'] > 0) {
                    $hasReturnedItems = true;
                    $originalItem = InvoiceItem::find($itemData['item_id']);

                    $maxQty = $originalItem->quantity - $originalItem->quantity_returned;
                    if ($itemData['quantity'] > $maxQty) {
                        throw new \Exception("Jumlah retur melebihi batas untuk produk " . $originalItem->product->product_name);
                    }

                    // Hitung nilai retur (jual)
                    $priceAfterDiscount = $originalItem->price_per_unit * (1 - $discountRate);
                    $subtotal = $itemData['quantity'] * $priceAfterDiscount;
                    $totalReturnValue += $subtotal;

                    // ✅ BARU: Hitung nilai HPP retur
                    $itemHpp = $originalItem->hpp ?? 0;
                    $totalHppValue += $itemData['quantity'] * $itemHpp;
                }
            }

            if (!$hasReturnedItems) {
                throw new \Exception("Tidak ada item yang dipilih untuk diretur.");
            }

            /**
             * -----------------------------------------------------------
             * Tahap 2: Koreksi tipe handling jika nilai retur melebihi sisa tagihan
             * -----------------------------------------------------------
             */
            /*
            $sisaTagihanInvoice = $invoice->remaining_balance;
            if ($handlingType == 'deduct_invoice' && $totalReturnValue > $sisaTagihanInvoice) {
                $handlingType = 'store_as_credit';
            }
            */

            /**
             * -----------------------------------------------------------
             * Tahap 3: Simpan data retur utama
             * -----------------------------------------------------------
             * ✅ DIPERBARUI: Menyimpan total_hpp_amount
             */
            $salesReturn = SalesReturn::create([
                'return_number' => SalesReturn::generateReturnNumber(),
                'client_id' => $invoice->client_id,
                'sales_invoice_id' => $invoice->invoice_id,
                'user_id' => Auth::id(),
                'return_date' => $validated['return_date'],
                'return_handling_type' => $handlingType,
                'notes' => $validated['notes'],
                'total_amount' => $totalReturnValue,
                'total_hpp_amount' => $totalHppValue, // ✅ SIMPAN HPP
            ]);

            /**
             * -----------------------------------------------------------
             * Tahap 4: Simpan item retur dan update stok
             * -----------------------------------------------------------
             */
            foreach ($validated['items'] as $itemData) {
                if (!empty($itemData['quantity']) && $itemData['quantity'] > 0) {
                    $originalItem = InvoiceItem::find($itemData['item_id']);
                    $priceAfterDiscount = $originalItem->price_per_unit * (1 - $discountRate);
                    $subtotal = $itemData['quantity'] * $priceAfterDiscount;

                    $salesReturn->items()->create([
                        'product_id' => $originalItem->product_id,
                        'quantity' => $itemData['quantity'],
                        'price_per_unit' => $priceAfterDiscount,
                        'subtotal' => $subtotal,
                    ]);

                    // Update jumlah retur dan stok produk
                    $originalItem->increment('quantity_returned', $itemData['quantity']);

                    $product = Product::find($originalItem->product_id);
                    if ($product) {
                        $product->increment('stock_quantity', $itemData['quantity']);
                    }
                }
            }

            /**
             * -----------------------------------------------------------
             * Tahap 5: Catat kredit jika handling = "store_as_credit"
             * -----------------------------------------------------------
             */
            if ($handlingType == 'store_as_credit') {
                $ledgerStatus = ($invoice->status == 'paid') ? 'available' : 'pending';
                ClientLedger::create([
                    'client_id' => $invoice->client_id,
                    'sales_invoice_id' => $invoice->invoice_id,
                    'reference_type' => SalesReturn::class,
                    'reference_id' => $salesReturn->return_id,
                    'transaction_date' => $validated['return_date'],
                    'type' => 'credit',
                    'amount' => $totalReturnValue,
                    'status' => $ledgerStatus,
                    'description' => 'Kredit dari Retur Penjualan #' . $salesReturn->return_number
                        . ($ledgerStatus == 'pending' ? ' (Ditahan)' : ''),
                    'user_id' => Auth::id(),
                ]);
            }

            /**
             * -----------------------------------------------------------
             * Tahap 6: Perbarui status invoice
             * -----------------------------------------------------------
             */
            $invoice->updatePaymentStatus();

            /**
             * -----------------------------------------------------------
             * ✅ Tahap 7: Post Jurnal Akuntansi (BARU)
             * -----------------------------------------------------------
             * Jurnal 4-langkah untuk membalik jurnal penjualan
             */
            $journalGroupId = "SRET-" . $salesReturn->return_number;
            $description = "Retur Penjualan Inv #" . $invoice->invoice_number;

            $debitEntries = [];
            $creditEntries = [];

            // 1. (Debit) Retur Penjualan (sebesar nilai jual)
            $debitEntries[] = [$salesReturnAccountId, $totalReturnValue, "Retur atas Inv #" . $invoice->invoice_number];
            
            // 2. (Debit) Persediaan (sebesar nilai HPP)
            $debitEntries[] = [$inventoryAccountId, $totalHppValue, "Pengembalian barang HPP"];

            // 3. (Kredit) Piutang ATAU Deposit
            if ($handlingType == 'store_as_credit') {
                $creditEntries[] = [$clientDepositAccountId, $totalReturnValue, "Pengembalian ke deposit klien"];
            } else { // deduct_invoice
                $creditEntries[] = [$arAccountId, $totalReturnValue, "Pengurangan piutang Inv #" . $invoice->invoice_number];
            }
            
            // 4. (Kredit) HPP (membalikkan beban HPP)
            $creditEntries[] = [$cogsAccountId, $totalHppValue, "Reversal HPP atas retur"];
            
            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['return_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $salesReturn,
                Auth::id() // Model referensi
            );

            DB::commit();

            return redirect()
                ->route('admin.sales-returns.index')
                ->with('success', 'Retur penjualan berhasil disimpan dan dijurnal.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan retur penjualan: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()
                ->with('error', 'Gagal menyimpan retur: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * ===========================================================
     *  DETAIL RETUR PENJUALAN
     * ===========================================================
     * - Menampilkan informasi lengkap retur dan relasinya
     */
    public function show(SalesReturn $salesReturn): View
    {
        $this->authorize('view', $salesReturn);

        $salesReturn->load(['client', 'salesInvoice', 'user', 'items.product.unit']);

        return view('admin.sales_returns.show', compact('salesReturn'));
    }

    /**
     * ===========================================================
     *  MENGHAPUS RETUR PENJUALAN
     * ===========================================================
     * - Mengembalikan stok dan kuantitas item
     * - Menghapus ledger (jika ada)
     * - Posting jurnal reversal dan hapus jurnal asli
     * - Memperbarui status invoice
     */
    public function destroy(SalesReturn $salesReturn): RedirectResponse
    {
        $this->authorize('delete', $salesReturn);

        DB::beginTransaction();

        try {
            $invoice_id = $salesReturn->sales_invoice_id;

            /**
             * -----------------------------------------------------------
             * Tahap 1: Hapus entri ledger jika tipe handling adalah kredit
             * -----------------------------------------------------------
             */
            if ($salesReturn->return_handling_type == 'store_as_credit') {
                ClientLedger::where('reference_type', SalesReturn::class)
                    ->where('reference_id', $salesReturn->return_id)
                    ->delete();
            }

            /**
             * -----------------------------------------------------------
             * Tahap 2: Kembalikan stok dan kuantitas yang diretur
             * -----------------------------------------------------------
             */
            foreach ($salesReturn->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->decrement('stock_quantity', $item->quantity);
                }

                $originalItem = InvoiceItem::where('invoice_id', $salesReturn->sales_invoice_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($originalItem) {
                    $originalItem->decrement('quantity_returned', $item->quantity);
                }
            }

            /**
             * -----------------------------------------------------------
             * ✅ Tahap 3: Post Jurnal Reversal (BARU)
             * -----------------------------------------------------------
             */
            $journalGroupId = "SRET-REV-" . $salesReturn->return_number;
            $description = "Reversal Retur Penjualan #" . $salesReturn->return_number;
            
            // Ambil data dari jurnal asli
            $originalJournalEntries = DB::table('general_ledgers')
                                        ->where('journal_group_id', "SRET-" . $salesReturn->return_number)
                                        ->get();
            $debitEntries = [];
            $creditEntries = [];
            
            foreach ($originalJournalEntries as $entry) {
                /** @var \App\Models\GeneralLedger $entry */
                if ($entry->debit > 0) {
                    $creditEntries[] = [$entry->chart_of_account_id, $entry->debit, "Reversal: " . $entry->description];
                }
                if ($entry->credit > 0) {
                    $debitEntries[] = [$entry->chart_of_account_id, $entry->credit, "Reversal: " . $entry->description];
                }
            }

            if (!empty($debitEntries) || !empty($creditEntries)) {
                $this->accountingService->postJournal(
                    $journalGroupId,
                    now(), // Tanggal reversal
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $salesReturn,
                    Auth::id()
                );
            }
            
            /**
             * -----------------------------------------------------------
             * ✅ Tahap 4: Hapus Jurnal Asli (BARU)
             * -----------------------------------------------------------
             */
            DB::table('general_ledgers')->where('journal_group_id', "SRET-" . $salesReturn->return_number)->delete();

            /**
             * -----------------------------------------------------------
             * Tahap 5: Hapus data retur
             * -----------------------------------------------------------
             */
            $salesReturn->delete();

            /**
             * -----------------------------------------------------------
             * Tahap 6: Perbarui status invoice setelah retur dihapus
             * -----------------------------------------------------------
             */
            $invoice = SalesInvoice::find($invoice_id);
            if ($invoice) {
                $invoice->updatePaymentStatus();
            }

            DB::commit();

            return redirect()
                ->route('admin.sales-returns.index')
                ->with('success', 'Retur penjualan berhasil dibatalkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal batalkan retur penjualan: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal membatalkan retur: ' . $e->getMessage());
        }
    }
}