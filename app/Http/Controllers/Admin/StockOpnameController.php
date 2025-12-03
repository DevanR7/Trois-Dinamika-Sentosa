<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        return view('admin.stock_opnames.index', compact('opnames'));
    }

    public function create()
    {
        // Ambil semua produk untuk dihitung
        // (Untuk sistem besar, mungkin perlu filter per kategori atau lokasi)
        $products = Product::orderBy('product_name')->get();
        return view('admin.stock_opnames.create', compact('products'));
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
            return redirect()->route('admin.stock-opnames.index')->with('success', 'Stock Opname berhasil disimpan. Stok dan Jurnal telah diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal Stock Opname: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function show(StockOpname $stockOpname)
    {
        $stockOpname->load('items.product', 'user');
        return view('admin.stock_opnames.show', compact('stockOpname'));
    }

    public function destroy(StockOpname $stockOpname): \Illuminate\Http\RedirectResponse
    {
        // Cek Transaction Lock (Penting agar tidak menghapus data periode yg sudah tutup buku)
        $journalGroupId = "SO-" . $stockOpname->opname_number;
        // Pastikan trait ValidatesAccountingPeriod digunakan di class ini atau panggil manual servicenya
        // Jika menggunakan trait:
        // if ($error = $this->checkTransactionLock($stockOpname->opname_date, $journalGroupId)) {
        //    return back()->with('error', "Gagal Hapus: " . $error);
        // }

        DB::beginTransaction();
        try {
            // 1. Kembalikan Stok Produk
            // Kita balikkan logikanya: 
            // Jika dulu fisik > sistem (Diff +), stok ditambah. Sekarang kita kurangi.
            // Jika dulu fisik < sistem (Diff -), stok dikurangi. Sekarang kita tambah.
            foreach ($stockOpname->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if ($product) {
                    // Gunakan pengurangan langsung terhadap difference
                    // Contoh: Diff +5 (Stok nambah 5). Kita decrement 5 -> Stok berkurang 5 (Balik asal).
                    // Contoh: Diff -5 (Stok kurang 5). Kita decrement -5 -> Stok bertambah 5 (Balik asal).
                    $product->decrement('stock_quantity', $item->difference);
                }
            }

            // 2. Hapus Jurnal Akuntansi (GL) secara langsung
            // Kita tidak perlu membuat jurnal reversal karena transaksi utamanya dihapus.
            DB::table('general_ledgers')->where('journal_group_id', $journalGroupId)->delete();

            // 3. Hapus Record Stock Opname (Cascade akan menghapus items)
            $stockOpname->delete();

            DB::commit();
            return redirect()->route('admin.stock-opnames.index')
                ->with('success', 'Stock Opname berhasil dihapus. Stok telah dikembalikan ke posisi semula.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal hapus stock opname: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
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