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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Halaman Dashboard & Profil
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Resource Controllers
    Route::resource('users', UserController::class); // Duplikasi sudah dihapus
    Route::resource('products', ProductController::class);
    Route::resource('sales-orders', SalesOrderController::class);
    Route::resource('invoices', SalesInvoiceController::class);
    Route::resource('taxes', TaxController::class)->except(['show']);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('purchase-orders', PurchaseOrderController::class);

    // Custom Routes untuk Sales Invoice & Payment
    Route::get('/invoices/create/from-order/{salesOrder}', [SalesInvoiceController::class, 'createFromOrder'])->name('invoices.createFromOrder');
    Route::post('/invoices/{invoice}/cancel', [SalesInvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::get('/invoices/{invoice}/download', [SalesInvoiceController::class, 'downloadPDF'])->name('invoices.pdf');
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');

    // Custom Routes untuk Purchase Order
    Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('/purchase-orders/{purchaseOrder}/mark-as-paid', [PurchaseOrderController::class, 'markAsPaid'])->name('purchase-orders.markAsPaid');
    Route::post('/purchase-orders/{purchaseOrder}/payments', [PurchaseOrderPaymentController::class, 'store'])->name('purchase-orders.payments.store');
    Route::post('/purchase-orders/{purchaseOrder}/add-supplier-invoice', [PurchaseOrderController::class, 'addSupplierInvoice'])->name('purchase-orders.addSupplierInvoice');
    Route::get('/purchase-orders/{purchaseOrder}/download-pdf', [PurchaseOrderController::class, 'downloadPDF'])->name('purchase-orders.pdf');
    
    // Parameter {id} diganti menjadi {purchaseOrder}
    Route::get('/purchase-orders/{purchaseOrder}/export-excel', [PurchaseOrderController::class, 'exportExcel'])->name('purchase-orders.exportExcel');
    Route::resource('clients', ClientController::class);

    Route::resource('units', UnitController::class)->except(['show']);
});

require __DIR__.'/auth.php';