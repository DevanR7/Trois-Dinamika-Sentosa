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


// --- RUTE PUBLIK ---
// Rute ini HARUS publik agar Midtrans bisa mengaksesnya.
// Keamanan rute ini ditangani di dalam Controller-nya (validasi signature).
Route::post('/midtrans/callback', [MidtransController::class, 'callback'])->name('midtrans.callback');


// --- RUTE TERPROTEKSI ---
// Semua rute di dalam grup ini WAJIB menggunakan token (login)
Route::middleware('auth:sanctum')->group(function () {

    // Rute untuk mengambil data user yang sedang login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // ✅ DIPINDAHKAN KE SINI
    // Sekarang aman dan butuh login
    Route::get('/invoices/{invoice}/items', function (Request $request, SalesInvoice $invoice) {
        
        // 1. Otorisasi menggunakan Policy
        // Ini akan memanggil method 'view' di SalesInvoicePolicy
        // dan memasukkan $request->user() (bisa User atau Client)
        // serta $invoice
        if ($request->user()->cannot('view', $invoice)) {
            // Jika policy return false, kirim error 403 Forbidden
            return response()->json(['message' => 'Anda tidak diizinkan mengakses invoice ini.'], 403);
        }

        // 2. Jika lolos, lanjutkan
        $invoice->load('items.product');
        return response()->json([
            'invoice' => $invoice,
            'items' => $invoice->items
        ]);
    });

    // ✅ DIPINDAHKAN KE SINI
    // Sekarang aman dan butuh login
    Route::get('/purchase-orders/{purchaseOrder}/items', function (Request $request, PurchaseOrder $purchaseOrder) {
        
        // Otorisasi
        if ($request->user()->cannot('view', $purchaseOrder)) {
            return response()->json(['message' => 'Anda tidak diizinkan mengakses PO ini.'], 403);
        }

        $purchaseOrder->load('items.product.unit');
        return response()->json(['items' => $purchaseOrder->items]);
    });

});