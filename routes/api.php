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

    

});