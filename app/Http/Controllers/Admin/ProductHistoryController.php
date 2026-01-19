<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::orderBy('product_name')->get();
        $selectedProduct = null;
        $history = collect();

        if ($request->filled('product_id')) {
            $selectedProduct = Product::find($request->product_id);
            
            if ($selectedProduct) {
                
                // 1. Penjualan (Sales Invoice)
                $sales = DB::table('invoice_items')
                    ->join('sales_invoices', 'invoice_items.invoice_id', '=', 'sales_invoices.invoice_id')
                    ->where('invoice_items.product_id', $selectedProduct->product_id)
                    ->whereNotIn('sales_invoices.status', ['draft', 'cancelled'])
                    ->select(
                        'sales_invoices.order_date as date',
                        'sales_invoices.invoice_number as reference',
                        DB::raw("'sales_invoice' as source"),   // Untuk Logic Switch di View
                        DB::raw("'Penjualan' as type"),         // Untuk Label Teks di Badge
                        DB::raw("'Penjualan ke Klien' as description"),
                        DB::raw('0 as qty_in'),
                        'invoice_items.quantity as qty_out',
                        'sales_invoices.created_at'
                    );

                // 2. Pembelian (Purchase Order)
                $purchases = DB::table('purchase_order_items')
                    ->join('purchase_orders', 'purchase_order_items.po_id', '=', 'purchase_orders.po_id')
                    ->where('purchase_order_items.product_id', $selectedProduct->product_id)
                    ->where('purchase_orders.status', 'completed')
                    ->select(
                        'purchase_orders.order_date as date',
                        'purchase_orders.po_number as reference',
                        DB::raw("'purchase_order' as source"),
                        DB::raw("'Pembelian' as type"),         // Label
                        DB::raw("'Penerimaan dari Supplier' as description"),
                        'purchase_order_items.quantity as qty_in',
                        DB::raw('0 as qty_out'),
                        'purchase_orders.created_at'
                    );

                // 3. Stock Opname
                $opnames = DB::table('stock_opname_items')
                    ->join('stock_opnames', 'stock_opname_items.opname_id', '=', 'stock_opnames.opname_id')
                    ->where('stock_opname_items.product_id', $selectedProduct->product_id)
                    ->where('stock_opnames.status', 'completed')
                    ->select(
                        'stock_opnames.opname_date as date',
                        'stock_opnames.opname_number as reference',
                        DB::raw("'stock_opname' as source"),
                        DB::raw("'Stock Opname' as type"),      // Label
                        DB::raw("'Penyesuaian Stok Fisik' as description"),
                        DB::raw('CASE WHEN stock_opname_items.difference > 0 THEN stock_opname_items.difference ELSE 0 END as qty_in'),
                        DB::raw('CASE WHEN stock_opname_items.difference < 0 THEN ABS(stock_opname_items.difference) ELSE 0 END as qty_out'),
                        'stock_opnames.created_at'
                    );

                // 4. Retur Penjualan
                $salesReturns = DB::table('sales_return_items')
                    ->join('sales_returns', 'sales_return_items.return_id', '=', 'sales_returns.return_id')
                    ->where('sales_return_items.product_id', $selectedProduct->product_id)
                    ->select(
                        'sales_returns.return_date as date',
                        'sales_returns.return_number as reference',
                        DB::raw("'sales_return' as source"),
                        DB::raw("'Retur Jual' as type"),        // Label
                        DB::raw("'Retur dari Klien' as description"),
                        'sales_return_items.quantity as qty_in',
                        DB::raw('0 as qty_out'),
                        'sales_returns.created_at'
                    );

                // 5. Retur Pembelian
                $purchaseReturns = DB::table('purchase_return_items')
                    ->join('purchase_returns', 'purchase_return_items.return_id', '=', 'purchase_returns.return_id')
                    ->where('purchase_return_items.product_id', $selectedProduct->product_id)
                    ->select(
                        'purchase_returns.return_date as date',
                        'purchase_returns.return_number as reference',
                        DB::raw("'purchase_return' as source"),
                        DB::raw("'Retur Beli' as type"),        // Label
                        DB::raw("'Retur ke Supplier' as description"),
                        DB::raw('0 as qty_in'),
                        'purchase_return_items.quantity as qty_out',
                        'purchase_returns.created_at'
                    );

                // Gabungkan Semua
                $history = $purchases
                    ->unionAll($sales)
                    ->unionAll($opnames)
                    ->unionAll($salesReturns)
                    ->unionAll($purchaseReturns)
                    ->orderBy('date', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->get();
            }
        }

        return view('admin.reports.product_history', compact('products', 'selectedProduct', 'history'));
    }
}