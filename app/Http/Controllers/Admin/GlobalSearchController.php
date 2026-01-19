<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalesInvoice;
use App\Models\Product;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\PurchaseOrder;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = [];

        // 1. Cari Produk
        $products = Product::where('product_name', 'like', "%{$query}%")
            ->orWhere('product_code', 'like', "%{$query}%")
            ->take(3)->get();
        
        foreach ($products as $p) {
            $results[] = [
                'category' => 'Produk',
                'label' => $p->product_name . ' (' . $p->product_code . ')',
                'url' => route('admin.products.edit', $p->product_id),
                'icon' => 'inventory_2'
            ];
        }

        // 2. Cari Invoice Penjualan
        $invoices = SalesInvoice::where('invoice_number', 'like', "%{$query}%")
            ->take(3)->get();

        foreach ($invoices as $inv) {
            $results[] = [
                'category' => 'Invoice',
                'label' => $inv->invoice_number . ' - ' . ($inv->client->client_name ?? '-'),
                'url' => route('admin.invoices.show', $inv->invoice_id),
                'icon' => 'receipt_long'
            ];
        }

        // 3. Cari Purchase Order
        $pos = PurchaseOrder::where('po_number', 'like', "%{$query}%")
            ->take(3)->get();

        foreach ($pos as $po) {
            $results[] = [
                'category' => 'Purchase Order',
                'label' => $po->po_number . ' - ' . ($po->supplier->supplier_name ?? '-'),
                'url' => route('admin.purchase-orders.show', $po->po_id),
                'icon' => 'shopping_cart'
            ];
        }

        // 4. Cari Klien
        $clients = Client::where('client_name', 'like', "%{$query}%")
            ->take(3)->get();

        foreach ($clients as $c) {
            $results[] = [
                'category' => 'Klien',
                'label' => $c->client_name,
                'url' => route('admin.clients.show', $c->client_id),
                'icon' => 'group'
            ];
        }

        return response()->json($results);
    }
}