<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseReturn;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\SupplierLedger;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;

class PurchaseReturnController extends Controller
{
    protected $accountingService;
    protected $accountingSettings;

    public function __construct(
        AccountingService $accountingService, 
        AccountingSettingService $accountingSettingService
    ) {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettingService;

        $this->middleware('can:view-purchase-returns')->only(['index', 'show']);
        $this->middleware('can:create-purchase-returns')->only(['create', 'store']);
        $this->middleware('can:delete-purchase-returns')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseReturn::class);

        // [UPDATE] Eager load 'items.product.unit' untuk fitur accordion preview
        $query = PurchaseReturn::with(['supplier', 'purchaseOrder', 'items.product.unit', 'user']);

        // Filter Pencarian Global
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', fn($q) => $q->where('supplier_name', 'like', "%{$search}%"))
                  ->orWhereHas('purchaseOrder', fn($q) => $q->where('po_number', 'like', "%{$search}%"));
            });
        }

        // Filter Tanggal
        if ($request->filled('return_date')) {
            $query->whereDate('return_date', $request->return_date);
        }

        // [TAMBAHAN] Filter Supplier Spesifik (Dropdown)
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // [TAMBAHAN] Filter Tipe Penanganan
        if ($request->filled('return_handling_type')) {
            $query->where('return_handling_type', $request->return_handling_type);
        }

        $purchaseReturns = $query->latest('return_date')
                                 ->latest('return_id')
                                 ->paginate(15)
                                 ->appends($request->query());

        // [TAMBAHAN] Data untuk Dropdown Filter
        $suppliers = \App\Models\Supplier::orderBy('supplier_name')->get(['supplier_id', 'supplier_name']);

        return view('admin.purchase_returns.index', compact('purchaseReturns', 'suppliers'));
    }

    public function create(Request $request): View
{
    $this->authorize('create', PurchaseReturn::class);
    
    // 1. Ambil Purchase Order yang statusnya 'completed' (Barang sudah diterima)
    $purchaseOrders = PurchaseOrder::where('status', 'completed')
        ->with('supplier') // Eager load untuk performa
        ->orderBy('order_date', 'desc')
        ->get();

    // 2. Tangkap ID dari URL (jika diklik dari halaman index)
    $preselectedPoId = $request->query('purchase_order_id');
        
    // 3. Kirim variabel ke View
    return view('admin.purchase_returns.create', compact('purchaseOrders', 'preselectedPoId'));
}

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PurchaseReturn::class);

        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,po_id',
            'return_date' => 'required|date',
            'return_handling_type' => 'required|in:deduct_invoice,store_as_deposit',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:purchase_order_items,item_id',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        $apAccountId = $this->accountingSettings->getAccountsPayableId();
        $purchaseReturnAccountId = $this->accountingSettings->getPurchaseReturnId();
        $inventoryAccountId = $this->accountingSettings->getInventoryId();
        $supplierDepositAccountId = $this->accountingSettings->getSupplierDepositId();

        // [DEBUGGING] Cek mana yang null
        $missingAccounts = [];
        if (!$apAccountId) $missingAccounts[] = 'Hutang Dagang (AP)';
        if (!$purchaseReturnAccountId) $missingAccounts[] = 'Retur Pembelian';
        if (!$inventoryAccountId) $missingAccounts[] = 'Persediaan';
        if (!$supplierDepositAccountId) $missingAccounts[] = 'Deposit Supplier';

        if (!empty($missingAccounts)) {
            $msg = 'Gagal: Akun default berikut belum diatur di Pengaturan: ' . implode(', ', $missingAccounts);
            return back()->with('error', $msg)->withInput();
        }

        DB::beginTransaction();

        try {
            $purchaseOrder = PurchaseOrder::with(['tax', 'supplier'])->findOrFail($validated['purchase_order_id']);
            $totalReturnValue = 0;
            $hasReturnedItems = false;
            $handlingType = $validated['return_handling_type'];

            // 1. Buat Header Retur
            $purchaseReturn = PurchaseReturn::create([
                'return_number' => PurchaseReturn::generateReturnNumber(),
                'supplier_id' => $purchaseOrder->supplier_id,
                'purchase_order_id' => $purchaseOrder->po_id,
                'user_id' => Auth::id(),
                'return_date' => $validated['return_date'],
                'return_handling_type' => $handlingType,
                'notes' => $validated['notes'],
                'total_amount' => 0, 
            ]);

            // 2. Loop Item
            foreach ($validated['items'] as $itemData) {
                if (empty($itemData['quantity']) || $itemData['quantity'] <= 0) continue;

                $originalItem = PurchaseOrderItem::with('discounts')->find($itemData['item_id']);
                $maxQty = $originalItem->quantity - $originalItem->quantity_returned;
                if ($itemData['quantity'] > $maxQty) {
                    throw new \Exception("Jumlah retur melebihi batas untuk {$originalItem->product->product_name}");
                }

                $hasReturnedItems = true;

                // Hitung Nilai Retur (Berdasarkan harga beli ASLI di PO)
                $netPricePerUnit = $originalItem->price_per_unit;
                foreach ($originalItem->discounts as $discount) {
                    $netPricePerUnit *= (1 - ($discount->percentage / 100));
                }

                if ($purchaseOrder->tax) {
                    $dppFactor = $purchaseOrder->custom_dpp_factor ?? (1 / 1.11);
                    $taxRate = $purchaseOrder->tax->rate ?? 11;
                    $dpp = round($netPricePerUnit * $dppFactor);
                    $ppn = round($dpp * ($taxRate / 100));
                    $totalValuePerUnit = $netPricePerUnit + $ppn;
                } else {
                    $totalValuePerUnit = $netPricePerUnit;
                }

                $subtotal = $itemData['quantity'] * $totalValuePerUnit;
                $totalReturnValue += $subtotal;

                $purchaseReturn->items()->create([
                    'product_id' => $originalItem->product_id,
                    'quantity' => $itemData['quantity'],
                    'price_per_unit' => $totalValuePerUnit,
                    'subtotal' => $subtotal,
                ]);

                // Update Counter di PO Item
                $originalItem->increment('quantity_returned', $itemData['quantity']);

                // --- REVISI: UPDATE STOK ---
                // Kurangi stok fisik. HPP tidak berubah.
                $product = Product::lockForUpdate()->find($originalItem->product_id);
                if ($product) {
                    $product->decrement('stock_quantity', $itemData['quantity']);
                }
            }

            if (!$hasReturnedItems) {
                throw new \Exception("Tidak ada item yang dipilih untuk diretur.");
            }

            $purchaseReturn->update(['total_amount' => $totalReturnValue]);

            // 3. Jurnal Akuntansi
            $journalGroupId = "PUR-RET-" . $purchaseReturn->return_id;
            $description = "Retur Pembelian #" . $purchaseReturn->return_number . " untuk PO #" . $purchaseOrder->po_number;
            
            // Debit: Hutang Usaha (Mengurangi Hutang ke Supplier)
            $debitEntries = [[$apAccountId, $totalReturnValue, "Potongan hutang dari retur PO #" . $purchaseOrder->po_number]];
            
            // Kredit: Persediaan (Nilai barang keluar)
            $creditEntries = [[$inventoryAccountId, $totalReturnValue, "Pengembalian persediaan dari retur #" . $purchaseReturn->return_number]];

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['return_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $purchaseReturn,
                Auth::id()
            );

            // 4. Handle Deposit / Deduct Invoice
            if ($handlingType === 'store_as_deposit') {
                $ledgerStatus = ($purchaseOrder->payment_status === 'paid') ? 'available' : 'pending';
                $descLedger = "Deposit dari Retur Pembelian #{$purchaseReturn->return_number}";
                if ($ledgerStatus === 'pending') $descLedger .= ' (Ditahan)';

                $supplierLedger = SupplierLedger::create([
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'purchase_order_id' => $purchaseOrder->po_id,
                    'reference_type' => PurchaseReturn::class,
                    'reference_id' => $purchaseReturn->return_id,
                    'transaction_date' => $validated['return_date'],
                    'type' => 'credit',
                    'amount' => $totalReturnValue,
                    'status' => $ledgerStatus,
                    'description' => $descLedger,
                    'user_id' => Auth::id(),
                ]);

                // Jika status available, jurnal pemindahan dari Retur (AP) ke Deposit (Asset)
                // Sebenarnya jurnal diatas (Debit AP) sudah mengurangi hutang.
                // Sekarang kita harus Debit Deposit dan Kredit AP (karena uangnya jadi deposit, bukan lunas hutang).
                // Atau lebih simpel: Jurnal pertama tadi sudah mengurangi hutang.
                // Jika jadi deposit, berarti: Debit Deposit, Kredit ??
                // LOGIKA YANG BENAR:
                // Jurnal 1 (atas): Debit AP, Kredit Inventory. (Hutang berkurang karena barang kembali).
                // Jika store as deposit: Berarti Supplier BERHUTANG ke kita (Deposit).
                // Maka: Debit Deposit Supplier, Kredit AP.
                // Net effect AP: 0 (Hutang PO tetap ada, tapi kita punya Aset Deposit).
                
                if ($ledgerStatus === 'available') {
                    $this->accountingService->postJournal(
                        "SUP-DEP-" . $supplierLedger->ledger_id,
                        $validated['return_date'],
                        "Deposit dari retur pembelian #" . $purchaseReturn->return_number,
                        [[$supplierDepositAccountId, $totalReturnValue, "Deposit dari retur PO #" . $purchaseOrder->po_number]],
                        [[$apAccountId, $totalReturnValue, "Pindah ke deposit supplier"]],
                        $supplierLedger,
                        Auth::id()
                    );
                }

            } else {
                // Deduct Invoice (Update PO Total Returned -> Mengurangi sisa hutang PO ini)
                $totalReturDipotong = $purchaseOrder->returns()
                    ->where('return_handling_type', 'deduct_invoice')
                    ->sum('total_amount');

                $purchaseOrder->update(['total_returned' => $totalReturDipotong]);
                $purchaseOrder->refresh();
                
                $sisaUtang = $purchaseOrder->total_amount - $purchaseOrder->total_returned - $purchaseOrder->amount_paid;
                $purchaseOrder->payment_status = $sisaUtang <= 0.01 ? 'paid' : ($purchaseOrder->amount_paid > 0 ? 'partially_paid' : 'unpaid');
                $purchaseOrder->save();
            }

            DB::commit();
            return redirect()->route('admin.purchase-returns.index')
                ->with('success', 'Retur pembelian berhasil disimpan. Nomor: ' . $purchaseReturn->return_number);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan retur pembelian: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan retur: ' . $e->getMessage())->withInput();
        }
    }

    public function show(PurchaseReturn $purchaseReturn): View
    {
        $this->authorize('view', $purchaseReturn);
        $purchaseReturn->load(['supplier', 'purchaseOrder', 'user', 'items.product.unit']);
        return view('admin.purchase_returns.show', compact('purchaseReturn'));
    }

    public function destroy(PurchaseReturn $purchaseReturn): RedirectResponse
    {
        $this->authorize('delete', $purchaseReturn);

        DB::beginTransaction();
        try {
            $purchaseOrder = $purchaseReturn->purchaseOrder;
            $isDeductInvoice = $purchaseReturn->return_handling_type === 'deduct_invoice';

            // Reversal Jurnal Utama
            $this->reverseAndClearJournal("PUR-RET-" . $purchaseReturn->return_id, "Reversal Retur #" . $purchaseReturn->return_number, $purchaseReturn);

            if ($purchaseReturn->return_handling_type === 'store_as_deposit') {
                $supplierLedger = SupplierLedger::where('reference_type', PurchaseReturn::class)
                    ->where('reference_id', $purchaseReturn->return_id)
                    ->first();
                if ($supplierLedger) {
                    $this->reverseAndClearJournal("SUP-DEP-" . $supplierLedger->ledger_id, "Reversal Deposit dari Retur #" . $purchaseReturn->return_number, $supplierLedger);
                    $supplierLedger->delete();
                }
            } else {
                if ($purchaseOrder && $purchaseOrder->payment_status === 'paid') {
                    $sisaUtang = $purchaseOrder->total_amount - $purchaseOrder->total_returned - $purchaseOrder->amount_paid;
                    // Cek safety: jika pembatalan retur menyebabkan hutang muncul kembali pada PO yang sudah lunas, itu valid.
                    // Tidak perlu throw exception kecuali ada aturan bisnis khusus.
                }
            }

            foreach ($purchaseReturn->items as $item) {
                // Kembalikan stok (Barang masuk lagi seolah tidak jadi retur)
                $product = Product::withTrashed()->lockForUpdate()->find($item->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                }

                // Kembalikan counter quantity_returned di PO Item
                $poItem = PurchaseOrderItem::where('po_id', $purchaseReturn->purchase_order_id)
                    ->where('product_id', $item->product_id)
                    ->first();
                if ($poItem) {
                    $poItem->decrement('quantity_returned', $item->quantity);
                }
            }

            $purchaseReturn->delete();

            if ($purchaseOrder && $isDeductInvoice) {
                $totalReturDipotong = $purchaseOrder->returns()
                    ->where('return_handling_type', 'deduct_invoice')
                    ->sum('total_amount');

                $purchaseOrder->update(['total_returned' => $totalReturDipotong]);
                $sisaUtang = $purchaseOrder->total_amount - $totalReturDipotong - $purchaseOrder->amount_paid;
                $purchaseOrder->payment_status = $sisaUtang <= 0.01 ? 'paid' : ($purchaseOrder->amount_paid > 0 ? 'partially_paid' : 'unpaid');
                $purchaseOrder->save();
            }

            DB::commit();
            return redirect()->route('admin.purchase-returns.index')->with('success', 'Retur pembelian berhasil dibatalkan dan jurnal direversal.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal membatalkan retur pembelian: ' . $e->getMessage());
            return back()->with('error', 'Gagal membatalkan retur: ' . $e->getMessage());
        }
    }

    private function reverseAndClearJournal(string $journalGroupId, string $reversalDescription, $referenceModel)
    {
        $originalJournalEntries = DB::table('general_ledgers')->where('journal_group_id', $journalGroupId)->get();
        if ($originalJournalEntries->isEmpty()) return;

        $debitEntries = [];
        $creditEntries = [];

        foreach ($originalJournalEntries as $entry) {
            if ($entry->debit > 0) $creditEntries[] = [$entry->chart_of_account_id, $entry->debit, "Reversal: " . $entry->description];
            if ($entry->credit > 0) $debitEntries[] = [$entry->chart_of_account_id, $entry->credit, "Reversal: " . $entry->description];
        }

        if (!empty($debitEntries) || !empty($creditEntries)) {
            $this->accountingService->postJournal(
                str_replace(['PUR-RET-', 'SUP-DEP-'], ['PUR-RET-REV-', 'SUP-DEP-REV-'], $journalGroupId),
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