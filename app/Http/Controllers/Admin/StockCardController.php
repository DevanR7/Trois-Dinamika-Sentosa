<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockCardController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view-reports');

        $products = Product::orderBy('product_name')->get();
        $selectedProduct = null;
        $stockCardData = [];
        $openingStock = 0;
        $endingStock = 0;

        // Default tanggal: bulan ini
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        if ($request->filled('product_id')) {
            $selectedProduct = Product::find($request->product_id);

            if ($selectedProduct) {
                // 1. HITUNG SALDO AWAL (Opening Stock)
                // Jumlahkan semua transaksi SEBELUM start_date
                $openingStock = $this->calculateStockMovement($selectedProduct->product_id, '1970-01-01', Carbon::parse($startDate)->subDay()->toDateString());

                // 2. AMBIL TRANSAKSI PERIODE INI
                $transactions = $this->getTransactions($selectedProduct->product_id, $startDate, $endDate);
                
                // 3. HITUNG RUNNING BALANCE
                $currentBalance = $openingStock;
                foreach ($transactions as $trx) {
                    // Tentukan In/Out
                    $in = 0;
                    $out = 0;

                    // Logika arah stok
                    switch ($trx->source) {
                        case 'purchase_order': // Beli = Masuk
                            $in = $trx->qty;
                            break;
                        case 'sales_invoice': // Jual = Keluar
                            $out = $trx->qty;
                            break;
                        case 'sales_return': // Retur Jual = Masuk Kembali
                            $in = $trx->qty;
                            break;
                        case 'purchase_return': // Retur Beli = Keluar Kembali
                            $out = $trx->qty;
                            break;
                        case 'stock_opname': // Opname = Selisih (Bisa + atau -)
                            if ($trx->qty >= 0) $in = $trx->qty;
                            else $out = abs($trx->qty);
                            break;
                    }

                    $currentBalance = $currentBalance + $in - $out;

                    $stockCardData[] = (object) [
                        'date' => $trx->date,
                        'reference' => $trx->reference,
                        'description' => $trx->description,
                        'in' => $in,
                        'out' => $out,
                        'balance' => $currentBalance
                    ];
                }
                $endingStock = $currentBalance;
            }
        }

        return view('admin.reports.stock_card', compact(
            'products', 'selectedProduct', 'stockCardData', 
            'openingStock', 'endingStock', 'startDate', 'endDate'
        ));
    }

    // Helper: Query Union Semua Tabel Transaksi
    private function getTransactions($productId, $start, $end)
    {
        // 1. Pembelian (Masuk) - Hanya yang completed
        $purchases = DB::table('purchase_order_items as poi')
            ->join('purchase_orders as po', 'poi.po_id', '=', 'po.po_id')
            ->where('poi.product_id', $productId)
            ->where('po.status', 'completed')
            ->whereBetween('po.order_date', [$start, $end])
            ->selectRaw("'purchase_order' as source, po.order_date as date, po.po_number as reference, 'Pembelian Barang' as description, poi.quantity as qty, po.created_at");

        // 2. Penjualan (Keluar) - Status bukan draft/cancelled
        $sales = DB::table('invoice_items as ii')
            ->join('sales_invoices as si', 'ii.invoice_id', '=', 'si.invoice_id')
            ->where('ii.product_id', $productId)
            ->whereNotIn('si.status', ['draft', 'cancelled'])
            ->whereBetween('si.order_date', [$start, $end])
            ->selectRaw("'sales_invoice' as source, si.order_date as date, si.invoice_number as reference, 'Penjualan Barang' as description, ii.quantity as qty, si.created_at");

        // 3. Retur Penjualan (Masuk)
        $salesReturns = DB::table('sales_return_items as sri')
            ->join('sales_returns as sr', 'sri.return_id', '=', 'sr.return_id')
            ->where('sri.product_id', $productId)
            ->whereBetween('sr.return_date', [$start, $end])
            ->selectRaw("'sales_return' as source, sr.return_date as date, sr.return_number as reference, 'Retur Penjualan' as description, sri.quantity as qty, sr.created_at");

        // 4. Retur Pembelian (Keluar)
        $purchaseReturns = DB::table('purchase_return_items as pri')
            ->join('purchase_returns as pr', 'pri.return_id', '=', 'pr.return_id')
            ->where('pri.product_id', $productId)
            ->whereBetween('pr.return_date', [$start, $end])
            ->selectRaw("'purchase_return' as source, pr.return_date as date, pr.return_number as reference, 'Retur Pembelian' as description, pri.quantity as qty, pr.created_at");

        // 5. Stock Opname (Adjustment)
        $opnames = DB::table('stock_opname_items as soi')
            ->join('stock_opnames as so', 'soi.opname_id', '=', 'so.opname_id')
            ->where('soi.product_id', $productId)
            ->where('so.status', 'completed')
            ->whereBetween('so.opname_date', [$start, $end])
            ->selectRaw("'stock_opname' as source, so.opname_date as date, so.opname_number as reference, 'Penyesuaian Stok' as description, soi.difference as qty, so.created_at");

        return $purchases
            ->unionAll($sales)
            ->unionAll($salesReturns)
            ->unionAll($purchaseReturns)
            ->unionAll($opnames)
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();
    }

    private function calculateStockMovement($productId, $start, $end)
    {
        $transactions = $this->getTransactions($productId, $start, $end);
        $balance = 0;

        foreach ($transactions as $trx) {
            if ($trx->source == 'purchase_order') $balance += $trx->qty;
            elseif ($trx->source == 'sales_invoice') $balance -= $trx->qty;
            elseif ($trx->source == 'sales_return') $balance += $trx->qty;
            elseif ($trx->source == 'purchase_return') $balance -= $trx->qty;
            elseif ($trx->source == 'stock_opname') $balance += $trx->qty; // difference bisa negatif
        }

        return $balance;
    }
}