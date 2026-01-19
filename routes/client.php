<?php

use App\Http\Controllers\Client\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Client\Auth\ClientGoogleController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\InvoiceController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\SalesPlacedOrderController; 
use App\Http\Controllers\Client\ClientOnlineOrderController; 
use App\Http\Controllers\Client\OrderChangeRequestController;
use App\Http\Controllers\Client\Auth\ForgotPasswordController;
use App\Http\Controllers\Client\Auth\ResetPasswordController;
use App\Http\Controllers\Client\Auth\RegisteredClientController;
use App\Http\Controllers\Client\AnnouncementController;
// Import Controller Pembayaran Client
use App\Http\Controllers\Client\ClientPaymentController; 

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckClientLockStatus;

Route::prefix('client')->name('client.')->group(function () {
    
    // === LOGIN GOOGLE ===
    Route::get('auth/google', [ClientGoogleController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('auth/google/callback', [ClientGoogleController::class, 'handleGoogleCallback']);

    // === GUEST ROUTES ===
    Route::middleware('guest:client')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);
        
        Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
        Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
        
        Route::get('register', [RegisteredClientController::class, 'create'])->name('register');
        Route::post('register', [RegisteredClientController::class, 'store']);
    });

    // === AUTHENTICATED ROUTES ===
    Route::middleware(['auth:client', CheckClientLockStatus::class, 'client.approved'])
     ->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        
        // === MIDTRANS PAYMENT ROUTES (NEW) ===
        // Single Invoice
        Route::post('/invoices/{invoice}/pay-midtrans', [ClientPaymentController::class, 'payMidtrans'])
            ->name('invoices.pay.midtrans');
        
        // Bulk Payment
        Route::post('/invoices/bulk-pay-midtrans', [ClientPaymentController::class, 'payBulkMidtrans'])
            ->name('invoices.bulkPay.midtrans');

        // === MAIN DASHBOARD & FEATURES ===
        Route::middleware('ensure.client.profile.complete')->group(function() {
            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
            
            // Invoices
            Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            
            Route::get('invoices/bulk-pay', [InvoiceController::class, 'showBulkPay']) 
                ->name('invoices.bulkPay.create'); 
            
            // Manual Transfer Proof Upload
            Route::post('invoices/bulk-pay-manual', [InvoiceController::class, 'storeBulkProof'])
                ->name('invoices.bulkPay.storeManual'); 
            
            Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::post('invoices/{invoice}/upload-proof', [InvoiceController::class, 'uploadProof'])->name('invoices.uploadProof');

            // Client Orders
            Route::prefix('client-orders')->name('client-orders.')->group(function() {
                Route::get('/', [ClientOnlineOrderController::class, 'index'])->name('index'); 
                Route::get('/create', [ClientOnlineOrderController::class, 'create'])->name('create'); 
                Route::post('/', [ClientOnlineOrderController::class, 'store'])->name('store'); 
                Route::get('/{order}', [ClientOnlineOrderController::class, 'show'])->name('show'); 
            });

            // Sales Placed Orders
            Route::prefix('sales-orders')->name('sales-orders.')->group(function() {
                Route::get('/', [SalesPlacedOrderController::class, 'index'])->name('index'); 
                Route::get('/{order}', [SalesPlacedOrderController::class, 'show'])->name('show'); 
                
                Route::get('/{order}/request-change', [OrderChangeRequestController::class, 'create'])->name('requestChange.create'); 
                Route::post('/{order}/request-change', [OrderChangeRequestController::class, 'store'])->name('requestChange.store'); 
            });
        });
        
        Route::get('/api/my-announcements', [AnnouncementController::class, 'index'])->name('api.announcements');
    });
});