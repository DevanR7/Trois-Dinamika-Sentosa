<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Http\Controllers\MidtransController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('/midtrans/callback', [MidtransController::class, 'callback'])->name('midtrans.callback');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/invoices/{invoice}/items', function (SalesInvoice $invoice) {
    // Load relasi item beserta info produknya
    $invoice->load('items.product');
    // Kembalikan data invoice dan itemnya
    return response()->json([
        'invoice' => $invoice, // Mengirim info invoice (termasuk diskon)
        'items' => $invoice->items
    ]);
});

Route::get('/purchase-orders/{purchaseOrder}/items', function (PurchaseOrder $purchaseOrder) {
    $purchaseOrder->load('items.product.unit');
    return response()->json(['items' => $purchaseOrder->items]);
});

