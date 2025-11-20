<?php

namespace App\Http\Controllers;

use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;

class StockOpnameController extends Controller
{
    protected $accountingService;
    protected $accountingSettings;

    public function __construct(AccountingService $accountingService, AccountingSettingService $accountingSettingService)
    {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettingService;
        // Tambahkan middleware permission jika perlu
        $this->middleware('can:manage-stock-opnames');
    }

    public function index()
    {
        $opnames = StockOpname::with('user')->latest('opname_date')->paginate(15);
        return view('stock_opnames.index', compact('opnames'));
    }

    public function create()
    {
        // Ambil semua produk untuk dihitung
        // (Untuk sistem besar, mungkin perlu filter per kategori atau lokasi)
        $products = Product::orderBy('product_name')->get();
        return view('stock_opnames.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'opname_date' => 'required|date',
            'notes' => 'nullable|string',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.physical_qty' => 'required|integer|min:0',
        ]);

        // Validasi Akun
        $inventoryAccountId = $this->accountingSettings->getInventoryId();
        $adjustmentAccountId = $this->accountingSettings->getInventoryAdjustmentId();

        if (!$inventoryAccountId || !$adjustmentAccountId) {
            return back()->with('error', 'Gagal: Akun Persediaan atau Beban Selisih Stok belum diatur di Pengaturan.')->withInput();
        }

