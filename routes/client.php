<?php

use App\Http\Controllers\Client\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Client\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\InvoiceController;
use App\Http\Controllers\Client\SalesOrderController;

// Rute untuk tamu (belum login)
Route::middleware('guest:client')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

// Rute untuk klien yang sudah login
Route::middleware('auth:client')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
      Route::post('invoices/{invoice}/upload-proof', [InvoiceController::class, 'uploadProof'])->name('invoices.uploadProof');

    Route::get('sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::get('sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
});