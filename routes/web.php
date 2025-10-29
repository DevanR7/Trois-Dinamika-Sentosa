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
use App\Http\Controllers\ClientController; // Pastikan ini ada
use App\Http\Controllers\UnitController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\OrderChangeRequestController;
use App\Http\Controllers\ClientOrderReviewController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FixedAssetController;
use App\Http\Controllers\EquityTransactionController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanPaymentController;
use App\Http\Controllers\BatchPaymentController;

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
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Resource Controllers
    Route::resource('users', UserController::class);
    Route::resource('products', ProductController::class);
    Route::resource('sales-orders', SalesOrderController::class)
          ->parameters(['sales-orders' => 'order']);
    Route::resource('invoices', SalesInvoiceController::class);
    Route::resource('taxes', TaxController::class)->except(['show']);
    Route::resource('suppliers', SupplierController::class);
    Route::patch('suppliers/{supplier}/restore', [SupplierController::class, 'restore'])
          ->name('suppliers.restore')
          ->withTrashed();
    Route::resource('purchase-orders', PurchaseOrderController::class);
    // 
    // --- Rute Klien (SUDAH DIPERBAIKI) ---
    // 1. Daftarkan resource, KECUALI 'show'
    Route::resource('clients', ClientController::class)->except(['show']);

    // 2. Buat grup untuk rute kustom Klien
    Route::controller(ClientController::class)->prefix('clients')->name('clients.')->group(function () {
        // Rute kustom untuk aksi
        Route::patch('/{client}/approve', 'approve')->name('approve');
        Route::patch('/{client}/lock', 'lock')->name('lock');
        Route::patch('/{client}/unlock', 'unlock')->name('unlock');
        
        // Rute kustom yang perlu ->withTrashed()
        Route::patch('/{client}/restore', 'restore')
             ->name('restore')
             ->withTrashed(); 

        // Rute 'show' yang dibuat manual dengan ->withTrashed()
        Route::get('/{client}', 'show')
             ->name('show')
             ->withTrashed(); 
    });
    // --- Akhir Rute Klien ---
    //
    Route::resource('units', UnitController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('sales-returns', SalesReturnController::class);
    Route::resource('purchase-returns', PurchaseReturnController::class);
    Route::resource('announcements', AnnouncementController::class);

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

    // Custom Routes untuk User
    Route::patch('users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
    Route::patch('users/{user}/restore', [UserController::class, 'restore'])
          ->name('users.restore')
          ->withTrashed();

    // Custom Routes untuk Announcement
    Route::patch('announcements/{announcement}/restore', [AnnouncementController::class, 'restore'])
          ->name('announcements.restore')
          ->withTrashed();
    Route::delete('announcements/{announcement}/force-delete', [AnnouncementController::class, 'forceDelete'])
          ->name('announcements.forceDelete')
          ->withTrashed();

    // Pengaturan & Laporan
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Review Order Klien
    Route::prefix('client-order-reviews')->name('client-order-reviews.')->group(function() {
        Route::get('/', [ClientOrderReviewController::class, 'index'])->name('index'); 
        Route::get('/{order}', [ClientOrderReviewController::class, 'show'])->name('show'); 
        Route::post('/{order}/approve', [ClientOrderReviewController::class, 'approve'])->name('approve'); 
        Route::post('/{order}/reject', [ClientOrderReviewController::class, 'reject'])->name('reject'); 
    });

    // Review Request Perubahan Order
    Route::prefix('order-change-requests')->name('order-change-requests.')->group(function() {
        Route::get('/', [OrderChangeRequestController::class, 'index'])->name('index');
        Route::get('/{changeRequest}', [OrderChangeRequestController::class, 'show'])->name('show');
        Route::post('/{changeRequest}/process', [OrderChangeRequestController::class, 'process'])->name('process');
    });
    
    // Keuangan Lanjutan (Beban, Aset, Modal, Pinjaman)
    Route::resource('expenses', ExpenseController::class);
    Route::resource('fixed-assets', FixedAssetController::class);
    Route::resource('equity-transactions', EquityTransactionController::class);

    Route::resource('loans', LoanController::class);
    Route::resource('loans.payments', LoanPaymentController::class)
     ->only(['create', 'store', 'destroy'])
     ->scoped();

    // Batch Payment (Piutang)
    Route::get('batch-payments/create', [BatchPaymentController::class, 'create'])->name('batch-payments.create');
    Route::post('batch-payments', [BatchPaymentController::class, 'store'])->name('batch-payments.store');

    // API Endpoints untuk Batch Payment (dilindungi auth web)
    Route::get('/api/clients/{client}/unpaid-invoices', [BatchPaymentController::class, 'getUnpaidInvoicesApi'])->name('api.clients.unpaid-invoices');

    // ==========================================================
    // ✅ PINDAHKAN ROUTE DETAIL KLIEN KE SINI
    // ==========================================================
    Route::get('/api/clients/{client}/details', function (App\Models\Client $client) {
        return response()->json([
            'client_id' => $client->client_id,
            'client_name' => $client->client_name,
            'credit_balance' => $client->credit_balance ?? 0,
        ]);
    })->name('api.clients.details');
});

require __DIR__.'/auth.php';