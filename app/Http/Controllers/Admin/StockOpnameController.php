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
        $this->middleware('can:manage-stock-opnames');
    }

    public function index()
    {
        $opnames = StockOpname::with('user')->latest('opname_date')->paginate(15);
        return view('admin.stock_opnames.index', compact('opnames'));
    }

    public function create()
    {
        $products = Product::orderBy('product_name')->get();
        return view('admin.stock_opnames.create', compact('products'));
    }

    public function store(Request $request)
    {   
        Log::info('Stock Opname Store Request:', $request->all());
        
        $validated = $request->validate([
            'opname_date' => 'required|date',
            'notes' => 'nullable|string',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.physical_qty' => 'required|numeric|min:0',
            'confirmed' => 'sometimes|in:1'
        ]);
        
        Log::info('Stock Opname Validation Passed:', $validated);

        $inventoryAccountId = $this->accountingSettings->getInventoryId();
        $adjustmentAccountId = $this->accountingSettings->getInventoryAdjustmentId();

        if (!$inventoryAccountId || !$adjustmentAccountId) {
            return back()->with('error', 'Gagal: Akun Persediaan atau Beban Selisih Stok belum diatur di Pengaturan.')->withInput();
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

            $totalAdjustmentValue = 0;
            $itemsToInsert = [];

            foreach ($validated['products'] as $itemData) {
                $product = Product::lockForUpdate()->find($itemData['product_id']);
                
                $systemQty = $product->stock_quantity;
                $physicalQty = (float) $itemData['physical_qty'];
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
                    'created_at' => now(), 'updated_at' => now()
                ];

                if ($difference != 0) {
                    $product->stock_quantity = $physicalQty;
                    $product->save();
                    $totalAdjustmentValue += $adjustmentValue;
                }
            }

            StockOpnameItem::insert($itemsToInsert);
            $opname->update(['total_adjustment_value' => $totalAdjustmentValue]);

            if (abs($totalAdjustmentValue) > 0.01) {
                $journalGroupId = "SO-" . $opname->opname_number;
                $description = "Penyesuaian Stok Opname #" . $opname->opname_number;
                
                $debitEntries = [];
                $creditEntries = [];

                if ($totalAdjustmentValue < 0) {
                    $lossAmount = abs($totalAdjustmentValue);
                    $debitEntries[] = [$adjustmentAccountId, $lossAmount, "Selisih Kurang Stok"];
                    $creditEntries[] = [$inventoryAccountId, $lossAmount, "Pengurangan nilai persediaan"];
                } else {
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
        $journalGroupId = "SO-" . $stockOpname->opname_number;

        DB::beginTransaction();
        try {
            foreach ($stockOpname->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if ($product) {
                    $product->decrement('stock_quantity', $item->difference);
                }
            }

            DB::table('general_ledgers')->where('journal_group_id', $journalGroupId)->delete();

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