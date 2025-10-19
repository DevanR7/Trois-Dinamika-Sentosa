<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseOrderPaymentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MidtransController;
// ✅ TAMBAHKAN USE STATEMENT UNTUK CONTROLLER BARU
use App\Http\Controllers\OrderChangeRequestController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

Route::middleware(['auth', 'verified'])->group(function () {

    // Halaman Dashboard & Profil
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Resource Controllers
    Route::resource('users', UserController::class);
    Route::resource('products', ProductController::class);
    Route::resource('sales-orders', SalesOrderController::class)
          ->parameters(['sales-orders' => 'order']); // <-- Ini sudah benar
    Route::resource('invoices', SalesInvoiceController::class);
    Route::resource('taxes', TaxController::class)->except(['show']);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::resource('clients', ClientController::class);
    Route::resource('units', UnitController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('sales-returns', SalesReturnController::class);
    Route::resource('purchase-returns', PurchaseReturnController::class);

    // Custom Routes untuk Sales Invoice & Payment
    Route::get('/invoices/create/from-order/{order}', [SalesInvoiceController::class, 'createFromOrder'])->name('invoices.createFromOrder');
    Route::post('/invoices/{invoice}/cancel', [SalesInvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::get('/invoices/{invoice}/download', [SalesInvoiceController::class, 'downloadPDF'])->name('invoices.pdf');
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');

    // Custom Routes untuk Purchase Order
    Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('/purchase-orders/{purchaseOrder}/mark-as-paid', [PurchaseOrderController::class, 'markAsPaid'])->name('purchase-orders.markAsPaid');
    Route::post('/purchase-orders/{purchaseOrder}/payments', [PurchaseOrderPaymentController::class, 'store'])->name('purchase-orders.payments.store');
    Route::post('/purchase-orders/{purchaseOrder}/add-supplier-invoice', [PurchaseOrderController::class, 'addSupplierInvoice'])->name('purchase-orders.addSupplierInvoice');
    Route::get('/purchase-orders/{purchaseOrder}/download-pdf', [PurchaseOrderController::class, 'downloadPDF'])->name('purchase-orders.pdf');

    // Custom Routes untuk Client
    Route::patch('clients/{client}/approve', [ClientController::class, 'approve'])->name('clients.approve');

    // Pengaturan & Laporan
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');


    // ✅ === ROUTE BARU UNTUK ADMIN REVIEW REQUEST PERUBAHAN ORDER ===
    // (Anda bisa pindahkan grup ini ke mana saja di dalam middleware group)
    Route::prefix('order-change-requests')->name('order-change-requests.')->group(function() {
        Route::get('/', [OrderChangeRequestController::class, 'index'])->name('index');
        Route::get('/{changeRequest}', [OrderChangeRequestController::class, 'show'])->name('show'); // Opsional
        Route::post('/{changeRequest}/process', [OrderChangeRequestController::class, 'process'])->name('process');
    });
    // ==========================================================

});

require __DIR__.'/auth.php';