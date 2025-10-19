<?php

use App\Http\Controllers\Client\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Client\Auth\ClientGoogleController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\InvoiceController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\OrderController; // Controller Riwayat Order
use App\Http\Controllers\Client\ClientOrderController; // Controller Create/Store Order
use App\Http\Controllers\Client\OrderChangeRequestController; // Controller Request Ubah
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MidtransController;

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

        // Profil bisa diakses walau belum lengkap
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');

        // Midtrans (Mungkin perlu di dalam middleware profile complete juga?)
        Route::post('/invoices/{invoice}/pay', [MidtransController::class, 'pay'])->name('invoices.pay');

        

        // === WAJIB PROFIL LENGKAP ===
        Route::middleware('ensure.client.profile.complete')->group(function() {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Invoices (Tidak ada konflik)
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('invoices/{invoice}/upload-proof', [InvoiceController::class, 'uploadProof'])->name('invoices.uploadProof');

    // === BAGIAN ORDERS ===
    // 1. Definisikan '/create' DULU (lebih spesifik)
    Route::get('orders/create', [ClientOrderController::class, 'create'])->name('orders.create');
    Route::post('orders', [ClientOrderController::class, 'store'])->name('orders.store'); // Store juga harus sebelum wildcard jika path sama

    // 2. Definisikan '/{order}/request-change' KEMUDIAN (lebih spesifik dari /{order})
    Route::get('orders/{order}/request-change', [OrderChangeRequestController::class, 'create'])->name('orders.requestChange.create');
    Route::post('orders/{order}/request-change', [OrderChangeRequestController::class, 'store'])->name('orders.requestChange.store');

    // 3. Definisikan '/' (index) dan '/{order}' (show) TERAKHIR
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    // ======================
});
    });
});