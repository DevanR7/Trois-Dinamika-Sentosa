<?php

use App\Http\Controllers\Client\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Client\Auth\ClientGoogleController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\InvoiceController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\SalesOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('client')->name('client.')->group(function () {

    // === LOGIN GOOGLE ===
    Route::get('auth/google', [ClientGoogleController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('auth/google/callback', [ClientGoogleController::class, 'handleGoogleCallback']);

    // === TAMU (belum login) ===
    Route::middleware('guest:client')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);
    });

    // === SUDAH LOGIN ===
    Route::middleware('auth:client')->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');

        // Wajib profil lengkap
        Route::middleware('ensure.client.profile.complete')->group(function() {
            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

            Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::post('invoices/{invoice}/upload-proof', [InvoiceController::class, 'uploadProof'])->name('invoices.uploadProof');

            Route::get('sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
            Route::get('sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
        });
    });
});