        DB::beginTransaction();
        try {
            // 1. Buat Header
            $opname = StockOpname::create([
                'opname_number' => StockOpname::generateNumber(),
                'opname_date' => $validated['opname_date'],
                'notes' => $validated['notes'],
                'user_id' => Auth::id(),
                'status' => 'completed', // Langsung completed karena stok langsung berubah
                'total_adjustment_value' => 0 // Nanti diupdate
            ]);

            $totalAdjustmentValue = 0;
            $itemsToInsert = [];

            // 2. Proses Item
            foreach ($validated['products'] as $itemData) {
                $product = Product::lockForUpdate()->find($itemData['product_id']);
                
                $systemQty = $product->stock_quantity;
                $physicalQty = (int)$itemData['physical_qty'];
                $difference = $physicalQty - $systemQty;
                
                // Jika tidak ada selisih, tetap catat tapi nilai 0
                $cost = $product->average_cost;
                $adjustmentValue = $difference * $cost;

                $itemsToInsert[] = [
                    'opname_id' => $opname->opname_id,
                    'product_id' => $product->product_id,
                    'system_qty' => $systemQty,
                    'physical_qty' => $physicalQty,
                    'difference' => $difference,
                    'cost_per_unit' => $cost,
                    'adjustment_value' => $adjustmentValue,
                    'created_at' => now(), 'updated_at' => now()
                ];

                // Update Stok Produk
                if ($difference != 0) {
                    $product->stock_quantity = $physicalQty;
                    $product->save();
                    $totalAdjustmentValue += $adjustmentValue;
                }
            }

            StockOpnameItem::insert($itemsToInsert);
            $opname->update(['total_adjustment_value' => $totalAdjustmentValue]);

            // 3. Post Jurnal Akuntansi (Hanya jika ada selisih nilai)
            if (abs($totalAdjustmentValue) > 0.01) {
                $journalGroupId = "SO-" . $opname->opname_number;
                $description = "Penyesuaian Stok Opname #" . $opname->opname_number;
                
                $debitEntries = [];
                $creditEntries = [];

                if ($totalAdjustmentValue < 0) {
                    // KERUGIAN (Stok Hilang/Minus)
                    // (Debit) Beban Selisih Stok
                    // (Kredit) Persediaan Barang
                    $lossAmount = abs($totalAdjustmentValue);
                    $debitEntries[] = [$adjustmentAccountId, $lossAmount, "Selisih Kurang Stok"];
                    $creditEntries[] = [$inventoryAccountId, $lossAmount, "Pengurangan nilai persediaan"];
                } else {
                    // KEUNTUNGAN (Stok Lebih)
                    // (Debit) Persediaan Barang
                    // (Kredit) Beban Selisih Stok (sebagai pengurang beban/pendapatan lain)
                    $gainAmount = $totalAdjustmentValue;
                    $debitEntries[] = [$inventoryAccountId, $gainAmount, "Penambahan nilai persediaan"];
                    $creditEntries[] = [$adjustmentAccountId, $gainAmount, "Selisih Lebih Stok (Adjustment)"];
                }

                $this->accountingService->postJournal(
                    $journalGroupId,
                    $validated['opname_date'],
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $opname
                );
            }

            DB::commit();
            return redirect()->route('stock-opnames.index')->with('success', 'Stock Opname berhasil disimpan. Stok dan Jurnal telah diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal Stock Opname: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function show(StockOpname $stockOpname)
    {
        $stockOpname->load('items.product', 'user');
        return view('stock_opnames.show', compact('stockOpname'));
    }

    public function destroy(StockOpname $stockOpname): \Illuminate\Http\RedirectResponse
    {
        // Cek permission (opsional, jika pakai middleware di construct sudah aman)
        // $this->authorize('delete', $stockOpname);

        DB::beginTransaction();
        try {
            // 1. Kembalikan Stok Produk
            foreach ($stockOpname->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if ($product) {
                    // Logika Reversal:
                    // Jika Opname menambah stok (difference positif), kita kurangi.
                    // Jika Opname mengurangi stok (difference negatif), kita tambah.
                    // Matematikanya: Stok Sekarang - Difference
                    
                    // Contoh: Awal 10. Fisik 8. Diff -2. Stok Skrg 8.
                    // Batal: 8 - (-2) = 10. (Benar)
                    
                    $product->decrement('stock_quantity', $item->difference);
                }
            }

            // 2. Post Jurnal Reversal (Pembalikan)
            // Kita perlu tahu akun-akunnya lagi
            $inventoryAccountId = $this->accountingSettings->getInventoryId();
            $adjustmentAccountId = $this->accountingSettings->getInventoryAdjustmentId();

            if ($inventoryAccountId && $adjustmentAccountId && abs($stockOpname->total_adjustment_value) > 0.01) {
                
                $journalGroupId = "SO-" . $stockOpname->opname_number;
                $reversalGroupId = "SO-REV-" . $stockOpname->opname_number;
                $description = "Reversal Stock Opname #" . $stockOpname->opname_number;
                
                // Ambil jurnal asli untuk dibalik
                $originalJournalEntries = DB::table('general_ledgers')
                                            ->where('journal_group_id', $journalGroupId)
                                            ->get();
                
                $debitEntries = [];
                $creditEntries = [];

                foreach ($originalJournalEntries as $entry) {
                    // Balikkan Debit jadi Kredit
                    if ($entry->debit > 0) {
                        $creditEntries[] = [$entry->chart_of_account_id, $entry->debit, "Reversal: " . $entry->description];
                    }
                    // Balikkan Kredit jadi Debit
                    if ($entry->credit > 0) {
                        $debitEntries[] = [$entry->chart_of_account_id, $entry->credit, "Reversal: " . $entry->description];
                    }
                }

                if (!empty($debitEntries) || !empty($creditEntries)) {
                    $this->accountingService->postJournal(
                        $reversalGroupId,
                        now(), // Tanggal reversal
                        $description,
                        $debitEntries,
                        $creditEntries,
                        $stockOpname
                    );
                }

                // 3. Hapus Jurnal Asli
                DB::table('general_ledgers')->where('journal_group_id', $journalGroupId)->delete();
            }

            // 4. Hapus Record Stock Opname (Cascade akan menghapus items)
            $stockOpname->delete();

            DB::commit();
            return redirect()->route('stock-opnames.index')
                             ->with('success', 'Stock Opname berhasil dibatalkan. Stok telah dikembalikan ke posisi semula.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal batalkan stock opname: ' . $e->getMessage());
            return back()->with('error', 'Gagal membatalkan: ' . $e->getMessage());
        }
    }

    public function downloadWorksheet()
    {
        // Ambil semua produk, urutkan berdasarkan nama/lokasi rak agar mudah dicek
        $products = Product::with('unit')
            ->orderBy('product_name', 'asc')
            ->get();

        $data = [
            'products' => $products,
            'date' => now(),
        ];

        // Load view PDF (kita buat setelah ini)
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('stock_opnames.pdf_worksheet', $data);
        
        // Set ukuran kertas A4 Portrait
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('Lembar-Kerja-Stock-Opname-' . now()->format('d-m-Y') . '.pdf');
    }
}