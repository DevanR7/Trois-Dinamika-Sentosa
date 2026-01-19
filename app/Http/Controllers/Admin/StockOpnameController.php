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
use App\Traits\ValidatesAccountingPeriod;

class StockOpnameController extends Controller
{
    use ValidatesAccountingPeriod;

    protected $accountingService;
    protected $accountingSettings;

    public function __construct(AccountingService $accountingService, AccountingSettingService $accountingSettingService)
    {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettingService;
        
        $this->middleware('can:manage-stock-opnames');
    }

    public function index()
    {
        $opnames = StockOpname::with('user')->latest('opname_date')->paginate(15);
        return view('admin.stock_opnames.index', compact('opnames'));
    }

    public function create()
{
    // Kita kirim data produk lengkap untuk AlpineJS (ID, Nama, Kode, Stok Sistem, Unit)
    $products = \App\Models\Product::with('unit')
        ->where('is_active', true) // Hanya produk aktif
        ->orderBy('product_name')
        ->get()
        ->map(function ($product) {
            return [
                'id' => $product->product_id,
                'name' => $product->product_name,
                'code' => $product->product_code,
                'stock' => (float) $product->stock_quantity, // Pastikan float
                'unit' => $product->unit->name ?? 'pcs',
                'image' => $product->image_path ? asset('storage/' . $product->image_path) : null,
            ];
        });

    return view('admin.stock_opnames.create', compact('products'));
}

    public function store(Request $request)
    {   
        $validated = $request->validate([
            'opname_date' => 'required|date',
            'notes' => 'nullable|string',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.physical_qty' => 'required|numeric|min:0',
        ]);
        
        // 1. Cek Periode Tutup Buku
        if ($this->isDateClosed($request->opname_date)) {
            return back()->with('error', 'Gagal: Tanggal Opname masuk periode tutup buku.')->withInput();
        }

        $inventoryAccountId = $this->accountingSettings->getInventoryId();
        $adjustmentAccountId = $this->accountingSettings->getInventoryAdjustmentId(); // Akun Beban/Pendapatan Selisih Stok

        if (!$inventoryAccountId || !$adjustmentAccountId) {
            return back()->with('error', 'Gagal: Akun Persediaan atau Akun Penyesuaian Stok belum diatur di Pengaturan.')->withInput();
        }

        DB::beginTransaction();
        try {
            $opname = StockOpname::create([
                'opname_number' => StockOpname::generateNumber(),
                'opname_date' => $validated['opname_date'],
                'notes' => $validated['notes'],
                'user_id' => Auth::id(),
                'status' => 'completed', 
                'total_adjustment_value' => 0
            ]);

            $totalAdjustmentValue = 0; // Net Value (Bisa plus atau minus)
            $itemsToInsert = [];

            foreach ($validated['products'] as $itemData) {
                // 2. LOCKING PRODUCT (CRITICAL)
                // Kunci produk ini. Sales/Purchase lain harus antri sampai opname selesai.
                $product = Product::lockForUpdate()->find($itemData['product_id']);
                
                if(!$product) continue;

                $systemQty = $product->stock_quantity;
                $physicalQty = (float) $itemData['physical_qty'];
                
                // Selisih: Jika Fisik > Sistem = Positif (Barang ketemu lebih)
                // Jika Fisik < Sistem = Negatif (Barang hilang)
                $difference = $physicalQty - $systemQty; 
                
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
                    'created_at' => now(), 
                    'updated_at' => now()
                ];

                if (abs($difference) > 0.0001) {
                    $product->stock_quantity = $physicalQty; // Paksa stok sesuai fisik
                    $product->save();
                    
                    $totalAdjustmentValue += $adjustmentValue;
                }
            }

            if (!empty($itemsToInsert)) {
                StockOpnameItem::insert($itemsToInsert);
            }

            $opname->update(['total_adjustment_value' => $totalAdjustmentValue]);

            // 3. Jurnal Akuntansi (Net Journal)
            if (abs($totalAdjustmentValue) > 0.01) {
                $journalGroupId = "SO-" . $opname->opname_number;
                $description = "Penyesuaian Stok Opname #" . $opname->opname_number;
                
                $debitEntries = [];
                $creditEntries = [];

                if ($totalAdjustmentValue < 0) {
                    // RUGI / SELISIH KURANG (Nilai Persediaan Turun)
                    // Debit: Beban Selisih Stok
                    // Kredit: Persediaan
                    $lossAmount = abs($totalAdjustmentValue);
                    $debitEntries[] = [$adjustmentAccountId, $lossAmount, "Selisih Kurang Stok (Loss)"];
                    $creditEntries[] = [$inventoryAccountId, $lossAmount, "Pengurangan Persediaan"];
                } else {
                    // UNTUNG / SELISIH LEBIH (Nilai Persediaan Naik)
                    // Debit: Persediaan
                    // Kredit: Pendapatan Lain/Penyesuaian Stok
                    $gainAmount = $totalAdjustmentValue;
                    $debitEntries[] = [$inventoryAccountId, $gainAmount, "Penambahan Persediaan"];
                    $creditEntries[] = [$adjustmentAccountId, $gainAmount, "Selisih Lebih Stok (Gain)"];
                }

                $this->accountingService->postJournal(
                    $journalGroupId,
                    $validated['opname_date'],
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $opname,
                    Auth::id()
                );
            }

            DB::commit();
            return redirect()->route('admin.stock-opnames.index')->with('success', 'Stock Opname berhasil disimpan. Stok fisik disinkronisasi.');

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
        $journalGroupId = "SO-" . $stockOpname->opname_number;
        
        if ($error = $this->checkTransactionLock($stockOpname->opname_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus: " . $error);
        }

        DB::beginTransaction();
        try {
            // Kembalikan Stok ke posisi sebelum Opname
            foreach ($stockOpname->items as $item) {
    // Gunakan withTrashed()
    $product = Product::withTrashed()->lockForUpdate()->find($item->product_id);
    
    if ($product) {
        // Balikkan stok sesuai selisih (difference)
        // decrement akan otomatis menangani nilai positif/negatif dengan benar
        // Contoh: difference +5 -> decrement 5 (stok berkurang)
        // Contoh: difference -5 -> decrement -5 (sama dengan increment 5, stok bertambah)
        $product->decrement('stock_quantity', $item->difference);
    }
}

            // Hapus Jurnal
            DB::table('general_ledgers')->where('journal_group_id', $journalGroupId)->delete();
            
            $stockOpname->delete();

            DB::commit();
            return redirect()->route('admin.stock-opnames.index')
                ->with('success', 'Stock Opname berhasil dibatalkan. Stok dikembalikan ke kondisi semula.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal hapus stock opname: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    public function downloadWorksheet()
    {
        $products = Product::with('unit')
            ->orderBy('product_name', 'asc')
            ->get();

        $data = [
            'products' => $products,
            'date' => now(),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.stock_opnames.pdf_worksheet', $data);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('Lembar-Kerja-Stock-Opname-' . now()->format('d-m-Y') . '.pdf');
    }
}