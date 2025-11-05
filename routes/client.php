<?php

use App\Http\Controllers\Client\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Client\Auth\ClientGoogleController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\InvoiceController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\SalesPlacedOrderController; // Controller Riwayat Order Sales
use App\Http\Controllers\Client\ClientOnlineOrderController;  // Controller Order Online Klien
use App\Http\Controllers\Client\OrderChangeRequestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MidtransController;
// Controller Lupa Password & Register
use App\Http\Controllers\Client\Auth\ForgotPasswordController;
use App\Http\Controllers\Client\Auth\ResetPasswordController;
use App\Http\Controllers\Client\Auth\RegisteredClientController;
use App\Http\Middleware\CheckClientLockStatus;

Route::prefix('client')->name('client.')->group(function () {

    // === LOGIN GOOGLE ===
    Route::get('auth/google', [ClientGoogleController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('auth/google/callback', [ClientGoogleController::class, 'handleGoogleCallback']);

    // === TAMU (belum login) ===
    Route::middleware('guest:client')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);
        // Rute Lupa Password & Register
        Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
        Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
        Route::get('register', [RegisteredClientController::class, 'create'])->name('register');
        Route::post('register', [RegisteredClientController::class, 'store']);
    });

    // === SUDAH LOGIN ===
    Route::middleware(['auth:client', CheckClientLockStatus::class, 'client.approved'])
     ->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::post('/invoices/{invoice}/pay', [MidtransController::class, 'pay'])->name('invoices.pay');

        // === WAJIB PROFIL LENGKAP ===
        Route::middleware('ensure.client.profile.complete')->group(function() {
            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

            // Invoices
            Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('invoices/batch-pay', [InvoiceController::class, 'showBatchPay'])
                 ->name('invoices.batchPay.create');
            
            Route::post('invoices/batch-pay-midtrans', [MidtransController::class, 'payBatch'])
                 ->name('invoices.batchPay.storeMidtrans'); // Ganti nama agar unik
            
            // ✅ TAMBAHKAN RUTE BARU INI
            Route::post('invoices/batch-pay-manual', [InvoiceController::class, 'storeBatchProof'])
                 ->name('invoices.batchPay.storeManual');

            Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::post('invoices/{invoice}/upload-proof', [InvoiceController::class, 'uploadProof'])->name('invoices.uploadProof');

            // === PESANAN ONLINE SAYA (Dibuat oleh Klien) ===
            Route::prefix('client-orders')->name('client-orders.')->group(function() {
                // ✅ Ganti controller
                Route::get('/', [ClientOnlineOrderController::class, 'index'])->name('index'); // client.client-orders.index
                Route::get('/create', [ClientOnlineOrderController::class, 'create'])->name('create'); // client.client-orders.create
                Route::post('/', [ClientOnlineOrderController::class, 'store'])->name('store'); // client.client-orders.store
                Route::get('/{order}', [ClientOnlineOrderController::class, 'show'])->name('show'); // client.client-orders.show
            });

            // === RIWAYAT PESANAN SALES (Dibuat oleh Sales/Admin) ===
            Route::prefix('sales-orders')->name('sales-orders.')->group(function() {
                Route::get('/', [SalesPlacedOrderController::class, 'index'])->name('index'); // client.sales-orders.index
                Route::get('/{order}', [SalesPlacedOrderController::class, 'show'])->name('show'); // client.sales-orders.show

                // Request Perubahan HANYA untuk order yang dibuat sales
                Route::get('/{order}/request-change', [OrderChangeRequestController::class, 'create'])->name('requestChange.create'); // client.sales-orders.requestChange.create
                Route::post('/{order}/request-change', [OrderChangeRequestController::class, 'store'])->name('requestChange.store'); // client.sales-orders.requestChange.store
            });
            // ==========================================================
        });
    });
});